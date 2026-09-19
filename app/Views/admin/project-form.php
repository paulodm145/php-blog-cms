<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
            <a class="text-secondary" href="/admin/projetos">Voltar para projetos</a>
            <div class="d-flex justify-content-between align-items-center gap-3 mt-3 mb-4">
                <h1 class="h3 mb-0"><?= $isNew ? 'Novo projeto' : 'Editar projeto' ?></h1>
                <?php if (!$isNew && $item['status'] === 'published' && $item['slug'] !== ''): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="/projetos/<?= rawurlencode($item['slug']) ?>" target="_blank" rel="noopener">
                        <i class="fa-solid fa-up-right-from-square me-1"></i> Ver projeto
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!$isNew && !empty($success)): ?>
                <div class="alert alert-success">Projeto salvo com sucesso.</div>
            <?php endif; ?>

            <form method="post" action="<?= $isNew ? '/admin/projetos' : '/admin/projetos/' . (int) $item['id'] . '/edit' ?>" id="project-form">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="name">Nome do projeto</label>
                        <input class="form-control" id="name" name="name" type="text" value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="slug">Slug</label>
                        <input class="form-control" id="slug" name="slug" type="text" value="<?= htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Gerado automaticamente se ficar vazio">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="tagline">Resumo curto</label>
                        <input class="form-control" id="tagline" name="tagline" type="text" maxlength="255" value="<?= htmlspecialchars($item['tagline'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Uma frase pro card e pra busca">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="project_type">Tipo</label>
                        <select class="form-select" id="project_type" name="project_type">
                            <option value="" <?= $item['project_type'] === null || $item['project_type'] === '' ? 'selected' : '' ?>>Nenhum</option>
                            <option value="personal" <?= $item['project_type'] === 'personal' ? 'selected' : '' ?>>Pessoal</option>
                            <option value="professional" <?= $item['project_type'] === 'professional' ? 'selected' : '' ?>>Profissional</option>
                            <option value="freelance" <?= $item['project_type'] === 'freelance' ? 'selected' : '' ?>>Freelance</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="role">Papel no projeto</label>
                        <input class="form-control" id="role" name="role" type="text" value="<?= htmlspecialchars((string) ($item['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Desenvolvedor solo">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="technologies">Tecnologias (separadas por vírgula)</label>
                        <input class="form-control" id="technologies" name="technologies" type="text" value="<?= htmlspecialchars($item['technologies'], ENT_QUOTES, 'UTF-8') ?>" placeholder="React, Node.js, PostgreSQL">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="start_date">Data inicial</label>
                        <input class="form-control" id="start_date" name="start_date" type="date" value="<?= htmlspecialchars((string) ($item['start_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="end_date">Data final</label>
                        <input class="form-control" id="end_date" name="end_date" type="date" value="<?= htmlspecialchars((string) ($item['end_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <p class="text-secondary small mt-1 mb-0">Deixe em branco se ainda está em andamento.</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="live_url">URL ao vivo</label>
                        <input class="form-control" id="live_url" name="live_url" type="url" placeholder="https://..." value="<?= htmlspecialchars((string) ($item['live_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label d-block">Repositórios</label>
                        <p class="text-secondary small mb-2">
                            Um projeto pode ter mais de um repositório (ex: backend e frontend separados). O rótulo é opcional — sem ele, e com só um link, o botão mostra "Ver código".
                        </p>
                        <div id="source-link-rows" class="d-flex flex-column gap-2 mb-2">
                            <?php foreach ($item['source_links'] ?? [] as $link): ?>
                                <div class="source-link-row d-flex gap-2">
                                    <input class="form-control" style="max-width: 12rem" type="text" name="source_links_label[]" placeholder="Rótulo (opcional)" value="<?= htmlspecialchars($link['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <input class="form-control" type="url" name="source_links_url[]" placeholder="https://github.com/..." value="<?= htmlspecialchars($link['url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="button" class="btn btn-outline-danger btn-sm source-link-remove" title="Remover"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="add-source-link">
                            <i class="fa-solid fa-plus me-1"></i> Adicionar repositório
                        </button>
                    </div>
                    <div class="col-12">
                        <label class="form-label d-block">Capa</label>
                        <input type="hidden" id="cover_media_id" name="cover_media_id" value="<?= (int) ($item['cover_media_id'] ?? 0) ?>">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-secondary btn-sm" type="button" id="choose-cover">
                                <i class="fa-solid fa-photo-film me-1"></i> Escolher da biblioteca de mídia
                            </button>
                            <span id="cover-current" class="small">
                                <?php if (!empty($item['cover_url'])): ?>
                                    <a href="<?= htmlspecialchars($item['cover_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                        <?= htmlspecialchars($item['cover_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <a href="#" id="remove-cover" class="text-danger ms-2">Remover</a>
                                <?php else: ?>
                                    <span class="text-secondary">Nenhuma imagem escolhida.</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label d-block">Galeria de imagens (screenshots)</label>
                        <input type="hidden" id="gallery_media_ids" name="gallery_media_ids" value="<?= htmlspecialchars(implode(',', array_column($item['gallery'] ?? [], 'id')), ENT_QUOTES, 'UTF-8') ?>">
                        <div id="gallery-thumbs" class="d-flex flex-wrap gap-2 mb-2">
                            <?php foreach ($item['gallery'] ?? [] as $image): ?>
                                <div class="gallery-thumb" data-id="<?= (int) $image['id'] ?>">
                                    <img src="<?= htmlspecialchars($image['url'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                                    <button type="button" class="gallery-thumb-remove" title="Remover">&times;</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="add-gallery-image">
                            <i class="fa-solid fa-photo-film me-1"></i> Adicionar imagem
                        </button>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="featured" name="featured" value="1" <?= !empty($item['featured']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="featured">Destaque (aparece no currículo e no PDF)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="draft" <?= $item['status'] === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                            <option value="published" <?= $item['status'] === 'published' ? 'selected' : '' ?>>Publicado</option>
                        </select>
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
                        <div id="editor" class="editor-surface"><?= \App\Core\Html::postContent($item['content']) ?></div>
                        <textarea id="editor-html" class="form-control d-none" rows="16" spellcheck="false"></textarea>
                        <input type="hidden" name="content" id="content">
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <?php if (!$isNew): ?>
                            <button class="btn btn-outline-danger" type="submit" form="delete-project-form">Excluir</button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary" href="/admin/projetos">Cancelar</a>
                        <button class="btn btn-primary" type="submit">Salvar</button>
                    </div>
                </div>
            </form>
            <?php if (!$isNew): ?>
                <form id="delete-project-form" method="post" action="/admin/projetos/<?= (int) $item['id'] ?>/delete" onsubmit="return confirm('Excluir este projeto?');"></form>
            <?php endif; ?>
            <?php require __DIR__ . '/partials/media-library-modal.php'; ?>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
    <script src="/assets/js/quill-tables.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/quill-tables.js') ?: '1' ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill-image-resize-module@3.0.0/image-resize.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="/assets/js/media-library.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/media-library.js') ?: '1' ?>"></script>
    <script>
        // O modulo de redimensionar imagem vem de um CDN separado, menos
        // confiavel que o do Quill em si — se essa requisicao falhar
        // (instabilidade de rede, bloqueador de anuncio etc.), ImageResize
        // fica indefinido. Sem essa checagem, Quill.register() quebra e
        // trava o script inteiro, deixando o editor inteiro morto (nem o
        // Quill chega a inicializar). Com a checagem, so perde o recurso
        // de redimensionar imagem — o editor continua funcionando.
        var hasImageResize = typeof ImageResize !== 'undefined';

        if (hasImageResize) {
            Quill.register('modules/imageResize', ImageResize.default);
        }

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
                imageResize: hasImageResize ? {} : undefined
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

        document.getElementById('choose-cover').addEventListener('click', function () {
            MediaLibrary.open(function (item) {
                document.getElementById('cover_media_id').value = item.id;
                document.getElementById('cover-current').innerHTML =
                    '<a href="' + item.url + '" target="_blank" rel="noopener">' + item.original_name + '</a>'
                    + ' <a href="#" id="remove-cover" class="text-danger ms-2">Remover</a>';
                bindRemoveCover();
            });
        });

        function bindRemoveCover() {
            var removeLink = document.getElementById('remove-cover');

            if (!removeLink) {
                return;
            }

            removeLink.addEventListener('click', function (event) {
                event.preventDefault();
                document.getElementById('cover_media_id').value = '';
                document.getElementById('cover-current').innerHTML = '<span class="text-secondary">Nenhuma imagem escolhida.</span>';
            });
        }

        bindRemoveCover();

        var galleryThumbsWrap = document.getElementById('gallery-thumbs');

        document.getElementById('add-gallery-image').addEventListener('click', function () {
            MediaLibrary.open(function (item) {
                if (item.kind !== 'image') {
                    alert('A galeria aceita só imagens.');
                    return;
                }

                var thumb = document.createElement('div');
                thumb.className = 'gallery-thumb';
                thumb.setAttribute('data-id', item.id);
                thumb.innerHTML = '<img src="' + item.url + '" alt="">'
                    + '<button type="button" class="gallery-thumb-remove" title="Remover">&times;</button>';
                galleryThumbsWrap.appendChild(thumb);
                bindGalleryRemove(thumb.querySelector('.gallery-thumb-remove'));
                updateGalleryInput();
            });
        });

        function bindGalleryRemove(button) {
            button.addEventListener('click', function () {
                button.closest('.gallery-thumb').remove();
                updateGalleryInput();
            });
        }

        function updateGalleryInput() {
            var ids = Array.prototype.map.call(galleryThumbsWrap.querySelectorAll('.gallery-thumb'), function (el) {
                return el.getAttribute('data-id');
            });
            document.getElementById('gallery_media_ids').value = ids.join(',');
        }

        galleryThumbsWrap.querySelectorAll('.gallery-thumb-remove').forEach(bindGalleryRemove);

        var sourceLinkRowsWrap = document.getElementById('source-link-rows');

        function bindSourceLinkRemove(button) {
            button.addEventListener('click', function () {
                button.closest('.source-link-row').remove();
            });
        }

        document.getElementById('add-source-link').addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'source-link-row d-flex gap-2';
            row.innerHTML = '<input class="form-control" style="max-width: 12rem" type="text" name="source_links_label[]" placeholder="Rótulo (opcional)">'
                + '<input class="form-control" type="url" name="source_links_url[]" placeholder="https://github.com/...">'
                + '<button type="button" class="btn btn-outline-danger btn-sm source-link-remove" title="Remover"><i class="fa-solid fa-xmark"></i></button>';
            sourceLinkRowsWrap.appendChild(row);
            bindSourceLinkRemove(row.querySelector('.source-link-remove'));
            row.querySelector('input[type="text"]').focus();
        });

        sourceLinkRowsWrap.querySelectorAll('.source-link-remove').forEach(bindSourceLinkRemove);

        document.getElementById('project-form').addEventListener('submit', function () {
            document.getElementById('content').value = htmlView ? editorHtmlTextarea.value : quill.root.innerHTML;
        });
    </script>
</body>
</html>
