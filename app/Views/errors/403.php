<?php
$title = 'Acesso negado | ' . ($settings['site_name'] ?? 'paulorb.dev');
$description = '';
$active = '';
$robots = 'noindex,follow';
require dirname(__DIR__) . '/partials/site-top.php';
?>
    <main class="container-lg py-5 text-center" style="max-width:520px;margin:0 auto">
        <div class="accent mb-2" style="font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;font-weight:600">Erro 403</div>
        <h1 class="mb-3" style="font-size:1.9rem;font-weight:700">Acesso negado.</h1>
        <p class="text-muted mb-4">Você não tem permissão para acessar esta página.</p>
        <a class="btn-accent" style="display:inline-block" href="/">Voltar ao início</a>
    </main>
<?php require dirname(__DIR__) . '/partials/site-bottom.php'; ?>
