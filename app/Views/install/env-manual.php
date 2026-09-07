<?php

/** @var string $envContents */

ob_start();
?>
<h2>Envie o arquivo .env manualmente</h2>
<p>Nao foi possivel gravar o arquivo <code>.env</code> na raiz do site. Crie um arquivo com exatamente este conteudo e envie para a raiz por FTP.</p>

<div class="code-block"><?= htmlspecialchars($envContents, ENT_QUOTES, 'UTF-8') ?></div>

<p>O arquivo precisa se chamar <code>.env</code>, com o ponto no inicio. Alguns clientes de FTP escondem arquivos assim; ative a exibicao de arquivos ocultos se nao encontrar.</p>

<form method="post" action="/install/database/confirm">
    <div class="actions">
        <button type="submit" class="button">Ja enviei, continuar</button>
        <a class="button button--ghost" href="/install/database">Voltar</a>
    </div>
</form>
<?php
$content = ob_get_clean();

require __DIR__ . '/layout.php';
