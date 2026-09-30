<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ErrorPage;
use App\Core\View;
use App\Repositories\ResumeDocumentFolderRepository;
use App\Repositories\ResumeExperienceDocumentRepository;

class AdminResumeDocumentFolderController
{
    private const NAME_MAX_LENGTH = 160;

    private $folders;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->folders = new ResumeDocumentFolderRepository();
    }

    public function createForm(): void
    {
        $parentId = $this->folders->resolveId($_GET['parent_id'] ?? null);

        View::render('admin/resume-document-folder-form', [
            'title' => 'Nova pasta | Admin paulorb.dev',
            'user' => Auth::user(),
            'isNew' => true,
            'name' => '',
            'parentId' => $parentId,
            'formAction' => '/admin/curriculo/documentos/pastas',
            'backHref' => $this->folderUrl($parentId),
        ]);
    }

    public function store(): void
    {
        $parentId = $this->folders->resolveId($_POST['parent_id'] ?? null);
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            header(
                'Location: /admin/curriculo/documentos/pastas/criar?parent_id=' . ($parentId !== null ? $parentId : '')
                . '&erro=' . urlencode('Informe um nome de pasta com até ' . self::NAME_MAX_LENGTH . ' caracteres.')
            );
            return;
        }

        $this->folders->create($parentId, $name);

        header('Location: ' . $this->folderUrl($parentId));
    }

    public function renameForm(string $id): void
    {
        $folder = $this->folders->find((int) $id);

        if ($folder === null) {
            ErrorPage::notFound();
            return;
        }

        $parentId = $folder['parent_id'] !== null ? (int) $folder['parent_id'] : null;

        View::render('admin/resume-document-folder-form', [
            'title' => 'Renomear pasta | Admin paulorb.dev',
            'user' => Auth::user(),
            'isNew' => false,
            'name' => $folder['name'],
            'parentId' => $parentId,
            'formAction' => '/admin/curriculo/documentos/pastas/' . (int) $id . '/renomear',
            'backHref' => $this->folderUrl($parentId),
        ]);
    }

    public function rename(string $id): void
    {
        $folder = $this->folders->find((int) $id);

        if ($folder === null) {
            ErrorPage::notFound();
            return;
        }

        $parentId = $folder['parent_id'] !== null ? (int) $folder['parent_id'] : null;
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            header(
                'Location: /admin/curriculo/documentos/pastas/' . (int) $id . '/renomear?erro='
                . urlencode('Informe um nome de pasta com até ' . self::NAME_MAX_LENGTH . ' caracteres.')
            );
            return;
        }

        $this->folders->rename((int) $id, $name);

        // Volta pra pasta-mae (onde o card desta pasta esta listado), nao
        // pra dentro dela mesma — renomear nao é "entrar" na pasta.
        header('Location: ' . $this->folderUrl($parentId));
    }

    public function delete(string $id): void
    {
        $folderId = (int) $id;
        $folder = $this->folders->find($folderId);

        if ($folder === null) {
            ErrorPage::notFound();
            return;
        }

        $parentId = $folder['parent_id'] !== null ? (int) $folder['parent_id'] : null;
        $descendantIds = $this->folders->descendantIds($folderId);
        $paths = (new ResumeExperienceDocumentRepository())->pathsByFolderIds($descendantIds);

        $this->folders->deleteMany($descendantIds);

        foreach ($paths as $path) {
            $absolutePath = dirname(__DIR__, 2) . $path;

            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
        }

        foreach ($descendantIds as $descendantId) {
            @rmdir(dirname(__DIR__, 2) . '/storage/uploads/resume-documents/pasta-' . $descendantId);
        }

        header('Location: ' . $this->folderUrl($parentId));
    }

    public function moveForm(string $id): void
    {
        $folderId = (int) $id;
        $folder = $this->folders->find($folderId);

        if ($folder === null) {
            ErrorPage::notFound();
            return;
        }

        $parentId = $folder['parent_id'] !== null ? (int) $folder['parent_id'] : null;

        View::render('admin/resume-document-move-form', [
            'title' => 'Mover pasta | Admin paulorb.dev',
            'user' => Auth::user(),
            'subjectLabel' => $folder['name'],
            'formAction' => '/admin/curriculo/documentos/pastas/' . $folderId . '/mover',
            'backHref' => $this->folderUrl($parentId),
            'options' => $this->buildFolderDestinationOptions($folderId),
        ]);
    }

    public function move(string $id): void
    {
        $folderId = (int) $id;
        $folder = $this->folders->find($folderId);

        if ($folder === null) {
            ErrorPage::notFound();
            return;
        }

        $parts = explode(':', (string) ($_POST['destination'] ?? ''), 2);
        $destinationType = $parts[0] ?? '';
        $destinationIdRaw = $parts[1] ?? '';

        if ($destinationType !== 'folder') {
            ErrorPage::notFound();
            return;
        }

        $newParentId = null;

        if ($destinationIdRaw !== '') {
            $newParentId = (int) $destinationIdRaw;

            if ($this->folders->find($newParentId) === null) {
                ErrorPage::notFound();
                return;
            }
        }

        if ($this->folders->wouldCreateCycle($folderId, $newParentId)) {
            header(
                'Location: /admin/curriculo/documentos/pastas/' . $folderId . '/mover?erro='
                . urlencode('Não é possível mover uma pasta para dentro dela mesma ou de uma subpasta dela.')
            );
            return;
        }

        $this->folders->move($folderId, $newParentId);

        header('Location: ' . $this->folderUrl($newParentId));
    }

    private function buildFolderDestinationOptions(int $excludeFolderId): array
    {
        $blocked = $this->folders->descendantIds($excludeFolderId);
        $options = [['group' => 'Pastas', 'value' => 'folder:', 'label' => 'Raiz das pastas']];

        foreach ($this->folders->all() as $folder) {
            if (in_array((int) $folder['id'], $blocked, true)) {
                continue;
            }

            $options[] = [
                'group' => 'Pastas',
                'value' => 'folder:' . $folder['id'],
                'label' => $this->folders->path((int) $folder['id']),
            ];
        }

        return $options;
    }

    private function folderUrl(?int $folderId): string
    {
        return '/admin/curriculo/documentos?tab=pastas' . ($folderId !== null ? '&folder_id=' . $folderId : '');
    }
}
