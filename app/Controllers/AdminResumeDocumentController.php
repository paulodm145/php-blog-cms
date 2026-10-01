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
    private $folders;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->documents = new ResumeExperienceDocumentRepository();
        $this->experience = new ResumeExperienceRepository();
        $this->folders = new ResumeDocumentFolderRepository();
    }

    public function index(): void
    {
        $currentFolderId = $this->folders->resolveId($_GET['folder_id'] ?? null);

        View::render('admin/resume-documents', [
            'title' => 'Documentos | Admin paulorb.dev',
            'user' => Auth::user(),
            'groups' => $this->documents->allGroupedByExperience(),
            'shares' => (new ResumeDocumentShareRepository())->listAllForAdmin(),
            'appUrl' => rtrim((string) Env::get('APP_URL', ''), '/'),
            'currentFolderId' => $currentFolderId,
            'folderBreadcrumb' => $currentFolderId !== null ? $this->folders->breadcrumb($currentFolderId) : [],
            'folderChildren' => $this->folders->children($currentFolderId),
            'folderDocuments' => $this->documents->listByFolder($currentFolderId),
            'allFolders' => $this->folders->all(),
        ]);
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

            if ($this->folders->find($folderId) === null) {
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

    /**
     * So PDF (por enquanto): abrir um binario arbitrario inline no navegador
     * nao tem visualizador nenhum do lado do cliente e so confundiria.
     */
    public function view(string $id): void
    {
        $document = $this->documents->findById((int) $id);

        if ($document === null || $document['mime_type'] !== 'application/pdf') {
            ErrorPage::notFound();
            return;
        }

        $absolutePath = dirname(__DIR__, 2) . $document['path'];

        if (!is_file($absolutePath)) {
            ErrorPage::notFound();
            return;
        }

        FileDownload::stream($absolutePath, $document['mime_type'], $document['original_name'], 'inline');
    }

    public function editTextForm(string $id): void
    {
        $document = $this->findEditableTextDocument($id);

        if ($document === null) {
            ErrorPage::notFound();
            return;
        }

        $absolutePath = dirname(__DIR__, 2) . $document['path'];
        $returnUrl = $this->sanitizeReturnUrl($_GET['return'] ?? null);

        View::render('admin/resume-document-edit-text-form', [
            'title' => 'Editar arquivo | Admin paulorb.dev',
            'user' => Auth::user(),
            'documentId' => (int) $document['id'],
            'originalName' => $document['original_name'],
            'content' => is_file($absolutePath) ? (string) file_get_contents($absolutePath) : '',
            'backHref' => $returnUrl,
            'returnUrl' => $returnUrl,
        ]);
    }

    public function editText(string $id): void
    {
        $document = $this->findEditableTextDocument($id);

        if ($document === null) {
            ErrorPage::notFound();
            return;
        }

        $absolutePath = dirname(__DIR__, 2) . $document['path'];
        $content = (string) ($_POST['content'] ?? '');

        if (file_put_contents($absolutePath, $content) === false) {
            ErrorPage::serverError();
            return;
        }

        $this->documents->updateSize((int) $document['id'], strlen($content));

        header('Location: ' . $this->sanitizeReturnUrl($_POST['return'] ?? null));
    }

    /**
     * So permite editar documentos "text/plain" (os criados via "Novo
     * arquivo") — abrir um PDF/imagem num <textarea> e regravar por cima
     * corromperia o arquivo original sem nenhum aviso.
     */
    private function findEditableTextDocument(string $id): ?array
    {
        $document = $this->documents->findById((int) $id);

        if ($document === null || $document['mime_type'] !== 'text/plain') {
            return null;
        }

        return $document;
    }

    public function moveForm(string $id): void
    {
        $document = $this->documents->findById((int) $id);

        if ($document === null) {
            ErrorPage::notFound();
            return;
        }

        $returnUrl = $this->sanitizeReturnUrl($_GET['return'] ?? null);

        View::render('admin/resume-document-move-form', [
            'title' => 'Mover documento | Admin paulorb.dev',
            'user' => Auth::user(),
            'subjectLabel' => $document['original_name'],
            'formAction' => '/admin/curriculo/documentos/' . (int) $id . '/mover',
            'backHref' => $returnUrl,
            'returnUrl' => $returnUrl,
            'options' => $this->buildDocumentDestinationOptions(),
        ]);
    }

    /**
     * So aceita um caminho relativo comecando com uma unica barra
     * (ex.: "/admin/curriculo/documentos?tab=pastas&folder_id=5") — nunca
     * uma URL absoluta nem "//host/..." (protocol-relative), que um
     * "return" adulterado poderia usar pra mandar o admin, depois de
     * mover um documento, pra fora do proprio site.
     */
    private function sanitizeReturnUrl($raw): string
    {
        $default = '/admin/curriculo/documentos';
        $value = (string) ($raw ?? '');

        if ($value === '' || $value[0] !== '/' || (isset($value[1]) && $value[1] === '/')) {
            return $default;
        }

        return $value;
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

                if ($this->folders->find($folderId) === null) {
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

        try {
            $this->documents->move((int) $id, $experienceId, $folderId, $newPath);
        } catch (\Throwable $exception) {
            // Desfaz o rename fisico: sem isso, uma falha aqui (ex.: o
            // destino foi excluido entre o find() acima e este UPDATE)
            // deixava o arquivo fisicamente movido mas o registro ainda
            // apontando pro caminho antigo — download() e o link publico
            // passavam a dar 404 pra sempre, sem nenhum jeito de recuperar
            // pela propria aplicacao.
            rename($newAbsolutePath, $oldAbsolutePath);
            ErrorPage::serverError();
            return;
        }

        // Mesmo padrao de delete(): rmdir() recusa sozinho se a pasta de
        // origem ainda tiver outros documentos, sem checagem extra antes.
        @rmdir(dirname($oldAbsolutePath));

        header('Location: ' . $this->sanitizeReturnUrl($_POST['return'] ?? null));
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

        foreach ($this->folders->all() as $folder) {
            $options[] = [
                'group' => 'Pastas',
                'value' => 'folder:' . $folder['id'],
                'label' => $this->folders->path((int) $folder['id']),
            ];
        }

        return $options;
    }

    public function createTextFileForm(): void
    {
        $folderId = $this->folders->resolveId($_GET['folder_id'] ?? null);

        View::render('admin/resume-document-text-file-form', [
            'title' => 'Novo arquivo de texto | Admin paulorb.dev',
            'user' => Auth::user(),
            'folderId' => $folderId,
            'backHref' => $this->folderTabUrl($folderId),
        ]);
    }

    public function createTextFile(): void
    {
        $folderId = $this->folders->resolveId($_POST['folder_id'] ?? null);
        $name = trim((string) ($_POST['name'] ?? ''));
        $createFormUrl = '/admin/curriculo/documentos/novo-arquivo?folder_id=' . ($folderId !== null ? $folderId : '');

        if ($name === '' || mb_strlen($name) > 160) {
            header('Location: ' . $createFormUrl . '&erro=' . urlencode('Informe um nome de arquivo com até 160 caracteres.'));
            return;
        }

        if (strtolower(substr($name, -4)) !== '.txt') {
            $name .= '.txt';
        }

        $directory = $folderId !== null ? 'pasta-' . $folderId : 'pasta-raiz';
        $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/resume-documents/' . $directory;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = UploadValidator::generateFileName($name, 'txt');
        $absolutePath = $uploadDir . '/' . $fileName;

        if (file_put_contents($absolutePath, '') === false) {
            ErrorPage::serverError();
            return;
        }

        $this->documents->create([
            'experience_id' => null,
            'folder_id' => $folderId,
            'file_name' => $fileName,
            'original_name' => $name,
            'path' => '/storage/uploads/resume-documents/' . $directory . '/' . $fileName,
            'mime_type' => 'text/plain',
            'size' => 0,
            'uploaded_by' => Auth::user()['id'] ?? null,
        ]);

        header('Location: ' . $this->folderTabUrl($folderId));
    }

    private function folderTabUrl(?int $folderId): string
    {
        return '/admin/curriculo/documentos?tab=pastas' . ($folderId !== null ? '&folder_id=' . $folderId : '');
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

    public function downloadZip(): void
    {
        $documentIds = array_map('intval', (array) ($_POST['document_ids'] ?? []));
        $folderIds = array_map('intval', (array) ($_POST['folder_ids'] ?? []));

        if (empty($documentIds) && empty($folderIds)) {
            ErrorPage::notFound();
            return;
        }

        $byId = [];

        foreach ($this->folders->all() as $folder) {
            $byId[(int) $folder['id']] = $folder;
        }

        // descendantIds() ja inclui a propria pasta pedida — $rootOfDescendant
        // guarda, pra cada pasta resolvida, qual foi a pasta MARCADA pelo
        // usuario que a trouxe pra selecao, pra calcular o caminho relativo
        // dentro do zip a partir dela (nao a partir da raiz da arvore inteira).
        $allFolderIds = [];
        $rootOfDescendant = [];

        foreach ($folderIds as $rootId) {
            if (!isset($byId[$rootId])) {
                continue;
            }

            foreach ($this->folders->descendantIds($rootId) as $descendantId) {
                $allFolderIds[$descendantId] = true;

                if (!isset($rootOfDescendant[$descendantId])) {
                    $rootOfDescendant[$descendantId] = $rootId;
                }
            }
        }

        $allFolderIds = array_keys($allFolderIds);
        $folderDocuments = empty($allFolderIds) ? [] : $this->documents->findByFolderIds($allFolderIds);
        $standaloneDocuments = empty($documentIds) ? [] : $this->documents->findManyByIds($documentIds);

        $zipPath = tempnam(sys_get_temp_dir(), 'docs-zip-');
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            ErrorPage::serverError();
            return;
        }

        // Garante que toda pasta selecionada vira um diretorio no zip mesmo
        // quando ela (ou uma subpasta dela) nao tem nenhum documento dentro —
        // sem isso, uma pasta vazia marcada simplesmente desaparecia do zip.
        foreach ($allFolderIds as $folderId) {
            $rootId = $rootOfDescendant[$folderId];
            $pathParts = $this->relativeFolderPath($folderId, $rootId, $byId);
            $zip->addEmptyDir(implode('/', $pathParts));
        }

        $usedEntryNames = [];
        $addedCount = 0;

        foreach ($folderDocuments as $document) {
            $folderId = (int) $document['folder_id'];
            $rootId = $rootOfDescendant[$folderId] ?? $folderId;
            $pathParts = $this->relativeFolderPath($folderId, $rootId, $byId);
            $directory = implode('/', $pathParts) . '/';

            if ($this->addDocumentToZip($zip, $document, $directory, $usedEntryNames)) {
                $addedCount++;
            }
        }

        foreach ($standaloneDocuments as $document) {
            if ((int) ($document['folder_id'] ?? 0) !== 0 && in_array((int) $document['folder_id'], $allFolderIds, true)) {
                // Ja foi incluido via pasta selecionada — evita duplicar.
                continue;
            }

            if ($this->addDocumentToZip($zip, $document, '', $usedEntryNames)) {
                $addedCount++;
            }
        }

        $zip->close();

        if ($addedCount === 0) {
            unlink($zipPath);
            ErrorPage::notFound();
            return;
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="documentos.zip"');
        header('Content-Length: ' . (string) filesize($zipPath));
        readfile($zipPath);
        unlink($zipPath);
    }

    private function addDocumentToZip(\ZipArchive $zip, array $document, string $directory, array &$usedEntryNames): bool
    {
        $absolutePath = dirname(__DIR__, 2) . $document['path'];

        if (!is_file($absolutePath)) {
            return false;
        }

        $entryName = $this->uniqueZipEntryName($directory . $document['original_name'], $usedEntryNames);

        return $zip->addFile($absolutePath, $entryName);
    }

    /**
     * "Financeiro/2026" — lista de nomes de pasta da raiz da SELECAO (nao da
     * raiz da arvore inteira) ate $folderId, pra cada documento/pasta cair no
     * subdiretorio certo dentro do zip.
     */
    private function relativeFolderPath(int $folderId, int $rootId, array $byId): array
    {
        $names = [];
        $currentId = $folderId;

        while ($currentId !== null && isset($byId[$currentId])) {
            array_unshift($names, $byId[$currentId]['name']);

            if ($currentId === $rootId) {
                break;
            }

            $currentId = $byId[$currentId]['parent_id'] !== null ? (int) $byId[$currentId]['parent_id'] : null;
        }

        return $names;
    }

    /**
     * Dois documentos com o mesmo original_name na mesma pasta sao permitidos
     * no banco (o nome unico de verdade e o file_name no disco) — sem isso o
     * segundo arquivo simplesmente sobrescreveria o primeiro dentro do zip.
     */
    private function uniqueZipEntryName(string $name, array &$usedEntryNames): string
    {
        if (!isset($usedEntryNames[$name])) {
            $usedEntryNames[$name] = true;
            return $name;
        }

        $extension = '';
        $base = $name;
        $dotPos = strrpos($name, '.');

        if ($dotPos !== false) {
            $extension = substr($name, $dotPos);
            $base = substr($name, 0, $dotPos);
        }

        $counter = 2;

        do {
            $candidate = $base . ' (' . $counter . ')' . $extension;
            $counter++;
        } while (isset($usedEntryNames[$candidate]));

        $usedEntryNames[$candidate] = true;

        return $candidate;
    }
}
