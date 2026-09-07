<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <a class="text-secondary" href="/admin/curriculo">Voltar para currículo</a>
            <h1 class="h3 mt-3 mb-4"><?= $isNew ? 'Nova experiência' : 'Editar experiência' ?></h1>

            <form method="post" action="<?= $isNew ? '/admin/curriculo/experiencia' : '/admin/curriculo/experiencia/' . (int) $item['id'] . '/edit' ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="role">Cargo</label>
                        <input class="form-control" id="role" name="role" type="text" value="<?= htmlspecialchars($item['role'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="company">Empresa</label>
                        <input class="form-control" id="company" name="company" type="text" value="<?= htmlspecialchars($item['company'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="period">Período (texto livre, ex: "jan/2023 — atual")</label>
                        <input class="form-control" id="period" name="period" type="text" value="<?= htmlspecialchars($item['period'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <?php if (!$isNew): ?>
                            <button class="btn btn-outline-danger" type="submit" form="delete-form">Excluir</button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary" href="/admin/curriculo">Cancelar</a>
                        <button class="btn btn-primary" type="submit">Salvar</button>
                    </div>
                </div>
            </form>
            <?php if (!$isNew): ?>
                <form id="delete-form" method="post" action="/admin/curriculo/experiencia/<?= (int) $item['id'] ?>/delete" onsubmit="return confirm('Excluir esta experiência?');"></form>
            <?php endif; ?>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
</body>
</html>
