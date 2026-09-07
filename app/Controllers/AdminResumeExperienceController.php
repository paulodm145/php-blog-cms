<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ErrorPage;
use App\Core\View;
use App\Repositories\ResumeExperienceRepository;

class AdminResumeExperienceController
{
    private $experience;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->experience = new ResumeExperienceRepository();
    }

    public function create(): void
    {
        View::render('admin/resume-experience-form', [
            'title' => 'Nova experiência | Admin paulorb.dev',
            'user' => Auth::user(),
            'item' => ['id' => null, 'role' => '', 'company' => '', 'period' => '', 'description' => '', 'sort_order' => 0],
            'isNew' => true,
        ]);
    }

    public function store(): void
    {
        $this->experience->create($_POST);
        header('Location: /admin/curriculo?saved=1#tab-experiencia');
    }

    public function edit(string $id): void
    {
        $item = $this->experience->find((int) $id);

        if ($item === null) {
            ErrorPage::notFound();
            return;
        }

        View::render('admin/resume-experience-form', [
            'title' => 'Editar experiência | Admin paulorb.dev',
            'user' => Auth::user(),
            'item' => $item,
            'isNew' => false,
        ]);
    }

    public function update(string $id): void
    {
        $this->experience->update((int) $id, $_POST);
        header('Location: /admin/curriculo?saved=1#tab-experiencia');
    }

    public function delete(string $id): void
    {
        $this->experience->delete((int) $id);
        header('Location: /admin/curriculo?saved=1#tab-experiencia');
    }

    public function moveUp(string $id): void
    {
        $this->experience->moveUp((int) $id);
        header('Location: /admin/curriculo#tab-experiencia');
    }

    public function moveDown(string $id): void
    {
        $this->experience->moveDown((int) $id);
        header('Location: /admin/curriculo#tab-experiencia');
    }
}
