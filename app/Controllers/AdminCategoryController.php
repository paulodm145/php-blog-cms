<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ErrorPage;
use App\Core\View;
use App\Repositories\CategoryRepository;

class AdminCategoryController
{
    private $categories;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->categories = new CategoryRepository();
    }

    public function index(): void
    {
        View::render('admin/categories', [
            'title' => 'Categorias | Admin paulorb.dev',
            'user' => Auth::user(),
            'categories' => $this->categories->all(),
        ]);
    }

    public function create(): void
    {
        View::render('admin/category-form', [
            'title' => 'Nova categoria | Admin paulorb.dev',
            'user' => Auth::user(),
            'category' => ['id' => null, 'name' => '', 'slug' => ''],
            'isNew' => true,
        ]);
    }

    public function store(): void
    {
        $this->categories->create($_POST);
        header('Location: /admin/categories');
    }

    public function edit(string $id): void
    {
        $category = $this->categories->find((int) $id);

        if ($category === null) {
            ErrorPage::notFound();
            return;
        }

        View::render('admin/category-form', [
            'title' => 'Editar categoria | Admin paulorb.dev',
            'user' => Auth::user(),
            'category' => $category,
            'isNew' => false,
        ]);
    }

    public function update(string $id): void
    {
        $this->categories->update((int) $id, $_POST);
        header('Location: /admin/categories');
    }

    public function delete(string $id): void
    {
        $this->categories->delete((int) $id);
        header('Location: /admin/categories');
    }
}
