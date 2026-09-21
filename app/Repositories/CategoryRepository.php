<?php

namespace App\Repositories;

use App\Core\Text;
use App\Database\Database;

class CategoryRepository
{
    private $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?: new Database();
    }

    public function all(): array
    {
        return $this->database->fetchAll('SELECT id, name, slug FROM categories ORDER BY name');
    }

    public function publishedCounts(): array
    {
        return $this->database->fetchAll(
            'SELECT categories.id, categories.name, categories.slug, COUNT(posts.id) AS total
             FROM categories
             INNER JOIN post_category ON post_category.category_id = categories.id
             INNER JOIN posts ON posts.id = post_category.post_id
             WHERE posts.status = "published"
               AND posts.published_at IS NOT NULL
               AND posts.deleted_at IS NULL
             GROUP BY categories.id, categories.name, categories.slug
             ORDER BY categories.name'
        );
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->database->fetch(
            'SELECT id, name, slug FROM categories WHERE slug = :slug LIMIT 1',
            ['slug' => $slug]
        );
    }

    public function find(int $id): ?array
    {
        return $this->database->fetch('SELECT id, name, slug FROM categories WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function create(array $data): int
    {
        $name = trim($data['name']);
        $slug = trim($data['slug']) !== '' ? trim($data['slug']) : $this->slugify($name);
        $pdo = $this->database->connection();

        $this->database->execute(
            'INSERT INTO categories (name, slug) VALUES (:name, :slug)',
            ['name' => $name, 'slug' => $slug]
        );

        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $name = trim($data['name']);
        $slug = trim($data['slug']) !== '' ? trim($data['slug']) : $this->slugify($name);

        $this->database->execute(
            'UPDATE categories SET name = :name, slug = :slug WHERE id = :id',
            ['name' => $name, 'slug' => $slug, 'id' => $id]
        );
    }

    public function delete(int $id): void
    {
        $this->database->execute('DELETE FROM categories WHERE id = :id', ['id' => $id]);
    }

    private function slugify(string $value): string
    {
        // iconv('UTF-8', 'ASCII//TRANSLIT', ...) depende do locale do
        // servidor pra transliterar (á -> a); quando falha, ele so descarta
        // o caractere acentuado (ou vira "?", que a regex tambem descarta),
        // entao "Correção" virava "corre-o" em vez de "correcao". Text::slugify
        // troca acentuados por seus equivalentes sem acento de forma explicita,
        // sem depender de locale.
        $value = Text::slugify($value);

        return $value !== '' ? $value : 'categoria';
    }
}
