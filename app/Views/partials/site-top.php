<?php

use App\Core\Env;
use App\Core\Text;

$siteName = $settings['site_name'] ?? 'paulorb.dev';
$active = $active ?? '';
$canonical = $canonical ?? null;
$ogType = $ogType ?? 'website';
$robots = $robots ?? null;
$jsonLd = $jsonLd ?? null;
$metaDescription = Text::truncate($description ?? '', 155);

$appUrl = rtrim((string) Env::get('APP_URL', ''), '/');
$imagePath = $image ?? '/assets/images/og-default.png';
$ogImage = preg_match('#^https?://#i', $imagePath) === 1 ? $imagePath : $appUrl . $imagePath;
$pageUrl = $canonical ?? $appUrl;

// Validado de novo aqui (alem de na hora de salvar em SettingRepository) —
// so entra no <script> inline se bater exatamente no formato do GA4, pra
// nunca virar um vetor de injecao mesmo que o valor tenha chegado no banco
// por outro caminho que nao o formulario de configuracoes.
$gaId = $settings['google_analytics_id'] ?? '';
$gaId = preg_match('/^G-[A-Z0-9]+$/', $gaId) === 1 ? $gaId : '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($robots !== null): ?>
        <meta name="robots" content="<?= htmlspecialchars($robots, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <?php if ($canonical !== null): ?>
        <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <!-- Open Graph / Twitter Card: como o link aparece ao compartilhar -->
    <meta property="og:site_name" content="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="<?= htmlspecialchars($ogType, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:locale" content="pt_BR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">

    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon-512.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <?php if ($jsonLd !== null): ?>
        <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endif; ?>

    <?php if ($gaId !== ''): ?>
        <!-- Google Analytics (GA4) — so carrega quando o Measurement ID
             esta configurado em /admin/settings. -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $gaId ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());
            gtag('config', '<?= $gaId ?>');
        </script>
    <?php endif; ?>

    <script>
        (function () {
            try {
                var saved = localStorage.getItem('theme');
                var theme = saved || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,400;8..60,600;8..60,700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/blog-theme.css?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/css/blog-theme.css') ?: '1' ?>" rel="stylesheet">
</head>
<body>
    <header class="site-head py-3">
        <nav class="navbar navbar-expand-md container">
            <a class="brand navbar-brand" href="/">paulorb<span class="accent">.dev</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#site-nav" aria-controls="site-nav" aria-expanded="false" aria-label="Abrir menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="site-nav">
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-md-4 gap-2 py-3 py-md-0">
                    <a class="navlink<?= $active === 'home' ? ' active' : '' ?>" href="/">Artigos</a>
                    <a class="navlink<?= $active === 'projetos' ? ' active' : '' ?>" href="/projetos">Projetos</a>
                    <a class="navlink<?= $active === 'categorias' ? ' active' : '' ?>" href="/categorias">Categorias</a>
                    <a class="navlink<?= $active === 'sobre' ? ' active' : '' ?>" href="/sobre">Sobre</a>
                    <a class="navlink<?= $active === 'curriculo' ? ' active' : '' ?>" href="/curriculo">Currículo</a>
                    <button class="icon-btn" id="theme-toggle" type="button" aria-label="Alternar tema claro/escuro" title="Alternar tema">
                        <i class="fa-solid fa-sun" id="theme-icon-sun"></i>
                        <i class="fa-solid fa-moon d-none" id="theme-icon-moon"></i>
                    </button>
                </div>
            </div>
        </nav>
    </header>
