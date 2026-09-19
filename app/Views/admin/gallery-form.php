<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <a class="text-secondary" href="/admin/galerias">Voltar para galerias</a>
            <h1 class="h3 mt-3 mb-4"><?= $isNew ? 'Nova galeria' : 'Editar galeria' ?></h1>

            <?php if (!$isNew && !empty($success)): ?>
                <div class="alert alert-success">Galeria salva com sucesso.</div>
            <?php endif; ?>

            <?php if (!$isNew): ?>
                <div class="alert alert-secondary d-flex align-items-center gap-2 flex-wrap">
                    <span>Código pra usar em posts e projetos:</span>
                    <code class="gallery-shortcode" data-shortcode="[@<?= htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8') ?>@]">[@<?= htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8') ?>@]</code>
                    <button type="button" class="btn btn-sm btn-outline-secondary gallery-copy-shortcode">Copiar</button>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= $isNew ? '/admin/galerias' : '/admin/galerias/' . (int) $item['id'] . '/edit' ?>" id="gallery-form">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="name">Nome</label>
                        <input class="form-control" id="name" name="name" type="text" value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="slug">Slug</label>
                        <input class="form-control" id="slug" name="slug" type="text" value="<?= htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Gerado automaticamente se ficar vazio">
                    </div>
                    <div class="col-12">
                        <label class="form-label d-block">Fotos</label>
                        <p class="text-secondary small mb-2">Arraste os quadradinhos pra reordenar.</p>
                        <input type="hidden" id="gallery_media_ids" name="gallery_media_ids" value="<?= htmlspecialchars(implode(',', array_column($item['photos'] ?? [], 'id')), ENT_QUOTES, 'UTF-8') ?>">
                        <div id="gallery-thumbs" class="d-flex flex-wrap gap-2 mb-2">
                            <?php foreach ($item['photos'] ?? [] as $photo): ?>
                                <div class="gallery-thumb" data-id="<?= (int) $photo['id'] ?>" draggable="true">
                                    <img src="<?= htmlspecialchars($photo['thumbnail_url'] ?: $photo['url'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                                    <button type="button" class="gallery-thumb-remove" title="Remover">&times;</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="add-gallery-photo">
                            <i class="fa-solid fa-photo-film me-1"></i> Adicionar foto
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <?php if (!$isNew): ?>
                            <button class="btn btn-outline-danger" type="submit" form="delete-gallery-form">Excluir</button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary" href="/admin/galerias">Cancelar</a>
                        <button class="btn btn-primary" type="submit">Salvar</button>
                    </div>
                </div>
            </form>
            <?php if (!$isNew): ?>
                <form id="delete-gallery-form" method="post" action="/admin/galerias/<?= (int) $item['id'] ?>/delete" onsubmit="return confirm('Excluir esta galeria? Qualquer [@<?= htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8') ?>@] em posts ou projetos vai parar de aparecer.');"></form>
            <?php endif; ?>
            <?php require __DIR__ . '/partials/media-library-modal.php'; ?>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="/assets/js/media-library.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/media-library.js') ?: '1' ?>"></script>
    <script>
        var galleryThumbsWrap = document.getElementById('gallery-thumbs');
        var draggedThumb = null;

        function updateGalleryInput() {
            var ids = Array.prototype.map.call(galleryThumbsWrap.querySelectorAll('.gallery-thumb'), function (el) {
                return el.getAttribute('data-id');
            });
            document.getElementById('gallery_media_ids').value = ids.join(',');
        }

        function bindGalleryRemove(button) {
            button.addEventListener('click', function () {
                button.closest('.gallery-thumb').remove();
                updateGalleryInput();
            });
        }

        function bindGalleryDrag(thumb) {
            thumb.addEventListener('dragstart', function () {
                draggedThumb = thumb;
                thumb.classList.add('dragging');
            });

            thumb.addEventListener('dragend', function () {
                thumb.classList.remove('dragging');
                draggedThumb = null;
                updateGalleryInput();
            });

            thumb.addEventListener('dragover', function (event) {
                event.preventDefault();

                if (!draggedThumb || draggedThumb === thumb) {
                    return;
                }

                var rect = thumb.getBoundingClientRect();
                var isAfter = (event.clientX - rect.left) > (rect.width / 2);

                if (isAfter) {
                    thumb.parentNode.insertBefore(draggedThumb, thumb.nextSibling);
                } else {
                    thumb.parentNode.insertBefore(draggedThumb, thumb);
                }
            });
        }

        document.getElementById('add-gallery-photo').addEventListener('click', function () {
            MediaLibrary.open(function (item) {
                if (item.kind !== 'image') {
                    alert('A galeria aceita só imagens.');
                    return;
                }

                var thumb = document.createElement('div');
                thumb.className = 'gallery-thumb';
                thumb.setAttribute('data-id', item.id);
                thumb.setAttribute('draggable', 'true');
                thumb.innerHTML = '<img src="' + item.url + '" alt="">'
                    + '<button type="button" class="gallery-thumb-remove" title="Remover">&times;</button>';
                galleryThumbsWrap.appendChild(thumb);
                bindGalleryRemove(thumb.querySelector('.gallery-thumb-remove'));
                bindGalleryDrag(thumb);
                updateGalleryInput();
            });
        });

        galleryThumbsWrap.querySelectorAll('.gallery-thumb').forEach(function (thumb) {
            bindGalleryRemove(thumb.querySelector('.gallery-thumb-remove'));
            bindGalleryDrag(thumb);
        });

        document.querySelectorAll('.gallery-copy-shortcode').forEach(function (button) {
            button.addEventListener('click', function () {
                var code = button.parentNode.querySelector('.gallery-shortcode').getAttribute('data-shortcode');
                navigator.clipboard.writeText(code).then(function () {
                    var original = button.textContent;
                    button.textContent = 'Copiado!';
                    setTimeout(function () {
                        button.textContent = original;
                    }, 1800);
                });
            });
        });
    </script>
</body>
</html>
