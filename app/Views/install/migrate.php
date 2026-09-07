<?php

/** @var array $pending */

ob_start();
?>
<h2>Criando as tabelas</h2>
<p>Cada arquivo de migration e executado e registrado separadamente. Nao feche esta pagina.</p>

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

<script src="/assets/js/migrate-progress.js"></script>
<script>
    window.startMigrationProgress({
        endpoint: '/install/migrate/run',
        nextUrl: '/install/admin'
    });
</script>
<?php
$content = ob_get_clean();

require __DIR__ . '/layout.php';
