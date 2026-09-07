<?php

declare(strict_types=1);

use App\Core\Env;
use App\Core\InstallState;
use App\Core\Migrator;

echo "\nInstallState\n";

it('nao instalado quando nao ha .env', function (): void {
    assertFalse((new InstallState(tempDir()))->isInstalled());
});

it('nao instalado quando o .env existe sem APP_INSTALLED', function (): void {
    $root = tempDir();
    file_put_contents($root . '/.env', "DB_HOST=localhost\n");

    assertFalse((new InstallState($root))->isInstalled());
});

it('nao instalado quando APP_INSTALLED e diferente de true', function (): void {
    $root = tempDir();
    file_put_contents($root . '/.env', "APP_INSTALLED=false\n");

    assertFalse((new InstallState($root))->isInstalled());
});

it('instalado quando APP_INSTALLED e true', function (): void {
    $root = tempDir();
    file_put_contents($root . '/.env', "APP_INSTALLED=true\n");

    assertTrue((new InstallState($root))->isInstalled());
});

it('nao ha chave quando falta o .env.install', function (): void {
    assertFalse((new InstallState(tempDir()))->hasInstallKey());
});

it('nao ha chave quando INSTALL_KEY esta vazio', function (): void {
    $root = tempDir();
    file_put_contents($root . '/.env.install', "INSTALL_KEY=\n");

    assertFalse((new InstallState($root))->hasInstallKey());
});

it('le a chave de instalacao', function (): void {
    $root = tempDir();
    file_put_contents($root . '/.env.install', "INSTALL_KEY=k7f2m9x4qp\n");
    $state = new InstallState($root);

    assertTrue($state->hasInstallKey());
    assertSame('k7f2m9x4qp', $state->installKey());
});

it('installKey devolve null quando nao ha arquivo', function (): void {
    assertSame(null, (new InstallState(tempDir()))->installKey());
});

it('pendingCount e zero quando nao instalado', function (): void {
    assertSame(0, (new InstallState(tempDir()))->pendingCount());
});

it('pendingCount e zero quando o banco nao responde', function (): void {
    $root = tempDir();
    mkdir($root . '/database/migrations', 0777, true);
    file_put_contents($root . '/database/migrations/001_a.sql', 'CREATE TABLE a (id INT);');
    file_put_contents(
        $root . '/.env',
        "APP_INSTALLED=true\nDB_HOST=127.0.0.1\nDB_PORT=9\nDB_DATABASE=inexistente\nDB_USERNAME=ninguem\nDB_PASSWORD=x\n"
    );

    try {
        $_ENV['DB_PORT'] = '9';
        $_ENV['DB_DATABASE'] = 'inexistente';

        assertSame(0, (new InstallState($root))->pendingCount());
    } finally {
        Env::load(dirname(__DIR__, 2) . '/.env');
    }
});

it('pendingCount devolve count correto de migrations pendentes', function (): void {
    try {
        $db = testDatabase();
        $testDbName = testDatabaseName();
        $_ENV['DB_DATABASE'] = $testDbName;

        $root = tempDir();
        mkdir($root . '/database/migrations', 0777, true);
        file_put_contents($root . '/database/migrations/001_first.sql', 'SELECT 1;');
        file_put_contents($root . '/database/migrations/002_second.sql', 'SELECT 2;');
        file_put_contents($root . '/.env', "APP_INSTALLED=true\n");

        $migrator = new Migrator($db, $root . '/database/migrations');
        $migrator->pending();

        assertSame(2, (new InstallState($root))->pendingCount());

        $db->connection()->exec("INSERT INTO migrations (migration) VALUES ('001_first.sql')");

        assertSame(1, (new InstallState($root))->pendingCount());
    } finally {
        Env::load(dirname(__DIR__, 2) . '/.env');
    }
});
