<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ImageThumbnail;
use App\Core\View;
use App\Repositories\MediaRepository;

class AdminMediaController
{
    private $media;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->media = new MediaRepository();
    }

    public function index(): void
    {
        $currentPage = max(1, (int) ($_GET['page'] ?? 1));
        $search = trim($_GET['q'] ?? '');
        $kind = $this->normalizeKind($_GET['kind'] ?? '');
        $view = $this->normalizeView($_GET['view'] ?? '');
        $perPage = 24;
        $totalMedia = $this->media->countForAdmin($search, $kind);

        $data = [
            'title' => 'Mídia | Admin paulorb.dev',
            'user' => Auth::user(),
            'search' => $search,
            'kind' => $kind,
            'view' => $view,
            'totalMedia' => $totalMedia,
        ];

        if ($view === 'grouped') {
            // Sem paginacao nesse modo: mostra tudo que bateu com a busca/
            // filtro, organizado por mes de envio.
            $data['groups'] = $this->media->groupedByMonthForAdmin($search, $kind);
            $data['mediaItems'] = [];
            $data['currentPage'] = 1;
            $data['totalPages'] = 1;
        } else {
            $totalPages = max(1, (int) ceil($totalMedia / $perPage));

            if ($currentPage > $totalPages) {
                $currentPage = $totalPages;
            }

            $data['groups'] = [];
            $data['mediaItems'] = $this->media->paginateForAdmin($currentPage, $perPage, $search, $kind);
            $data['currentPage'] = $currentPage;
            $data['totalPages'] = $totalPages;
        }

        if (($_GET['ajax'] ?? '') === '1') {
            View::render('admin/partials/media-grid', $data);
            return;
        }

        View::render('admin/media', $data);
    }

    public function upload(): void
    {
        header('Content-Type: application/json');

        $files = $this->normalizeUploadedFiles($_FILES['files'] ?? null);
        $items = [];
        $errors = [];

        foreach ($files as $file) {
            try {
                $items[] = $this->storeUploadedFile($file);
            } catch (\RuntimeException $exception) {
                $errors[] = [
                    'name' => $file['name'],
                    'error' => $exception->getMessage(),
                ];
            }
        }

        echo json_encode(['items' => $items, 'errors' => $errors]);
    }

    public function updateMeta(string $id): void
    {
        header('Content-Type: application/json');

        $media = $this->media->findByIdForAdmin((int) $id);

        if ($media === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Arquivo não encontrado']);
            return;
        }

        $this->media->updateMeta(
            (int) $id,
            trim($_POST['title'] ?? ''),
            trim($_POST['alt_text'] ?? '')
        );

        echo json_encode(['ok' => true]);
    }

    public function delete(string $id): void
    {
        header('Content-Type: application/json');

        $media = $this->media->findByIdForAdmin((int) $id);

        if ($media !== null) {
            $absolutePath = dirname(__DIR__, 2) . '/public' . $media['path'];

            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }

            if (!empty($media['thumbnail_path'])) {
                $thumbnailAbsolutePath = dirname(__DIR__, 2) . '/public' . $media['thumbnail_path'];

                if (is_file($thumbnailAbsolutePath)) {
                    unlink($thumbnailAbsolutePath);
                }
            }

            $this->media->softDeleteForAdmin((int) $id);
        }

        echo json_encode(['ok' => true]);
    }

    /**
     * $_FILES['files'] chega no formato "invertido" do PHP pra inputs com
     * `multiple` (name/tmp_name/error/size viram arrays paralelos). Aqui
     * viram uma lista de arrays normais, um por arquivo.
     */
    private function normalizeUploadedFiles($filesField): array
    {
        if (!is_array($filesField) || !isset($filesField['name'])) {
            return [];
        }

        if (!is_array($filesField['name'])) {
            return [$filesField];
        }

        $normalized = [];

        foreach ($filesField['name'] as $index => $name) {
            if (($filesField['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'type' => $filesField['type'][$index] ?? '',
                'tmp_name' => $filesField['tmp_name'][$index] ?? '',
                'error' => $filesField['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $filesField['size'][$index] ?? 0,
            ];
        }

        return $normalized;
    }

    private function storeUploadedFile(array $file): array
    {
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Falha no envio do arquivo');
        }

        $mimeType = (string) mime_content_type((string) $file['tmp_name']);
        $classification = \App\Core\UploadValidator::classify($mimeType, (string) $file['name'], (int) $file['size']);
        $kind = $classification['kind'];
        $extension = $classification['extension'];

        $year = date('Y');
        $month = date('m');
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/media/' . $year . '/' . $month;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = \App\Core\UploadValidator::generateFileName((string) $file['name'], $extension);

        if (!move_uploaded_file((string) $file['tmp_name'], $uploadDir . '/' . $fileName)) {
            throw new \RuntimeException('Não foi possível salvar o arquivo');
        }

        $absolutePath = $uploadDir . '/' . $fileName;
        $path = '/uploads/media/' . $year . '/' . $month . '/' . $fileName;
        $width = null;
        $height = null;
        $thumbnailAbsolutePath = null;
        $thumbnailPath = null;

        if ($kind === 'image') {
            $dimensions = @getimagesize($absolutePath);

            // getimagesize() confia nos metadados do arquivo; um upload
            // corrompido (mas com magic bytes validos, entao aceito pelo
            // mime_content_type) pode devolver dimensoes absurdas que nao
            // cabem em SMALLINT UNSIGNED (0-65535). Nesse caso, guarda sem
            // largura/altura em vez de estourar a constraint do banco.
            if ($dimensions !== false && $dimensions[0] > 0 && $dimensions[0] <= 65535
                && $dimensions[1] > 0 && $dimensions[1] <= 65535) {
                $width = $dimensions[0];
                $height = $dimensions[1];
            }

            $thumbnailAbsolutePath = ImageThumbnail::generate($absolutePath, $mimeType);

            if ($thumbnailAbsolutePath !== null) {
                $thumbnailPath = '/uploads/media/' . $year . '/' . $month . '/' . basename($thumbnailAbsolutePath);
            }
        }

        try {
            $id = $this->media->create([
                'file_name' => $fileName,
                'original_name' => (string) $file['name'],
                'path' => $path,
                'thumbnail_path' => $thumbnailPath,
                'mime_type' => $mimeType,
                'kind' => $kind,
                'size' => (int) $file['size'],
                'width' => $width,
                'height' => $height,
                'uploaded_by' => Auth::user()['id'] ?? null,
            ]);
        } catch (\Throwable $exception) {
            unlink($absolutePath);

            if ($thumbnailAbsolutePath !== null && is_file($thumbnailAbsolutePath)) {
                unlink($thumbnailAbsolutePath);
            }

            throw new \RuntimeException('Não foi possível salvar o arquivo');
        }

        return [
            'id' => $id,
            'url' => $path,
            'kind' => $kind,
            'original_name' => (string) $file['name'],
            'title' => '',
            'alt_text' => '',
        ];
    }

    private function normalizeKind(string $kind): string
    {
        return in_array($kind, ['image', 'file'], true) ? $kind : '';
    }

    private function normalizeView(string $view): string
    {
        return in_array($view, ['list', 'grouped'], true) ? $view : 'grid';
    }
}
