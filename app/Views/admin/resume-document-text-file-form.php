<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <a class="text-secondary" href="<?= htmlspecialchars($backHref, ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
            <h1 class="h3 mt-3 mb-4">Novo arquivo de texto</h1>

            <?php if (!empty($_GET['erro'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_GET['erro'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" action="/admin/curriculo/documentos/novo-arquivo">
                <input type="hidden" name="folder_id" value="<?= $folderId !== null ? (int) $folderId : '' ?>">
                <div class="mb-3">
                    <label class="form-label" for="name">Nome do arquivo</label>
                    <input class="form-control" id="name" name="name" type="text" placeholder="anotacoes.txt" maxlength="160" required autofocus>
                    <div class="form-text">A extensão <code>.txt</code> é adicionada automaticamente se você não digitar.</div>
                </div>
                <button class="btn btn-primary" type="submit">Criar arquivo vazio</button>
            </form>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
</body>
</html>
