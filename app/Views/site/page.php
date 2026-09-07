<?php

use App\Core\Html;

require dirname(__DIR__) . '/partials/site-top.php';
?>
    <main class="container-lg py-5">
        <div class="row g-5">
            <div class="col-lg-8">
                <div class="accent mb-2" style="font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;font-weight:600"><?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?></div>
                <h1 class="mb-4" style="font-size:2.1rem;font-weight:700;line-height:1.15"><?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="prose"><?= Html::postContent((string) $page['content']) ?></div>
            </div>
            <div class="col-lg-4">
                <?php $sidebarOrder = ['about', 'categories', 'archive', 'recent']; ?>
                <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
            </div>
        </div>
    </main>
<?php require dirname(__DIR__) . '/partials/site-bottom.php'; ?>
