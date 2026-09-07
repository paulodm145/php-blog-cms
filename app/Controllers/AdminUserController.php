<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ErrorPage;
use App\Core\View;
use App\Repositories\UserRepository;

class AdminUserController
{
    private $users;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->users = new UserRepository();
    }

    public function index(): void
    {
        View::render('admin/users', [
            'title' => 'Usuarios | Admin paulorb.dev',
            'user' => Auth::user(),
            'users' => $this->users->all(),
        ]);
    }

    public function create(): void
    {
        View::render('admin/user-form', [
            'title' => 'Novo usuario | Admin paulorb.dev',
            'user' => Auth::user(),
            'adminUser' => ['id' => null, 'name' => '', 'email' => ''],
            'isNew' => true,
        ]);
    }

    public function store(): void
    {
        $this->users->create($_POST);
        header('Location: /admin/users');
    }

    public function edit(string $id): void
    {
        $user = $this->users->find((int) $id);

        if ($user === null) {
            ErrorPage::notFound();
            return;
        }

        View::render('admin/user-form', [
            'title' => 'Editar usuario | Admin paulorb.dev',
            'user' => Auth::user(),
            'adminUser' => $user,
            'isNew' => false,
        ]);
    }

    public function update(string $id): void
    {
        $this->users->update((int) $id, $_POST);
        header('Location: /admin/users');
    }

    public function delete(string $id): void
    {
        $currentUser = Auth::user();

        if ((int) $currentUser['id'] !== (int) $id) {
            $this->users->delete((int) $id);
        }

        header('Location: /admin/users');
    }
}
