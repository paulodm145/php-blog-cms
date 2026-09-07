<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Repositories\UserRepository;

class AdminAuthController
{
    private $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function loginForm(): void
    {
        if (Auth::check()) {
            header('Location: /admin');
            return;
        }

        View::render('admin/auth/login', [
            'title' => 'Login administrativo | paulorb.dev',
            'error' => null,
        ]);
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $user = $this->users->findByEmail($email);

        if ($user === null || !$this->users->verifyPassword($password, $user)) {
            http_response_code(422);
            View::render('admin/auth/login', [
                'title' => 'Login administrativo | paulorb.dev',
                'error' => 'E-mail ou senha invalidos.',
            ]);
            return;
        }

        Auth::login($user);
        header('Location: /admin');
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /admin/login');
    }
}

