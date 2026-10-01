<?php
/** @var array $shares */
/** @var string $appUrl */
?>
<div class="table-responsive admin-table-wrap">
    <table class="table admin-table align-middle mb-0">
        <thead class="admin-table-head">
            <tr><th>Link</th><th>Documentos</th><th>Expira em</th><th>Status</th><th class="text-end">Ações</th></tr>
        </thead>
        <tbody>
            <?php foreach ($shares as $share): ?>
                <?php
                    $isRevoked = $share['revoked_at'] !== null;
                    $isExpired = !$isRevoked && strtotime($share['expires_at']) < time();
                    $status = $isRevoked ? 'Revogado' : ($isExpired ? 'Expirado' : 'Ativo');
                    $statusClass = ($isRevoked || $isExpired) ? 'text-bg-secondary' : 'text-bg-success';
                    $shareUrl = $appUrl . '/compartilhado/' . $share['token'];
                ?>
                <tr>
                    <td class="text-truncate" style="max-width:220px">
                        <code><?= htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8') ?></code>
                    </td>
                    <td><?= (int) $share['document_count'] ?></td>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($share['expires_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge <?= $statusClass ?>"><?= $status ?></span></td>
                    <td class="text-end admin-actions">
                        <button
                            class="admin-action-link"
                            type="button"
                            data-copy-share-url="<?= htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8') ?>"
                            title="Copiar link"
                        ><i class="fa-solid fa-copy"></i></button>
                        <?php if ($status === 'Ativo'): ?>
                            <button
                                class="admin-action-link"
                                type="button"
                                data-edit-share
                                data-share-id="<?= (int) $share['id'] ?>"
                                data-document-ids="<?= htmlspecialchars((string) $share['document_ids'], ENT_QUOTES, 'UTF-8') ?>"
                                data-expires-at="<?= htmlspecialchars($share['expires_at'], ENT_QUOTES, 'UTF-8') ?>"
                                title="Editar"
                            ><i class="fa-solid fa-pen"></i></button>
                            <form class="d-inline" method="post" action="/admin/curriculo/documentos/compartilhamentos/<?= (int) $share['id'] ?>/revogar" onsubmit="return confirm('Revogar este link?');">
                                <button class="admin-action-link admin-action-danger" type="submit">Revogar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($shares)): ?>
                <tr><td colspan="5" class="text-secondary">Nenhum link gerado ainda.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
