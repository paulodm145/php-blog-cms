<?php
/** @var int|null $currentFolderId */
/** @var array $folderBreadcrumb */
/** @var array $folderChildren */
/** @var array $folderDocuments */
?>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item<?= empty($folderBreadcrumb) ? ' active' : '' ?>">
            <?php if (empty($folderBreadcrumb)): ?>
                Pastas
            <?php else: ?>
                <a href="/admin/curriculo/documentos?tab=pastas">Pastas</a>
            <?php endif; ?>
        </li>
        <?php foreach ($folderBreadcrumb as $index => $crumb): ?>
            <li class="breadcrumb-item<?= $index === count($folderBreadcrumb) - 1 ? ' active' : '' ?>">
                <?php if ($index === count($folderBreadcrumb) - 1): ?>
                    <?= htmlspecialchars($crumb['name'], ENT_QUOTES, 'UTF-8') ?>
                <?php else: ?>
                    <a href="/admin/curriculo/documentos?tab=pastas&folder_id=<?= (int) $crumb['id'] ?>"><?= htmlspecialchars($crumb['name'], ENT_QUOTES, 'UTF-8') ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-secondary small"><?= count($folderChildren) ?> subpasta(s)</span>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-secondary" href="/admin/curriculo/documentos/novo-arquivo?folder_id=<?= $currentFolderId !== null ? (int) $currentFolderId : '' ?>">Novo arquivo</a>
        <a class="btn btn-sm btn-outline-primary" href="/admin/curriculo/documentos/pastas/criar?parent_id=<?= $currentFolderId !== null ? (int) $currentFolderId : '' ?>">Nova pasta</a>
    </div>
</div>

<?php if (!empty($folderChildren)): ?>
    <div class="row row-cols-1 row-cols-md-3 g-2 mb-4">
        <?php foreach ($folderChildren as $child): ?>
            <div class="col">
                <div class="border rounded p-2 d-flex align-items-center justify-content-between" draggable="true" data-draggable-folder-id="<?= (int) $child['id'] ?>">
                    <a class="text-decoration-none flex-grow-1 min-w-0" href="/admin/curriculo/documentos?tab=pastas&folder_id=<?= (int) $child['id'] ?>" data-folder-link>
                        <i class="fa-solid fa-folder me-2"></i>
                        <span data-folder-name-text><?= htmlspecialchars($child['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="text-secondary small">(<?= (int) $child['document_count'] ?>)</span>
                    </a>
                    <div class="admin-actions flex-shrink-0">
                        <button class="admin-action-link" type="button" data-rename-folder title="Renomear"><i class="fa-solid fa-pen"></i></button>
                        <a class="admin-action-link" href="/admin/curriculo/documentos/pastas/<?= (int) $child['id'] ?>/mover" title="Mover"><i class="fa-solid fa-arrows-up-down-left-right"></i></a>
                        <form class="d-inline" method="post" action="/admin/curriculo/documentos/pastas/<?= (int) $child['id'] ?>/excluir">
                            <button class="admin-action-link admin-action-danger" type="button" data-delete-folder title="Excluir"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$folderTabReturnUrl = '/admin/curriculo/documentos?tab=pastas' . ($currentFolderId !== null ? '&folder_id=' . $currentFolderId : '');
$group = ['id' => $currentFolderId, 'documents' => $folderDocuments, 'selectable' => true, 'ownerType' => 'folder', 'returnUrl' => $folderTabReturnUrl];
?>
<?php require __DIR__ . '/resume-document-panel.php'; ?>
