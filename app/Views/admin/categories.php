<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Categorias</h1>
                <a class="btn btn-primary" href="/admin/categories/create">Nova categoria</a>
            </div>
            <div class="table-responsive admin-table-wrap">
                <table class="table admin-table align-middle mb-0">
                    <thead class="admin-table-head">
                        <tr>
                            <th>Nome</th>
                            <th>Slug</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($category['slug'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end admin-actions">
                                    <a class="admin-action-link admin-action-danger" href="/admin/categories/<?= (int) $category['id'] ?>/edit">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
</body>
</html>
