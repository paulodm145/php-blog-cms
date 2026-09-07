<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Repositories\CommentRepository;

class AdminCommentController
{
    private $comments;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->comments = new CommentRepository();
    }

    public function index(): void
    {
        $status = trim((string) ($_GET['status'] ?? 'pending'));
        $status = in_array($status, ['pending', 'approved', 'spam', ''], true) ? $status : 'pending';

        View::render('admin/comments', [
            'title' => 'Comentários | Admin paulorb.dev',
            'user' => Auth::user(),
            'comments' => $this->comments->allForAdmin($status),
            'status' => $status,
            'pendingCount' => $this->comments->countPending(),
        ]);
    }

    public function approve(string $id): void
    {
        $this->comments->updateStatus((int) $id, 'approved');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/comentarios'));
    }

    public function spam(string $id): void
    {
        $this->comments->updateStatus((int) $id, 'spam');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/comentarios'));
    }

    public function delete(string $id): void
    {
        $this->comments->delete((int) $id);
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/comentarios'));
    }
}
