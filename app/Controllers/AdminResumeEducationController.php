<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ErrorPage;
use App\Core\View;
use App\Repositories\ResumeEducationRepository;

class AdminResumeEducationController
{
    private $education;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->education = new ResumeEducationRepository();
    }

    public function create(): void
    {
        View::render('admin/resume-education-form', [
            'title' => 'Nova formação | Admin paulorb.dev',
            'user' => Auth::user(),
            'item' => ['id' => null, 'course' => '', 'institution' => '', 'period' => '', 'sort_order' => 0],
            'isNew' => true,
        ]);
    }

    public function store(): void
    {
        $this->education->create($_POST);
        header('Location: /admin/curriculo?saved=1#tab-formacao');
    }

    public function edit(string $id): void
    {
        $item = $this->education->find((int) $id);

        if ($item === null) {
            ErrorPage::notFound();
            return;
        }

        View::render('admin/resume-education-form', [
            'title' => 'Editar formação | Admin paulorb.dev',
            'user' => Auth::user(),
            'item' => $item,
            'isNew' => false,
        ]);
    }

    public function update(string $id): void
    {
        $this->education->update((int) $id, $_POST);
        header('Location: /admin/curriculo?saved=1#tab-formacao');
    }

    public function delete(string $id): void
    {
        $this->education->delete((int) $id);
        header('Location: /admin/curriculo?saved=1#tab-formacao');
    }

    public function moveUp(string $id): void
    {
        $this->education->moveUp((int) $id);
        header('Location: /admin/curriculo#tab-formacao');
    }

    public function moveDown(string $id): void
    {
        $this->education->moveDown((int) $id);
        header('Location: /admin/curriculo#tab-formacao');
    }
}
