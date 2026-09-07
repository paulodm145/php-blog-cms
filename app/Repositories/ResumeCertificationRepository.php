<?php

namespace App\Repositories;

use App\Database\Database;

class ResumeCertificationRepository
{
    private $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?: new Database();
    }

    public function all(): array
    {
        return $this->database->fetchAll('SELECT * FROM resume_certifications ORDER BY sort_order, id');
    }

    public function find(int $id): ?array
    {
        return $this->database->fetch('SELECT * FROM resume_certifications WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function create(array $data): void
    {
        $values = $this->normalize($data);
        $values['sort_order'] = $this->nextSortOrder();

        $this->database->execute(
            'INSERT INTO resume_certifications (name, issuer, period, credential_url, sort_order)
             VALUES (:name, :issuer, :period, :credential_url, :sort_order)',
            $values
        );
    }

    public function update(int $id, array $data): void
    {
        $values = $this->normalize($data);
        unset($values['sort_order']);
        $values['id'] = $id;

        $this->database->execute(
            'UPDATE resume_certifications
             SET name = :name, issuer = :issuer, period = :period, credential_url = :credential_url
             WHERE id = :id',
            $values
        );
    }

    public function delete(int $id): void
    {
        $this->database->execute('DELETE FROM resume_certifications WHERE id = :id', ['id' => $id]);
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
                'UPDATE resume_certifications SET sort_order = :sort_order WHERE id = :id',
                ['sort_order' => $position, 'id' => $item['id']]
            );
        }
    }

    private function nextSortOrder(): int
    {
        $row = $this->database->fetch('SELECT COALESCE(MAX(sort_order), -1) AS max_order FROM resume_certifications');

        return (int) $row['max_order'] + 1;
    }

    private function normalize(array $data): array
    {
        $credentialUrl = trim((string) ($data['credential_url'] ?? ''));

        return [
            'name' => trim((string) $data['name']),
            'issuer' => trim((string) $data['issuer']),
            'period' => trim((string) $data['period']),
            'credential_url' => $credentialUrl !== '' && preg_match('#^https?://#i', $credentialUrl) ? $credentialUrl : '',
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
