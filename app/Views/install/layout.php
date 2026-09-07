<?php

/** @var string $title */
/** @var int $step */
/** @var string $content */

$steps = [
    1 => 'Requisitos',
    2 => 'Banco de dados',
    3 => 'Migrations',
    4 => 'Administrador',
    5 => 'Concluido',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/install.css">
</head>
<body>
    <main class="install">
        <h1 class="install__brand">Instalacao</h1>
        <ol class="steps">
            <?php foreach ($steps as $number => $label): ?>
                <li class="steps__item<?= $number === $step ? ' steps__item--current' : '' ?><?= $number < $step ? ' steps__item--done' : '' ?>">
                    <span class="steps__number"><?= $number ?></span>
                    <span class="steps__label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
        <section class="panel">
            <?= $content ?>
        </section>
    </main>
</body>
</html>
