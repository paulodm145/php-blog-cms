<?php

namespace App\Repositories;

use App\Database\Database;

class UserRepository
{
    private $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database ?: new Database();
    }

    public function findByEmail(string $email): ?array
    {
        return $this->database->fetch(
            'SELECT id, name, email, password FROM users WHERE email = :email LIMIT 1',
            ['email' => $email]
        );
    }

    public function all(): array
    {
        return $this->database->fetchAll('SELECT id, name, email, created_at FROM users ORDER BY name');
    }

    public function find(int $id): ?array
    {
        return $this->database->fetch(
            'SELECT id, name, email FROM users WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function create(array $data): void
    {
        $this->database->execute(
            'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)',
            [
                'name' => trim($data['name']),
                'email' => trim($data['email']),
                'password' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            ]
        );
    }

    public function update(int $id, array $data): void
    {
        $params = [
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'id' => $id,
        ];
        $passwordSql = '';

        if (trim((string) ($data['password'] ?? '')) !== '') {
            $passwordSql = ', password = :password';
            $params['password'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }

        $this->database->execute(
            'UPDATE users SET name = :name, email = :email' . $passwordSql . ' WHERE id = :id',
            $params
        );
    }

    public function delete(int $id): void
    {
        $this->database->execute('DELETE FROM users WHERE id = :id', ['id' => $id]);
    }

    public function verifyPassword(string $plain, array $user): bool
    {
        $hash = (string) ($user['password'] ?? '');

        if ($hash === '') {
            return false;
        }

        if (password_verify($plain, $hash)) {
            return true;
        }

        if (strlen($hash) === 32 && hash_equals($hash, md5($plain))) {
            $this->database->execute(
                'UPDATE users SET password = :password WHERE id = :id',
                [
                    'password' => password_hash($plain, PASSWORD_DEFAULT),
                    'id' => (int) $user['id'],
                ]
            );

            return true;
        }

        return false;
    }

    public function deleteByEmail(string $email): void
    {
        $this->database->execute('DELETE FROM users WHERE email = :email', ['email' => $email]);
    }
}
