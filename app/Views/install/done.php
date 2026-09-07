<?php

/** @var string $email */
/** @var bool $keyRemoved */
/** @var bool $envSaved */

ob_start();

if (!$envSaved):
?>
<h2>Nao foi possivel concluir</h2>
<p class="alert alert--warn">
    Nao foi possivel gravar <code>APP_INSTALLED=true</code> no arquivo <code>.env</code>.
    Edite o arquivo por FTP acrescentando essa linha e depois tente novamente.
</p>

<div class="actions">
    <a class="button" href="/install/done">Tentar novamente</a>
</div>
<?php
else:
?>
<h2>Instalacao concluida</h2>
<p>O site esta no ar e o instalador foi desativado.</p>

<p class="alert alert--warn">
    Configure o e-mail de contato e o numero de WhatsApp do site em <code>/admin/settings</code>
    antes de divulgar o site.
</p>

<?php if (!$keyRemoved): ?>
    <p class="alert alert--warn">Nao foi possivel apagar o arquivo <code>.env.install</code>. Remova-o por FTP.</p>
<?php endif; ?>

<p>Entre no painel com <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong> e a senha que voce acabou de definir.</p>

<div class="actions">
    <a class="button" href="/admin">Ir para o painel</a>
    <a class="button button--ghost" href="/">Ver o site</a>
</div>
<?php
endif;
$content = ob_get_clean();

require __DIR__ . '/layout.php';
