<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <a class="text-secondary" href="<?= htmlspecialchars($backHref, ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
            <h1 class="h3 mt-3 mb-4">Editar <?= htmlspecialchars($originalName, ENT_QUOTES, 'UTF-8') ?></h1>

            <?php if (!empty($_GET['erro'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_GET['erro'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" action="/admin/curriculo/documentos/<?= (int) $documentId ?>/editar-conteudo">
                <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-3">
                    <label class="form-label" for="content">Conteúdo</label>
                    <textarea class="form-control" id="content" name="content" rows="20" style="font-family: monospace;"><?= htmlspecialchars($content, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Salvar</button>
            </form>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
</body>
</html>
