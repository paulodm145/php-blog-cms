<?php

namespace App\Core;

use App\Controllers\InstallController;

class InstallRoutes
{
    public static function build(): Router
    {
        $router = new Router();

        $router->get('/install', [InstallController::class, 'requirements']);
        $router->post('/install/key', [InstallController::class, 'submitKey']);
        $router->get('/install/database', [InstallController::class, 'database']);
        $router->post('/install/database', [InstallController::class, 'saveDatabase']);
        $router->post('/install/database/test', [InstallController::class, 'testConnection']);
        $router->post('/install/database/confirm', [InstallController::class, 'confirmEnv']);
        $router->get('/install/migrate', [InstallController::class, 'migrate']);
        $router->post('/install/migrate/run', [InstallController::class, 'runMigration']);
        $router->get('/install/admin', [InstallController::class, 'admin']);
        $router->post('/install/admin', [InstallController::class, 'saveAdmin']);
        $router->get('/install/done', [InstallController::class, 'done']);

        return $router;
    }

    public static function handle(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        if ($path !== '/install' && strpos($path, '/install/') !== 0) {
            header('Location: /install');

            return;
        }

        self::build()->dispatch($method, $uri);
    }
}
