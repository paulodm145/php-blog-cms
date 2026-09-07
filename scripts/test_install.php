<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Env;
use App\Database\Database;

$rootPath = dirname(__DIR__);

require_once $rootPath . '/app/Core/Autoloader.php';

$autoloader = new Autoloader($rootPath . '/app');
$autoloader->register();

Env::load($rootPath . '/.env');

$GLOBALS['test_failures'] = 0;
$GLOBALS['test_count'] = 0;
$GLOBALS['test_temp_dirs'] = [];

function it(string $name, callable $body): void
{
    $GLOBALS['test_count']++;

    try {
        $body();
        echo "  \033[32m✓\033[0m {$name}\n";
    } catch (Throwable $error) {
        $GLOBALS['test_failures']++;
        echo "  \033[31m✗\033[0m {$name}\n";
        echo "      " . $error->getMessage() . "\n";
        echo "      " . $error->getFile() . ':' . $error->getLine() . "\n";
    }
}

function assertSame($expected, $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            ($message !== '' ? $message . ' — ' : '')
            . 'esperado ' . var_export($expected, true)
            . ', recebido ' . var_export($actual, true)
        );
    }
}

function assertTrue($value, string $message = ''): void
{
    assertSame(true, $value, $message);
}

function assertFalse($value, string $message = ''): void
{
    assertSame(false, $value, $message);
}

function assertContains(string $needle, string $haystack, string $message = ''): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException(
            ($message !== '' ? $message . ' — ' : '')
            . 'esperado encontrar ' . var_export($needle, true)
            . ' em ' . var_export($haystack, true)
        );
    }
}

function assertThrows(callable $body, string $message = ''): void
{
    try {
        $body();
    } catch (Throwable $error) {
        return;
    }

    throw new RuntimeException(
        ($message !== '' ? $message . ' — ' : '') . 'esperava uma excecao, nada foi lancado'
    );
}

function tempDir(): string
{
    $path = sys_get_temp_dir() . '/blog_php_test_' . bin2hex(random_bytes(6));
    mkdir($path, 0777, true);
    $GLOBALS['test_temp_dirs'][] = $path;

    return $path;
}

function removeDir(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    foreach (scandir($path) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $full = $path . '/' . $entry;
        is_dir($full) ? removeDir($full) : unlink($full);
    }

    rmdir($path);
}

function testDatabaseName(): string
{
    return getenv('TEST_DB_DATABASE') ?: 'blog_test';
}

function testDatabase(): Database
{
    $name = testDatabaseName();

    if (in_array($name, ['blog', 'db', Env::get('DB_DATABASE')], true)) {
        fwrite(STDERR, "RECUSADO: os testes nao podem rodar contra o banco {$name}.\n");
        exit(1);
    }

    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            Env::get('DB_HOST', '127.0.0.1'),
            Env::get('DB_PORT', '3306'),
            $name,
            Env::get('DB_CHARSET', 'utf8mb4')
        ),
        Env::get('DB_USERNAME', 'root'),
        Env::get('DB_PASSWORD', ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // O usuario do banco nao tem CREATE DATABASE neste ambiente, entao o schema
    // e reaproveitado e esvaziado a cada chamada.
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        $pdo->exec('DROP TABLE IF EXISTS `' . $table . '`');
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    return new Database($pdo);
}

require_once __DIR__ . '/tests/env_writer_test.php';
require_once __DIR__ . '/tests/migrator_test.php';
require_once __DIR__ . '/tests/install_state_test.php';
require_once __DIR__ . '/tests/user_password_test.php';
require_once __DIR__ . '/tests/requirements_test.php';

foreach ($GLOBALS['test_temp_dirs'] as $dir) {
    removeDir($dir);
}

echo "\n{$GLOBALS['test_count']} testes, {$GLOBALS['test_failures']} falhas\n";

exit($GLOBALS['test_failures'] > 0 ? 1 : 0);
