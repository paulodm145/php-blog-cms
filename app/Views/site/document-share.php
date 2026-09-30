<?php

use App\Core\Html;

require dirname(__DIR__) . '/partials/site-top.php';
?>
    <main class="container-lg py-5" style="max-width:760px;margin:0 auto">
        <?php if (!$valid): ?>
            <div class="text-center">
                <div class="accent mb-2" style="font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;font-weight:600">Link indisponível</div>
                <h1 class="mb-3" style="font-size:1.9rem;font-weight:700">Este link expirou ou não existe.</h1>
                <p class="text-muted mb-4">Peça um novo link a quem compartilhou estes documentos com você.</p>
            </div>
        <?php elseif (empty($groups)): ?>
            <div class="text-center">
                <div class="accent mb-2" style="font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;font-weight:600">Sem documentos</div>
                <h1 class="mb-3" style="font-size:1.9rem;font-weight:700">Este link não tem mais documentos disponíveis.</h1>
                <p class="text-muted mb-4">Os documentos compartilhados aqui foram removidos. Peça um novo link a quem compartilhou com você.</p>
            </div>
        <?php else: ?>
            <h1 class="h3 mb-4">Documentos compartilhados</h1>
            <?php foreach ($groups as $group): ?>
                <div class="border rounded p-3 mb-3">
                    <h2 class="h6 mb-1"><?= htmlspecialchars($group['role'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($group['company'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($group['period'], ENT_QUOTES, 'UTF-8') ?></p>
                    <ul class="list-group">
                        <?php foreach ($group['documents'] as $document): ?>
                            <li class="list-group-item d-flex align-items-center gap-2">
                                <i class="fa-solid <?= strpos($document['mime_type'], 'image/') === 0 ? 'fa-file-image' : Html::fileIcon($document['mime_type']) ?>"></i>
                                <div class="flex-grow-1">
                                    <div><?= htmlspecialchars($document['original_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (!empty($document['caption'])): ?>
                                        <div class="text-muted small"><?= htmlspecialchars($document['caption'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>
                                <a class="btn-accent btn-sm" href="/compartilhado/<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>/documentos/<?= (int) $document['id'] ?>/download">Baixar</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
<?php require dirname(__DIR__) . '/partials/site-bottom.php'; ?>
