<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
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
                        <button class="btn btn-primary" type="submit">Salvar</button>
                    </div>
                </div>
            </form>
            <?php if (!$isNew): ?>
                <form id="delete-page-form" method="post" action="/admin/paginas/<?= (int) $page['id'] ?>/delete" onsubmit="return confirm('Excluir esta página?');"></form>
            <?php endif; ?>
            <?php require __DIR__ . '/partials/media-library-modal.php'; ?>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill-image-resize-module@3.0.0/image-resize.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="/assets/js/media-library.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/media-library.js') ?: '1' ?>"></script>
    <script>
        Quill.register('modules/imageResize', ImageResize.default);
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['blockquote', 'code-block'],
                    ['link'],
                    ['clean']
                ],
                imageResize: {}
            }
        });
        var htmlView = false;
        var editorEl = document.getElementById('editor');
        var editorHtmlTextarea = document.getElementById('editor-html');
        var toggleHtmlButton = document.getElementById('toggle-html-view');
        var openMediaLibraryButton = document.getElementById('open-media-library');
        var quillToolbarEl = document.querySelector('.ql-toolbar');

        toggleHtmlButton.addEventListener('click', function () {
            if (htmlView) {
                quill.clipboard.dangerouslyPasteHTML(editorHtmlTextarea.value);
                editorHtmlTextarea.classList.add('d-none');
                editorEl.classList.remove('d-none');
                quillToolbarEl.classList.remove('d-none');
                openMediaLibraryButton.disabled = false;
                toggleHtmlButton.innerHTML = '<i class="fa-solid fa-code me-1"></i> Ver HTML';
            } else {
                editorHtmlTextarea.value = quill.root.innerHTML;
                editorEl.classList.add('d-none');
                quillToolbarEl.classList.add('d-none');
                editorHtmlTextarea.classList.remove('d-none');
                openMediaLibraryButton.disabled = true;
                toggleHtmlButton.innerHTML = '<i class="fa-solid fa-eye me-1"></i> Ver visual';
            }

            htmlView = !htmlView;
        });
        document.getElementById('page-form').addEventListener('submit', function () {
            document.getElementById('content').value = htmlView ? editorHtmlTextarea.value : quill.root.innerHTML;
        });
        document.getElementById('open-media-library').addEventListener('click', function () {
            MediaLibrary.open(function (item) {
                var range = quill.getSelection(true);

                if (item.kind === 'image') {
                    quill.clipboard.dangerouslyPasteHTML(
                        range.index,
                        '<img src="' + item.url + '" alt="' + (item.alt_text || '') + '">'
                    );
                } else {
                    quill.clipboard.dangerouslyPasteHTML(
                        range.index,
                        '<a href="' + item.url + '">' + item.original_name + '</a>'
                    );
                }

                quill.setSelection(range.index + 1);
            });
        });
    </script>
</body>
</html>
