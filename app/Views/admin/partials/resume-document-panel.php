<?php
/** @var array $group */
$experienceId = (int) $group['id'];
$documents = $group['documents'];
?>
<div class="document-manager" data-experience-id="<?= $experienceId ?>">
    <div class="document-dropzone border border-dashed rounded p-3 text-center text-secondary mb-3" data-dropzone>
        Arraste arquivos aqui ou <button class="btn btn-link p-0 align-baseline" type="button" data-upload-trigger>selecione</button>
        <input type="file" class="d-none" multiple data-upload-input accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.txt,.csv">
    </div>
    <div class="progress mb-3 d-none" data-upload-progress-wrap style="height:6px">
        <div class="progress-bar" data-upload-progress-bar style="width:0%"></div>
    </div>
    <ul class="list-group document-list mb-2" data-document-list>
        <?php foreach ($documents as $document): ?>
            <?php require __DIR__ . '/resume-document-item.php'; ?>
        <?php endforeach; ?>
    </ul>
    <p class="text-secondary small mb-0 document-empty-message<?= empty($documents) ? '' : ' d-none' ?>">Nenhum documento anexado.</p>
</div>
