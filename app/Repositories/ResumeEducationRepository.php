<?php

namespace App\Repositories;

use App\Database\Database;

class ResumeEducationRepository
{
    private $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?: new Database();
    }

    public function all(): array
    {
        return $this->database->fetchAll('SELECT * FROM resume_education ORDER BY sort_order, id');
    }

    public function find(int $id): ?array
    {
        return $this->database->fetch('SELECT * FROM resume_education WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function create(array $data): void
    {
        $values = $this->normalize($data);
        $values['sort_order'] = $this->nextSortOrder();

        $this->database->execute(
            'INSERT INTO resume_education (course, institution, period, sort_order)
             VALUES (:course, :institution, :period, :sort_order)',
            $values
        );
    }

    public function update(int $id, array $data): void
    {
        $values = $this->normalize($data);
        unset($values['sort_order']);
        $values['id'] = $id;

        $this->database->execute(
            'UPDATE resume_education
             SET course = :course, institution = :institution, period = :period
             WHERE id = :id',
            $values
        );
    }

    public function delete(int $id): void
    {
        $this->database->execute('DELETE FROM resume_education WHERE id = :id', ['id' => $id]);
    }

    public function moveUp(int $id): void
    {
        $this->swap($id, -1);
    }

    public function moveDown(int $id): void
    {
        $this->swap($id, 1);
    }

    private function swap(int $id, int $direction): void
    {
        $items = $this->all();
        $index = null;

        foreach ($items as $position => $item) {
            if ((int) $item['id'] === $id) {
                $index = $position;
                break;
            }
        }

        $targetIndex = $index + $direction;

        if ($index === null || $targetIndex < 0 || $targetIndex >= count($items)) {
            return;
        }

        [$items[$index], $items[$targetIndex]] = [$items[$targetIndex], $items[$index]];

        foreach ($items as $position => $item) {
            $this->database->execute(
                'UPDATE resume_education SET sort_order = :sort_order WHERE id = :id',
                ['sort_order' => $position, 'id' => $item['id']]
            );
        }
    }

    private function nextSortOrder(): int
    {
        $row = $this->database->fetch('SELECT COALESCE(MAX(sort_order), -1) AS max_order FROM resume_education');

        return (int) $row['max_order'] + 1;
    }

    private function normalize(array $data): array
    {
        return [
            'course' => trim((string) $data['course']),
            'institution' => trim((string) $data['institution']),
            'period' => trim((string) $data['period']),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
