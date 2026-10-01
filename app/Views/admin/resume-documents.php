<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <div class="mb-4">
                <h1 class="h3 mb-1">Documentos</h1>
                <p class="text-secondary mb-0">Contratos, notas fiscais e outros arquivos de cada experiência profissional.</p>
            </div>

            <?php $activeTab = in_array($_GET['tab'] ?? '', ['documentos', 'pastas', 'shares'], true) ? $_GET['tab'] : 'documentos'; ?>

            <ul class="nav nav-tabs mb-4" id="documents-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link<?= $activeTab === 'documentos' ? ' active' : '' ?>" id="tab-docs-btn" data-bs-toggle="tab" data-bs-target="#tab-docs" type="button" role="tab">Documentos</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link<?= $activeTab === 'pastas' ? ' active' : '' ?>" id="tab-pastas-btn" data-bs-toggle="tab" data-bs-target="#tab-pastas" type="button" role="tab">Pastas</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link<?= $activeTab === 'shares' ? ' active' : '' ?>" id="tab-shares-btn" data-bs-toggle="tab" data-bs-target="#tab-shares" type="button" role="tab">Links compartilhados</button>
                </li>
            </ul>

            <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-3" id="documents-selection-bar">
                <span class="text-secondary small">
                    <span id="documents-selected-count">0</span> selecionado(s)
                    <span id="documents-editing-indicator" class="d-none text-primary"> — editando link compartilhado</span>
                </span>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary d-none" id="documents-cancel-edit" type="button">Cancelar edição</button>
                    <button class="btn btn-sm btn-outline-secondary" id="documents-clear-selection" type="button">Limpar seleção</button>
                    <button class="btn btn-sm btn-primary" id="documents-share-trigger" type="button" disabled>Compartilhar selecionados</button>
                </div>
            </div>

            <div class="tab-content" id="documents-tabs-content">
                <div class="tab-pane fade<?= $activeTab === 'documentos' ? ' show active' : '' ?>" id="tab-docs" role="tabpanel">
                    <div class="accordion" id="documents-accordion">
                        <?php foreach ($groups as $index => $group): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button<?= $index === 0 ? '' : ' collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#experience-<?= (int) $group['id'] ?>">
                                        <?= htmlspecialchars($group['role'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($group['company'], ENT_QUOTES, 'UTF-8') ?>
                                        <span class="text-secondary small ms-2"><?= htmlspecialchars($group['period'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="badge text-bg-secondary ms-2"><?= count($group['documents']) ?></span>
                                    </button>
                                </h2>
                                <div id="experience-<?= (int) $group['id'] ?>" class="accordion-collapse collapse<?= $index === 0 ? ' show' : '' ?>" data-bs-parent="#documents-accordion">
                                    <div class="accordion-body">
                                        <?php $group['returnUrl'] = '/admin/curriculo/documentos?tab=documentos'; ?>
                                        <?php require __DIR__ . '/partials/resume-document-panel.php'; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($groups)): ?>
                            <p class="text-secondary">Nenhuma experiência cadastrada ainda. <a href="/admin/curriculo/experiencia/create">Cadastre uma experiência</a> antes de anexar documentos.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="tab-pane fade<?= $activeTab === 'pastas' ? ' show active' : '' ?>" id="tab-pastas" role="tabpanel">
                    <?php require __DIR__ . '/partials/resume-document-folder-tab.php'; ?>
                </div>

                <div class="tab-pane fade<?= $activeTab === 'shares' ? ' show active' : '' ?>" id="tab-shares" role="tabpanel">
                    <div id="shares-table-wrap">
                        <?php require __DIR__ . '/partials/resume-document-shares-table.php'; ?>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="share-modal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="share-modal-title">Compartilhar documentos</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="share-modal-form">
                                <label class="form-label" for="share-expires-preset">Validade do link</label>
                                <select class="form-select mb-3" id="share-expires-preset">
                                    <option value="1">1 dia</option>
                                    <option value="7" selected>7 dias</option>
                                    <option value="30">30 dias</option>
                                    <option value="custom">Personalizado…</option>
                                </select>
                                <input class="form-control mb-3 d-none" type="datetime-local" id="share-expires-custom">
                                <button class="btn btn-primary" id="share-generate-btn" type="button">Gerar link</button>
                            </div>
                            <div id="share-modal-result" class="d-none">
                                <label class="form-label">Link gerado</label>
                                <div class="input-group">
                                    <input class="form-control" id="share-result-url" type="text" readonly>
                                    <button class="btn btn-outline-secondary" id="share-copy-btn" type="button">Copiar</button>
                                </div>
                                <p class="text-secondary small mt-2 mb-0">Expira em <span id="share-result-expiry"></span>.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="/assets/js/resume-documents.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/resume-documents.js') ?: '1' ?>"></script>
</body>
</html>
