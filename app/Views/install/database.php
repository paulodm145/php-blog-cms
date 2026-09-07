<?php

/** @var array $values */
/** @var string|null $error */

$field = function (string $key) use ($values): string {
    return htmlspecialchars((string) ($values[$key] ?? ''), ENT_QUOTES, 'UTF-8');
};

ob_start();
?>
<h2>Banco de dados</h2>
<p>Informe os dados do banco MySQL. Ele precisa existir — o instalador cria as tabelas, nao o banco.</p>

<?php if ($error !== null): ?>
    <p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post" action="/install/database" id="db-form">
    <div class="field">
        <label for="APP_URL">Endereco do site</label>
        <input type="url" id="APP_URL" name="APP_URL" value="<?= $field('APP_URL') ?>" required>
        <p class="field__hint">Exemplo: https://seudominio.com</p>
    </div>
    <div class="field">
        <label for="APP_NAME">Nome do site</label>
        <input type="text" id="APP_NAME" name="APP_NAME" value="<?= $field('APP_NAME') ?>" required>
    </div>
    <div class="grid-2">
        <div class="field">
            <label for="DB_HOST">Servidor</label>
            <input type="text" id="DB_HOST" name="DB_HOST" value="<?= $field('DB_HOST') ?>" required>
        </div>
        <div class="field">
            <label for="DB_PORT">Porta</label>
            <input type="text" id="DB_PORT" name="DB_PORT" value="<?= $field('DB_PORT') ?>" required>
        </div>
    </div>
    <div class="field">
        <label for="DB_DATABASE">Banco de dados</label>
        <input type="text" id="DB_DATABASE" name="DB_DATABASE" value="<?= $field('DB_DATABASE') ?>" required>
    </div>
    <div class="grid-2">
        <div class="field">
            <label for="DB_USERNAME">Usuario</label>
            <input type="text" id="DB_USERNAME" name="DB_USERNAME" value="<?= $field('DB_USERNAME') ?>" required autocomplete="off">
        </div>
        <div class="field">
            <label for="DB_PASSWORD">Senha</label>
            <input type="password" id="DB_PASSWORD" name="DB_PASSWORD" autocomplete="off">
        </div>
    </div>

    <p class="alert alert--ok" id="db-feedback" hidden></p>

    <div class="actions">
        <button type="button" class="button button--ghost" id="db-test">Testar conexao</button>
        <button type="submit" class="button">Salvar e continuar</button>
    </div>
</form>

<script>
(function () {
    var form = document.getElementById('db-form');
    var button = document.getElementById('db-test');
    var feedback = document.getElementById('db-feedback');

    button.addEventListener('click', function () {
        button.disabled = true;
        button.textContent = 'Testando...';
        feedback.hidden = true;

        fetch('/install/database/test', {
            method: 'POST',
            body: new FormData(form)
        }).then(function (response) {
            return response.json();
        }).then(function (data) {
            feedback.hidden = false;
            feedback.className = data.ok ? 'alert alert--ok' : 'alert';
            feedback.textContent = data.message;
        }).catch(function () {
            feedback.hidden = false;
            feedback.className = 'alert';
            feedback.textContent = 'Nao foi possivel falar com o servidor.';
        }).then(function () {
            button.disabled = false;
            button.textContent = 'Testar conexao';
        });
    });
})();
</script>
<?php
$content = ob_get_clean();

require __DIR__ . '/layout.php';
