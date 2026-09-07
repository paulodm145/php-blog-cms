<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ErrorPage;
use App\Core\View;
use App\Repositories\PageRepository;

class AdminPageController
{
    private $pages;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->pages = new PageRepository();
    }

    public function index(): void
    {
        View::render('admin/pages', [
            'title' => 'Páginas | Admin paulorb.dev',
            'user' => Auth::user(),
            'pages' => $this->pages->allForAdmin(),
        ]);
    }

    public function create(): void
    {
        View::render('admin/page-form', [
            'title' => 'Nova página | Admin paulorb.dev',
            'user' => Auth::user(),
            'page' => ['id' => null, 'title' => '', 'slug' => '', 'content' => '', 'status' => 'draft'],
            'isNew' => true,
        ]);
    }

    public function store(): void
    {
        $id = $this->pages->create($this->pageData());
        header('Location: /admin/paginas/' . $id . '/edit?saved=1');
    }

    public function edit(string $id): void
    {
        $page = $this->pages->findByIdForAdmin((int) $id);

        if ($page === null) {
            ErrorPage::notFound();
            return;
        }

        View::render('admin/page-form', [
            'title' => 'Editar página | Admin paulorb.dev',
            'user' => Auth::user(),
            'page' => $page,
            'isNew' => false,
            'success' => ($_GET['saved'] ?? '') === '1',
        ]);
    }

    public function update(string $id): void
    {
        $pageId = (int) $id;

        if ($this->pages->findByIdForAdmin($pageId) === null) {
            ErrorPage::notFound();
            return;
        }

        $this->pages->update($pageId, $this->pageData());
        header('Location: /admin/paginas/' . $pageId . '/edit?saved=1');
    }

    public function delete(string $id): void
    {
        $this->pages->delete((int) $id);
        header('Location: /admin/paginas');
    }

    private function pageData(): array
    {
        return [
            'title' => trim($_POST['title'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'status' => trim($_POST['status'] ?? 'draft'),
        ];
    }
}
