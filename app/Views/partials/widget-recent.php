<?php if (!empty($sidebar['recent'])): ?>
<div class="widget">
    <h4 class="widget-title">Posts recentes</h4>
    <?php foreach ($sidebar['recent'] as $recentPost): ?>
        <?php $hasThumb = !empty($recentPost['featured_image']) && $recentPost['featured_image'] !== '/assets/images/blog-feature.svg'; ?>
        <a href="/blog/<?= rawurlencode($recentPost['slug']) ?>" class="recent-item">
            <?php if ($hasThumb): ?>
                <div class="cover recent-thumb">
                    <img src="<?= htmlspecialchars($recentPost['featured_image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($recentPost['title'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
            <?php else: ?>
                <div class="cover recent-thumb" style="background:linear-gradient(125deg,var(--chip),var(--surface))"><div class="cover-stripes"></div></div>
            <?php endif; ?>
            <div class="min-w-0">
                <div class="recent-title"><?= htmlspecialchars($recentPost['title'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="recent-date"><?= \App\Core\Text::shortDate($recentPost['published_at']) ?></div>
            </div>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
