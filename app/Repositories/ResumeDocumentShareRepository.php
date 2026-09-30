<?php

namespace App\Repositories;

use App\Database\Database;

class ResumeDocumentShareRepository
{
    private $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?: new Database();
    }

    public function create(array $documentIds, string $expiresAt, ?int $createdBy): array
    {
        $pdo = $this->database->connection();
        $token = bin2hex(random_bytes(32));

        $pdo->beginTransaction();

        try {
            $this->database->execute(
                'INSERT INTO resume_document_shares (token, expires_at, created_by) VALUES (:token, :expires_at, :created_by)',
                ['token' => $token, 'expires_at' => $expiresAt, 'created_by' => $createdBy]
            );

            $shareId = (int) $pdo->lastInsertId();

            foreach ($documentIds as $documentId) {
                $this->database->execute(
                    'INSERT INTO resume_document_share_items (share_id, document_id) VALUES (:share_id, :document_id)',
                    ['share_id' => $shareId, 'document_id' => $documentId]
                );
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        return ['id' => $shareId, 'token' => $token, 'expires_at' => $expiresAt];
    }

    public function findValidByToken(string $token): ?array
    {
        return $this->database->fetch(
            'SELECT * FROM resume_document_shares WHERE token = :token AND revoked_at IS NULL AND expires_at > NOW() LIMIT 1',
            ['token' => $token]
        );
    }

    public function documentsGroupedForToken(string $token): array
    {
        $share = $this->findValidByToken($token);

        if ($share === null) {
            return [];
        }

        $documents = $this->database->fetchAll(
            'SELECT d.*, e.role, e.company, e.period, e.sort_order AS experience_sort_order
             FROM resume_document_share_items si
             INNER JOIN resume_experience_documents d ON d.id = si.document_id AND d.deleted_at IS NULL
             INNER JOIN resume_experience e ON e.id = d.experience_id
             WHERE si.share_id = :share_id
             ORDER BY e.sort_order, e.id, d.created_at DESC',
            ['share_id' => $share['id']]
        );

        $groups = [];

        foreach ($documents as $document) {
            $experienceId = (int) $document['experience_id'];

            if (!isset($groups[$experienceId])) {
                $groups[$experienceId] = [
                    'id' => $experienceId,
                    'role' => $document['role'],
                    'company' => $document['company'],
                    'period' => $document['period'],
                    'documents' => [],
                ];
            }

            $groups[$experienceId]['documents'][] = $document;
        }

        return array_values($groups);
    }

    public function documentBelongsToShare(int $documentId, int $shareId): bool
    {
        $row = $this->database->fetch(
            'SELECT id FROM resume_document_share_items WHERE share_id = :share_id AND document_id = :document_id LIMIT 1',
            ['share_id' => $shareId, 'document_id' => $documentId]
        );

        return $row !== null;
    }

    public function listAllForAdmin(): array
    {
        return $this->database->fetchAll(
            'SELECT s.*, COUNT(si.id) AS document_count
             FROM resume_document_shares s
             LEFT JOIN resume_document_share_items si ON si.share_id = s.id
             GROUP BY s.id
             ORDER BY s.created_at DESC'
        );
    }

    public function revoke(int $id): void
    {
        $this->database->execute(
            'UPDATE resume_document_shares SET revoked_at = NOW() WHERE id = :id',
            ['id' => $id]
        );
    }
}
