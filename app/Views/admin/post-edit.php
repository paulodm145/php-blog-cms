<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">
            <a class="text-secondary" href="/admin/posts">Voltar para posts</a>
            <div class="d-flex justify-content-between align-items-center gap-3 mt-3 mb-4">
                <h1 class="h3 mb-0"><?= $isNew ? 'Novo post' : 'Editar post' ?></h1>
                <?php if (!$isNew && $post['status'] === 'published' && $post['slug'] !== ''): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="/blog/<?= rawurlencode($post['slug']) ?>" target="_blank" rel="noopener">
                        <i class="fa-solid fa-up-right-from-square me-1"></i> Ver post
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">Post atualizado com sucesso.</div>
            <?php endif; ?>

            <form method="post" action="<?= $isNew ? '/admin/posts' : '/admin/posts/' . (int) $post['id'] . '/edit' ?>" id="post-form" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="title">Titulo</label>
                        <input class="form-control" id="title" name="title" type="text" value="<?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="published_at">Publicado em</label>
                        <input class="form-control" id="published_at" name="published_at" type="datetime-local" value="<?= $post['published_at'] ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publicado</option>
                            <option value="draft" <?= ($post['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                            <option value="hidden" <?= ($post['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>Oculto</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="slug">Slug</label>
                        <input class="form-control" id="slug" name="slug" type="text" value="<?= htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Gerado automaticamente se ficar vazio">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="author_id">Autor</label>
                        <select class="form-select" id="author_id" name="author_id" required>
                            <?php foreach ($users as $author): ?>
                                <option
                                    value="<?= (int) $author['id'] ?>"
                                    <?= (int) ($post['author_id'] ?? 0) === (int) $author['id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($author['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="category-dropdown-button">Categorias</label>
                        <div class="dropdown category-select">
                            <button
                                class="form-select text-start category-select-toggle"
                                id="category-dropdown-button"
                                type="button"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                            >
                                Selecione categorias
                            </button>
                            <div class="dropdown-menu category-select-menu p-2" aria-labelledby="category-dropdown-button">
                                <?php foreach ($categories as $categoryOption): ?>
                                    <?php $checked = in_array((int) $categoryOption['id'], $post['category_ids'] ?? [], true); ?>
                                    <label class="dropdown-item category-select-option">
                                        <input
                                            class="form-check-input category-checkbox me-2"
                                            type="checkbox"
                                            name="category_ids[]"
                                            value="<?= (int) $categoryOption['id'] ?>"
                                            data-label="<?= htmlspecialchars($categoryOption['name'], ENT_QUOTES, 'UTF-8') ?>"
                                            <?= $checked ? 'checked' : '' ?>
                                        >
                                        <?= htmlspecialchars($categoryOption['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tags">Tags</label>
                        <input class="form-control" id="tags" name="tags" type="text" value="<?= htmlspecialchars(implode(', ', array_column($post['tags'], 'name')), ENT_QUOTES, 'UTF-8') ?>" placeholder="PHP, MVC, SEO">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="featured_image_upload">Imagem de destaque</label>
                        <p class="text-secondary small mb-2">
                            Tamanho ideal: 1200 x 520 px. Envie JPG, PNG ou WebP com ate 2 MB, ou escolha uma imagem ja existente na biblioteca — os dois jeitos passam pelo recorte abaixo.
                        </p>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <input class="form-control" style="max-width: 22rem" id="featured_image_upload" name="featured_image_upload" type="file" accept="image/jpeg,image/png,image/webp">
                            <button class="btn btn-sm btn-outline-secondary" type="button" id="choose-featured-image">
                                <i class="fa-solid fa-photo-film me-1"></i> Escolher da biblioteca de mídia
                            </button>
                        </div>
                        <input type="hidden" name="featured_image_data" id="featured_image_data">
                        <input type="hidden" name="current_featured_image" value="<?= htmlspecialchars($post['featured_image'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="row g-3 mt-2">
                            <div class="col-lg-7">
                                <div class="image-crop-frame">
                                    <img
                                        id="crop_source"
                                        src="<?= htmlspecialchars($post['featured_image'], ENT_QUOTES, 'UTF-8') ?>"
                                        alt="Previa da imagem de destaque"
                                    >
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="featured-preview">
                                    <img
                                        id="featured_preview"
                                        src="<?= htmlspecialchars($post['featured_image'], ENT_QUOTES, 'UTF-8') ?>"
                                        alt="Visualizacao antes de salvar"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="excerpt">Resumo</label>
                        <textarea class="form-control" id="excerpt" name="excerpt" rows="3" required><?= htmlspecialchars($post['excerpt'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="col-12 editor-col">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Conteudo</label>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-secondary" type="button" id="open-media-library">
                                    <i class="fa-solid fa-photo-film me-1"></i> Biblioteca de mídia
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" id="insert-gallery">
                                    <i class="fa-solid fa-photo-film me-1"></i> Inserir galeria
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" id="toggle-html-view">
                                    <i class="fa-solid fa-code me-1"></i> Ver HTML
                                </button>
                            </div>
                        </div>
                        <div id="editor" class="editor-surface"><?= \App\Core\Html::postContent($post['content']) ?></div>
                        <textarea id="editor-html" class="form-control d-none" rows="16" spellcheck="false"></textarea>
                        <input type="hidden" name="content" id="content">
                    </div>
                </div>

                <div class="post-form-actions d-flex justify-content-between gap-2 mt-4">
                    <div>
                        <?php if (!$isNew): ?>
                            <button class="btn btn-outline-danger" type="submit" form="delete-post-form">Excluir</button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary" href="/admin/posts">Cancelar</a>
                        <button class="btn btn-primary" type="submit" id="post-form-submit" disabled>Carregando editor...</button>
                    </div>
                </div>
            </form>
            <?php if (!$isNew): ?>
                <form id="delete-post-form" method="post" action="/admin/posts/<?= (int) $post['id'] ?>/delete" onsubmit="return confirm('Excluir este post?');"></form>
            <?php endif; ?>
            <?php require __DIR__ . '/partials/media-library-modal.php'; ?>
            <div class="modal fade" id="gallery-picker-modal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Inserir galeria</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="gallery-picker-list"></div>
                    </div>
                </div>
            </div>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/tinymce@8.7.0/tinymce.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce-i18n@26.9.14/langs8/pt-BR.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="/assets/js/media-library.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/media-library.js') ?: '1' ?>"></script>
    <script src="/assets/js/gallery-picker.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/gallery-picker.js') ?: '1' ?>"></script>
    <script>
        // Faz o upload de um arquivo pro endpoint de imagem do post e chama
        // onDone(url) quando terminar — mesma logica que ja existia no
        // handler de imagem do Quill, so extraida pra reaproveitar tanto no
        // botao "Inserir imagem" da toolbar quanto em qualquer outro lugar
        // que precise do mesmo fluxo.
        function uploadContentImage(file, onDone) {
            var formData = new FormData();
            formData.append('image', file);
            fetch('/admin/posts/upload-image', { method: 'POST', body: formData })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok || !payload.url) {
                            throw new Error(payload.error || 'Falha no envio da imagem');
                        }
                        onDone(payload.url);
                    });
                })
                .catch(function (error) {
                    alert(error.message || 'Falha no envio da imagem');
                });
        }

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
            toolbar: 'blocks | bold italic underline | bullist numlist | link uploadimage | table | removeformat',
            block_formats: 'Parágrafo=p; Título 2=h2; Título 3=h3; Citação=blockquote; Código=pre',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 16px; line-height: 1.6; } table { width: 100%; border-collapse: collapse; margin: 1rem 0; } th, td { border: 1px solid #d1d5db; padding: 0.5rem 0.75rem; text-align: left; } th { background: rgba(0,0,0,0.04); font-weight: 600; } pre { background: #f1f5f9; border: 1px solid #d1d5db; border-radius: 6px; padding: 1rem; overflow-x: auto; } code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }',
            setup: function (editor) {
                editor.ui.registry.addButton('uploadimage', {
                    icon: 'image',
                    tooltip: 'Inserir imagem',
                    onAction: function () {
                        var input = document.createElement('input');
                        input.type = 'file';
                        input.accept = 'image/jpeg,image/png,image/webp';
                        input.onchange = function () {
                            if (!input.files || !input.files[0]) {
                                return;
                            }
                            uploadContentImage(input.files[0], function (url) {
                                editor.focus();
                                editor.insertContent('<img src="' + url + '">');
                            });
                        };
                        input.click();
                    }
                });
            }
        }).then(function (editors) {
            tinyEditor = editors[0];

            // O formulario comeca com o botao "Salvar" desabilitado porque
            // tinymce.init() e assincrono (diferente do Quill, que
            // inicializava na hora) — sem isso, salvar rapido demais (ou um
            // duplo-clique logo apos a pagina carregar) tentaria ler
            // tinyEditor.getContent() antes dele existir.
            var submitButton = document.getElementById('post-form-submit');
            submitButton.disabled = false;
            submitButton.textContent = 'Salvar';

            document.getElementById('open-media-library').addEventListener('click', function () {
                MediaLibrary.open(function (item) {
                    // O modal do Bootstrap rouba o foco da pagina — sem
                    // focus() explicito, insertContent() pode inserir no
                    // ultimo lugar que o navegador lembra (nem sempre o
                    // cursor onde o usuario estava antes de abrir o modal).
                    tinyEditor.focus();

                    if (item.kind === 'image') {
                        tinyEditor.insertContent('<img src="' + item.url + '" alt="' + (item.alt_text || '') + '">');
                    } else {
                        tinyEditor.insertContent('<a href="' + item.url + '">' + item.original_name + '</a>');
                    }
                });
            });

            document.getElementById('insert-gallery').addEventListener('click', function () {
                GalleryPicker.open(function (slug) {
                    tinyEditor.focus();
                    tinyEditor.insertContent('[@' + slug + '@]');
                });
            });

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
        });

        var cropper = null;
        var cropSource = document.getElementById('crop_source');
        var preview = document.getElementById('featured_preview');
        var imageInput = document.getElementById('featured_image_upload');
        var croppedInput = document.getElementById('featured_image_data');
        // Fica true tanto ao escolher um arquivo novo quanto ao escolher uma
        // imagem da biblioteca — nos dois casos precisa exportar o recorte
        // no submit. Sem isso (so olhando imageInput.files, como era antes),
        // uma imagem escolhida na biblioteca nunca seria salva.
        var featuredImageSourceChanged = false;
        var categoryButton = document.getElementById('category-dropdown-button');
        var categoryCheckboxes = document.querySelectorAll('.category-checkbox');

        function updateCategoryButton() {
            var selected = Array.prototype.slice.call(categoryCheckboxes)
                .filter(function (checkbox) {
                    return checkbox.checked;
                })
                .map(function (checkbox) {
                    return checkbox.getAttribute('data-label');
                });

            categoryButton.textContent = selected.length ? selected.join(', ') : 'Selecione categorias';
        }

        categoryCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', updateCategoryButton);
        });

        updateCategoryButton();

        function startCropper() {
            if (cropper) {
                cropper.destroy();
            }

            cropper = new Cropper(cropSource, {
                aspectRatio: 1200 / 520,
                viewMode: 1,
                autoCropArea: 1,
                responsive: true,
                crop: updatePreview
            });
        }

        function updatePreview() {
            if (!cropper) {
                return;
            }

            var canvas = cropper.getCroppedCanvas({
                width: 1200,
                height: 520,
                imageSmoothingQuality: 'high'
            });

            if (canvas) {
                preview.src = canvas.toDataURL('image/jpeg', 0.88);
            }
        }

        startCropper();

        imageInput.addEventListener('change', function () {
            var file = imageInput.files[0];

            if (!file) {
                return;
            }

            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                alert('Use uma imagem JPG, PNG ou WebP.');
                imageInput.value = '';
                return;
            }

            if (file.size > 2097152) {
                alert('A imagem deve ter no maximo 2 MB.');
                imageInput.value = '';
                return;
            }

            cropSource.src = URL.createObjectURL(file);
            cropSource.onload = startCropper;
            featuredImageSourceChanged = true;
        });

        document.getElementById('choose-featured-image').addEventListener('click', function () {
            MediaLibrary.open(function (item) {
                if (item.kind !== 'image') {
                    alert('Escolha uma imagem na biblioteca de mídia.');
                    return;
                }

                imageInput.value = '';
                cropSource.src = item.url;
                cropSource.onload = startCropper;
                featuredImageSourceChanged = true;
            });
        });

        document.getElementById('post-form').addEventListener('submit', function () {
            document.getElementById('content').value = htmlView ? editorHtmlTextarea.value : tinyEditor.getContent();

            if (cropper && featuredImageSourceChanged) {
                var canvas = cropper.getCroppedCanvas({
                    width: 1200,
                    height: 520,
                    imageSmoothingQuality: 'high'
                });

                if (canvas) {
                    croppedInput.value = canvas.toDataURL('image/jpeg', 0.88);
                }
            }
        });
    </script>
</body>
</html>
