<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <a class="text-secondary" href="/admin/curriculo">Voltar para currículo</a>
            <h1 class="h3 mt-3 mb-4"><?= $isNew ? 'Novo curso' : 'Editar curso' ?></h1>

            <form method="post" action="<?= $isNew ? '/admin/curriculo/cursos' : '/admin/curriculo/cursos/' . (int) $item['id'] . '/edit' ?>" id="course-form">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label" for="name">Curso</label>
                        <input class="form-control" id="name" name="name" type="text" value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="institution">Instituição</label>
                        <input class="form-control" id="institution" name="institution" type="text" value="<?= htmlspecialchars($item['institution'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="start_date">Data inicial</label>
                        <input class="form-control" id="start_date" name="start_date" type="date" value="<?= htmlspecialchars((string) ($item['start_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="end_date">Data final</label>
                        <input class="form-control" id="end_date" name="end_date" type="date" value="<?= htmlspecialchars((string) ($item['end_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <p class="text-secondary small mt-1 mb-0">Deixe em branco se o curso ainda está em andamento.</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label d-block">Carga horária</label>
                        <div class="input-group">
                            <input class="form-control" id="hours" name="hours" type="number" min="0" placeholder="Horas" value="<?= htmlspecialchars((string) ($item['hours'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <span class="input-group-text">h</span>
                            <input class="form-control" id="minutes" name="minutes" type="number" min="0" max="59" placeholder="Minutos" value="<?= htmlspecialchars((string) ($item['minutes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <span class="input-group-text">min</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label d-block">Certificado</label>
                        <input type="hidden" id="certificate_media_id" name="certificate_media_id" value="<?= (int) ($item['certificate_media_id'] ?? 0) ?>">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <button class="btn btn-outline-secondary btn-sm" type="button" id="choose-certificate">
                                <i class="fa-solid fa-photo-film me-1"></i> Escolher da biblioteca de mídia
                            </button>
                            <span id="certificate-current" class="small">
                                <?php if (!empty($item['certificate_media_url'])): ?>
                                    <a href="<?= htmlspecialchars($item['certificate_media_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                        <?= htmlspecialchars($item['certificate_media_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <a href="#" id="remove-certificate" class="text-danger ms-2">Remover</a>
                                <?php else: ?>
                                    <span class="text-secondary">Nenhum arquivo escolhido.</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <label class="form-label" for="certificate_url">URL de emissão/verificação do certificado</label>
                        <input class="form-control" id="certificate_url" name="certificate_url" type="url" placeholder="https://..." value="<?= htmlspecialchars((string) ($item['certificate_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
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
                <form id="delete-form" method="post" action="/admin/curriculo/cursos/<?= (int) $item['id'] ?>/delete" onsubmit="return confirm('Excluir este curso?');"></form>
            <?php endif; ?>
            <?php require __DIR__ . '/partials/media-library-modal.php'; ?>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="/assets/js/media-library.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/media-library.js') ?: '1' ?>"></script>
    <script>
        document.getElementById('choose-certificate').addEventListener('click', function () {
            MediaLibrary.open(function (item) {
                document.getElementById('certificate_media_id').value = item.id;
                document.getElementById('certificate-current').innerHTML =
                    '<a href="' + item.url + '" target="_blank" rel="noopener">' + item.original_name + '</a>'
                    + ' <a href="#" id="remove-certificate" class="text-danger ms-2">Remover</a>';
                bindRemoveCertificate();
            });
        });

        function bindRemoveCertificate() {
            var removeLink = document.getElementById('remove-certificate');

            if (!removeLink) {
                return;
            }

            removeLink.addEventListener('click', function (event) {
                event.preventDefault();
                document.getElementById('certificate_media_id').value = '';
                document.getElementById('certificate-current').innerHTML = '<span class="text-secondary">Nenhum arquivo escolhido.</span>';
            });
        }

        bindRemoveCertificate();
    </script>
</body>
</html>
