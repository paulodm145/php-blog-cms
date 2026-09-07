<?php

/** @var array $requirements */
/** @var bool $passes */
/** @var bool $hasKey */
/** @var string|null $error */

ob_start();
?>
<h2>Requisitos do servidor</h2>
<p>Confira os itens abaixo e informe a chave de instalacao definida no arquivo <code>.env.install</code>.</p>

<?php if ($error !== null): ?>
    <p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<ul class="checklist">
    <?php foreach ($requirements as $item): ?>
        <?php
        $mark = $item['ok'] ? 'ok' : ($item['fatal'] ? 'error' : 'warn');
        $symbol = $item['ok'] ? '✓' : ($item['fatal'] ? '✗' : '!');
        ?>
        <li>
            <span class="checklist__mark checklist__mark--<?= $mark ?>"><?= $symbol ?></span>
            <span>
                <strong><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                <p class="checklist__detail"><?= htmlspecialchars($item['detail'], ENT_QUOTES, 'UTF-8') ?></p>
            </span>
        </li>
    <?php endforeach; ?>
</ul>

<?php if (!$passes): ?>
    <p class="alert">Corrija os itens marcados com ✗ antes de continuar. Depois, recarregue esta pagina.</p>
<?php elseif (!$hasKey): ?>
    <p class="alert">
        O arquivo <code>.env.install</code> nao foi encontrado na raiz do site.
        Copie <code>.env.install.example</code>, defina um valor aleatorio em
        <code>INSTALL_KEY</code>, envie o arquivo por FTP e recarregue esta pagina.
    </p>
<?php else: ?>
    <form method="post" action="/install/key">
        <div class="field">
            <label for="install_key">Chave de instalacao</label>
            <input type="password" id="install_key" name="install_key" required autocomplete="off" autofocus>
            <p class="field__hint">O valor de <code>INSTALL_KEY</code> no arquivo <code>.env.install</code> que voce enviou por FTP.</p>
        </div>
        <div class="actions">
            <button type="submit" class="button">Continuar</button>
        </div>
    </form>
<?php endif; ?>
<?php
$content = ob_get_clean();

require __DIR__ . '/layout.php';
