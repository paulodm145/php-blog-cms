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
        // Compara contra o relogio do PHP (o mesmo usado em store() pra
        // gravar expires_at), nunca o NOW() do MySQL: os dois servidores
        // podem rodar em fusos horarios diferentes (ex.: app com
        // date.timezone=America/Sao_Paulo, banco em UTC), e comparar contra
        // NOW() fazia links expirarem antes ou depois do prometido conforme
        // essa diferenca.
        return $this->database->fetch(
            'SELECT * FROM resume_document_shares WHERE token = :token AND revoked_at IS NULL AND expires_at > :now LIMIT 1',
            ['token' => $token, 'now' => date('Y-m-d H:i:s')]
        );
    }

    public function documentsGroupedForToken(string $token): array
    {
        $share = $this->findValidByToken($token);

        if ($share === null) {
            return [];
        }

        $documents = $this->database->fetchAll(
            'SELECT d.*, e.role, e.company, e.period, e.sort_order AS experience_sort_order, f.name AS folder_name
             FROM resume_document_share_items si
             INNER JOIN resume_experience_documents d ON d.id = si.document_id AND d.deleted_at IS NULL
             LEFT JOIN resume_experience e ON e.id = d.experience_id
             LEFT JOIN resume_document_folders f ON f.id = d.folder_id
             WHERE si.share_id = :share_id
             ORDER BY (d.experience_id IS NULL), e.sort_order, f.name, d.created_at DESC',
            ['share_id' => $share['id']]
        );

        $groups = [];

        foreach ($documents as $document) {
            if ($document['experience_id'] !== null) {
                $key = 'experience-' . $document['experience_id'];
                $title = $document['role'] . ' — ' . $document['company'];
                $subtitle = $document['period'];
            } elseif ($document['folder_id'] !== null) {
                $key = 'folder-' . $document['folder_id'];
                $title = 'Pasta: ' . $document['folder_name'];
                $subtitle = null;
            } else {
                $key = 'folder-root';
                $title = 'Documentos';
                $subtitle = null;
            }

            if (!isset($groups[$key])) {
                $groups[$key] = ['title' => $title, 'subtitle' => $subtitle, 'documents' => []];
            }

            $groups[$key]['documents'][] = $document;
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
        // Junta ate resume_experience_documents (nao so ate share_items) e
        // filtra deleted_at IS NULL: sem isso, um documento excluido depois
        // de compartilhado continuava contado aqui, mas documentsGroupedForToken()
        // ja o esconde da pagina publica — a contagem batia com o que o
        // link prometia, nao com o que ele realmente serve.
        return $this->database->fetchAll(
            'SELECT s.*, COUNT(d.id) AS document_count
             FROM resume_document_shares s
             LEFT JOIN resume_document_share_items si ON si.share_id = s.id
             LEFT JOIN resume_experience_documents d ON d.id = si.document_id AND d.deleted_at IS NULL
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
