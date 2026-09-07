<?php

namespace App\Core;

class View
{
    public static function render(string $template, array $data = []): void
    {
        $viewPath = dirname(__DIR__) . '/Views/' . trim($template, '/') . '.php';

        if (!is_file($viewPath)) {
            ErrorPage::serverError();
            return;
        }

        extract($data, EXTR_SKIP);
        require $viewPath;
    }
}
