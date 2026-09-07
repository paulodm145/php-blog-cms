<?php if (!empty($sidebar['categories'])): ?>
<div class="widget">
    <h4 class="widget-title">Categorias</h4>
    <ul class="widget-list">
        <?php foreach ($sidebar['categories'] as $cat): ?>
            <li>
                <a href="/blog/categoria/<?= rawurlencode($cat['slug']) ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></a>
                <span class="count"><?= (int) $cat['total'] ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
