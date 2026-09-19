<?php

use App\Repositories\CommentRepository;

$pendingComments = 0;

try {
    $pendingComments = (new CommentRepository())->countPending();
} catch (\Throwable $exception) {
    $pendingComments = 0;
}

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$currentPath = $currentPath === '/' ? '/' : rtrim($currentPath, '/');

$isActive = function (string $prefix) use ($currentPath): bool {
    if ($prefix === '/admin') {
        return $currentPath === '/admin';
    }

    return strpos($currentPath, $prefix) === 0;
};

$navItems = [
    ['href' => '/admin', 'icon' => 'fa-gauge', 'label' => 'Painel', 'prefix' => '/admin'],
    ['href' => '/admin/posts', 'icon' => 'fa-newspaper', 'label' => 'Posts', 'prefix' => '/admin/posts'],
    ['href' => '/admin/media', 'icon' => 'fa-images', 'label' => 'Mídia', 'prefix' => '/admin/media'],
    ['href' => '/admin/galerias', 'icon' => 'fa-photo-film', 'label' => 'Galerias', 'prefix' => '/admin/galerias'],
    ['href' => '/admin/categories', 'icon' => 'fa-tags', 'label' => 'Categorias', 'prefix' => '/admin/categories'],
    ['href' => '/admin/paginas', 'icon' => 'fa-file-lines', 'label' => 'Páginas', 'prefix' => '/admin/paginas'],
    ['href' => '/admin/projetos', 'icon' => 'fa-diagram-project', 'label' => 'Projetos', 'prefix' => '/admin/projetos'],
    ['href' => '/admin/curriculo', 'icon' => 'fa-id-card', 'label' => 'Currículo', 'prefix' => '/admin/curriculo'],
    ['href' => '/admin/comentarios', 'icon' => 'fa-comments', 'label' => 'Comentários', 'prefix' => '/admin/comentarios', 'badge' => $pendingComments],
    ['href' => '/admin/users', 'icon' => 'fa-users', 'label' => 'Usuários', 'prefix' => '/admin/users'],
    ['href' => '/admin/settings', 'icon' => 'fa-gear', 'label' => 'Configurações', 'prefix' => '/admin/settings'],
];
?>
<div class="offcanvas-lg offcanvas-start admin-sidebar" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
    <div class="offcanvas-header d-lg-none">
        <h5 class="offcanvas-title" id="adminSidebarLabel">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0">
        <a class="admin-brand d-none d-lg-flex" href="/admin">paulorb<span class="accent">.dev</span></a>
        <nav class="admin-nav">
            <?php foreach ($navItems as $navItem): ?>
                <a class="admin-nav-link<?= $isActive($navItem['prefix']) ? ' active' : '' ?>" href="<?= $navItem['href'] ?>">
                    <span class="admin-nav-label">
                        <i class="fa-solid <?= $navItem['icon'] ?> admin-nav-icon"></i>
                        <?= $navItem['label'] ?>
                    </span>
                    <?php if (!empty($navItem['badge'])): ?>
                        <span class="badge bg-danger rounded-pill"><?= (int) $navItem['badge'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-sidebar-footer">
            <?php if (($user['email'] ?? '') !== ''): ?>
                <div class="admin-sidebar-user"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form method="post" action="/admin/logout">
                <button class="btn btn-outline-light btn-sm w-100" type="submit">
                    <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Sair
                </button>
            </form>
        </div>
    </div>
</div>
