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

    public static function requireMcpApiKey(): void
    {
        $expected = Env::get('MCP_API_KEY', '');
        $received = $_SERVER['HTTP_X_BLOG_API_KEY'] ?? '';

        if ($expected === '' || $received === '' || !hash_equals($expected, $received)) {
            self::logMcpCall(false);
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'unauthorized']);
            exit;
        }
    }

    /**
     * Log de auditoria da API /api/mcp/*. Chamado tanto daqui (negacao de
     * acesso, antes do controller existir) quanto de
     * McpController::jsonResponse() (toda resposta, sucesso ou erro de
     * validacao) — por isso e publico, nao privado.
     */
    public static function logMcpCall(bool $success): void
    {
        $line = date('c') . ' ' . ($success ? 'OK' : 'DENIED') . ' ' . ($_SERVER['REQUEST_METHOD'] ?? '?') . ' ' . ($_SERVER['REQUEST_URI'] ?? '?') . "\n";
        $logDir = dirname(__DIR__, 2) . '/storage/logs';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logDir . '/mcp-api.log', $line, FILE_APPEND);
    }
}

