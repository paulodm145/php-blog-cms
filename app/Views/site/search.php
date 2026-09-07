<?php require dirname(__DIR__) . '/partials/site-top.php'; ?>
    <main class="container-lg py-5">
        <div class="row g-5">
            <div class="col-lg-8">
                <div class="accent mb-2" style="font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;font-weight:600">Busca</div>
                <h1 class="mb-1" style="font-size:1.9rem;font-weight:700">
                    <?= $term !== '' ? 'Resultados para "' . htmlspecialchars($term, ENT_QUOTES, 'UTF-8') . '"' : 'Buscar no blog' ?>
                </h1>
                <p class="text-muted mb-4" style="font-size:.9rem"><?= $totalPosts ?> artigo(s) encontrado(s)</p>

                <?php if ($term === ''): ?>
                    <p class="text-muted">Digite um termo na busca ao lado.</p>
                <?php elseif (count($posts) === 0): ?>
                    <p class="text-muted">Nenhum artigo encontrado para esse termo.</p>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($posts as $post): ?>
                            <div class="col-md-6"><?php require dirname(__DIR__) . '/partials/post-card.php'; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php
                    $pageHref = function (int $page) use ($term): string {
                        return '/blog/busca?q=' . urlencode($term) . '&page=' . $page;
                    };
                    require dirname(__DIR__) . '/partials/pagination.php';
                ?>
            </div>
            <div class="col-lg-4">
                <?php $sidebarOrder = ['search', 'categories', 'recent']; ?>
                <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
            </div>
        </div>
    </main>
<?php require dirname(__DIR__) . '/partials/site-bottom.php'; ?>
