<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <h1 class="h3 mb-4"><?= $isNew ? 'Novo usuario' : 'Editar usuario' ?></h1>
            <form method="post" action="<?= $isNew ? '/admin/users' : '/admin/users/' . (int) $adminUser['id'] . '/edit' ?>">
                <div class="mb-3">
                    <label class="form-label" for="name">Nome</label>
                    <input class="form-control" id="name" name="name" value="<?= htmlspecialchars($adminUser['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">E-mail</label>
                    <input class="form-control" id="email" name="email" type="email" value="<?= htmlspecialchars($adminUser['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Senha <?= $isNew ? '' : '(deixe em branco para manter)' ?></label>
                    <input class="form-control" id="password" name="password" type="password" <?= $isNew ? 'required' : '' ?>>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        <?php if (!$isNew): ?>
                            <button class="btn btn-outline-danger" type="submit" form="delete-user-form">Excluir</button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary" href="/admin/users">Cancelar</a>
                        <button class="btn btn-primary" type="submit">Salvar</button>
                    </div>
                </div>
            </form>
            <?php if (!$isNew): ?>
                <form id="delete-user-form" method="post" action="/admin/users/<?= (int) $adminUser['id'] ?>/delete" onsubmit="return confirm('Excluir este usuario?');"></form>
            <?php endif; ?>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
</body>
</html>

