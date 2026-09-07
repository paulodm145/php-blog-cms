<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ErrorPage;
use App\Core\View;
use App\Repositories\ResumeCertificationRepository;

class AdminResumeCertificationController
{
    private $certifications;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->certifications = new ResumeCertificationRepository();
    }

    public function create(): void
    {
        View::render('admin/resume-certification-form', [
            'title' => 'Nova certificação | Admin paulorb.dev',
            'user' => Auth::user(),
            'item' => ['id' => null, 'name' => '', 'issuer' => '', 'period' => '', 'credential_url' => ''],
            'isNew' => true,
        ]);
    }

    public function store(): void
    {
        $this->certifications->create($_POST);
        header('Location: /admin/curriculo?saved=1#tab-certificacoes');
    }

    public function edit(string $id): void
    {
        $item = $this->certifications->find((int) $id);

        if ($item === null) {
            ErrorPage::notFound();
            return;
        }

        View::render('admin/resume-certification-form', [
            'title' => 'Editar certificação | Admin paulorb.dev',
            'user' => Auth::user(),
            'item' => $item,
            'isNew' => false,
        ]);
    }

    public function update(string $id): void
    {
        $this->certifications->update((int) $id, $_POST);
        header('Location: /admin/curriculo?saved=1#tab-certificacoes');
    }

    public function delete(string $id): void
    {
        $this->certifications->delete((int) $id);
        header('Location: /admin/curriculo?saved=1#tab-certificacoes');
    }

    public function moveUp(string $id): void
    {
        $this->certifications->moveUp((int) $id);
        header('Location: /admin/curriculo#tab-certificacoes');
    }

    public function moveDown(string $id): void
    {
        $this->certifications->moveDown((int) $id);
        header('Location: /admin/curriculo#tab-certificacoes');
    }
}
