<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Mídia</h1>
                    <p class="text-secondary mb-0"><?= (int) $totalMedia ?> arquivo<?= $totalMedia === 1 ? '' : 's' ?> na biblioteca.</p>
                </div>
                <div>
                    <button class="btn btn-primary" type="button" id="media-upload-trigger">Enviar mídia</button>
                    <input type="file" id="media-upload-input" name="files[]" multiple hidden>
                    <div class="progress mt-2 d-none" id="media-upload-progress" style="height: 6px;">
                        <div class="progress-bar" id="media-upload-progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <form class="row g-2 align-items-end mb-4" method="get" action="/admin/media" id="media-filter-form">
                <div class="col-md-7">
                    <label class="form-label" for="q">Buscar por nome do arquivo</label>
                    <input class="form-control" id="q" name="q" type="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Digite parte do nome">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="kind">Tipo</label>
                    <select class="form-select" id="kind" name="kind">
                        <option value="" <?= $kind === '' ? 'selected' : '' ?>>Todos</option>
                        <option value="image" <?= $kind === 'image' ? 'selected' : '' ?>>Imagens</option>
                        <option value="file" <?= $kind === 'file' ? 'selected' : '' ?>>Documentos</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Buscar</button>
                    <?php if ($search !== '' || $kind !== ''): ?>
                        <a class="btn btn-outline-secondary" href="/admin/media">Limpar</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="d-flex justify-content-end mb-3">
                <div class="btn-group" role="group" aria-label="Visualização" id="media-view-toggle">
                    <button type="button" class="btn btn-outline-secondary<?= $view === 'grid' ? ' active' : '' ?>" data-view="grid" title="Grade">
                        <i class="fa-solid fa-table-cells-large"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary<?= $view === 'list' ? ' active' : '' ?>" data-view="list" title="Lista">
                        <i class="fa-solid fa-list"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary<?= $view === 'grouped' ? ' active' : '' ?>" data-view="grouped" title="Agrupado por mês">
                        <i class="fa-solid fa-calendar-days"></i>
                    </button>
                </div>
            </div>

            <div id="media-grid-wrap">
                <?php require __DIR__ . '/partials/media-grid.php'; ?>
            </div>

            <div class="offcanvas offcanvas-end" tabindex="-1" id="media-detail-panel" aria-labelledby="media-detail-panel-label">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="media-detail-panel-label">Detalhes do arquivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
                </div>
                <div class="offcanvas-body">
                    <div class="text-center mb-3" id="media-detail-preview"></div>
                    <div class="mb-3">
                        <label class="form-label" for="media-detail-title">Título</label>
                        <input class="form-control" id="media-detail-title" type="text">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="media-detail-alt">Texto alternativo</label>
                        <input class="form-control" id="media-detail-alt" type="text">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="media-detail-url">URL do arquivo</label>
                        <div class="input-group">
                            <input class="form-control" id="media-detail-url" type="text" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="media-detail-copy">Copiar</button>
                        </div>
                    </div>
                    <p class="text-secondary small mb-4" id="media-detail-meta"></p>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-primary" type="button" id="media-detail-save">Salvar</button>
                        <a class="btn btn-outline-secondary" id="media-detail-download" href="#" download>
                            <i class="fa-solid fa-download me-1"></i> Baixar
                        </a>
                        <button class="btn btn-outline-danger" type="button" id="media-detail-delete">Excluir permanentemente</button>
                    </div>
                </div>
            </div>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="/assets/js/media-library.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/media-library.js') ?: '1' ?>"></script>
    <script>
        MediaLibrary.mountPage({
            gridWrap: document.getElementById('media-grid-wrap'),
            searchInput: document.getElementById('q'),
            kindSelect: document.getElementById('kind'),
            uploadTrigger: document.getElementById('media-upload-trigger'),
            uploadInput: document.getElementById('media-upload-input'),
            progressWrap: document.getElementById('media-upload-progress'),
            progressBar: document.getElementById('media-upload-progress-bar'),
            viewButtons: document.querySelectorAll('#media-view-toggle button'),
            onCardClick: function (item) {
                var panelEl = document.getElementById('media-detail-panel');
                var panel = bootstrap.Offcanvas.getOrCreateInstance(panelEl);
                var preview = document.getElementById('media-detail-preview');

                if (item.kind === 'image') {
                    preview.innerHTML = '<img src="' + item.url + '" alt="" style="max-width:100%;max-height:220px;border-radius:8px;">';
                } else {
                    preview.innerHTML = '<i class="fa-solid ' + item.iconClass + '" style="font-size:4rem;color:var(--admin-muted);"></i>';
                }

                document.getElementById('media-detail-title').value = item.title || '';
                document.getElementById('media-detail-alt').value = item.alt || '';
                document.getElementById('media-detail-url').value = item.url;
                document.getElementById('media-detail-meta').textContent =
                    item.mime + ' · ' + MediaLibrary.formatSize(item.size) + ' · ' + item.created;

                var downloadLink = document.getElementById('media-detail-download');
                downloadLink.href = item.url;
                downloadLink.setAttribute('download', item.name || '');

                document.getElementById('media-detail-copy').onclick = function () {
                    navigator.clipboard.writeText(item.url);
                };

                document.getElementById('media-detail-save').onclick = function () {
                    var formData = new FormData();
                    formData.append('title', document.getElementById('media-detail-title').value);
                    formData.append('alt_text', document.getElementById('media-detail-alt').value);
                    fetch('/admin/media/' + item.id, { method: 'POST', body: formData })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('status ' + response.status);
                            }
                            MediaLibrary.notifySuccess('Alterações salvas');
                        })
                        .catch(function () {
                            MediaLibrary.notifyError('Falha ao salvar as alterações');
                        });
                };

                document.getElementById('media-detail-delete').onclick = function () {
                    MediaLibrary.confirmDelete(item, function () {
                        panel.hide();
                        MediaLibrary.refreshPage();
                    });
                };

                panel.show();
            }
        });
    </script>
</body>
</html>
