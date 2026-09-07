<?php

namespace App\Core;

use App\Database\Database;

class InstallState
{
    private $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
    }

    public function isInstalled(): bool
    {
        $values = (new EnvWriter($this->rootPath . '/.env'))->read();

        return ($values['APP_INSTALLED'] ?? '') === 'true';
    }

    public function hasInstallKey(): bool
    {
        $key = $this->installKey();

        return $key !== null && $key !== '';
    }

    public function installKey(): ?string
    {
        $values = (new EnvWriter($this->rootPath . '/.env.install'))->read();

        return $values['INSTALL_KEY'] ?? null;
    }

    public function pendingCount(): int
    {
        if (!$this->isInstalled()) {
            return 0;
        }

        try {
            $migrator = new Migrator(new Database(), $this->rootPath . '/database/migrations');

            return count($migrator->pending());
        } catch (\PDOException $error) {
            return 0;
        }
    }
}
