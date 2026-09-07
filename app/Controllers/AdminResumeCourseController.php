<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ErrorPage;
use App\Core\View;
use App\Repositories\ResumeCourseRepository;

class AdminResumeCourseController
{
    private $courses;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->courses = new ResumeCourseRepository();
    }

    public function create(): void
    {
        View::render('admin/resume-course-form', [
            'title' => 'Novo curso | Admin paulorb.dev',
            'user' => Auth::user(),
            'item' => [
                'id' => null,
                'name' => '',
                'institution' => '',
                'start_date' => '',
                'end_date' => '',
                'hours' => '',
                'minutes' => '',
                'certificate_media_id' => null,
                'certificate_media_url' => '',
                'certificate_media_name' => '',
                'certificate_url' => '',
            ],
            'isNew' => true,
        ]);
    }

    public function store(): void
    {
        $this->courses->create($_POST);
        header('Location: /admin/curriculo?saved=1#tab-cursos');
    }

    public function edit(string $id): void
    {
        $item = $this->courses->find((int) $id);

        if ($item === null) {
            ErrorPage::notFound();
            return;
        }

        View::render('admin/resume-course-form', [
            'title' => 'Editar curso | Admin paulorb.dev',
            'user' => Auth::user(),
            'item' => $item,
            'isNew' => false,
        ]);
    }

    public function update(string $id): void
    {
        $this->courses->update((int) $id, $_POST);
        header('Location: /admin/curriculo?saved=1#tab-cursos');
    }

    public function delete(string $id): void
    {
        $this->courses->delete((int) $id);
        header('Location: /admin/curriculo?saved=1#tab-cursos');
    }

    public function visibility(string $id): void
    {
        header('Content-Type: application/json');

        $courseId = (int) $id;
        $item = $this->courses->find($courseId);

        if ($item === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Curso não encontrado']);
            return;
        }

        $visible = ($_POST['visible'] ?? '') === '1';
        $this->courses->setVisible($courseId, $visible);

        echo json_encode(['ok' => true, 'visible' => $visible]);
    }
}
