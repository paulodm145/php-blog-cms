<?php

namespace App\Core;

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function check(): bool
    {
        self::start();

        return isset($_SESSION['admin_user']);
    }

    public static function user(): ?array
    {
        self::start();

        return $_SESSION['admin_user'] ?? null;
    }

    public static function login(array $user): void
    {
        self::start();

        $_SESSION['admin_user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];
    }

    public static function logout(): void
    {
        self::start();

        unset($_SESSION['admin_user']);
        session_regenerate_id(true);
    }

    public static function requireAdmin(): void
    {
        if (!self::check()) {
            header('Location: /admin/login');
            exit;
        }
    }
}

