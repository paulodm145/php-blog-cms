<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Comentários</h1>
                <?php if ($pendingCount > 0): ?>
                    <span class="badge bg-danger"><?= $pendingCount ?> pendente(s)</span>
                <?php endif; ?>
            </div>

            <ul class="nav nav-pills mb-4">
                <?php foreach (['pending' => 'Pendentes', 'approved' => 'Aprovados', 'spam' => 'Spam', '' => 'Todos'] as $key => $label): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $status === $key ? ' active' : '' ?>" href="/admin/comentarios?status=<?= $key ?>"><?= $label ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if (count($comments) === 0): ?>
                <p class="text-secondary">Nenhum comentário aqui.</p>
            <?php else: ?>
                <div class="table-responsive admin-table-wrap">
                    <table class="table admin-table align-middle mb-0">
                        <thead class="admin-table-head">
                            <tr>
                                <th>Post</th>
                                <th>Autor</th>
                                <th>Comentário</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <td><a href="/blog/<?= rawurlencode($comment['post_slug']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($comment['post_title'], ENT_QUOTES, 'UTF-8') ?></a></td>
                                    <td>
                                        <?= htmlspecialchars($comment['author_name'], ENT_QUOTES, 'UTF-8') ?><br>
                                        <span class="text-secondary small"><?= htmlspecialchars($comment['author_email'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td style="max-width:320px"><?= nl2br(htmlspecialchars($comment['body'], ENT_QUOTES, 'UTF-8')) ?></td>
                                    <td><?= htmlspecialchars($comment['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end">
                                        <div class="d-flex gap-1 justify-content-end flex-wrap">
                                            <?php if ($comment['status'] !== 'approved'): ?>
                                                <form method="post" action="/admin/comentarios/<?= (int) $comment['id'] ?>/aprovar">
                                                    <button class="btn btn-sm btn-outline-success" type="submit">Aprovar</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($comment['status'] !== 'spam'): ?>
                                                <form method="post" action="/admin/comentarios/<?= (int) $comment['id'] ?>/spam">
                                                    <button class="btn btn-sm btn-outline-warning" type="submit">Spam</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="post" action="/admin/comentarios/<?= (int) $comment['id'] ?>/delete" onsubmit="return confirm('Excluir este comentário?');">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                                            </form>
                                        </div>
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
