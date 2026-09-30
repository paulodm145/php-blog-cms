<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Env;
use App\Core\ErrorPage;
use App\Core\FileDownload;
use App\Core\UploadValidator;
use App\Core\View;
use App\Repositories\ResumeDocumentFolderRepository;
use App\Repositories\ResumeDocumentShareRepository;
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
        $folders = new ResumeDocumentFolderRepository();
        $currentFolderId = $this->resolveFolderId($_GET['folder_id'] ?? null, $folders);

        View::render('admin/resume-documents', [
            'title' => 'Documentos | Admin paulorb.dev',
            'user' => Auth::user(),
            'groups' => $this->documents->allGroupedByExperience(),
            'shares' => (new ResumeDocumentShareRepository())->listAllForAdmin(),
            'appUrl' => rtrim((string) Env::get('APP_URL', ''), '/'),
            'currentFolderId' => $currentFolderId,
            'folderBreadcrumb' => $currentFolderId !== null ? $folders->breadcrumb($currentFolderId) : [],
            'folderChildren' => $folders->children($currentFolderId),
            'folderDocuments' => $this->documents->listByFolder($currentFolderId),
        ]);
    }

    private function resolveFolderId($raw, ResumeDocumentFolderRepository $folders): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $id = (int) $raw;

        return $folders->find($id) !== null ? $id : null;
    }

    public function upload(): void
    {
        header('Content-Type: application/json');

        $owner = $this->resolveUploadOwner();

        if ($owner === null) {
            http_response_code(422);
            echo json_encode(['items' => [], 'errors' => [['name' => '', 'error' => 'Destino inválido']]]);
            return;
        }

        $files = UploadValidator::normalizeUploadedFiles($_FILES['files'] ?? null);
        $items = [];
        $errors = [];

        foreach ($files as $file) {
            try {
                $items[] = $this->storeUploadedFile($owner, $file);
            } catch (\RuntimeException $exception) {
                $errors[] = ['name' => $file['name'], 'error' => $exception->getMessage()];
            }
        }

        echo json_encode(['items' => $items, 'errors' => $errors]);
    }

    /**
     * @return array{experienceId: ?int, folderId: ?int, directory: string}|null
     */
    private function resolveUploadOwner(): ?array
    {
        if (isset($_POST['experience_id'])) {
            $experienceId = (int) $_POST['experience_id'];

            if ($experienceId <= 0 || $this->experience->find($experienceId) === null) {
                return null;
            }

            return ['experienceId' => $experienceId, 'folderId' => null, 'directory' => 'experiencia-' . $experienceId];
        }

        if (isset($_POST['folder_id'])) {
            $folderIdRaw = (string) $_POST['folder_id'];

            if ($folderIdRaw === '') {
                return ['experienceId' => null, 'folderId' => null, 'directory' => 'pasta-raiz'];
            }

            $folderId = (int) $folderIdRaw;

            if ((new ResumeDocumentFolderRepository())->find($folderId) === null) {
                return null;
            }

            return ['experienceId' => null, 'folderId' => $folderId, 'directory' => 'pasta-' . $folderId];
        }

        return null;
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
                // rmdir() recusa sozinho se a pasta ainda tiver outros
                // documentos daquela experiencia — sem checagem extra de
                // "esta vazia?" antes.
                @rmdir(dirname($absolutePath));
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

    public function moveForm(string $id): void
    {
        $document = $this->documents->findById((int) $id);

        if ($document === null) {
            ErrorPage::notFound();
            return;
        }

        View::render('admin/resume-document-move-form', [
            'title' => 'Mover documento | Admin paulorb.dev',
            'user' => Auth::user(),
            'subjectLabel' => $document['original_name'],
            'formAction' => '/admin/curriculo/documentos/' . (int) $id . '/mover',
            'backHref' => '/admin/curriculo/documentos',
            'options' => $this->buildDocumentDestinationOptions(),
        ]);
    }

    public function move(string $id): void
    {
        $document = $this->documents->findById((int) $id);

        if ($document === null) {
            ErrorPage::notFound();
            return;
        }

        $parts = explode(':', (string) ($_POST['destination'] ?? ''), 2);
        $destinationType = $parts[0] ?? '';
        $destinationIdRaw = $parts[1] ?? '';

        $experienceId = null;
        $folderId = null;

        if ($destinationType === 'experience') {
            $experienceId = (int) $destinationIdRaw;

            if ($experienceId <= 0 || $this->experience->find($experienceId) === null) {
                ErrorPage::notFound();
                return;
            }

            $directory = 'experiencia-' . $experienceId;
        } elseif ($destinationType === 'folder') {
            if ($destinationIdRaw === '') {
                $directory = 'pasta-raiz';
            } else {
                $folderId = (int) $destinationIdRaw;

                if ((new ResumeDocumentFolderRepository())->find($folderId) === null) {
                    ErrorPage::notFound();
                    return;
                }

                $directory = 'pasta-' . $folderId;
            }
        } else {
            ErrorPage::notFound();
            return;
        }

        $oldAbsolutePath = dirname(__DIR__, 2) . $document['path'];
        $newDir = dirname(__DIR__, 2) . '/storage/uploads/resume-documents/' . $directory;

        if (!is_dir($newDir)) {
            mkdir($newDir, 0755, true);
        }

        $newAbsolutePath = $newDir . '/' . $document['file_name'];

        if (!rename($oldAbsolutePath, $newAbsolutePath)) {
            ErrorPage::serverError();
            return;
        }

        $newPath = '/storage/uploads/resume-documents/' . $directory . '/' . $document['file_name'];
        $this->documents->move((int) $id, $experienceId, $folderId, $newPath);

        header('Location: /admin/curriculo/documentos');
    }

    private function buildDocumentDestinationOptions(): array
    {
        $options = [];

        foreach ($this->experience->all() as $experience) {
            $options[] = [
                'group' => 'Experiências',
                'value' => 'experience:' . $experience['id'],
                'label' => $experience['role'] . ' — ' . $experience['company'],
            ];
        }

        $options[] = ['group' => 'Pastas', 'value' => 'folder:', 'label' => 'Raiz das pastas'];

        foreach ((new ResumeDocumentFolderRepository())->all() as $folder) {
            $options[] = [
                'group' => 'Pastas',
                'value' => 'folder:' . $folder['id'],
                'label' => $folder['name'],
            ];
        }

        return $options;
    }

    private function storeUploadedFile(array $owner, array $file): array
    {
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Falha no envio do arquivo');
        }

        $mimeType = (string) mime_content_type((string) $file['tmp_name']);
        $classification = UploadValidator::classify($mimeType, (string) $file['name'], (int) $file['size']);
        $fileName = UploadValidator::generateFileName((string) $file['name'], $classification['extension']);

        $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/resume-documents/' . $owner['directory'];

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file((string) $file['tmp_name'], $uploadDir . '/' . $fileName)) {
            throw new \RuntimeException('Não foi possível salvar o arquivo');
        }

        $path = '/storage/uploads/resume-documents/' . $owner['directory'] . '/' . $fileName;

        try {
            $id = $this->documents->create([
                'experience_id' => $owner['experienceId'],
                'folder_id' => $owner['folderId'],
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
