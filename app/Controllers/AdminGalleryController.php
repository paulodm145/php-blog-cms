<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ErrorPage;
use App\Core\View;
use App\Repositories\GalleryRepository;

class AdminGalleryController
{
    private $galleries;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->galleries = new GalleryRepository();
    }

    public function index(): void
    {
        $currentPage = max(1, (int) ($_GET['page'] ?? 1));
        $search = trim($_GET['q'] ?? '');
        $perPage = 10;
        $totalGalleries = $this->galleries->countForAdmin($search);
        $totalPages = max(1, (int) ceil($totalGalleries / $perPage));

        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        View::render('admin/galleries', [
            'title' => 'Galerias | Admin paulorb.dev',
            'user' => Auth::user(),
            'galleries' => $this->galleries->paginateForAdmin($currentPage, $perPage, $search),
            'search' => $search,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalGalleries' => $totalGalleries,
        ]);
    }

    public function create(): void
    {
        $kind = ($_GET['kind'] ?? '') === 'video' ? 'video' : 'photo';

        View::render('admin/gallery-form', [
            'title' => ($kind === 'video' ? 'Nova galeria de vídeos' : 'Nova galeria de fotos') . ' | Admin paulorb.dev',
            'user' => Auth::user(),
            'item' => [
                'id' => null,
                'name' => '',
                'slug' => '',
                'kind' => $kind,
                'photos' => [],
                'videos' => [],
            ],
            'isNew' => true,
        ]);
    }

    public function store(): void
    {
        $id = $this->galleries->create($_POST);
        header('Location: /admin/galerias/' . $id . '/edit?saved=1');
    }

    public function edit(string $id): void
    {
        $item = $this->galleries->findByIdForAdmin((int) $id);

        if ($item === null) {
            ErrorPage::notFound();
            return;
        }

        View::render('admin/gallery-form', [
            'title' => 'Editar galeria | Admin paulorb.dev',
            'user' => Auth::user(),
            'item' => $item,
            'isNew' => false,
            'success' => ($_GET['saved'] ?? '') === '1',
        ]);
    }

    public function update(string $id): void
    {
        $galleryId = (int) $id;
        $item = $this->galleries->findByIdForAdmin($galleryId);

        if ($item === null) {
            ErrorPage::notFound();
            return;
        }

        $this->galleries->update($galleryId, $_POST);
        header('Location: /admin/galerias/' . $galleryId . '/edit?saved=1');
    }

    public function delete(string $id): void
    {
        $this->galleries->delete((int) $id);
        header('Location: /admin/galerias');
    }

    public function picker(): void
    {
        header('Content-Type: text/html; charset=utf-8');

        View::render('admin/partials/gallery-picker', [
            'galleries' => $this->galleries->allForPicker(),
        ]);
    }
}
