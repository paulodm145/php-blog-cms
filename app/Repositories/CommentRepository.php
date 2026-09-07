<?php

namespace App\Repositories;

use App\Database\Database;

class CommentRepository
{
    private $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?: new Database();
    }

    public function approvedForPost(int $postId): array
    {
        return $this->database->fetchAll(
            'SELECT id, author_name, body, created_at
             FROM comments
             WHERE post_id = :post_id AND status = "approved"
             ORDER BY created_at ASC',
            ['post_id' => $postId]
        );
    }

    public function create(array $data): int
    {
        $this->database->execute(
            'INSERT INTO comments (post_id, author_name, author_email, body, status)
             VALUES (:post_id, :author_name, :author_email, :body, "pending")',
            [
                'post_id' => (int) $data['post_id'],
                'author_name' => trim((string) $data['author_name']),
                'author_email' => trim((string) $data['author_email']),
                'body' => trim((string) $data['body']),
            ]
        );

        return (int) $this->database->connection()->lastInsertId();
    }

    public function allForAdmin(string $status = ''): array
    {
        $sql = 'SELECT comments.id, comments.author_name, comments.author_email, comments.body,
                       comments.status, comments.created_at,
                       posts.title AS post_title, posts.slug AS post_slug
                FROM comments
                INNER JOIN posts ON posts.id = comments.post_id';
        $params = [];

        if ($status !== '') {
            $sql .= ' WHERE comments.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY comments.created_at DESC';

        return $this->database->fetchAll($sql, $params);
    }

    public function countPending(): int
    {
        $row = $this->database->fetch(
            'SELECT COUNT(*) AS total FROM comments WHERE status = "pending"'
        );

        return (int) ($row['total'] ?? 0);
    }

    public function updateStatus(int $id, string $status): void
    {
        $allowed = ['pending', 'approved', 'spam'];
        $status = in_array($status, $allowed, true) ? $status : 'pending';

        $this->database->execute(
            'UPDATE comments SET status = :status WHERE id = :id',
            ['status' => $status, 'id' => $id]
        );
    }

    public function delete(int $id): void
    {
        $this->database->execute('DELETE FROM comments WHERE id = :id', ['id' => $id]);
    }
}
