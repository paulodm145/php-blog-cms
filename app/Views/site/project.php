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
                    <?php $sourceLinks = $project['source_links'] ?? []; ?>
                    <?php if (!empty($project['live_url']) || count($sourceLinks) > 0): ?>
                        <div class="d-flex gap-2 align-items-center flex-wrap mt-3">
                            <?php if (!empty($project['live_url'])): ?>
                                <a class="btn-accent" style="display:inline-flex;align-items:center;gap:.4rem" href="<?= htmlspecialchars($project['live_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                    Ver projeto <i class="fa-solid fa-arrow-up-right-from-square fa-2xs"></i>
                                </a>
                            <?php endif; ?>
                            <?php foreach ($sourceLinks as $sourceLink): ?>
                                <a class="chip-pill" href="<?= htmlspecialchars($sourceLink['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                    <i class="fa-brands fa-github"></i> <?= htmlspecialchars($sourceLink['label'] !== null && $sourceLink['label'] !== '' ? $sourceLink['label'] : 'Ver código', ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endforeach; ?>
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
                        <?php
                            $galleryKey = 'project-' . $project['id'];
                            $photos = $project['gallery'];
                            require dirname(__DIR__) . '/partials/gallery-grid.php';
                        ?>
                    </section>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <?php $sidebarOrder = ['about', 'categories', 'archive', 'recent']; ?>
                <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
            </div>
        </div>
    </main>

    <?php $hasEmbeddedGallery = strpos($content, 'gallery-grid-thumb') !== false; ?>
    <?php if (!empty($project['gallery']) || $hasEmbeddedGallery): ?>
        <?php require dirname(__DIR__) . '/partials/gallery-lightbox-modal.php'; ?>
    <?php endif; ?>
<?php require dirname(__DIR__) . '/partials/site-bottom.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlightjs-line-numbers.js/2.8.0/highlightjs-line-numbers.min.js"></script>
<script>
    (function () {
        document.querySelectorAll('.prose pre code').forEach(function (block) {
            hljs.highlightElement(block);
            hljs.lineNumbersBlock(block);
        });

        document.querySelectorAll('.code-copy-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                var code = button.closest('.code-window').querySelector('code');
                navigator.clipboard.writeText(code.innerText).then(function () {
                    var original = button.textContent;
                    button.textContent = 'Copiado!';
                    button.classList.add('copied');
                    setTimeout(function () {
                        button.textContent = original;
                        button.classList.remove('copied');
                    }, 1800);
                });
            });
        });
    })();
</script>
<?php if (!empty($project['gallery']) || $hasEmbeddedGallery): ?>
    <script src="/assets/js/gallery-lightbox.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/gallery-lightbox.js') ?: '1' ?>"></script>
<?php endif; ?>
