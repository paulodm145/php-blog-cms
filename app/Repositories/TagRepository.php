<?php

namespace App\Repositories;

use App\Database\Database;

class TagRepository
{
    private $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?: new Database();
    }

    public function all(): array
    {
        return $this->database->fetchAll('SELECT id, name, slug FROM tags ORDER BY name');
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->database->fetch(
            'SELECT id, name, slug FROM tags WHERE slug = :slug LIMIT 1',
            ['slug' => $slug]
        );
    }

    /**
     * Tags com pelo menos um post publicado, com a contagem — para a nuvem
     * de tags e os chips do front-end publico.
     */
    public function publishedCounts(): array
    {
        return $this->database->fetchAll(
            'SELECT tags.id, tags.name, tags.slug, COUNT(posts.id) AS total
             FROM tags
             INNER JOIN post_tag ON post_tag.tag_id = tags.id
             INNER JOIN posts ON posts.id = post_tag.post_id
             WHERE posts.status = "published"
               AND posts.published_at IS NOT NULL
               AND posts.deleted_at IS NULL
             GROUP BY tags.id, tags.name, tags.slug
             ORDER BY tags.name'
        );
    }
}
