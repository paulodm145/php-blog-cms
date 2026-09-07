<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <a class="text-secondary" href="/admin/curriculo">Voltar para currículo</a>
            <h1 class="h3 mt-3 mb-4"><?= $isNew ? 'Nova certificação' : 'Editar certificação' ?></h1>

            <form method="post" action="<?= $isNew ? '/admin/curriculo/certificacoes' : '/admin/curriculo/certificacoes/' . (int) $item['id'] . '/edit' ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Certificação</label>
                        <input class="form-control" id="name" name="name" type="text" value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="issuer">Emissor</label>
                        <input class="form-control" id="issuer" name="issuer" type="text" value="<?= htmlspecialchars($item['issuer'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="period">Período (texto livre, ex: "2024")</label>
                        <input class="form-control" id="period" name="period" type="text" value="<?= htmlspecialchars($item['period'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="credential_url">Link de verificação (opcional)</label>
                        <input class="form-control" id="credential_url" name="credential_url" type="url" placeholder="https://..." value="<?= htmlspecialchars($item['credential_url'], ENT_QUOTES, 'UTF-8') ?>">
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
                <form id="delete-form" method="post" action="/admin/curriculo/certificacoes/<?= (int) $item['id'] ?>/delete" onsubmit="return confirm('Excluir esta certificação?');"></form>
            <?php endif; ?>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
</body>
</html>
