<?php
/**
 * Paginação com o componente nativo do Bootstrap (.pagination), usada nas
 * telas do admin — mesmo estilo ja usado em admin/posts.php, so que
 * generalizada com o mesmo contrato da partial publica (partials/
 * pagination.php): espera $currentPage, $totalPages e $pageHref (closure:
 * int $page -> string url) definidos antes do require.
 */
if ($totalPages > 1):
    $window = 2;
    $start = max(1, $currentPage - $window);
    $end = min($totalPages, $currentPage + $window);
?>
<nav class="mt-4" aria-label="Paginação">
    <ul class="pagination justify-content-center mb-0">
        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($pageHref(max(1, $currentPage - 1)), ENT_QUOTES, 'UTF-8') ?>" aria-label="Anterior">&laquo;</a>
        </li>

        <?php if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($pageHref(1), ENT_QUOTES, 'UTF-8') ?>">1</a></li>
            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <?php endif; ?>

        <?php for ($page = $start; $page <= $end; $page++): ?>
            <li class="page-item <?= $page === $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars($pageHref($page), ENT_QUOTES, 'UTF-8') ?>" <?= $page === $currentPage ? 'aria-current="page"' : '' ?>><?= $page ?></a>
            </li>
        <?php endfor; ?>

        <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($pageHref($totalPages), ENT_QUOTES, 'UTF-8') ?>"><?= $totalPages ?></a></li>
        <?php endif; ?>

        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($pageHref(min($totalPages, $currentPage + 1)), ENT_QUOTES, 'UTF-8') ?>" aria-label="Próximo">&raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
