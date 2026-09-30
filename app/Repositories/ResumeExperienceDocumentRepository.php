<?php

namespace App\Repositories;

use App\Database\Database;

class ResumeExperienceDocumentRepository
{
    private $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?: new Database();
    }

    public function allGroupedByExperience(): array
    {
        $experiences = $this->database->fetchAll('SELECT * FROM resume_experience ORDER BY sort_order, id');
        $documents = $this->database->fetchAll(
            'SELECT * FROM resume_experience_documents WHERE deleted_at IS NULL ORDER BY created_at DESC'
        );

        $byExperience = [];

        foreach ($documents as $document) {
            $byExperience[(int) $document['experience_id']][] = $document;
        }

        $result = [];

        foreach ($experiences as $experience) {
            $experience['documents'] = $byExperience[(int) $experience['id']] ?? [];
            $result[] = $experience;
        }

        return $result;
    }

    public function listByExperience(int $experienceId): array
    {
        return $this->database->fetchAll(
            'SELECT * FROM resume_experience_documents WHERE experience_id = :experience_id AND deleted_at IS NULL ORDER BY created_at DESC',
            ['experience_id' => $experienceId]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->database->fetch(
            'SELECT * FROM resume_experience_documents WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => $id]
        );
    }

    public function findManyByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if (empty($ids)) {
            return [];
        }

        $placeholders = [];
        $params = [];

        foreach ($ids as $index => $id) {
            $key = 'id' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }

        return $this->database->fetchAll(
            'SELECT * FROM resume_experience_documents WHERE id IN (' . implode(',', $placeholders) . ') AND deleted_at IS NULL',
            $params
        );
    }

    public function create(array $data): int
    {
        $pdo = $this->database->connection();

        $this->database->execute(
            'INSERT INTO resume_experience_documents (experience_id, file_name, original_name, path, mime_type, size, caption, uploaded_by)
             VALUES (:experience_id, :file_name, :original_name, :path, :mime_type, :size, :caption, :uploaded_by)',
            [
                'experience_id' => $data['experience_id'],
                'file_name' => $data['file_name'],
                'original_name' => $data['original_name'],
                'path' => $data['path'],
                'mime_type' => $data['mime_type'],
                'size' => $data['size'],
                'caption' => $data['caption'] ?? null,
                'uploaded_by' => $data['uploaded_by'] ?? null,
            ]
        );

        return (int) $pdo->lastInsertId();
    }

    public function updateCaption(int $id, string $caption): void
    {
        $this->database->execute(
            'UPDATE resume_experience_documents SET caption = :caption WHERE id = :id AND deleted_at IS NULL',
            ['caption' => $caption !== '' ? $caption : null, 'id' => $id]
        );
    }

    public function softDelete(int $id): void
    {
        $this->database->execute(
            'UPDATE resume_experience_documents SET deleted_at = NOW() WHERE id = :id',
            ['id' => $id]
        );
    }

    public function pathsByExperience(int $experienceId): array
    {
        $rows = $this->database->fetchAll(
            'SELECT path FROM resume_experience_documents WHERE experience_id = :experience_id',
            ['experience_id' => $experienceId]
        );

        return array_column($rows, 'path');
    }
}
