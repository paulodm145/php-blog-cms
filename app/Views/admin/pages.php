<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Páginas</h1>
                <a class="btn btn-primary" href="/admin/paginas/create">Nova página</a>
            </div>
            <?php if (count($pages) === 0): ?>
                <p class="text-secondary">Nenhuma página cadastrada ainda.</p>
            <?php else: ?>
                <div class="table-responsive admin-table-wrap">
                    <table class="table admin-table align-middle mb-0">
                        <thead class="admin-table-head">
                            <tr>
                                <th>Título</th>
                                <th>URL</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pages as $page): ?>
                                <tr>
                                    <td><?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>/<?= htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= $page['status'] === 'published' ? 'Publicada' : 'Rascunho' ?></td>
                                    <td class="text-end admin-actions">
                                        <a class="admin-action-link admin-action-danger" href="/admin/paginas/<?= (int) $page['id'] ?>/edit">Editar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
</body>
</html>
