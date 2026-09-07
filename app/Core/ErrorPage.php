<?php

namespace App\Core;

use App\Repositories\SettingRepository;

class ErrorPage
{
    public static function notFound(): void
    {
        self::render(404, 'Pagina nao encontrada');
    }

    public static function forbidden(): void
    {
        self::render(403, 'Acesso negado');
    }

    public static function serverError(): void
    {
        self::render(500, 'Erro interno');
    }

    public static function render(int $statusCode, string $title): void
    {
        http_response_code($statusCode);

        $viewPath = dirname(__DIR__) . '/Views/errors/' . $statusCode . '.php';

        if (!is_file($viewPath)) {
            echo $statusCode . ' - ' . $title;
            return;
        }

        $settings = self::settings();
        require $viewPath;
    }

    private static function settings(): array
    {
        $defaults = [
            'site_name' => 'paulorb.dev',
            'blog_description' => 'Engenheiro de software. Escrevo sobre IA, LLMs, Go, TypeScript e arquitetura.',
            'github_url' => '',
            'linkedin_url' => '',
        ];

        try {
            return (new SettingRepository())->all();
        } catch (\Throwable $exception) {
            return $defaults;
        }
    }
}
