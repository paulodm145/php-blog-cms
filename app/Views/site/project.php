<?php

use App\Core\Auth;
use App\Core\Text;

$hasImage = !empty($project['cover_url']);
$typeLabel = Text::projectTypeLabel($project['project_type']);
require dirname(__DIR__) . '/partials/site-top.php';
?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <div class="bg-surface border-b">
        <div class="container-lg py-5">
            <div class="row">
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="/projetos" class="accent" style="font-size:.78rem;font-weight:500">← voltar</a>
                        <?php if (Auth::check()): ?>
                            <a href="/admin/projetos/<?= (int) $project['id'] ?>/edit" class="chip-pill no-print"><i class="fa-solid fa-pen"></i> Editar projeto</a>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2 flex-wrap mt-3 mb-3">
                        <span class="chip-pill"><?= $project['is_ongoing'] ? 'Em andamento' : 'Concluído' ?></span>
                        <?php if ($typeLabel !== ''): ?>
                            <span class="chip-pill"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php foreach ($project['technology_list'] as $tech): ?>
                            <span class="chip-pill"><?= htmlspecialchars($tech, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                    </div>
                    <h1 class="post-title"><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <?php if ($project['tagline'] !== ''): ?>
                        <p class="text-muted mt-2 mb-0"><?= htmlspecialchars($project['tagline'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <div class="post-meta-line d-flex gap-2 align-items-center num mt-2 flex-wrap">
                        <span><?= htmlspecialchars($project['period'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($project['role'])): ?>
                            <span class="opacity-50">·</span><span><?= htmlspecialchars($project['role'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($project['live_url']) || !empty($project['source_url'])): ?>
                        <div class="d-flex gap-2 align-items-center mt-3">
                            <?php if (!empty($project['live_url'])): ?>
                                <a class="btn-accent" style="display:inline-flex;align-items:center;gap:.4rem" href="<?= htmlspecialchars($project['live_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                    Ver projeto <i class="fa-solid fa-arrow-up-right-from-square fa-2xs"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($project['source_url'])): ?>
                                <a class="chip-pill" href="<?= htmlspecialchars($project['source_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                    <i class="fa-brands fa-github"></i> Ver código
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <main class="container-lg py-5">
        <div class="row g-5">
            <div class="col-lg-8">
                <?php if ($hasImage): ?>
                    <div class="cover mb-4" style="height:320px">
                        <img src="<?= htmlspecialchars($project['cover_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                <?php endif; ?>
                <div class="prose"><?= $content ?></div>

                <?php if (!empty($project['gallery'])): ?>
                    <section class="mt-5 pt-4 border-t">
                        <h3 class="widget-title mb-3">Screenshots</h3>
                        <div class="row g-2">
                            <?php foreach ($project['gallery'] as $index => $image): ?>
                                <div class="col-4 col-md-3">
                                    <button type="button" class="gallery-grid-thumb cover d-block w-100" style="height:90px" data-index="<?= (int) $index ?>" data-url="<?= htmlspecialchars($image['url'], ENT_QUOTES, 'UTF-8') ?>">
                                        <img src="<?= htmlspecialchars($image['url'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <?php $sidebarOrder = ['about', 'categories', 'archive', 'recent']; ?>
                <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
            </div>
        </div>
    </main>

    <?php if (!empty($project['gallery'])): ?>
        <div class="modal fade" id="gallery-lightbox" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content bg-transparent border-0">
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Fechar" style="z-index:10"></button>
                    <div class="modal-body d-flex align-items-center justify-content-center position-relative p-0">
                        <button type="button" class="lightbox-nav lightbox-prev" id="lightbox-prev" aria-label="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
                        <img id="lightbox-image" src="" alt="" class="lightbox-image">
                        <button type="button" class="lightbox-nav lightbox-next" id="lightbox-next" aria-label="Próximo"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                    <div class="text-center text-white-50 small mt-2" id="lightbox-counter"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php require dirname(__DIR__) . '/partials/site-bottom.php'; ?>
<?php if (!empty($project['gallery'])): ?>
<script>
    (function () {
        var thumbs = document.querySelectorAll('.gallery-grid-thumb');

        if (thumbs.length === 0) {
            return;
        }

        var urls = Array.prototype.map.call(thumbs, function (thumb) {
            return thumb.getAttribute('data-url');
        });
        var current = 0;
        var modalEl = document.getElementById('gallery-lightbox');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        var image = document.getElementById('lightbox-image');
        var counter = document.getElementById('lightbox-counter');

        function show(index) {
            current = (index + urls.length) % urls.length;
            image.src = urls[current];
            counter.textContent = (current + 1) + ' / ' + urls.length;
        }

        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                show(parseInt(thumb.getAttribute('data-index'), 10));
                modal.show();
            });
        });

        document.getElementById('lightbox-prev').addEventListener('click', function () {
            show(current - 1);
        });

        document.getElementById('lightbox-next').addEventListener('click', function () {
            show(current + 1);
        });

        document.addEventListener('keydown', function (event) {
            if (!modalEl.classList.contains('show')) {
                return;
            }

            if (event.key === 'ArrowLeft') {
                show(current - 1);
            } else if (event.key === 'ArrowRight') {
                show(current + 1);
            }
        });
    })();
</script>
<?php endif; ?>
