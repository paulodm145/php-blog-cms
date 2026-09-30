<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ErrorPage;
use App\Core\View;
use App\Repositories\ResumeDocumentFolderRepository;
use App\Repositories\ResumeExperienceDocumentRepository;

class AdminResumeDocumentFolderController
{
    private $folders;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->folders = new ResumeDocumentFolderRepository();
    }

    public function createForm(): void
    {
        $parentId = $this->resolveFolderId($_GET['parent_id'] ?? null);

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
        $parentId = $this->resolveFolderId($_POST['parent_id'] ?? null);
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($name !== '') {
            $this->folders->create($parentId, $name);
        }

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

        $name = trim((string) ($_POST['name'] ?? ''));

        if ($name !== '') {
            $this->folders->rename((int) $id, $name);
        }

        header('Location: ' . $this->folderUrl((int) $id));
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

        $this->folders->delete($folderId);

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

        View::render('admin/resume-document-move-form', [
            'title' => 'Mover pasta | Admin paulorb.dev',
            'user' => Auth::user(),
            'subjectLabel' => $folder['name'],
            'formAction' => '/admin/curriculo/documentos/pastas/' . $folderId . '/mover',
            'backHref' => '/admin/curriculo/documentos?tab=pastas',
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
        $newParentId = isset($parts[1]) && $parts[1] !== '' ? (int) $parts[1] : null;

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
                'label' => $folder['name'],
            ];
        }

        return $options;
    }

    private function resolveFolderId($raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $id = (int) $raw;

        return $this->folders->find($id) !== null ? $id : null;
    }

    private function folderUrl(?int $folderId): string
    {
        return '/admin/curriculo/documentos?tab=pastas' . ($folderId !== null ? '&folder_id=' . $folderId : '');
    }
}
