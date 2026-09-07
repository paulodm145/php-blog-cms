<?php

use App\Core\Text;

$postUrl = '/blog/' . rawurlencode($post['slug']);
$hasImage = !empty($post['featured_image']) && $post['featured_image'] !== '/assets/images/blog-feature.svg';
$hue = 190 + (crc32($post['slug']) % 60);
?>
<article>
    <a href="<?= $postUrl ?>" class="d-block cover" style="height:150px;<?= $hasImage ? '' : 'background:linear-gradient(125deg, hsl(' . $hue . ' 30% 77%), hsl(' . ($hue - 8) . ' 22% 89%))' ?>">
        <?php if ($hasImage): ?>
            <img src="<?= htmlspecialchars($post['featured_image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
        <?php else: ?>
            <div class="cover-stripes"></div>
        <?php endif; ?>
    </a>
    <div class="mt-3">
        <div class="post-meta-line d-flex gap-2 align-items-center mb-2 num">
            <span><?= Text::shortDate($post['published_at']) ?></span>
            <span class="opacity-50">·</span>
            <span><?= Text::readingTime((string) ($post['content'] ?? '')) ?></span>
        </div>
        <h3 class="post-card-title"><a href="<?= $postUrl ?>"><?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?></a></h3>
        <p class="post-card-excerpt"><?= htmlspecialchars((string) $post['excerpt'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php if (!empty($post['category_name'])): ?>
            <span class="chip-pill"><?= htmlspecialchars($post['category_name'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>
</article>
