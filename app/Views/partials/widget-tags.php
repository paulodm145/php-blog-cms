<?php if (!empty($sidebar['tags'])): ?>
<div class="widget">
    <h4 class="widget-title">Tags</h4>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach (array_slice($sidebar['tags'], 0, 9) as $tagItem): ?>
            <a href="/blog/tag/<?= rawurlencode($tagItem['slug']) ?>" class="chip-pill"><?= htmlspecialchars($tagItem['name'], ENT_QUOTES, 'UTF-8') ?></a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
