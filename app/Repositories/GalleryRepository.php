<?php

namespace App\Repositories;

use App\Core\Text;
use App\Core\VideoEmbed;
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

        if ($row['kind'] === 'video') {
            $row['videos'] = $this->videosFor($id);
        } else {
            $row['photos'] = $this->photosFor($id);
        }

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

        if ($row['kind'] === 'video') {
            $row['videos'] = $this->videosFor((int) $row['id']);
        } else {
            $row['photos'] = $this->photosFor((int) $row['id']);
        }

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
            'SELECT id, name, slug, kind FROM galleries
             WHERE deleted_at IS NULL AND slug IN (' . implode(', ', $placeholders) . ')'
        );
        $statement->execute($params);
        $galleries = $statement->fetchAll();

        if (count($galleries) === 0) {
            return [];
        }

        $photoGalleryIds = array_column(array_filter($galleries, function (array $gallery): bool {
            return $gallery['kind'] !== 'video';
        }), 'id');
        $videoGalleryIds = array_column(array_filter($galleries, function (array $gallery): bool {
            return $gallery['kind'] === 'video';
        }), 'id');

        $photosByGallery = $this->photosForMany($photoGalleryIds);
        $videosByGallery = $this->videosForMany($videoGalleryIds);

        $result = [];

        foreach ($galleries as $gallery) {
            $id = (int) $gallery['id'];
            $result[$gallery['slug']] = [
                'id' => $id,
                'name' => $gallery['name'],
                'items' => $gallery['kind'] === 'video'
                    ? ($videosByGallery[$id] ?? [])
                    : ($photosByGallery[$id] ?? []),
            ];
        }

        return $result;
    }

    /**
     * Procura todo [@slug@] em $html e troca pela grade de miniaturas da
     * galeria correspondente. Slug que nao corresponde a galeria nenhuma:
     * some sem deixar rastro pro visitante comum; quando $isAdmin (post/
     * projeto sendo visto por quem esta logado), vira um aviso discreto —
     * ajuda a notar um slug digitado errado sem expor isso pro publico.
     */
    public function expandShortcodes(string $html, bool $isAdmin): string
    {
        if (strpos($html, '[@') === false) {
            return $html;
        }

        preg_match_all('/\[@([a-z0-9\-]+)@\]/', $html, $matches);
        $slugs = array_unique($matches[1]);

        if (count($slugs) === 0) {
            return $html;
        }

        $galleries = $this->findManyBySlugsForRender($slugs);

        foreach ($matches[0] as $index => $placeholder) {
            $slug = $matches[1][$index];
            $replacement = '';

            if (isset($galleries[$slug])) {
                $replacement = $this->renderGrid($slug, $galleries[$slug]['items']);
            } elseif ($isAdmin) {
                $replacement = '<p class="text-secondary small">[galeria "' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . '" não encontrada]</p>';
            }

            $html = str_replace($placeholder, $replacement, $html);
        }

        return $html;
    }

    private function renderGrid(string $galleryKey, array $items): string
    {
        ob_start();
        require dirname(__DIR__) . '/Views/partials/gallery-grid.php';

        return ob_get_clean();
    }

    public function create(array $data): int
    {
        $values = $this->normalize($data, null);
        $kind = in_array($data['kind'] ?? '', ['photo', 'video'], true) ? $data['kind'] : 'photo';
        $values['kind'] = $kind;

        $this->database->execute(
            'INSERT INTO galleries (name, slug, kind) VALUES (:name, :slug, :kind)',
            $values
        );

        $id = (int) $this->database->connection()->lastInsertId();

        if ($kind === 'video') {
            $this->syncVideos($id, $this->decodeVideosJson($data['gallery_videos_json'] ?? '[]'));
        } else {
            $this->syncMedia($id, $data['gallery_media_ids'] ?? '');
        }

        return $id;
    }

    public function update(int $id, array $data): void
    {
        // "kind" nunca muda numa edicao -- le da propria linha, ignora
        // qualquer coisa que o body tenha mandado nesse campo.
        $currentKind = $this->database->fetch('SELECT kind FROM galleries WHERE id = :id', ['id' => $id]);
        $kind = $currentKind['kind'] ?? 'photo';

        $values = $this->normalize($data, $id);
        $values['id'] = $id;

        $this->database->execute(
            'UPDATE galleries SET name = :name, slug = :slug WHERE id = :id',
            $values
        );

        if ($kind === 'video') {
            $this->syncVideos($id, $this->decodeVideosJson($data['gallery_videos_json'] ?? '[]'));
        } else {
            $this->syncMedia($id, $data['gallery_media_ids'] ?? '');
        }
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
     * Recria as linhas de gallery_videos a partir da lista de itens (na
     * ordem escolhida no form) — mesmo espirito de syncMedia(), so que
     * cada item aqui ainda nao tem id de banco nenhum antes do save
     * (nao e upload, e so uma URL colada), entao a lista inteira viaja
     * pronta em vez de so uma lista de ids.
     *
     * @param array<int, array{url: string, title: string, thumbnail_media_id: ?int}> $videos
     */
    private function syncVideos(int $galleryId, array $videos): void
    {
        $this->database->execute(
            'DELETE FROM gallery_videos WHERE gallery_id = :gallery_id',
            ['gallery_id' => $galleryId]
        );

        foreach (array_values($videos) as $position => $video) {
            $url = trim((string) ($video['url'] ?? ''));
            $detected = $url !== '' ? VideoEmbed::detect($url) : null;

            if ($detected === null) {
                // URL que nao bate com nenhum provedor conhecido —
                // ignorada silenciosamente em vez de travar o save
                // inteiro (mesma filosofia de uniqueSlug() abaixo: um
                // item ruim nao pode derrubar a galeria inteira). O
                // form (Task 5) ja valida no client antes de deixar
                // adicionar, entao isso so acontece se alguem manipular
                // o JSON na mao.
                continue;
            }

            $thumbnailMediaId = isset($video['thumbnail_media_id']) && (int) $video['thumbnail_media_id'] > 0
                ? (int) $video['thumbnail_media_id']
                : null;

            $this->database->execute(
                'INSERT INTO gallery_videos (gallery_id, url, provider, external_id, title, thumbnail_media_id, sort_order)
                 VALUES (:gallery_id, :url, :provider, :external_id, :title, :thumbnail_media_id, :sort_order)',
                [
                    'gallery_id' => $galleryId,
                    'url' => $url,
                    'provider' => $detected['provider'],
                    'external_id' => $detected['external_id'],
                    'title' => trim((string) ($video['title'] ?? '')) ?: null,
                    'thumbnail_media_id' => $thumbnailMediaId,
                    'sort_order' => $position,
                ]
            );
        }
    }

    private function decodeVideosJson(string $json): array
    {
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function videosFor(int $galleryId): array
    {
        return $this->mapVideoRows($this->database->fetchAll(
            'SELECT gallery_videos.url, gallery_videos.provider, gallery_videos.external_id, gallery_videos.title,
                    media.path AS media_path, media.thumbnail_path AS media_thumbnail_path
             FROM gallery_videos
             LEFT JOIN media ON media.id = gallery_videos.thumbnail_media_id AND media.deleted_at IS NULL
             WHERE gallery_videos.gallery_id = :gallery_id
             ORDER BY gallery_videos.sort_order',
            ['gallery_id' => $galleryId]
        ));
    }

    /**
     * Mesma consulta de videosFor(), pra varias galerias de uma vez —
     * mesmo espirito de photosForMany().
     */
    private function videosForMany(array $galleryIds): array
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
            'SELECT gallery_videos.gallery_id, gallery_videos.url, gallery_videos.provider,
                    gallery_videos.external_id, gallery_videos.title,
                    media.path AS media_path, media.thumbnail_path AS media_thumbnail_path
             FROM gallery_videos
             LEFT JOIN media ON media.id = gallery_videos.thumbnail_media_id AND media.deleted_at IS NULL
             WHERE gallery_videos.gallery_id IN (' . implode(', ', $placeholders) . ')
             ORDER BY gallery_videos.gallery_id, gallery_videos.sort_order'
        );
        $statement->execute($params);

        $grouped = [];

        foreach ($statement->fetchAll() as $row) {
            $galleryId = (int) $row['gallery_id'];
            unset($row['gallery_id']);
            $grouped[$galleryId][] = $row;
        }

        foreach ($grouped as $galleryId => $rows) {
            $grouped[$galleryId] = $this->mapVideoRows($rows);
        }

        return $grouped;
    }

    /**
     * Resolve a miniatura de cada linha crua de gallery_videos, nessa
     * ordem: thumbnail escolhida manualmente (media.thumbnail_path ou
     * media.path) -> miniatura automatica do provedor (so YouTube tem)
     * -> null (a grade decide o placeholder de play).
     */
    private function mapVideoRows(array $rows): array
    {
        return array_map(function (array $row): array {
            $thumbnailUrl = null;

            if (!empty($row['media_thumbnail_path'])) {
                $thumbnailUrl = $row['media_thumbnail_path'];
            } elseif (!empty($row['media_path'])) {
                $thumbnailUrl = $row['media_path'];
            } else {
                $thumbnailUrl = VideoEmbed::autoThumbnailUrl($row['provider'], $row['external_id']);
            }

            return [
                'type' => 'video',
                'url' => VideoEmbed::embedUrl($row['provider'], $row['external_id']),
                'thumbnail_url' => $thumbnailUrl,
                'name' => $row['title'] ?: '',
            ];
        }, $rows);
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
        $row['type'] = 'image';
        unset($row['thumbnail_path']);

        return $row;
    }

    private function selectSql(): string
    {
        // item_count/cover_thumbnail_url dependem do kind: galeria de
        // foto conta/olha gallery_media, galeria de video conta/olha
        // gallery_videos — nunca os dois ao mesmo tempo, por isso o
        // CASE em vez de somar as duas subqueries.
        return 'SELECT galleries.*,
                    CASE galleries.kind
                        WHEN \'video\' THEN (SELECT COUNT(*) FROM gallery_videos WHERE gallery_videos.gallery_id = galleries.id)
                        ELSE (SELECT COUNT(*) FROM gallery_media WHERE gallery_media.gallery_id = galleries.id)
                    END AS item_count,
                    CASE galleries.kind
                        WHEN \'video\' THEN (
                            SELECT COALESCE(media.thumbnail_path, media.path)
                            FROM gallery_videos
                            LEFT JOIN media ON media.id = gallery_videos.thumbnail_media_id AND media.deleted_at IS NULL
                            WHERE gallery_videos.gallery_id = galleries.id
                            ORDER BY gallery_videos.sort_order
                            LIMIT 1
                        )
                        ELSE (
                            SELECT COALESCE(media.thumbnail_path, media.path)
                            FROM gallery_media
                            INNER JOIN media ON media.id = gallery_media.media_id AND media.deleted_at IS NULL
                            WHERE gallery_media.gallery_id = galleries.id
                            ORDER BY gallery_media.sort_order
                            LIMIT 1
                        )
                    END AS cover_thumbnail_url
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
