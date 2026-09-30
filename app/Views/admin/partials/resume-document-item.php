<?php
/** @var array $document */
/** @var bool $documentSelectable */
$documentSelectable = $documentSelectable ?? true;
$documentIconClass = strpos($document['mime_type'], 'image/') === 0
    ? 'fa-file-image'
    : \App\Core\Html::fileIcon($document['mime_type']);
$documentSizeLabel = ((int) $document['size']) >= 1048576
    ? round(((int) $document['size']) / 1048576, 1) . ' MB'
    : (((int) $document['size']) >= 1024 ? round(((int) $document['size']) / 1024, 1) . ' KB' : (int) $document['size'] . ' B');
?>
<li class="list-group-item d-flex align-items-center gap-2" data-document-id="<?= (int) $document['id'] ?>">
    <?php if ($documentSelectable): ?>
        <input class="form-check-input flex-shrink-0" type="checkbox" data-document-checkbox>
    <?php endif; ?>
    <i class="fa-solid <?= $documentIconClass ?> flex-shrink-0"></i>
    <div class="flex-grow-1 min-w-0">
        <div class="text-truncate"><?= htmlspecialchars($document['original_name'], ENT_QUOTES, 'UTF-8') ?></div>
        <input
            class="form-control form-control-sm border-0 bg-transparent px-0 document-caption-input"
            type="text"
            placeholder="Adicionar legenda…"
            value="<?= htmlspecialchars((string) $document['caption'], ENT_QUOTES, 'UTF-8') ?>"
            data-caption-input
        >
    </div>
    <span class="text-secondary small flex-shrink-0"><?= $documentSizeLabel ?></span>
    <a class="admin-action-link flex-shrink-0" href="/admin/curriculo/documentos/<?= (int) $document['id'] ?>/download" title="Baixar"><i class="fa-solid fa-download"></i></a>
    <button class="admin-action-link admin-action-danger flex-shrink-0" type="button" data-delete-document title="Excluir"><i class="fa-solid fa-trash"></i></button>
</li>
