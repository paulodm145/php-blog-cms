<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <a class="text-secondary" href="/admin/curriculo">Voltar para currículo</a>
            <h1 class="h3 mt-3 mb-4"><?= $isNew ? 'Nova formação' : 'Editar formação' ?></h1>

            <form method="post" action="<?= $isNew ? '/admin/curriculo/formacao' : '/admin/curriculo/formacao/' . (int) $item['id'] . '/edit' ?>">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label" for="course">Curso</label>
                        <input class="form-control" id="course" name="course" type="text" value="<?= htmlspecialchars($item['course'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="institution">Instituição</label>
                        <input class="form-control" id="institution" name="institution" type="text" value="<?= htmlspecialchars($item['institution'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="period">Período (texto livre, ex: "2020 — 2024")</label>
                        <input class="form-control" id="period" name="period" type="text" value="<?= htmlspecialchars($item['period'], ENT_QUOTES, 'UTF-8') ?>" required>
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
                <form id="delete-form" method="post" action="/admin/curriculo/formacao/<?= (int) $item['id'] ?>/delete" onsubmit="return confirm('Excluir esta formação?');"></form>
            <?php endif; ?>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
</body>
</html>
