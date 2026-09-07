<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Usuarios</h1>
                <a class="btn btn-primary" href="/admin/users/create">Novo usuario</a>
            </div>
            <div class="table-responsive admin-table-wrap">
                <table class="table admin-table align-middle mb-0">
                    <thead class="admin-table-head">
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Criado em</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $adminUser): ?>
                            <tr>
                                <td><?= htmlspecialchars($adminUser['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($adminUser['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= date('d/m/Y', strtotime($adminUser['created_at'])) ?></td>
                                <td class="text-end admin-actions">
                                    <a class="admin-action-link admin-action-danger" href="/admin/users/<?= (int) $adminUser['id'] ?>/edit">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
</body>
</html>
