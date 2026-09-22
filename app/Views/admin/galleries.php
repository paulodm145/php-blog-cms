<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Galerias</h1>
                    <p class="text-secondary mb-0">Logado como <?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-primary" href="/admin/galerias/create?kind=video">
                        <i class="fa-solid fa-clapperboard me-1"></i> Nova galeria de vídeos
                    </a>
                    <a class="btn btn-primary" href="/admin/galerias/create">Nova galeria de fotos</a>
                </div>
            </div>

            <form class="row g-2 align-items-end mb-4" method="get" action="/admin/galerias">
                <div class="col-md-8">
                    <label class="form-label" for="q">Buscar por nome</label>
                    <input class="form-control" id="q" name="q" type="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Digite parte do nome">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Buscar</button>
                    <?php if ($search !== ''): ?>
                        <a class="btn btn-outline-secondary" href="/admin/galerias">Limpar</a>
                    <?php endif; ?>
                </div>
            </form>

            <p class="text-secondary small mb-3">
                <?= (int) $totalGalleries ?> galeria<?= $totalGalleries === 1 ? '' : 's' ?> encontrada<?= $totalGalleries === 1 ? '' : 's' ?>.
            </p>

            <div class="table-responsive admin-table-wrap">
                <table class="table admin-table align-middle mb-0">
                    <thead class="admin-table-head">
                        <tr>
                            <th>Capa</th>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Itens</th>
                            <th>Código</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($galleries as $gallery): ?>
                            <tr>
                                <td style="width: 64px">
                                    <?php if (!empty($gallery['cover_thumbnail_url'])): ?>
                                        <img src="<?= htmlspecialchars($gallery['cover_thumbnail_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <span class="text-secondary">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a class="fw-semibold text-decoration-none" href="/admin/galerias/<?= (int) $gallery['id'] ?>/edit">
                                        <?= htmlspecialchars($gallery['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($gallery['kind'] === 'video'): ?>
                                        <span class="badge text-bg-secondary"><i class="fa-solid fa-clapperboard me-1"></i>Vídeo</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary"><i class="fa-solid fa-photo-film me-1"></i>Foto</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) $gallery['item_count'] ?></td>
                                <td>
                                    <code class="gallery-shortcode" data-shortcode="[@<?= htmlspecialchars($gallery['slug'], ENT_QUOTES, 'UTF-8') ?>@]">[@<?= htmlspecialchars($gallery['slug'], ENT_QUOTES, 'UTF-8') ?>@]</code>
                                    <button type="button" class="admin-action-link gallery-copy-shortcode" title="Copiar código">Copiar</button>
                                </td>
                                <td class="text-end admin-actions">
                                    <a class="admin-action-link" href="/admin/galerias/<?= (int) $gallery['id'] ?>/edit">Editar</a>
                                    <form class="d-inline" method="post" action="/admin/galerias/<?= (int) $gallery['id'] ?>/delete" onsubmit="return confirm('Excluir esta galeria? Qualquer [@<?= htmlspecialchars($gallery['slug'], ENT_QUOTES, 'UTF-8') ?>@] em posts ou projetos vai parar de aparecer.');">
                                        <button class="admin-action-link admin-action-danger" type="submit">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($galleries) === 0): ?>
                            <tr><td colspan="6" class="text-secondary">Nenhuma galeria encontrada.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php
                $pageHref = function (int $page) use ($search): string {
                    return '/admin/galerias?' . http_build_query(array_filter(['q' => $search, 'page' => $page]));
                };
                require __DIR__ . '/partials/pagination.php';
            ?>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
<script>
    document.querySelectorAll('.gallery-copy-shortcode').forEach(function (button) {
        button.addEventListener('click', function () {
            var code = button.previousElementSibling.getAttribute('data-shortcode');
            navigator.clipboard.writeText(code).then(function () {
                var original = button.textContent;
                button.textContent = 'Copiado!';
                setTimeout(function () {
                    button.textContent = original;
                }, 1800);
            });
        });
    });
</script>
</body>
</html>
