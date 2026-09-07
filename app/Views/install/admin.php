<?php

/** @var array $values */
/** @var string|null $error */

$field = function (string $key) use ($values): string {
    return htmlspecialchars((string) ($values[$key] ?? ''), ENT_QUOTES, 'UTF-8');
};

ob_start();
?>
<h2>Conta de administrador</h2>
<p>Esta e a conta que voce usara para entrar no painel. A conta padrao de demonstracao sera removida.</p>

<?php if ($error !== null): ?>
    <p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post" action="/install/admin">
    <div class="field">
        <label for="name">Nome</label>
        <input type="text" id="name" name="name" value="<?= $field('name') ?>" required autofocus>
    </div>
    <div class="field">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?= $field('email') ?>" required autocomplete="off">
    </div>
    <div class="field">
        <label for="password">Senha</label>
        <input type="password" id="password" name="password" required autocomplete="new-password" minlength="8">
        <p class="field__hint">Minimo de 8 caracteres.</p>
    </div>
    <div class="field">
        <label for="password_confirmation">Repita a senha</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
    </div>
    <div class="actions">
        <button type="submit" class="button">Criar conta e concluir</button>
    </div>
</form>
<?php
$content = ob_get_clean();

require __DIR__ . '/layout.php';
