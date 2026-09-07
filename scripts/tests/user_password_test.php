<?php

declare(strict_types=1);

use App\Database\Database;
use App\Repositories\UserRepository;

echo "\nUserRepository\n";

function usersFixture(): Database
{
    $database = testDatabase();
    $database->connection()->exec(
        'CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(180) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    return $database;
}

it('create grava a senha com password_hash e nao em texto puro nem md5', function (): void {
    $users = new UserRepository(usersFixture());
    $users->create(['name' => 'Ana', 'email' => 'ana@exemplo.com', 'password' => 'segredo123']);

    $stored = $users->findByEmail('ana@exemplo.com')['password'];

    assertTrue(strlen($stored) > 32, 'hash moderno deve passar de 32 caracteres');
    assertTrue($stored !== md5('segredo123'));
    assertTrue($stored !== 'segredo123');
    assertTrue(password_verify('segredo123', $stored));
});

it('verifyPassword aceita a senha correta', function (): void {
    $users = new UserRepository(usersFixture());
    $users->create(['name' => 'Ana', 'email' => 'ana@exemplo.com', 'password' => 'segredo123']);

    assertTrue($users->verifyPassword('segredo123', $users->findByEmail('ana@exemplo.com')));
});

it('verifyPassword recusa a senha errada', function (): void {
    $users = new UserRepository(usersFixture());
    $users->create(['name' => 'Ana', 'email' => 'ana@exemplo.com', 'password' => 'segredo123']);

    assertFalse($users->verifyPassword('errada', $users->findByEmail('ana@exemplo.com')));
});

it('verifyPassword aceita hash md5 legado', function (): void {
    $database = usersFixture();
    $database->execute(
        'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)',
        ['name' => 'Antigo', 'email' => 'antigo@exemplo.com', 'password' => md5('123456')]
    );
    $users = new UserRepository($database);

    assertTrue($users->verifyPassword('123456', $users->findByEmail('antigo@exemplo.com')));
});

it('verifyPassword regrava o hash legado no formato novo', function (): void {
    $database = usersFixture();
    $database->execute(
        'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)',
        ['name' => 'Antigo', 'email' => 'antigo@exemplo.com', 'password' => md5('123456')]
    );
    $users = new UserRepository($database);
    $users->verifyPassword('123456', $users->findByEmail('antigo@exemplo.com'));

    $stored = $users->findByEmail('antigo@exemplo.com')['password'];

    assertTrue($stored !== md5('123456'), 'o hash md5 deveria ter sido substituido');
    assertTrue(password_verify('123456', $stored));
});

it('verifyPassword recusa senha errada contra hash md5 legado', function (): void {
    $database = usersFixture();
    $database->execute(
        'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)',
        ['name' => 'Antigo', 'email' => 'antigo@exemplo.com', 'password' => md5('123456')]
    );
    $users = new UserRepository($database);

    assertFalse($users->verifyPassword('outra', $users->findByEmail('antigo@exemplo.com')));
});

it('verifyPassword recusa o proprio digest md5 enviado como senha literal', function (): void {
    $database = usersFixture();
    $database->execute(
        'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)',
        ['name' => 'Antigo', 'email' => 'antigo@exemplo.com', 'password' => md5('123456')]
    );
    $users = new UserRepository($database);

    assertFalse($users->verifyPassword(md5('123456'), $users->findByEmail('antigo@exemplo.com')));
});

it('update sem senha preserva o hash existente', function (): void {
    $users = new UserRepository(usersFixture());
    $users->create(['name' => 'Ana', 'email' => 'ana@exemplo.com', 'password' => 'segredo123']);
    $user = $users->findByEmail('ana@exemplo.com');

    $users->update((int) $user['id'], ['name' => 'Ana Maria', 'email' => 'ana@exemplo.com']);

    assertSame($user['password'], $users->findByEmail('ana@exemplo.com')['password']);
});

it('deleteByEmail remove o usuario', function (): void {
    $users = new UserRepository(usersFixture());
    $users->create(['name' => 'Ana', 'email' => 'ana@exemplo.com', 'password' => 'segredo123']);

    $users->deleteByEmail('ana@exemplo.com');

    assertSame(null, $users->findByEmail('ana@exemplo.com'));
});
