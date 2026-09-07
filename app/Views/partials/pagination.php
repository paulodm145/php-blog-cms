<?php
/**
 * Paginação numérica. Espera $currentPage, $totalPages e $pageHref
 * (closure: int $page -> string url) definidos antes do require.
 * Mostra até 5 números de cada lado da página atual.
 */
if ($totalPages > 1):
    $window = 5;
    $start = max(1, $currentPage - $window);
    $end = min($totalPages, $currentPage + $window);
?>
<nav class="pagination-nav mt-5 pt-4 border-t" aria-label="Paginação">
    <?php if ($currentPage > 1): ?>
        <a class="page-btn page-btn-prev" href="<?= htmlspecialchars($pageHref($currentPage - 1), ENT_QUOTES, 'UTF-8') ?>">
            <i class="fa-solid fa-chevron-left"></i> <span class="d-none d-sm-inline">Anterior</span>
        </a>
    <?php endif; ?>

    <div class="page-numbers">
        <?php if ($start > 1): ?>
            <a class="page-num" href="<?= htmlspecialchars($pageHref(1), ENT_QUOTES, 'UTF-8') ?>">1</a>
            <?php if ($start > 2): ?><span class="page-ellipsis">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($page = $start; $page <= $end; $page++): ?>
            <?php if ($page === $currentPage): ?>
                <span class="page-num page-num-active" aria-current="page"><?= $page ?></span>
            <?php else: ?>
                <a class="page-num" href="<?= htmlspecialchars($pageHref($page), ENT_QUOTES, 'UTF-8') ?>"><?= $page ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
            <a class="page-num" href="<?= htmlspecialchars($pageHref($totalPages), ENT_QUOTES, 'UTF-8') ?>"><?= $totalPages ?></a>
        <?php endif; ?>
    </div>

    <?php if ($currentPage < $totalPages): ?>
        <a class="page-btn page-btn-next" href="<?= htmlspecialchars($pageHref($currentPage + 1), ENT_QUOTES, 'UTF-8') ?>">
            <span class="d-none d-sm-inline">Próximo</span> <i class="fa-solid fa-chevron-right"></i>
        </a>
    <?php endif; ?>
</nav>
<?php endif; ?>
