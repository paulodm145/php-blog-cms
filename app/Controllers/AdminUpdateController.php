<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Migrator;
use App\Core\View;
use App\Database\Database;

class AdminUpdateController
{
    private $rootPath;

    public function __construct()
    {
        $this->rootPath = dirname(__DIR__, 2);
    }

    public function show(): void
    {
        Auth::requireAdmin();

        View::render('admin/update', [
            'title' => 'Atualizar banco de dados | Admin paulorb.dev',
            'user' => Auth::user(),
            'pending' => $this->migrator()->pending(),
        ]);
    }

    public function run(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['error' => 'sessao_expirada']);

            return;
        }

        @set_time_limit(0);

        echo json_encode($this->migrator()->runNextPending());
    }

    private function migrator(): Migrator
    {
        return new Migrator(new Database(), $this->rootPath . '/database/migrations');
    }
}
