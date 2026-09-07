<?php

namespace App\Repositories;

use App\Database\Database;

class PageRepository
{
    private $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?: new Database();
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        return $this->database->fetch(
            'SELECT id, title, slug, content, status, updated_at
             FROM pages
             WHERE slug = :slug AND status = "published" AND deleted_at IS NULL
             LIMIT 1',
            ['slug' => $slug]
        );
    }

    /**
     * Todas as paginas publicadas (slug + data) — usado no sitemap.xml.
     */
    public function allPublishedForSitemap(): array
    {
        return $this->database->fetchAll(
            'SELECT slug, updated_at FROM pages WHERE status = "published" AND deleted_at IS NULL'
        );
    }

    public function allForAdmin(): array
    {
        return $this->database->fetchAll(
            'SELECT id, title, slug, status, updated_at
             FROM pages
             WHERE deleted_at IS NULL
             ORDER BY title'
        );
    }

    public function findByIdForAdmin(int $id): ?array
    {
        return $this->database->fetch(
            'SELECT id, title, slug, content, status
             FROM pages
             WHERE id = :id AND deleted_at IS NULL
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        $title = trim((string) $data['title']);
        $slug = trim((string) $data['slug']) !== '' ? trim((string) $data['slug']) : $this->slugify($title);

        $this->database->execute(
            'INSERT INTO pages (title, slug, content, status) VALUES (:title, :slug, :content, :status)',
            [
                'title' => $title,
                'slug' => $slug,
                'content' => (string) $data['content'],
                'status' => $this->normalizeStatus((string) ($data['status'] ?? 'draft')),
            ]
        );

        return (int) $this->database->connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $title = trim((string) $data['title']);
        $slug = trim((string) $data['slug']) !== '' ? trim((string) $data['slug']) : $this->slugify($title);

        $this->database->execute(
            'UPDATE pages SET title = :title, slug = :slug, content = :content, status = :status WHERE id = :id',
            [
                'title' => $title,
                'slug' => $slug,
                'content' => (string) $data['content'],
                'status' => $this->normalizeStatus((string) ($data['status'] ?? 'draft')),
                'id' => $id,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->database->execute('UPDATE pages SET deleted_at = NOW() WHERE id = :id', ['id' => $id]);
    }

    private function slugify(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $value = strtolower((string) $value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim((string) $value, '-');

        return $value !== '' ? $value : 'pagina';
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, ['published', 'draft'], true) ? $status : 'draft';
    }
}
