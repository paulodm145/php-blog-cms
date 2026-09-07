<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="robots" content="noindex,nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/blog.css?v=<?= @filemtime(dirname(__DIR__, 4) . '/public/assets/css/blog.css') ?: '1' ?>" rel="stylesheet">
</head>
<body>
<div class="admin-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar d-lg-none">
            <button class="admin-topbar-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar" aria-label="Abrir menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <span class="admin-topbar-brand">paulorb.dev</span>
        </div>
        <main class="admin-content">
            <?php
                $pendingMigrations = 0;

                if ($currentPath !== '/admin/atualizar') {
                    $pendingMigrations = (new App\Core\InstallState(dirname(__DIR__, 4)))->pendingCount();
                }
            ?>
            <?php if ($pendingMigrations > 0): ?>
                <div class="alert alert-warning d-flex justify-content-between align-items-center">
                    <span><?= $pendingMigrations ?> migration(s) pendente(s) apos o ultimo envio de arquivos.</span>
                    <a class="btn btn-sm btn-warning" href="/admin/atualizar">Atualizar banco</a>
                </div>
            <?php endif; ?>
            <section class="content-surface p-4 p-md-5">
