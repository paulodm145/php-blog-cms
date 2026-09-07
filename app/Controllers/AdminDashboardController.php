<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Migrator;
use App\Core\View;
use App\Database\Database;
use App\Repositories\CategoryRepository;
use App\Repositories\CommentRepository;
use App\Repositories\PageRepository;
use App\Repositories\PostRepository;

class AdminDashboardController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $posts = new PostRepository();
        $comments = new CommentRepository();
        $postCounts = $posts->countsByStatus();
        $pendingMigrationNames = [];

        try {
            $pendingMigrationNames = (new Migrator(new Database(), dirname(__DIR__, 2) . '/database/migrations'))->pending();
        } catch (\Throwable $exception) {
            $pendingMigrationNames = [];
        }

        View::render('admin/dashboard', [
            'title' => 'Painel | Admin paulorb.dev',
            'user' => Auth::user(),
            'postCounts' => $postCounts,
            'pageCount' => count((new PageRepository())->allForAdmin()),
            'categoryCount' => count((new CategoryRepository())->all()),
            'pendingCommentsCount' => $comments->countPending(),
            'pendingMigrationNames' => $pendingMigrationNames,
            'recentPosts' => $posts->recentForAdmin(5),
            'recentComments' => array_slice($comments->allForAdmin(''), 0, 5),
        ]);
    }
}
