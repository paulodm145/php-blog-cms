<?php

namespace App\Core;

use App\Database\Database;

class Migrator
{
    private $database;
    private $migrationsPath;
    private $migrationsTableEnsured = false;

    public function __construct(Database $database, string $migrationsPath)
    {
        $this->database = $database;
        $this->migrationsPath = rtrim($migrationsPath, DIRECTORY_SEPARATOR);
    }

    public function run(): array
    {
        $this->ensureMigrationsTable();
        $pdo = $this->database->connection();

        $executedNames = $this->getExecutedMigrationNames();
        $migrationFiles = $this->getMigrationFiles();

        $results = [];

        foreach ($migrationFiles as $filePath) {
            $migrationName = basename($filePath);

            if (in_array($migrationName, $executedNames, true)) {
                $results[] = ['migration' => $migrationName, 'status' => 'skipped'];
                continue;
            }

            $sql = file_get_contents($filePath);

            if ($sql === false || trim($sql) === '') {
                $this->database->execute('INSERT INTO migrations (migration) VALUES (:migration)', [
                    'migration' => $migrationName,
                ]);
                $results[] = ['migration' => $migrationName, 'status' => 'empty'];
                continue;
            }

            $pdo->exec($sql);
            $this->database->execute('INSERT INTO migrations (migration) VALUES (:migration)', [
                'migration' => $migrationName,
            ]);
            $results[] = ['migration' => $migrationName, 'status' => 'executed'];
        }

        return $results;
    }

    public function pending(): array
    {
        $this->ensureMigrationsTable();

        $executedNames = $this->getExecutedMigrationNames();
        $migrationFiles = $this->getMigrationFiles();

        $pending = [];

        foreach ($migrationFiles as $filePath) {
            $name = basename($filePath);

            if (!in_array($name, $executedNames, true)) {
                $pending[] = $name;
            }
        }

        return $pending;
    }

    public function runNextPending(): array
    {
        $pending = $this->pending();

        if ($pending === []) {
            return ['done' => true];
        }

        $result = $this->runOne($pending[0]);

        $remaining = count($pending);

        if ($result['status'] !== 'error') {
            $remaining--;
        }

        return [
            'done' => false,
            'migration' => $result['migration'],
            'status' => $result['status'],
            'duration_ms' => $result['duration_ms'],
            'error' => $result['error'],
            'remaining' => $remaining,
        ];
    }

    public function runOne(string $name): array
    {
        $this->ensureMigrationsTable();

        $result = [
            'migration' => $name,
            'status' => 'error',
            'duration_ms' => 0,
            'error' => null,
        ];

        if ($name !== basename($name)) {
            $result['error'] = 'Nome de migration invalido.';

            return $result;
        }

        $filePath = $this->migrationsPath . '/' . $name;

        if (!is_file($filePath)) {
            $result['error'] = 'Migration nao encontrada.';

            return $result;
        }

        $already = $this->database->fetch(
            'SELECT migration FROM migrations WHERE migration = :migration LIMIT 1',
            ['migration' => $name]
        );

        if ($already !== null) {
            $result['status'] = 'skipped';
            $result['error'] = null;

            return $result;
        }

        $sql = file_get_contents($filePath);

        if ($sql === false || trim($sql) === '') {
            $this->database->execute(
                'INSERT INTO migrations (migration) VALUES (:migration)',
                ['migration' => $name]
            );
            $result['status'] = 'empty';
            $result['error'] = null;

            return $result;
        }

        $startedAt = microtime(true);

        try {
            $this->database->connection()->exec($sql);
            $this->database->execute(
                'INSERT INTO migrations (migration) VALUES (:migration)',
                ['migration' => $name]
            );
            $result['status'] = 'executed';
            $result['error'] = null;
        } catch (\PDOException $exception) {
            $result['error'] = $exception->getMessage();
        }

        $result['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);

        return $result;
    }

    private function ensureMigrationsTable(): void
    {
        if ($this->migrationsTableEnsured) {
            return;
        }

        $this->database->connection()->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->migrationsTableEnsured = true;
    }

    private function getExecutedMigrationNames(): array
    {
        $executed = $this->database->fetchAll('SELECT migration FROM migrations');

        return array_column($executed, 'migration');
    }

    private function getMigrationFiles(): array
    {
        $migrationFiles = glob($this->migrationsPath . '/*.sql') ?: [];
        sort($migrationFiles);

        return $migrationFiles;
    }
}
