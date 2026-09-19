<?php

namespace App\Repositories;

use App\Core\Text;
use App\Database\Database;
use PDO;

class GalleryRepository
{
    private $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?: new Database();
    }

    public function paginateForAdmin(int $page, int $perPage, string $search): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $pdo = $this->database->connection();
        $where = ' WHERE galleries.deleted_at IS NULL';
        $params = [];

        if ($search !== '') {
            $where .= ' AND galleries.name LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $statement = $pdo->prepare(
            $this->selectSql() . $where . ' ORDER BY galleries.name LIMIT :limit OFFSET :offset'
        );

        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }

        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countForAdmin(string $search): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM galleries WHERE deleted_at IS NULL';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND name LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $row = $this->database->fetch($sql, $params);

        return (int) ($row['total'] ?? 0);
    }

    public function findByIdForAdmin(int $id): ?array
    {
        $row = $this->database->fetch(
            $this->selectSql() . ' WHERE galleries.id = :id AND galleries.deleted_at IS NULL LIMIT 1',
            ['id' => $id]
        );

        if ($row === null) {
            return null;
        }

        $row['photos'] = $this->photosFor($id);

        return $row;
    }

    public function findBySlug(string $slug): ?array
    {
        $row = $this->database->fetch(
            $this->selectSql() . ' WHERE galleries.slug = :slug AND galleries.deleted_at IS NULL LIMIT 1',
            ['slug' => $slug]
        );

        if ($row === null) {
            return null;
        }

        $row['photos'] = $this->photosFor((int) $row['id']);

        return $row;
    }

    /**
     * Lista leve pro modal "Inserir galeria" do editor (post-edit.php /
     * project-form.php) e pra listagem do proprio admin — sem paginacao,
     * poucas dezenas de galerias esperadas.
     */
    public function allForPicker(): array
    {
        return $this->database->fetchAll(
            $this->selectSql() . ' WHERE galleries.deleted_at IS NULL ORDER BY galleries.name'
        );
    }

    /**
     * Todas as galerias cujo slug esta na lista, ja com as fotos carregadas,
     * indexadas por slug — usado por GalleryRepository::expandShortcodes()
     * pra resolver todo [@slug@] de um conteudo com no maximo 2 queries
     * (uma pelas galerias, uma pelas fotos de todas elas), mesmo quando ha
     * varias galerias diferentes no mesmo post/projeto.
     */
    public function findManyBySlugsForRender(array $slugs): array
    {
        if (count($slugs) === 0) {
            return [];
        }

        $pdo = $this->database->connection();
        $placeholders = [];
        $params = [];

        foreach (array_values($slugs) as $index => $slug) {
            $key = ':slug' . $index;
            $placeholders[] = $key;
            $params[$key] = $slug;
        }

        $statement = $pdo->prepare(
            'SELECT id, name, slug FROM galleries
             WHERE deleted_at IS NULL AND slug IN (' . implode(', ', $placeholders) . ')'
        );
        $statement->execute($params);
        $galleries = $statement->fetchAll();

        if (count($galleries) === 0) {
            return [];
        }

        $galleryIds = array_column($galleries, 'id');
        $photosByGallery = $this->photosForMany($galleryIds);

        $result = [];

        foreach ($galleries as $gallery) {
            $result[$gallery['slug']] = [
                'id' => (int) $gallery['id'],
                'name' => $gallery['name'],
                'photos' => $photosByGallery[(int) $gallery['id']] ?? [],
            ];
        }

        return $result;
    }

    public function create(array $data): int
    {
        $values = $this->normalize($data, null);

        $this->database->execute(
            'INSERT INTO galleries (name, slug) VALUES (:name, :slug)',
            $values
        );

        $id = (int) $this->database->connection()->lastInsertId();
        $this->syncMedia($id, $data['gallery_media_ids'] ?? '');

        return $id;
    }

    public function update(int $id, array $data): void
    {
        $values = $this->normalize($data, $id);
        $values['id'] = $id;

        $this->database->execute(
            'UPDATE galleries SET name = :name, slug = :slug WHERE id = :id',
            $values
        );

        $this->syncMedia($id, $data['gallery_media_ids'] ?? '');
    }

    public function delete(int $id): void
    {
        $this->database->execute('UPDATE galleries SET deleted_at = NOW() WHERE id = :id', ['id' => $id]);
    }

    /**
     * Recria as linhas de gallery_media a partir da lista de IDs (texto
     * separado por virgula, na ordem escolhida no form) — mesmo padrao de
     * ProjectRepository::syncGallery().
     */
    private function syncMedia(int $galleryId, string $mediaIdsCsv): void
    {
        $this->database->execute(
            'DELETE FROM gallery_media WHERE gallery_id = :gallery_id',
            ['gallery_id' => $galleryId]
        );

        $mediaIds = array_filter(array_map('intval', explode(',', $mediaIdsCsv)));

        foreach (array_values($mediaIds) as $position => $mediaId) {
            $this->database->execute(
                'INSERT INTO gallery_media (gallery_id, media_id, sort_order) VALUES (:gallery_id, :media_id, :sort_order)',
                ['gallery_id' => $galleryId, 'media_id' => $mediaId, 'sort_order' => $position]
            );
        }
    }

    private function photosFor(int $galleryId): array
    {
        $rows = $this->database->fetchAll(
            'SELECT media.id, media.path AS url, media.thumbnail_path, media.original_name AS name
             FROM gallery_media
             INNER JOIN media ON media.id = gallery_media.media_id AND media.deleted_at IS NULL
             WHERE gallery_media.gallery_id = :gallery_id
             ORDER BY gallery_media.sort_order',
            ['gallery_id' => $galleryId]
        );

        return array_map([$this, 'withThumbnailUrl'], $rows);
    }

    /**
     * Mesma consulta de photosFor(), mas pra varias galerias de uma vez —
     * uma query com IN, agrupada em PHP por gallery_id. Evita N+1 quando
     * expandShortcodes() precisa renderizar varias galerias diferentes no
     * mesmo conteudo.
     */
    private function photosForMany(array $galleryIds): array
    {
        if (count($galleryIds) === 0) {
            return [];
        }

        $pdo = $this->database->connection();
        $placeholders = [];
        $params = [];

        foreach (array_values($galleryIds) as $index => $galleryId) {
            $key = ':gallery' . $index;
            $placeholders[] = $key;
            $params[$key] = $galleryId;
        }

        $statement = $pdo->prepare(
            'SELECT gallery_media.gallery_id, media.id, media.path AS url, media.thumbnail_path, media.original_name AS name
             FROM gallery_media
             INNER JOIN media ON media.id = gallery_media.media_id AND media.deleted_at IS NULL
             WHERE gallery_media.gallery_id IN (' . implode(', ', $placeholders) . ')
             ORDER BY gallery_media.gallery_id, gallery_media.sort_order'
        );
        $statement->execute($params);

        $grouped = [];

        foreach ($statement->fetchAll() as $row) {
            $galleryId = (int) $row['gallery_id'];
            unset($row['gallery_id']);
            $grouped[$galleryId][] = $this->withThumbnailUrl($row);
        }

        return $grouped;
    }

    private function withThumbnailUrl(array $row): array
    {
        $row['thumbnail_url'] = $row['thumbnail_path'] ?: $row['url'];
        unset($row['thumbnail_path']);

        return $row;
    }

    private function selectSql(): string
    {
        return 'SELECT galleries.*,
                    (SELECT COUNT(*) FROM gallery_media WHERE gallery_media.gallery_id = galleries.id) AS photo_count,
                    (SELECT COALESCE(media.thumbnail_path, media.path)
                       FROM gallery_media
                       INNER JOIN media ON media.id = gallery_media.media_id AND media.deleted_at IS NULL
                       WHERE gallery_media.gallery_id = galleries.id
                       ORDER BY gallery_media.sort_order
                       LIMIT 1) AS cover_thumbnail_url
                 FROM galleries';
    }

    private function normalize(array $data, ?int $excludeId): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $slugInput = trim((string) ($data['slug'] ?? ''));
        $slug = $this->uniqueSlug($slugInput !== '' ? $this->slugify($slugInput) : $this->slugify($name), $excludeId);

        return [
            'name' => $name,
            'slug' => $slug,
        ];
    }

    /**
     * Acrescenta -2, -3... ate achar um slug livre — mesmo padrao de
     * ProjectRepository::uniqueSlug(). Faz sentido nunca travar o form
     * aqui (diferente de posts, que deixam a constraint do banco
     * estourar): o slug e digitado dentro do conteudo de outros posts via
     * shortcode, uma colisao silenciosamente resolvida e melhor que travar.
     */
    private function uniqueSlug(string $baseSlug, ?int $excludeId): string
    {
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $excludeId): bool
    {
        $sql = 'SELECT id FROM galleries WHERE slug = :slug';
        $params = ['slug' => $slug];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        return $this->database->fetch($sql . ' LIMIT 1', $params) !== null;
    }

    private function slugify(string $value): string
    {
        $value = Text::slugify($value);

        return $value !== '' ? $value : 'galeria';
    }
}
