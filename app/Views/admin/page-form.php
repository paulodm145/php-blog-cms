<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <a class="text-secondary" href="/admin/paginas">Voltar para páginas</a>
            <h1 class="h3 mt-3 mb-4"><?= $isNew ? 'Nova página' : 'Editar página' ?></h1>

            <?php if (!$isNew && !empty($success)): ?>
                <div class="alert alert-success">Página salva com sucesso.</div>
            <?php endif; ?>

            <form method="post" action="<?= $isNew ? '/admin/paginas' : '/admin/paginas/' . (int) $page['id'] . '/edit' ?>" id="page-form">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="title">Título</label>
                        <input class="form-control" id="title" name="title" type="text" value="<?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publicada</option>
                            <option value="draft" <?= ($page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="slug">Slug (URL: /slug)</label>
                        <input class="form-control" id="slug" name="slug" type="text" value="<?= htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Gerado automaticamente se ficar vazio">
                    </div>
                    <div class="col-12 editor-col">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Conteúdo</label>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-secondary" type="button" id="open-media-library">
                                    <i class="fa-solid fa-photo-film me-1"></i> Biblioteca de mídia
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" id="toggle-html-view">
                                    <i class="fa-solid fa-code me-1"></i> Ver HTML
                                </button>
                            </div>
                        </div>
                        <div id="editor" class="editor-surface"><?= \App\Core\Html::postContent($page['content']) ?></div>
                        <textarea id="editor-html" class="form-control d-none" rows="16" spellcheck="false"></textarea>
                        <input type="hidden" name="content" id="content">
                    </div>
                </div>

                <div class="d-flex justify-content-between gap-2 mt-4">
                    <div>
                        <?php if (!$isNew): ?>
                            <button class="btn btn-outline-danger" type="submit" form="delete-page-form">Excluir</button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary" href="/admin/paginas">Cancelar</a>
                        <button class="btn btn-primary" type="submit" id="page-form-submit" disabled>Carregando editor...</button>
                    </div>
                </div>
            </form>
            <?php if (!$isNew): ?>
                <form id="delete-page-form" method="post" action="/admin/paginas/<?= (int) $page['id'] ?>/delete" onsubmit="return confirm('Excluir esta página?');"></form>
            <?php endif; ?>
            <?php require __DIR__ . '/partials/media-library-modal.php'; ?>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/tinymce@8.7.0/tinymce.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce-i18n@26.9.14/langs8/pt-BR.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="/assets/js/media-library.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/media-library.js') ?: '1' ?>"></script>
    <script>
        var htmlView = false;
        var editorHtmlTextarea = document.getElementById('editor-html');
        var toggleHtmlButton = document.getElementById('toggle-html-view');
        var openMediaLibraryButton = document.getElementById('open-media-library');
        var tinyEditor = null;

        tinymce.init({
            selector: '#editor',
            license_key: 'gpl',
            language: 'pt-BR',
            height: 420,
            menubar: false,
            statusbar: false,
            plugins: 'lists link table',
            toolbar: 'blocks | bold italic underline | bullist numlist | link | table | removeformat',
            block_formats: 'Parágrafo=p; Título 2=h2; Título 3=h3; Citação=blockquote; Código=pre',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 16px; line-height: 1.6; } table { width: 100%; border-collapse: collapse; margin: 1rem 0; } th, td { border: 1px solid #d1d5db; padding: 0.5rem 0.75rem; text-align: left; } th { background: rgba(0,0,0,0.04); font-weight: 600; } pre { background: #f1f5f9; border: 1px solid #d1d5db; border-radius: 6px; padding: 1rem; overflow-x: auto; } code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }'
        }).then(function (editors) {
            tinyEditor = editors[0];

            // O formulario comeca com o botao "Salvar" desabilitado porque
            // tinymce.init() e assincrono (diferente do Quill, que
            // inicializava na hora) — sem isso, salvar rapido demais tentaria
            // ler tinyEditor.getContent() antes dele existir.
            var submitButton = document.getElementById('page-form-submit');
            submitButton.disabled = false;
            submitButton.textContent = 'Salvar';

            toggleHtmlButton.addEventListener('click', function () {
                var editorContainer = tinyEditor.getContainer();

                if (htmlView) {
                    tinyEditor.setContent(editorHtmlTextarea.value);
                    editorHtmlTextarea.classList.add('d-none');
                    editorContainer.style.display = '';
                    openMediaLibraryButton.disabled = false;
                    toggleHtmlButton.innerHTML = '<i class="fa-solid fa-code me-1"></i> Ver HTML';
                } else {
                    editorHtmlTextarea.value = tinyEditor.getContent();
                    editorContainer.style.display = 'none';
                    editorHtmlTextarea.classList.remove('d-none');
                    openMediaLibraryButton.disabled = true;
                    toggleHtmlButton.innerHTML = '<i class="fa-solid fa-eye me-1"></i> Ver visual';
                }

                htmlView = !htmlView;
            });

            document.getElementById('open-media-library').addEventListener('click', function () {
                MediaLibrary.open(function (item) {
                    // O modal do Bootstrap rouba o foco da pagina — sem
                    // focus() explicito, insertContent() pode inserir no
                    // ultimo lugar que o navegador lembra, nao necessariamente
                    // onde o cursor estava antes de abrir o modal.
                    tinyEditor.focus();

                    if (item.kind === 'image') {
                        tinyEditor.insertContent('<img src="' + item.url + '" alt="' + (item.alt_text || '') + '">');
                    } else {
                        tinyEditor.insertContent('<a href="' + item.url + '">' + item.original_name + '</a>');
                    }
                });
            });
        });

        document.getElementById('page-form').addEventListener('submit', function () {
            document.getElementById('content').value = htmlView ? editorHtmlTextarea.value : tinyEditor.getContent();
        });
    </script>
</body>
</html>
