<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Text;
use App\Core\View;
use App\Repositories\ResumeCertificationRepository;
use App\Repositories\ResumeCourseRepository;
use App\Repositories\ResumeEducationRepository;
use App\Repositories\ResumeExperienceRepository;
use App\Repositories\SettingRepository;

class AdminResumeController
{
    private $settings;
    private $experience;
    private $education;
    private $courses;
    private $certifications;
    private $lastUploadedPath = '';

    public function __construct()
    {
        Auth::requireAdmin();
        $this->settings = new SettingRepository();
        $this->experience = new ResumeExperienceRepository();
        $this->education = new ResumeEducationRepository();
        $this->courses = new ResumeCourseRepository();
        $this->certifications = new ResumeCertificationRepository();
    }

    public function edit(): void
    {
        $courseSearch = trim($_GET['curso_q'] ?? '');
        $courseSort = trim($_GET['curso_sort'] ?? '');
        $courseDir = ($_GET['curso_dir'] ?? '') === 'desc' ? 'desc' : 'asc';
        $coursePage = max(1, (int) ($_GET['curso_page'] ?? 1));
        $coursePerPage = 10;
        $courseTotal = $this->courses->countForAdmin($courseSearch);
        $courseTotalPages = max(1, (int) ceil($courseTotal / $coursePerPage));

        if ($coursePage > $courseTotalPages) {
            $coursePage = $courseTotalPages;
        }

        View::render('admin/resume', [
            'title' => 'Currículo | Admin paulorb.dev',
            'user' => Auth::user(),
            'settings' => $this->settings->all(),
            'experience' => $this->experience->all(),
            'education' => $this->education->all(),
            'courses' => $this->courses->paginateForAdmin($coursePage, $coursePerPage, $courseSearch, $courseSort, $courseDir),
            'courseSearch' => $courseSearch,
            'courseSort' => $courseSort,
            'courseDir' => $courseDir,
            'coursePage' => $coursePage,
            'courseTotalPages' => $courseTotalPages,
            'courseTotal' => $courseTotal,
            'courseTotalDuration' => Text::duration($this->courses->totalDurationMinutes()),
            'certifications' => $this->certifications->all(),
            'success' => ($_GET['saved'] ?? '') === '1',
        ]);
    }

    public function update(): void
    {
        $this->settings->updateResume($_POST);
        header('Location: /admin/curriculo?saved=1');
    }

    public function uploadPhoto(): void
    {
        $error = $this->handleUpload('photo', 'public/uploads/resume', [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ], 3145728, 'resume');

        if ($error === null) {
            $this->settings->setResumePhoto($this->lastUploadedPath);
        }

        header('Location: /admin/curriculo?saved=1' . ($error !== null ? '&photo_error=' . rawurlencode($error) : ''));
    }

    private function handleUpload(string $fieldName, string $relativeUploadDir, array $allowedMimes, int $maxBytes, string $filePrefix): ?string
    {
        $file = $_FILES[$fieldName] ?? null;

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return 'Selecione um arquivo.';
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Falha no envio do arquivo.';
        }

        if ((int) $file['size'] > $maxBytes) {
            return 'Arquivo maior que o permitido (' . round($maxBytes / 1048576, 1) . ' MB).';
        }

        $mimeType = (string) mime_content_type((string) $file['tmp_name']);

        if (!isset($allowedMimes[$mimeType])) {
            return 'Tipo de arquivo não permitido.';
        }

        $uploadPath = dirname(__DIR__, 2) . '/' . $relativeUploadDir;

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $fileName = $filePrefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMimes[$mimeType];

        if (!move_uploaded_file((string) $file['tmp_name'], $uploadPath . '/' . $fileName)) {
            return 'Não foi possível salvar o arquivo.';
        }

        $this->lastUploadedPath = '/' . ltrim(substr($relativeUploadDir, strlen('public/')), '/') . '/' . $fileName;

        return null;
    }
}
