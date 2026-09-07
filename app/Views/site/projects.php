<?php require dirname(__DIR__) . '/partials/site-top.php'; ?>
    <main class="container-lg py-5">
        <div class="row g-5">
            <div class="col-lg-8">
                <div class="accent mb-2" style="font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;font-weight:600">Portfólio</div>
                <h1 class="mb-4" style="font-size:1.9rem;font-weight:700">Projetos</h1>

                <?php if (count($projects) === 0): ?>
                    <p class="text-muted">Nenhum projeto publicado ainda.</p>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($projects as $project): ?>
                            <div class="col-md-6"><?php require dirname(__DIR__) . '/partials/project-card.php'; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php
                    $pageHref = function (int $page) use ($basePath): string {
                        return $page === 1 ? $basePath : $basePath . '/page/' . $page;
                    };
                    require dirname(__DIR__) . '/partials/pagination.php';
                ?>
            </div>
            <div class="col-lg-4">
                <?php $sidebarOrder = ['search', 'categories', 'archive', 'recent', 'tags']; ?>
                <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
            </div>
        </div>
    </main>
<?php require dirname(__DIR__) . '/partials/site-bottom.php'; ?>
