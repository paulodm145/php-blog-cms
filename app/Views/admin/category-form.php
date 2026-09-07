<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <h1 class="h3 mb-4"><?= $isNew ? 'Nova categoria' : 'Editar categoria' ?></h1>
            <form method="post" action="<?= $isNew ? '/admin/categories' : '/admin/categories/' . (int) $category['id'] . '/edit' ?>">
                <div class="mb-3">
                    <label class="form-label" for="name">Nome</label>
                    <input class="form-control" id="name" name="name" value="<?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="slug">Slug</label>
                    <input class="form-control" id="slug" name="slug" value="<?= htmlspecialchars($category['slug'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        <?php if (!$isNew): ?>
                            <button class="btn btn-outline-danger" type="submit" form="delete-category-form">Excluir</button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary" href="/admin/categories">Cancelar</a>
                        <button class="btn btn-primary" type="submit">Salvar</button>
                    </div>
                </div>
            </form>
            <?php if (!$isNew): ?>
                <form id="delete-category-form" method="post" action="/admin/categories/<?= (int) $category['id'] ?>/delete" onsubmit="return confirm('Excluir esta categoria? Posts vinculados ficarão sem categoria.');"></form>
            <?php endif; ?>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>

</body>
</html>
