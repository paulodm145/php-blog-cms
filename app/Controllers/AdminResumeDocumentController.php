<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ErrorPage;
use App\Core\FileDownload;
use App\Core\UploadValidator;
use App\Core\View;
use App\Repositories\ResumeExperienceDocumentRepository;
use App\Repositories\ResumeExperienceRepository;

class AdminResumeDocumentController
{
    private $documents;
    private $experience;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->documents = new ResumeExperienceDocumentRepository();
        $this->experience = new ResumeExperienceRepository();
    }

    public function index(): void
    {
        View::render('admin/resume-documents', [
            'title' => 'Documentos | Admin paulorb.dev',
            'user' => Auth::user(),
            'groups' => $this->documents->allGroupedByExperience(),
        ]);
    }

    public function upload(): void
    {
        header('Content-Type: application/json');

        $experienceId = (int) ($_POST['experience_id'] ?? 0);

        if ($experienceId <= 0 || $this->experience->find($experienceId) === null) {
            http_response_code(422);
            echo json_encode(['items' => [], 'errors' => [['name' => '', 'error' => 'Experiência inválida']]]);
            return;
        }

        $files = UploadValidator::normalizeUploadedFiles($_FILES['files'] ?? null);
        $items = [];
        $errors = [];

        foreach ($files as $file) {
            try {
                $items[] = $this->storeUploadedFile($experienceId, $file);
            } catch (\RuntimeException $exception) {
                $errors[] = ['name' => $file['name'], 'error' => $exception->getMessage()];
            }
        }

        echo json_encode(['items' => $items, 'errors' => $errors]);
    }

    public function updateCaption(string $id): void
    {
        header('Content-Type: application/json');

        $document = $this->documents->findById((int) $id);

        if ($document === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Documento não encontrado']);
            return;
        }

        $this->documents->updateCaption((int) $id, trim((string) ($_POST['caption'] ?? '')));

        echo json_encode(['ok' => true]);
    }

    public function delete(string $id): void
    {
        header('Content-Type: application/json');

        $document = $this->documents->findById((int) $id);

        if ($document !== null) {
            $absolutePath = dirname(__DIR__, 2) . $document['path'];

            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }

            $this->documents->softDelete((int) $id);
        }

        echo json_encode(['ok' => true]);
    }

    public function download(string $id): void
    {
        $document = $this->documents->findById((int) $id);

        if ($document === null) {
            ErrorPage::notFound();
            return;
        }

        $absolutePath = dirname(__DIR__, 2) . $document['path'];

        if (!is_file($absolutePath)) {
            ErrorPage::notFound();
            return;
        }

        FileDownload::stream($absolutePath, $document['mime_type'], $document['original_name']);
    }

    private function storeUploadedFile(int $experienceId, array $file): array
    {
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Falha no envio do arquivo');
        }

        $mimeType = (string) mime_content_type((string) $file['tmp_name']);
        $classification = UploadValidator::classify($mimeType, (string) $file['name'], (int) $file['size']);
        $fileName = UploadValidator::generateFileName((string) $file['name'], $classification['extension']);

        $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/resume-documents/' . $experienceId;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file((string) $file['tmp_name'], $uploadDir . '/' . $fileName)) {
            throw new \RuntimeException('Não foi possível salvar o arquivo');
        }

        $path = '/storage/uploads/resume-documents/' . $experienceId . '/' . $fileName;

        try {
            $id = $this->documents->create([
                'experience_id' => $experienceId,
                'file_name' => $fileName,
                'original_name' => (string) $file['name'],
                'path' => $path,
                'mime_type' => $mimeType,
                'size' => (int) $file['size'],
                'uploaded_by' => Auth::user()['id'] ?? null,
            ]);
        } catch (\Throwable $exception) {
            unlink($uploadDir . '/' . $fileName);
            throw new \RuntimeException('Não foi possível salvar o arquivo');
        }

        return [
            'id' => $id,
            'original_name' => (string) $file['name'],
            'mime_type' => $mimeType,
            'size' => (int) $file['size'],
        ];
    }
}
