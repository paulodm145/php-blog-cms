<?php

namespace App\Repositories;

use App\Database\Database;
use PDO;

class MediaRepository
{
    private const MONTH_NAMES = [
        1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
    ];

    private $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?: new Database();
    }

    /**
     * Todos os arquivos que batem com a busca/filtro, agrupados por mes de
     * envio (mais recente primeiro) — usado pela visualizacao "Agrupado
     * por mes" do admin. Sem paginacao: reaproveita a mesma pasta fisica
     * (uploads/media/<ano>/<mes>) como organizacao, so exibida na tela.
     */
    public function groupedByMonthForAdmin(string $search, string $kind): array
    {
        $where = 'WHERE deleted_at IS NULL';
        $params = [];

        if ($kind !== '') {
            $where .= ' AND kind = :kind';
            $params['kind'] = $kind;
        }

        if ($search !== '') {
            $where .= ' AND original_name LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $rows = $this->database->fetchAll(
            'SELECT id, file_name, original_name, path, thumbnail_path, mime_type, kind, size, width, height,
                    title, alt_text, uploaded_by, created_at
             FROM media
             ' . $where . '
             ORDER BY created_at DESC, id DESC',
            $params
        );

        $groups = [];

        foreach ($rows as $row) {
            $timestamp = strtotime($row['created_at']);
            $year = (int) date('Y', $timestamp);
            $month = (int) date('n', $timestamp);
            $key = $year . '-' . $month;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'label' => self::MONTH_NAMES[$month] . ' de ' . $year,
                    'year' => $year,
                    'month' => $month,
                    'items' => [],
                ];
            }

            $groups[$key]['items'][] = $row;
        }

        return array_values($groups);
    }

    public function paginateForAdmin(int $page, int $perPage, string $search, string $kind): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $pdo = $this->database->connection();
        $where = 'WHERE deleted_at IS NULL';
        $params = [];

        if ($kind !== '') {
            $where .= ' AND kind = :kind';
            $params['kind'] = $kind;
        }

        if ($search !== '') {
            $where .= ' AND original_name LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $statement = $pdo->prepare(
            'SELECT id, file_name, original_name, path, thumbnail_path, mime_type, kind, size, width, height,
                    title, alt_text, uploaded_by, created_at
             FROM media
             ' . $where . '
             ORDER BY created_at DESC, id DESC
             LIMIT :limit OFFSET :offset'
        );

        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }

        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countForAdmin(string $search, string $kind): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM media WHERE deleted_at IS NULL';
        $params = [];

        if ($kind !== '') {
            $sql .= ' AND kind = :kind';
            $params['kind'] = $kind;
        }

        if ($search !== '') {
            $sql .= ' AND original_name LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $row = $this->database->fetch($sql, $params);

        return (int) ($row['total'] ?? 0);
    }

    public function findByIdForAdmin(int $id): ?array
    {
        return $this->database->fetch(
            'SELECT id, file_name, original_name, path, thumbnail_path, mime_type, kind, size, width, height,
                    title, alt_text, uploaded_by, created_at
             FROM media
             WHERE id = :id AND deleted_at IS NULL
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        $pdo = $this->database->connection();
        $this->database->execute(
            'INSERT INTO media (
                file_name, original_name, path, thumbnail_path, mime_type, kind, size, width, height,
                title, alt_text, uploaded_by
             ) VALUES (
                :file_name, :original_name, :path, :thumbnail_path, :mime_type, :kind, :size, :width, :height,
                :title, :alt_text, :uploaded_by
             )',
            [
                'file_name' => $data['file_name'],
                'original_name' => $data['original_name'],
                'path' => $data['path'],
                'thumbnail_path' => $data['thumbnail_path'] ?? null,
                'mime_type' => $data['mime_type'],
                'kind' => $data['kind'],
                'size' => $data['size'],
                'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null,
                'title' => $data['title'] ?? null,
                'alt_text' => $data['alt_text'] ?? null,
                'uploaded_by' => $data['uploaded_by'] ?? null,
            ]
        );

        return (int) $pdo->lastInsertId();
    }

    public function updateMeta(int $id, string $title, string $altText): void
    {
        $this->database->execute(
            'UPDATE media SET title = :title, alt_text = :alt_text WHERE id = :id AND deleted_at IS NULL',
            ['title' => $title, 'alt_text' => $altText, 'id' => $id]
        );
    }

    public function softDeleteForAdmin(int $id): void
    {
        $this->database->execute(
            'UPDATE media SET deleted_at = NOW() WHERE id = :id',
            ['id' => $id]
        );
    }
}
