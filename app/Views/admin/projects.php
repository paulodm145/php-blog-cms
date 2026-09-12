<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Projetos</h1>
                    <p class="text-secondary mb-0">Logado como <?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <a class="btn btn-primary" href="/admin/projetos/create">Novo projeto</a>
            </div>

            <form class="row g-2 align-items-end mb-4" method="get" action="/admin/projetos">
                <div class="col-md-8">
                    <label class="form-label" for="q">Buscar por nome</label>
                    <input class="form-control" id="q" name="q" type="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Digite parte do nome">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Buscar</button>
                    <?php if ($search !== ''): ?>
                        <a class="btn btn-outline-secondary" href="/admin/projetos">Limpar</a>
                    <?php endif; ?>
                </div>
            </form>

            <p class="text-secondary small mb-3">
                <?= (int) $totalProjects ?> projeto<?= $totalProjects === 1 ? '' : 's' ?> encontrado<?= $totalProjects === 1 ? '' : 's' ?>.
            </p>

            <div class="table-responsive admin-table-wrap">
                <table class="table admin-table align-middle mb-0">
                    <thead class="admin-table-head">
                        <tr>
                            <th>Ordem</th>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Período</th>
                            <th>Destaque</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <?php $orderPosition = array_search((int) $project['id'], $orderedIds, true); ?>
                            <tr>
                                <td class="admin-actions">
                                    <form class="d-inline" method="post" action="/admin/projetos/<?= (int) $project['id'] ?>/mover-cima">
                                        <button class="admin-action-link" type="submit" <?= $orderPosition === 0 ? 'disabled' : '' ?>><i class="fa-solid fa-arrow-up"></i></button>
                                    </form>
                                    <form class="d-inline" method="post" action="/admin/projetos/<?= (int) $project['id'] ?>/mover-baixo">
                                        <button class="admin-action-link" type="submit" <?= $orderPosition === count($orderedIds) - 1 ? 'disabled' : '' ?>><i class="fa-solid fa-arrow-down"></i></button>
                                    </form>
                                </td>
                                <td>
                                    <a class="fw-semibold text-decoration-none" href="/admin/projetos/<?= (int) $project['id'] ?>/edit">
                                        <?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars(\App\Core\Text::projectTypeLabel($project['project_type']) ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($project['period'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if ((int) $project['featured'] === 1): ?>
                                        <i class="fa-solid fa-star text-warning" title="Em destaque"></i>
                                    <?php else: ?>
                                        <span class="text-secondary">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $project['status'] === 'published' ? 'published' : 'draft' ?>">
                                        <?= $project['status'] === 'published' ? 'Publicado' : 'Rascunho' ?>
                                    </span>
                                </td>
                                <td class="text-end admin-actions">
                                    <a class="admin-action-link" href="/admin/projetos/<?= (int) $project['id'] ?>/edit">Editar</a>
                                    <?php if ($project['status'] === 'published'): ?>
                                        <a class="admin-action-link" href="/projetos/<?= rawurlencode($project['slug']) ?>" target="_blank" rel="noopener">Ver</a>
                                    <?php endif; ?>
                                    <form class="d-inline" method="post" action="/admin/projetos/<?= (int) $project['id'] ?>/delete" onsubmit="return confirm('Excluir este projeto?');">
                                        <button class="admin-action-link admin-action-danger" type="submit">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($projects) === 0): ?>
                            <tr><td colspan="7" class="text-secondary">Nenhum projeto encontrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php
                $pageHref = function (int $page) use ($search): string {
                    return '/admin/projetos?' . http_build_query(array_filter(['q' => $search, 'page' => $page]));
                };
                require __DIR__ . '/partials/pagination.php';
            ?>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
