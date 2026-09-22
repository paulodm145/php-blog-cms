<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <a class="text-secondary" href="/admin/galerias">Voltar para galerias</a>
            <div class="d-flex align-items-center gap-2 mt-3 mb-4">
                <h1 class="h3 mb-0"><?= $isNew ? 'Nova galeria' : 'Editar galeria' ?></h1>
                <span class="badge text-bg-secondary">
                    <?php if ($item['kind'] === 'video'): ?>
                        <i class="fa-solid fa-clapperboard me-1"></i>Vídeo
                    <?php else: ?>
                        <i class="fa-solid fa-photo-film me-1"></i>Foto
                    <?php endif; ?>
                </span>
            </div>

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
                <input type="hidden" name="kind" value="<?= htmlspecialchars($item['kind'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="name">Nome</label>
                        <input class="form-control" id="name" name="name" type="text" value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="slug">Slug</label>
                        <input class="form-control" id="slug" name="slug" type="text" value="<?= htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Gerado automaticamente se ficar vazio">
                    </div>

                    <?php if ($item['kind'] === 'video'): ?>
                    <div class="col-12">
                        <label class="form-label d-block">Vídeos</label>
                        <p class="text-secondary small mb-2">Cole um link do YouTube ou do Google Drive. Arraste os cartões pra reordenar.</p>
                        <input type="hidden" id="gallery_videos_json" name="gallery_videos_json" value="">
                        <div id="video-cards" class="d-flex flex-wrap gap-2 mb-3">
                            <?php foreach ($item['videos'] ?? [] as $video): ?>
                                <div class="video-card" draggable="true"
                                     data-url="<?= htmlspecialchars($video['url'], ENT_QUOTES, 'UTF-8') ?>"
                                     data-title="<?= htmlspecialchars($video['name'], ENT_QUOTES, 'UTF-8') ?>"
                                     data-provider="<?= htmlspecialchars($video['provider'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                     data-thumbnail-media-id="<?= (int) ($video['thumbnail_media_id'] ?? 0) ?>"
                                     data-thumbnail-url="<?= htmlspecialchars($video['thumbnail_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if (!empty($video['thumbnail_url'])): ?>
                                        <img src="<?= htmlspecialchars($video['thumbnail_url'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                                    <?php else: ?>
                                        <span class="video-card-placeholder"><i class="fa-solid fa-circle-play"></i></span>
                                    <?php endif; ?>
                                    <span class="video-card-title"><?= htmlspecialchars($video['name'] ?: '(sem título)', ENT_QUOTES, 'UTF-8') ?></span>
                                    <button type="button" class="video-card-remove" title="Remover">&times;</button>
                                    <?php if (($video['provider'] ?? '') === 'google_drive' && empty($video['thumbnail_media_id'])): ?>
                                        <button type="button" class="video-card-set-thumbnail" title="Escolher miniatura"><i class="fa-solid fa-image"></i></button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2 align-items-end border rounded p-3">
                            <div class="flex-grow-1" style="min-width:220px">
                                <label class="form-label small" for="new-video-url">URL do vídeo</label>
                                <input class="form-control" id="new-video-url" type="url" placeholder="https://youtube.com/watch?v=... ou https://drive.google.com/file/d/...">
                            </div>
                            <div class="flex-grow-1" style="min-width:180px">
                                <label class="form-label small" for="new-video-title">Título (opcional)</label>
                                <input class="form-control" id="new-video-title" type="text">
                            </div>
                            <button class="btn btn-outline-secondary" type="button" id="add-video">
                                <i class="fa-solid fa-plus me-1"></i> Adicionar vídeo
                            </button>
                        </div>
                        <p class="text-danger small mt-2 mb-0 d-none" id="video-error"></p>
                    </div>
                    <?php else: ?>
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
                    <?php endif; ?>
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

        <?php if ($item['kind'] === 'video'): ?>
        // Mesma regra de deteccao do App\Core\VideoEmbed, so pra decidir
        // na hora se oferece o passo de escolher miniatura — a validacao
        // de verdade acontece sempre no servidor ao salvar.
        function detectVideoProvider(url) {
            var youtube = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/);
            if (youtube) {
                return { provider: 'youtube', id: youtube[1] };
            }
            var drive = url.match(/drive\.google\.com\/(?:file\/d\/|open\?id=)([a-zA-Z0-9_-]+)/);
            if (drive) {
                return { provider: 'google_drive', id: drive[1] };
            }
            return null;
        }

        var videoCardsWrap = document.getElementById('video-cards');
        var videoErrorEl = document.getElementById('video-error');
        var draggedVideoCard = null;

        function updateVideosInput() {
            var videos = Array.prototype.map.call(videoCardsWrap.querySelectorAll('.video-card'), function (card) {
                var thumbId = parseInt(card.getAttribute('data-thumbnail-media-id'), 10);
                return {
                    url: card.getAttribute('data-url'),
                    title: card.getAttribute('data-title') || '',
                    thumbnail_media_id: thumbId > 0 ? thumbId : null,
                };
            });
            document.getElementById('gallery_videos_json').value = JSON.stringify(videos);
        }

        function bindVideoCardRemove(button) {
            button.addEventListener('click', function () {
                button.closest('.video-card').remove();
                updateVideosInput();
            });
        }

        // Escolher miniatura e sempre uma acao separada, sobre um cartao
        // que ja existe -- nunca bloqueia nem arrisca perder o video em
        // si. Fechar o modal sem selecionar nada so nao muda nada (o
        // callback do MediaLibrary.open() so roda quando o usuario clica
        // "Inserir" de verdade; sem isso aqui, o video inteiro sumia sem
        // aviso se o admin abrisse o seletor e desistisse).
        function bindVideoCardSetThumbnail(button) {
            button.addEventListener('click', function () {
                var card = button.closest('.video-card');

                MediaLibrary.open(function (item) {
                    if (item.kind !== 'image') {
                        alert('A miniatura precisa ser uma imagem.');
                        return;
                    }

                    card.setAttribute('data-thumbnail-media-id', item.id);
                    card.setAttribute('data-thumbnail-url', item.url);

                    var placeholder = card.querySelector('.video-card-placeholder');
                    var img = document.createElement('img');
                    img.src = item.url;
                    img.alt = '';

                    if (placeholder) {
                        placeholder.replaceWith(img);
                    } else {
                        card.querySelector('img').replaceWith(img);
                    }

                    button.remove();
                    updateVideosInput();
                });
            });
        }

        function bindVideoCardDrag(card) {
            card.addEventListener('dragstart', function () {
                draggedVideoCard = card;
                card.classList.add('dragging');
            });

            card.addEventListener('dragend', function () {
                card.classList.remove('dragging');
                draggedVideoCard = null;
                updateVideosInput();
            });

            card.addEventListener('dragover', function (event) {
                event.preventDefault();

                if (!draggedVideoCard || draggedVideoCard === card) {
                    return;
                }

                var rect = card.getBoundingClientRect();
                var isAfter = (event.clientX - rect.left) > (rect.width / 2);

                if (isAfter) {
                    card.parentNode.insertBefore(draggedVideoCard, card.nextSibling);
                } else {
                    card.parentNode.insertBefore(draggedVideoCard, card);
                }
            });
        }

        function addVideoCard(url, title, thumbnailMediaId, thumbnailUrl, provider) {
            var card = document.createElement('div');
            card.className = 'video-card';
            card.setAttribute('draggable', 'true');
            card.setAttribute('data-url', url);
            card.setAttribute('data-title', title);
            card.setAttribute('data-provider', provider || '');
            card.setAttribute('data-thumbnail-media-id', thumbnailMediaId || 0);
            card.setAttribute('data-thumbnail-url', thumbnailUrl || '');

            var thumbHtml = thumbnailUrl
                ? '<img src="' + thumbnailUrl + '" alt="">'
                : '<span class="video-card-placeholder"><i class="fa-solid fa-circle-play"></i></span>';

            var thumbnailButtonHtml = (provider === 'google_drive' && !thumbnailMediaId)
                ? '<button type="button" class="video-card-set-thumbnail" title="Escolher miniatura"><i class="fa-solid fa-image"></i></button>'
                : '';

            card.innerHTML = thumbHtml
                + '<span class="video-card-title">' + (title || '(sem título)') + '</span>'
                + '<button type="button" class="video-card-remove" title="Remover">&times;</button>'
                + thumbnailButtonHtml;

            videoCardsWrap.appendChild(card);
            bindVideoCardRemove(card.querySelector('.video-card-remove'));
            bindVideoCardDrag(card);

            var setThumbnailButton = card.querySelector('.video-card-set-thumbnail');
            if (setThumbnailButton) {
                bindVideoCardSetThumbnail(setThumbnailButton);
            }

            updateVideosInput();
        }

        document.getElementById('add-video').addEventListener('click', function () {
            var urlInput = document.getElementById('new-video-url');
            var titleInput = document.getElementById('new-video-title');
            var url = urlInput.value.trim();
            var title = titleInput.value.trim();

            videoErrorEl.classList.add('d-none');

            var detected = detectVideoProvider(url);

            if (!detected) {
                videoErrorEl.textContent = 'Essa URL não parece ser do YouTube nem do Google Drive.';
                videoErrorEl.classList.remove('d-none');
                return;
            }

            // O video e adicionado na hora, sempre — escolher miniatura
            // pro Google Drive e uma acao separada e opcional depois
            // (botao no proprio cartao), nunca uma etapa que pode fazer
            // o video inteiro se perder se o admin desistir no meio.
            var autoThumb = detected.provider === 'youtube'
                ? 'https://img.youtube.com/vi/' + detected.id + '/hqdefault.jpg'
                : '';
            addVideoCard(url, title, null, autoThumb, detected.provider);
            urlInput.value = '';
            titleInput.value = '';
        });

        videoCardsWrap.querySelectorAll('.video-card').forEach(function (card) {
            bindVideoCardRemove(card.querySelector('.video-card-remove'));
            bindVideoCardDrag(card);

            var setThumbnailButton = card.querySelector('.video-card-set-thumbnail');
            if (setThumbnailButton) {
                bindVideoCardSetThumbnail(setThumbnailButton);
            }
        });

        updateVideosInput();

        document.getElementById('gallery-form').addEventListener('submit', function (event) {
            updateVideosInput();

            if (videoCardsWrap.querySelectorAll('.video-card').length === 0) {
                event.preventDefault();
                videoErrorEl.textContent = 'Adicione pelo menos um vídeo antes de salvar.';
                videoErrorEl.classList.remove('d-none');
            }
        });
        <?php else: ?>
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
        <?php endif; ?>
    </script>
</body>
</html>
