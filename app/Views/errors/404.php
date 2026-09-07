<?php
$title = 'Página não encontrada | ' . ($settings['site_name'] ?? 'paulorb.dev');
$description = '';
$active = '';
$robots = 'noindex,follow';
require dirname(__DIR__) . '/partials/site-top.php';
?>
    <main class="container-lg py-5 text-center" style="max-width:520px;margin:0 auto">
        <div class="accent mb-2" style="font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;font-weight:600">Erro 404</div>
        <h1 class="mb-3" style="font-size:1.9rem;font-weight:700">Página não encontrada.</h1>
        <p class="text-muted mb-4">O endereço pode ter mudado ou não existe mais.</p>
        <a class="btn-accent" style="display:inline-block" href="/">Voltar ao início</a>
    </main>
<?php require dirname(__DIR__) . '/partials/site-bottom.php'; ?>
