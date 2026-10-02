<?php
/** @var array $group */
$ownerType = $group['ownerType'] ?? 'experience';
$documents = $group['documents'];
// Falso quando o painel esta embutido na edicao de uma unica experiencia
// (app/Views/admin/resume-experience-form.php) — la nao existe botao de
// compartilhar, entao o checkbox de selecao nao tem pra que servir.
$documentSelectable = $group['selectable'] ?? true;
// Pra onde "Mover" volta depois — cada tela que inclui este painel passa
// a sua propria URL (aba Documentos, uma pasta especifica, ou a pagina de
// edicao da experiencia), senao cai pra raiz da tela de Documentos.
$documentReturnUrl = $group['returnUrl'] ?? '/admin/curriculo/documentos';
?>
<div class="document-manager"
     <?php if ($ownerType === 'folder'): ?>
         data-folder-id="<?= $group['id'] !== null ? (int) $group['id'] : '' ?>"
     <?php else: ?>
         data-experience-id="<?= (int) $group['id'] ?>"
     <?php endif; ?>
     data-selectable="<?= $documentSelectable ? 'true' : 'false' ?>">
    <div class="document-dropzone border border-dashed rounded p-3 text-center text-secondary mb-3" data-dropzone>
        Arraste arquivos aqui ou <button class="btn btn-link p-0 align-baseline" type="button" data-upload-trigger>selecione</button>
        <input type="file" class="d-none" multiple data-upload-input accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.txt,.csv">
    </div>
    <div class="progress mb-3 d-none" data-upload-progress-wrap style="height:6px">
        <div class="progress-bar" data-upload-progress-bar style="width:0%"></div>
    </div>
    <?php if ($documentSelectable): ?>
        <label class="form-check d-flex align-items-center gap-1 mb-2" title="Selecionar todos os documentos desta lista">
            <input class="form-check-input mt-0" type="checkbox" data-select-all-documents>
            <span class="form-check-label text-secondary small">Selecionar todos</span>
        </label>
    <?php endif; ?>
    <ul class="list-group document-list mb-2" data-document-list>
        <?php foreach ($documents as $document): ?>
            <?php require __DIR__ . '/resume-document-item.php'; ?>
        <?php endforeach; ?>
    </ul>
    <p class="text-secondary small mb-0 document-empty-message<?= empty($documents) ? '' : ' d-none' ?>">Nenhum documento anexado.</p>
</div>
