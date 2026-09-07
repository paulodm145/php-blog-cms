<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <h1 class="h4 mb-3">Atualizar banco de dados</h1>

            <?php if ($pending === []): ?>
                <div class="alert alert-success">Nenhuma migration pendente. O banco esta atualizado.</div>
                <a class="btn btn-outline-secondary" href="/admin">Voltar ao painel</a>
            <?php else: ?>
                <p class="text-muted">
                    <?= count($pending) ?> migration(s) pendente(s). Faca backup do banco antes de continuar.
                </p>

                <link rel="stylesheet" href="/assets/css/install.css">

                <div class="progress"><div class="progress__bar" id="migration-bar"></div></div>
                <p class="field__hint"><span id="migration-counter">0 / <?= count($pending) ?></span> migrations</p>

                <ul class="migrations" id="migration-list" data-total="<?= count($pending) ?>">
                    <?php foreach ($pending as $name): ?>
                        <li data-migration="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" data-status="pending">
                            <span class="migrations__mark"></span>
                            <span class="migrations__name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="migrations__time"></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="actions" id="migration-actions"></div>

            <?php endif; ?>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
<?php if ($pending !== []): ?>
<script src="/assets/js/migrate-progress.js"></script>
<script>
    window.startMigrationProgress({
        endpoint: '/admin/atualizar/run',
        nextUrl: '/admin/atualizar'
    });
</script>
<?php endif; ?>
</body>
</html>
