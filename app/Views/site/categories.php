<?php require dirname(__DIR__) . '/partials/site-top.php'; ?>
    <main class="container-lg py-5">
        <div class="row g-5">
            <div class="col-lg-8">
                <div class="accent mb-2" style="font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;font-weight:600">Categorias</div>
                <h1 class="mb-1" style="font-size:1.9rem;font-weight:700">Tópicos</h1>
                <p class="text-muted mb-4" style="font-size:.9rem">
                    <?= count($categories) ?> categorias · <?= array_sum(array_column($categories, 'total')) ?> posts
                </p>

                <?php if (count($categories) === 0): ?>
                    <p class="text-muted">Nenhuma categoria com posts publicados ainda.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($categories as $cat): ?>
                            <div class="col-sm-6">
                                <a href="/blog/categoria/<?= rawurlencode($cat['slug']) ?>" class="widget d-block mb-0 h-100">
                                    <div class="d-flex justify-content-between align-items-baseline">
                                        <span style="font-size:.95rem;font-weight:600"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="count"><?= (int) $cat['total'] ?></span>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size:.76rem;font-family:var(--mono)">/<?= htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8') ?></div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (count($tags) > 0): ?>
                    <h3 class="widget-title mt-5">Nuvem de tags</h3>
                    <div class="d-flex flex-wrap gap-2">
                        <?php $maxCount = max(array_column($tags, 'total')); ?>
                        <?php foreach ($tags as $tagItem): ?>
                            <?php $size = 0.8 + min((int) $tagItem['total'], $maxCount) * (0.6 / max($maxCount, 1)); ?>
                            <a href="/blog/tag/<?= rawurlencode($tagItem['slug']) ?>" class="widget mb-0 tag-cloud-item" style="padding:.4rem .8rem;border-radius:999px;font-size:<?= round($size, 2) ?>rem;font-weight:500">
                                <span><?= htmlspecialchars($tagItem['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="text-muted num" style="font-size:.7rem"><?= (int) $tagItem['total'] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <?php $sidebarOrder = ['search', 'archive', 'recent']; ?>
                <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
            </div>
        </div>
    </main>
<?php require dirname(__DIR__) . '/partials/site-bottom.php'; ?>
