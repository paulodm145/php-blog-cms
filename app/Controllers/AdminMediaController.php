<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Repositories\MediaRepository;

class AdminMediaController
{
    private const IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    private const FILE_MIMES = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/zip' => 'zip',
        'text/plain' => 'txt',
        // Aliases de CSV: o mime real detectado varia por sistema/versao
        // do libmagic (a maioria bate como text/plain, mas alguns
        // ambientes reportam um desses).
        'application/csv' => 'csv',
        'text/x-csv' => 'csv',
        'text/csv' => 'csv',
    ];

    private const IMAGE_MAX_SIZE = 5242880;

    private const FILE_MAX_SIZE = 15728640;

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
        $kind = null;
        $extension = null;

        if (isset(self::IMAGE_MIMES[$mimeType])) {
            $kind = 'image';
            $extension = self::IMAGE_MIMES[$mimeType];

            if ((int) $file['size'] > self::IMAGE_MAX_SIZE) {
                throw new \RuntimeException('Imagem maior que 5 MB');
            }
        } elseif (isset(self::FILE_MIMES[$mimeType])) {
            $kind = 'file';
            $extension = self::FILE_MIMES[$mimeType];

            // mime_content_type() nao distingue .txt de .csv (a imensa
            // maioria dos CSVs reais bate como text/plain nessa deteccao
            // por magic bytes) — se o arquivo original terminava em .csv,
            // preserva essa extensao em vez de forcar .txt.
            if ($mimeType === 'text/plain') {
                $originalExtension = strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION));

                if ($originalExtension === 'csv') {
                    $extension = 'csv';
                }
            }

            if ((int) $file['size'] > self::FILE_MAX_SIZE) {
                throw new \RuntimeException('Arquivo maior que 15 MB');
            }
        } else {
            throw new \RuntimeException('Tipo de arquivo não suportado');
        }

        $year = date('Y');
        $month = date('m');
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/media/' . $year . '/' . $month;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $baseName = $this->slugify(pathinfo((string) $file['name'], PATHINFO_FILENAME));
        $fileName = $baseName . '-' . bin2hex(random_bytes(3)) . '.' . $extension;

        if (!move_uploaded_file((string) $file['tmp_name'], $uploadDir . '/' . $fileName)) {
            throw new \RuntimeException('Não foi possível salvar o arquivo');
        }

        $absolutePath = $uploadDir . '/' . $fileName;
        $path = '/uploads/media/' . $year . '/' . $month . '/' . $fileName;
        $width = null;
        $height = null;

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
        }

        try {
            $id = $this->media->create([
                'file_name' => $fileName,
                'original_name' => (string) $file['name'],
                'path' => $path,
                'mime_type' => $mimeType,
                'kind' => $kind,
                'size' => (int) $file['size'],
                'width' => $width,
                'height' => $height,
                'uploaded_by' => Auth::user()['id'] ?? null,
            ]);
        } catch (\Throwable $exception) {
            unlink($absolutePath);
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

    private function slugify(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $value = strtolower((string) $value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim((string) $value, '-');

        return $value !== '' ? $value : 'arquivo';
    }
}
