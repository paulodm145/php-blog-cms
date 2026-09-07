<?php

use App\Core\Text;

require dirname(__DIR__) . '/partials/site-top.php';

$hasFeaturedImage = $featured !== null
    && !empty($featured['featured_image'])
    && $featured['featured_image'] !== '/assets/images/blog-feature.svg';
$sidebarOrder = ['search', 'categories', 'archive', 'tags'];
?>
    <main class="container-lg py-5">
        <div class="d-flex align-items-center gap-3 mb-5">
            <?php if (!empty($settings['resume_photo'])): ?>
                <img src="<?= htmlspecialchars($settings['resume_photo'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto de Paulo Roberto Bolsanello" class="resume-photo" style="width:72px;height:72px;flex-shrink:0">
            <?php endif; ?>
            <h1 class="mb-0" style="font-size:clamp(1.4rem,1.1rem+1.2vw,1.9rem);font-weight:700;line-height:1.25;max-width:46ch">
                <?= htmlspecialchars($settings['blog_description'], ENT_QUOTES, 'UTF-8') ?>
            </h1>
        </div>
        <?php if ($featured !== null): ?>
            <article class="row g-4 align-items-center pb-5 mb-5 border-b">
                <div class="col-md-7">
                    <a href="/blog/<?= rawurlencode($featured['slug']) ?>" class="d-block cover" style="height:330px;<?= $hasFeaturedImage ? '' : 'background:linear-gradient(125deg, hsl(212 30% 77%), hsl(204 22% 89%))' ?>">
                        <?php if ($hasFeaturedImage): ?>
                            <img src="<?= htmlspecialchars($featured['featured_image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($featured['title'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <div class="cover-stripes"></div>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="col-md-5 d-flex flex-column justify-content-center">
                    <div class="accent featured-kicker mb-2">★ Em destaque</div>
                    <h2 class="featured-title mb-3"><a href="/blog/<?= rawurlencode($featured['slug']) ?>"><?= htmlspecialchars($featured['title'], ENT_QUOTES, 'UTF-8') ?></a></h2>
                    <p class="text-muted mb-3"><?= htmlspecialchars((string) $featured['excerpt'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="post-meta-line d-flex gap-2 align-items-center mb-3 num">
                        <span><?= Text::shortDate($featured['published_at']) ?></span>
                        <span class="opacity-50">·</span>
                        <span><?= Text::readingTime((string) $featured['content']) ?></span>
                    </div>
                    <?php if (!empty($featured['category_name'])): ?>
                        <div><span class="chip-pill"><?= htmlspecialchars($featured['category_name'], ENT_QUOTES, 'UTF-8') ?></span></div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endif; ?>

        <div class="row g-5">
            <div class="col-lg-8">
                <?php if (count($posts) > 0): ?>
                    <div class="d-flex justify-content-between align-items-baseline mb-4">
                        <h2 class="widget-title mb-0">Mais artigos</h2>
                        <a href="/blog" class="accent" style="font-size:.78rem;font-weight:500">arquivo completo →</a>
                    </div>
                    <div class="row g-4">
                        <?php foreach ($posts as $post): ?>
                            <div class="col-md-6"><?php require dirname(__DIR__) . '/partials/post-card.php'; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($featured === null): ?>
                    <p class="text-muted">Nenhum artigo publicado por aqui ainda.</p>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
            </div>
        </div>
    </main>
<?php require dirname(__DIR__) . '/partials/site-bottom.php'; ?>
