<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <div class="mb-4">
                <h1 class="h3 mb-1">Currículo</h1>
                <p class="text-secondary mb-0">Gerencie a página pública em <a href="/curriculo" target="_blank" rel="noopener">/curriculo</a>.</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">Salvo com sucesso.</div>
            <?php endif; ?>
            <?php if (!empty($_GET['photo_error'])): ?>
                <div class="alert alert-danger">Foto: <?= htmlspecialchars($_GET['photo_error'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <ul class="nav nav-tabs mb-4" id="resume-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-perfil-btn" data-bs-toggle="tab" data-bs-target="#tab-perfil" type="button" role="tab">Perfil</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-experiencia-btn" data-bs-toggle="tab" data-bs-target="#tab-experiencia" type="button" role="tab">Experiência</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-formacao-btn" data-bs-toggle="tab" data-bs-target="#tab-formacao" type="button" role="tab">Formação</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-cursos-btn" data-bs-toggle="tab" data-bs-target="#tab-cursos" type="button" role="tab">Cursos</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-certificacoes-btn" data-bs-toggle="tab" data-bs-target="#tab-certificacoes" type="button" role="tab">Certificações</button>
                </li>
            </ul>

            <div class="tab-content" id="resume-tabs-content">
                <div class="tab-pane fade show active" id="tab-perfil" role="tabpanel">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h2 class="h6 mb-3">Foto</h2>
                                <?php if (!empty($settings['resume_photo'])): ?>
                                    <img src="<?= htmlspecialchars($settings['resume_photo'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto atual" class="rounded mb-3" style="width:110px;height:110px;object-fit:cover">
                                <?php endif; ?>
                                <form method="post" action="/admin/curriculo/foto" enctype="multipart/form-data" class="d-flex gap-2">
                                    <input class="form-control" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
                                    <button class="btn btn-primary text-nowrap" type="submit">Enviar</button>
                                </form>
                                <p class="text-secondary small mt-2 mb-0">JPG, PNG ou WebP, até 3 MB.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h2 class="h6 mb-3">PDF do currículo</h2>
                                <p class="text-secondary small mb-3">O PDF é gerado automaticamente a partir dos dados desta página — não precisa mais fazer upload. Editando experiência, formação, cursos ou certificações, o PDF já sai atualizado.</p>
                                <a class="btn btn-outline-primary" href="/curriculo/pdf" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf"></i> Ver PDF gerado</a>
                            </div>
                        </div>
                    </div>

                    <form method="post" action="/admin/curriculo">
                        <div class="mb-3">
                            <label class="form-label" for="resume_tagline">Resumo / tagline</label>
                            <textarea class="form-control" id="resume_tagline" name="resume_tagline" rows="3"><?= htmlspecialchars($settings['resume_tagline'], ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="resume_skills">Habilidades (separadas por vírgula)</label>
                            <textarea class="form-control" id="resume_skills" name="resume_skills" rows="2"><?= htmlspecialchars($settings['resume_skills'], ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <button class="btn btn-dark" type="submit">Salvar</button>
                    </form>
                </div>

                <div class="tab-pane fade" id="tab-experiencia" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Experiência profissional</h2>
                        <a class="btn btn-sm btn-primary" href="/admin/curriculo/experiencia/create">Adicionar</a>
                    </div>
                    <div class="table-responsive admin-table-wrap">
                        <table class="table admin-table align-middle mb-0">
                            <thead class="admin-table-head">
                                <tr><th>Ordem</th><th>Cargo</th><th>Empresa</th><th>Período</th><th class="text-end">Ações</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($experience as $index => $item): ?>
                                    <tr>
                                        <td class="admin-actions">
                                            <form class="d-inline" method="post" action="/admin/curriculo/experiencia/<?= (int) $item['id'] ?>/mover-cima">
                                                <button class="admin-action-link" type="submit" <?= $index === 0 ? 'disabled' : '' ?>><i class="fa-solid fa-arrow-up"></i></button>
                                            </form>
                                            <form class="d-inline" method="post" action="/admin/curriculo/experiencia/<?= (int) $item['id'] ?>/mover-baixo">
                                                <button class="admin-action-link" type="submit" <?= $index === count($experience) - 1 ? 'disabled' : '' ?>><i class="fa-solid fa-arrow-down"></i></button>
                                            </form>
                                        </td>
                                        <td><?= htmlspecialchars($item['role'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($item['company'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($item['period'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end admin-actions">
                                            <a class="admin-action-link" href="/admin/curriculo/experiencia/<?= (int) $item['id'] ?>/edit">Editar</a>
                                            <form class="d-inline" method="post" action="/admin/curriculo/experiencia/<?= (int) $item['id'] ?>/delete" onsubmit="return confirm('Excluir esta experiência?');">
                                                <button class="admin-action-link admin-action-danger" type="submit">Excluir</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (count($experience) === 0): ?>
                                    <tr><td colspan="5" class="text-secondary">Nenhuma experiência cadastrada.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-formacao" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Formação acadêmica</h2>
                        <a class="btn btn-sm btn-primary" href="/admin/curriculo/formacao/create">Adicionar</a>
                    </div>
                    <div class="table-responsive admin-table-wrap">
                        <table class="table admin-table align-middle mb-0">
                            <thead class="admin-table-head">
                                <tr><th>Ordem</th><th>Curso</th><th>Instituição</th><th>Período</th><th class="text-end">Ações</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($education as $index => $item): ?>
                                    <tr>
                                        <td class="admin-actions">
                                            <form class="d-inline" method="post" action="/admin/curriculo/formacao/<?= (int) $item['id'] ?>/mover-cima">
                                                <button class="admin-action-link" type="submit" <?= $index === 0 ? 'disabled' : '' ?>><i class="fa-solid fa-arrow-up"></i></button>
                                            </form>
                                            <form class="d-inline" method="post" action="/admin/curriculo/formacao/<?= (int) $item['id'] ?>/mover-baixo">
                                                <button class="admin-action-link" type="submit" <?= $index === count($education) - 1 ? 'disabled' : '' ?>><i class="fa-solid fa-arrow-down"></i></button>
                                            </form>
                                        </td>
                                        <td><?= htmlspecialchars($item['course'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($item['institution'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($item['period'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end admin-actions">
                                            <a class="admin-action-link" href="/admin/curriculo/formacao/<?= (int) $item['id'] ?>/edit">Editar</a>
                                            <form class="d-inline" method="post" action="/admin/curriculo/formacao/<?= (int) $item['id'] ?>/delete" onsubmit="return confirm('Excluir esta formação?');">
                                                <button class="admin-action-link admin-action-danger" type="submit">Excluir</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (count($education) === 0): ?>
                                    <tr><td colspan="5" class="text-secondary">Nenhuma formação cadastrada.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-cursos" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Cursos</h2>
                        <a class="btn btn-sm btn-primary" href="/admin/curriculo/cursos/create">Adicionar</a>
                    </div>

                    <div class="row g-2 align-items-end mb-3" data-admin-table data-default-sort="start_date" data-default-dir="-1">
                        <div class="col-md-6">
                            <label class="form-label" for="curso_q">Buscar</label>
                            <input
                                class="form-control"
                                id="curso_q"
                                type="search"
                                placeholder="Curso, instituição, visibilidade..."
                                data-table-search
                            >
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="curso_visivel">Visível</label>
                            <select class="form-select" id="curso_visivel" data-table-filter="status">
                                <option value="">Todos</option>
                                <option value="visible">Visível</option>
                                <option value="hidden">Oculto</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="curso_page_size">Por página</label>
                            <select class="form-select" id="curso_page_size" data-table-page-size>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="0">Todos</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <p class="text-secondary small mb-2" data-table-info>
                                <?= (int) $courseTotal ?> curso<?= $courseTotal === 1 ? '' : 's' ?> cadastrado<?= $courseTotal === 1 ? '' : 's' ?>
                                · Total de carga horária: <?= $courseTotalDuration !== '' ? htmlspecialchars($courseTotalDuration, ENT_QUOTES, 'UTF-8') : '0h' ?>
                            </p>

                            <div class="table-responsive admin-table-wrap">
                                <table class="table admin-table align-middle mb-0">
                                    <thead class="admin-table-head admin-table-head-sortable">
                                        <tr>
                                            <th><button type="button" class="admin-table-sort" data-sort="name">Curso <i class="fa-solid fa-sort"></i></button></th>
                                            <th><button type="button" class="admin-table-sort" data-sort="institution">Instituição <i class="fa-solid fa-sort"></i></button></th>
                                            <th><button type="button" class="admin-table-sort" data-sort="start_date" data-sort-type="number">Período <i class="fa-solid fa-sort-down"></i></button></th>
                                            <th><button type="button" class="admin-table-sort" data-sort="duration_minutes" data-sort-type="number">Carga horária <i class="fa-solid fa-sort"></i></button></th>
                                            <th><span class="admin-table-head-label">Certificado</span></th>
                                            <th><span class="admin-table-head-label">Visível</span></th>
                                            <th class="text-end"><span class="admin-table-head-label">Ações</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($courses as $item): ?>
                                            <?php
                                                $hasCertificate = !empty($item['certificate_media_url']) || !empty($item['certificate_url']);
                                                $periodTimestamp = strtotime((string) ($item['end_date'] ?? $item['start_date'] ?? '')) ?: 0;
                                                $visibilityLabel = $item['visible'] ? 'Visível' : 'Oculto';
                                                $searchHaystack = mb_strtolower(implode(' ', [
                                                    $item['name'],
                                                    $item['institution'],
                                                    $visibilityLabel,
                                                ]), 'UTF-8');
                                            ?>
                                            <tr data-row data-status="<?= $item['visible'] ? 'visible' : 'hidden' ?>" data-search="<?= htmlspecialchars($searchHaystack, ENT_QUOTES, 'UTF-8') ?>">
                                                <td data-col="name" data-value="<?= htmlspecialchars(mb_strtolower($item['name'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td data-col="institution" data-value="<?= htmlspecialchars(mb_strtolower($item['institution'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($item['institution'], ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td data-col="start_date" data-value="<?= $periodTimestamp ?>">
                                                    <?= htmlspecialchars($item['period'], ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td data-col="duration_minutes" data-value="<?= (int) ($item['duration_minutes'] ?? 0) ?>">
                                                    <?= $item['duration_text'] !== '' ? htmlspecialchars($item['duration_text'], ENT_QUOTES, 'UTF-8') : '-' ?>
                                                </td>
                                                <td>
                                                    <?php if ($hasCertificate): ?>
                                                        <i class="fa-solid fa-circle-check text-success" title="Tem certificado"></i>
                                                    <?php else: ?>
                                                        <span class="text-secondary">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch mb-0">
                                                        <input
                                                            class="form-check-input course-visibility-toggle"
                                                            type="checkbox"
                                                            role="switch"
                                                            data-id="<?= (int) $item['id'] ?>"
                                                            <?= $item['visible'] ? 'checked' : '' ?>
                                                        >
                                                    </div>
                                                </td>
                                                <td class="text-end admin-actions">
                                                    <a class="admin-action-link" href="/admin/curriculo/cursos/<?= (int) $item['id'] ?>/edit">Editar</a>
                                                    <form class="d-inline" method="post" action="/admin/curriculo/cursos/<?= (int) $item['id'] ?>/delete" onsubmit="return confirm('Excluir este curso?');">
                                                        <button class="admin-action-link admin-action-danger" type="submit">Excluir</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr data-table-empty hidden>
                                            <td colspan="7" class="text-secondary">Nenhum curso encontrado.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <nav class="pt-3" aria-label="Paginação administrativa de cursos">
                                <ul class="pagination justify-content-center mb-0" data-table-pager></ul>
                            </nav>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-certificacoes" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Certificações</h2>
                        <a class="btn btn-sm btn-primary" href="/admin/curriculo/certificacoes/create">Adicionar</a>
                    </div>
                    <div class="table-responsive admin-table-wrap">
                        <table class="table admin-table align-middle mb-0">
                            <thead class="admin-table-head">
                                <tr><th>Ordem</th><th>Certificação</th><th>Emissor</th><th>Período</th><th class="text-end">Ações</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($certifications as $index => $item): ?>
                                    <tr>
                                        <td class="admin-actions">
                                            <form class="d-inline" method="post" action="/admin/curriculo/certificacoes/<?= (int) $item['id'] ?>/mover-cima">
                                                <button class="admin-action-link" type="submit" <?= $index === 0 ? 'disabled' : '' ?>><i class="fa-solid fa-arrow-up"></i></button>
                                            </form>
                                            <form class="d-inline" method="post" action="/admin/curriculo/certificacoes/<?= (int) $item['id'] ?>/mover-baixo">
                                                <button class="admin-action-link" type="submit" <?= $index === count($certifications) - 1 ? 'disabled' : '' ?>><i class="fa-solid fa-arrow-down"></i></button>
                                            </form>
                                        </td>
                                        <td><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($item['issuer'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($item['period'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end admin-actions">
                                            <a class="admin-action-link" href="/admin/curriculo/certificacoes/<?= (int) $item['id'] ?>/edit">Editar</a>
                                            <form class="d-inline" method="post" action="/admin/curriculo/certificacoes/<?= (int) $item['id'] ?>/delete" onsubmit="return confirm('Excluir esta certificação?');">
                                                <button class="admin-action-link admin-action-danger" type="submit">Excluir</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (count($certifications) === 0): ?>
                                    <tr><td colspan="5" class="text-secondary">Nenhuma certificação cadastrada.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="/assets/js/admin-table.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/admin-table.js') ?: '1' ?>"></script>
    <script>
        document.querySelectorAll('.course-visibility-toggle').forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                var id = toggle.getAttribute('data-id');
                var previousState = !toggle.checked;
                var formData = new FormData();
                formData.append('visible', toggle.checked ? '1' : '0');
                // Mantem o filtro "Visível" da tabela (admin-table.js) coerente
                // sem precisar recarregar a pagina depois de alternar o switch.
                var row = toggle.closest('tr[data-row]');

                toggle.disabled = true;

                fetch('/admin/curriculo/cursos/' + id + '/visibilidade', { method: 'POST', body: formData })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('status ' + response.status);
                        }
                        return response.json();
                    })
                    .then(function () {
                        if (row) {
                            row.setAttribute('data-status', toggle.checked ? 'visible' : 'hidden');
                        }

                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: toggle.checked ? 'Curso visível no site' : 'Curso ocultado do site',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    })
                    .catch(function () {
                        toggle.checked = previousState;
                        Swal.fire({ icon: 'error', title: 'Falha ao atualizar a visibilidade' });
                    })
                    .finally(function () {
                        toggle.disabled = false;
                    });
            });
        });
    </script>
    <script>
    // Mantem a aba ativa depois de um submit (ex: salvar tagline, mover
    // item de ordem) — sem isso, todo POST volta pra tela sempre na aba
    // "Perfil", perdendo o contexto de onde o admin estava.
    (function () {
        var hash = window.location.hash;

        if (hash) {
            var trigger = document.querySelector('#resume-tabs button[data-bs-target="' + hash + '"]');

            if (trigger) {
                bootstrap.Tab.getOrCreateInstance(trigger).show();
            }
        }

        document.querySelectorAll('#resume-tabs button[data-bs-toggle="tab"]').forEach(function (button) {
            button.addEventListener('shown.bs.tab', function (event) {
                history.replaceState(null, '', event.target.getAttribute('data-bs-target'));
            });
        });

        document.querySelectorAll('.tab-pane form[method="post"]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var pane = form.closest('.tab-pane');

                if (pane) {
                    var action = new URL(form.action, window.location.origin);
                    action.hash = '#' + pane.id;
                    form.action = action.toString();
                }
            });
        });
    })();
</script>
</body>
</html>
