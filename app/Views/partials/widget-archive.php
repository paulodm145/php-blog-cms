<?php if (!empty($sidebar['archive'])): ?>
<div class="widget">
    <h4 class="widget-title">Arquivo</h4>
    <?php $currentYear = (int) date('Y'); ?>
    <?php foreach ($sidebar['archive'] as $year): ?>
        <details class="archive-year-group"<?= (int) $year['year'] === $currentYear ? ' open' : '' ?>>
            <summary class="archive-year">
                <span><?= (int) $year['year'] ?> <span class="text-muted num">(<?= (int) $year['count'] ?>)</span></span>
                <i class="fa-solid fa-chevron-down archive-year-caret" aria-hidden="true"></i>
            </summary>
            <div class="archive-months">
                <?php foreach ($year['months'] as $month): ?>
                    <a class="archive-month" href="/blog/<?= $month['year'] ?>/<?= str_pad((string) $month['month'], 2, '0', STR_PAD_LEFT) ?>">
                        <span><?= htmlspecialchars($month['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="text-muted num">(<?= $month['count'] ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </details>
    <?php endforeach; ?>
</div>
<?php endif; ?>
