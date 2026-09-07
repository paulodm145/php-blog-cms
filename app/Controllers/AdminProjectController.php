<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ErrorPage;
use App\Core\View;
use App\Repositories\ProjectRepository;

class AdminProjectController
{
    private $projects;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->projects = new ProjectRepository();
    }

    public function index(): void
    {
        $currentPage = max(1, (int) ($_GET['page'] ?? 1));
        $search = trim($_GET['q'] ?? '');
        $perPage = 10;
        $totalProjects = $this->projects->countForAdmin($search);
        $totalPages = max(1, (int) ceil($totalProjects / $perPage));

        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        View::render('admin/projects', [
            'title' => 'Projetos | Admin paulorb.dev',
            'user' => Auth::user(),
            'projects' => $this->projects->paginateForAdmin($currentPage, $perPage, $search),
            'search' => $search,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalProjects' => $totalProjects,
        ]);
    }

    public function create(): void
    {
        View::render('admin/project-form', [
            'title' => 'Novo projeto | Admin paulorb.dev',
            'user' => Auth::user(),
            'item' => [
                'id' => null,
                'name' => '',
                'slug' => '',
                'tagline' => '',
                'content' => '',
                'cover_media_id' => null,
                'cover_url' => '',
                'cover_name' => '',
                'gallery' => [],
                'technologies' => '',
                'role' => '',
                'project_type' => '',
                'live_url' => '',
                'source_url' => '',
                'start_date' => '',
                'end_date' => '',
                'featured' => 0,
                'status' => 'draft',
            ],
            'isNew' => true,
        ]);
    }

    public function store(): void
    {
        $id = $this->projects->create($_POST);
        header('Location: /admin/projetos/' . $id . '/edit?saved=1');
    }

    public function edit(string $id): void
    {
        $item = $this->projects->findByIdForAdmin((int) $id);

        if ($item === null) {
            ErrorPage::notFound();
            return;
        }

        View::render('admin/project-form', [
            'title' => 'Editar projeto | Admin paulorb.dev',
            'user' => Auth::user(),
            'item' => $item,
            'isNew' => false,
            'success' => ($_GET['saved'] ?? '') === '1',
        ]);
    }

    public function update(string $id): void
    {
        $projectId = (int) $id;
        $item = $this->projects->findByIdForAdmin($projectId);

        if ($item === null) {
            ErrorPage::notFound();
            return;
        }

        $this->projects->update($projectId, $_POST);
        header('Location: /admin/projetos/' . $projectId . '/edit?saved=1');
    }

    public function delete(string $id): void
    {
        $this->projects->delete((int) $id);
        header('Location: /admin/projetos');
    }
}
