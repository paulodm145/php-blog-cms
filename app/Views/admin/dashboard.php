<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <div class="mb-4">
                <h1 class="h3 mb-1">Olá, <?= htmlspecialchars(explode('@', $user['email'] ?? '')[0] ?? '', ENT_QUOTES, 'UTF-8') ?> 👋</h1>
                <p class="text-secondary mb-0">Visão geral do blog.</p>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <a class="btn btn-primary" href="/admin/posts/create"><i class="fa-solid fa-plus me-1"></i> Novo post</a>
                <a class="btn btn-outline-secondary" href="/admin/paginas/create"><i class="fa-solid fa-plus me-1"></i> Nova página</a>
                <a class="btn btn-outline-secondary" href="/admin/comentarios?status=pending">
                    <i class="fa-solid fa-comments me-1"></i> Comentários pendentes
                    <?php if ($pendingCommentsCount > 0): ?><span class="badge bg-danger ms-1"><?= $pendingCommentsCount ?></span><?php endif; ?>
                </a>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <a href="/admin/posts" class="dash-stat">
                        <span class="dash-stat-value"><?= $postCounts['published'] ?></span>
                        <span class="dash-stat-label">Posts publicados</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/admin/posts" class="dash-stat">
                        <span class="dash-stat-value"><?= $postCounts['draft'] ?></span>
                        <span class="dash-stat-label">Rascunhos</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/admin/paginas" class="dash-stat">
                        <span class="dash-stat-value"><?= $pageCount ?></span>
                        <span class="dash-stat-label">Páginas</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/admin/categories" class="dash-stat">
                        <span class="dash-stat-value"><?= $categoryCount ?></span>
                        <span class="dash-stat-label">Categorias</span>
                    </a>
                </div>
            </div>

            <?php if (count($pendingMigrationNames) > 0): ?>
                <div class="content-surface p-3 p-md-4 mb-4" style="border-color:#f0ad4e">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h2 class="h6 mb-2"><i class="fa-solid fa-triangle-exclamation text-warning me-1"></i> <?= count($pendingMigrationNames) ?> migration(s) pendente(s)</h2>
                            <ul class="mb-0 text-secondary small">
                                <?php foreach (array_slice($pendingMigrationNames, 0, 5) as $migration): ?>
                                    <li><?= htmlspecialchars($migration, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                                <?php if (count($pendingMigrationNames) > 5): ?>
                                    <li>e mais <?= count($pendingMigrationNames) - 5 ?>…</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <a class="btn btn-warning text-nowrap" href="/admin/atualizar">Atualizar banco</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">Últimos posts</h2>
                        <a class="small" href="/admin/posts">ver todos</a>
                    </div>
                    <?php if (count($recentPosts) === 0): ?>
                        <p class="text-secondary small">Nenhum post ainda.</p>
                    <?php else: ?>
                        <ul class="list-unstyled dash-list">
                            <?php foreach ($recentPosts as $post): ?>
                                <?php
                                    $statusLabels = ['published' => 'Publicado', 'draft' => 'Rascunho', 'hidden' => 'Oculto'];
                                ?>
                                <li>
                                    <a href="/admin/posts/<?= (int) $post['id'] ?>/edit" class="dash-list-title"><?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?></a>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="status-badge status-<?= htmlspecialchars($post['status'], ENT_QUOTES, 'UTF-8') ?>"><?= $statusLabels[$post['status']] ?? $post['status'] ?></span>
                                        <span class="text-secondary small"><?= date('d/m/Y', strtotime($post['created_at'])) ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="col-lg-6">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">Últimos comentários</h2>
                        <a class="small" href="/admin/comentarios?status=">ver todos</a>
                    </div>
                    <?php if (count($recentComments) === 0): ?>
                        <p class="text-secondary small">Nenhum comentário ainda.</p>
                    <?php else: ?>
                        <ul class="list-unstyled dash-list">
                            <?php
                                $commentStatusBadge = ['pending' => 'bg-warning text-dark', 'approved' => 'bg-success', 'spam' => 'bg-secondary'];
                                $commentStatusLabel = ['pending' => 'pendente', 'approved' => 'aprovado', 'spam' => 'spam'];
                            ?>
                            <?php foreach ($recentComments as $comment): ?>
                                <li>
                                    <div class="dash-list-title"><?= htmlspecialchars($comment['author_name'], ENT_QUOTES, 'UTF-8') ?> em <em><?= htmlspecialchars($comment['post_title'], ENT_QUOTES, 'UTF-8') ?></em></div>
                                    <p class="text-secondary small mb-1" style="max-width:52ch;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($comment['body'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <span class="badge <?= $commentStatusBadge[$comment['status']] ?? 'bg-secondary' ?>"><?= $commentStatusLabel[$comment['status']] ?? $comment['status'] ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
</body>
</html>
