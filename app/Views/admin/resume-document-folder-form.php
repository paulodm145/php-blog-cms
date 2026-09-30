<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <a class="text-secondary" href="<?= htmlspecialchars($backHref, ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
            <h1 class="h3 mt-3 mb-4"><?= $isNew ? 'Nova pasta' : 'Renomear pasta' ?></h1>

            <?php if (!empty($_GET['erro'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_GET['erro'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($isNew): ?>
                    <input type="hidden" name="parent_id" value="<?= $parentId !== null ? (int) $parentId : '' ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label" for="name">Nome da pasta</label>
                    <input class="form-control" id="name" name="name" type="text" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" maxlength="160" required autofocus>
                </div>
                <button class="btn btn-primary" type="submit">Salvar</button>
            </form>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
</body>
</html>
