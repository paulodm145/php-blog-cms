<?php

namespace App\Repositories;

use App\Database\Database;

class ResumeDocumentFolderRepository
{
    private $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?: new Database();
    }

    public function all(): array
    {
        return $this->database->fetchAll('SELECT * FROM resume_document_folders ORDER BY name');
    }

    public function find(int $id): ?array
    {
        return $this->database->fetch('SELECT * FROM resume_document_folders WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function children(?int $parentId): array
    {
        return $this->database->fetchAll(
            'SELECT f.*, (
                SELECT COUNT(*) FROM resume_experience_documents d
                WHERE d.folder_id = f.id AND d.deleted_at IS NULL
             ) AS document_count
             FROM resume_document_folders f
             WHERE f.parent_id <=> :parent_id
             ORDER BY f.name',
            ['parent_id' => $parentId]
        );
    }

    public function create(?int $parentId, string $name): int
    {
        $pdo = $this->database->connection();

        $this->database->execute(
            'INSERT INTO resume_document_folders (parent_id, name) VALUES (:parent_id, :name)',
            ['parent_id' => $parentId, 'name' => $name]
        );

        return (int) $pdo->lastInsertId();
    }

    public function rename(int $id, string $name): void
    {
        $this->database->execute(
            'UPDATE resume_document_folders SET name = :name WHERE id = :id',
            ['name' => $name, 'id' => $id]
        );
    }

    public function move(int $id, ?int $newParentId): void
    {
        $this->database->execute(
            'UPDATE resume_document_folders SET parent_id = :parent_id WHERE id = :id',
            ['parent_id' => $newParentId, 'id' => $id]
        );
    }

    public function delete(int $id): void
    {
        $this->database->execute('DELETE FROM resume_document_folders WHERE id = :id', ['id' => $id]);
    }

    public function breadcrumb(int $folderId): array
    {
        $byId = $this->indexById($this->all());
        $trail = [];
        $currentId = $folderId;

        while ($currentId !== null && isset($byId[$currentId])) {
            array_unshift($trail, $byId[$currentId]);
            $currentId = $byId[$currentId]['parent_id'] !== null ? (int) $byId[$currentId]['parent_id'] : null;
        }

        return $trail;
    }

    public function descendantIds(int $folderId): array
    {
        $childrenByParent = [];

        foreach ($this->all() as $folder) {
            $parentKey = $folder['parent_id'] !== null ? (int) $folder['parent_id'] : 0;
            $childrenByParent[$parentKey][] = (int) $folder['id'];
        }

        $ids = [$folderId];
        $queue = [$folderId];

        while (!empty($queue)) {
            $current = array_shift($queue);

            foreach ($childrenByParent[$current] ?? [] as $childId) {
                $ids[] = $childId;
                $queue[] = $childId;
            }
        }

        return $ids;
    }

    public function wouldCreateCycle(int $folderId, ?int $newParentId): bool
    {
        if ($newParentId === null) {
            return false;
        }

        if ($newParentId === $folderId) {
            return true;
        }

        $byId = $this->indexById($this->all());
        $currentId = $newParentId;

        while ($currentId !== null && isset($byId[$currentId])) {
            if ($currentId === $folderId) {
                return true;
            }

            $currentId = $byId[$currentId]['parent_id'] !== null ? (int) $byId[$currentId]['parent_id'] : null;
        }

        return false;
    }

    private function indexById(array $folders): array
    {
        $byId = [];

        foreach ($folders as $folder) {
            $byId[(int) $folder['id']] = $folder;
        }

        return $byId;
    }
}
