<?php
    $pageHref = function (int $page) use ($search, $kind, $view): string {
        return '/admin/media?' . http_build_query(array_filter([
            'q' => $search,
            'kind' => $kind,
            'view' => $view !== 'grid' ? $view : '',
            'page' => $page,
        ]));
    };

    $formatMediaSize = function (int $bytes): string {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    };

    $renderMediaCard = function (array $item) use ($formatMediaSize): void {
        ?>
        <div
            class="media-card"
            tabindex="0"
            data-id="<?= (int) $item['id'] ?>"
            data-url="<?= htmlspecialchars($item['path'], ENT_QUOTES, 'UTF-8') ?>"
            data-kind="<?= htmlspecialchars($item['kind'], ENT_QUOTES, 'UTF-8') ?>"
            data-name="<?= htmlspecialchars($item['original_name'], ENT_QUOTES, 'UTF-8') ?>"
            data-title="<?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8') ?>"
            data-alt="<?= htmlspecialchars((string) $item['alt_text'], ENT_QUOTES, 'UTF-8') ?>"
            data-mime="<?= htmlspecialchars($item['mime_type'], ENT_QUOTES, 'UTF-8') ?>"
            data-size="<?= (int) $item['size'] ?>"
            data-created="<?= htmlspecialchars($item['created_at'], ENT_QUOTES, 'UTF-8') ?>"
        >
            <div class="media-card-actions">
                <a
                    class="media-card-action"
                    href="<?= htmlspecialchars($item['path'], ENT_QUOTES, 'UTF-8') ?>"
                    download="<?= htmlspecialchars($item['original_name'], ENT_QUOTES, 'UTF-8') ?>"
                    title="Baixar"
                >
                    <i class="fa-solid fa-download"></i>
                </a>
                <button type="button" class="media-card-action media-card-action-delete" title="Excluir">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
            <?php if ($item['kind'] === 'image'): ?>
                <img src="<?= htmlspecialchars($item['path'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
            <?php else: ?>
                <i class="fa-solid <?= \App\Core\Html::fileIcon($item['mime_type']) ?>"></i>
            <?php endif; ?>
            <span class="media-card-name"><?= htmlspecialchars($item['original_name'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php
    };

    $renderMediaListRow = function (array $item) use ($formatMediaSize): void {
        ?>
        <div
            class="media-card media-list-item"
            tabindex="0"
            data-id="<?= (int) $item['id'] ?>"
            data-url="<?= htmlspecialchars($item['path'], ENT_QUOTES, 'UTF-8') ?>"
            data-kind="<?= htmlspecialchars($item['kind'], ENT_QUOTES, 'UTF-8') ?>"
            data-name="<?= htmlspecialchars($item['original_name'], ENT_QUOTES, 'UTF-8') ?>"
            data-title="<?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8') ?>"
            data-alt="<?= htmlspecialchars((string) $item['alt_text'], ENT_QUOTES, 'UTF-8') ?>"
            data-mime="<?= htmlspecialchars($item['mime_type'], ENT_QUOTES, 'UTF-8') ?>"
            data-size="<?= (int) $item['size'] ?>"
            data-created="<?= htmlspecialchars($item['created_at'], ENT_QUOTES, 'UTF-8') ?>"
        >
            <div class="media-list-thumb">
                <?php if ($item['kind'] === 'image'): ?>
                    <img src="<?= htmlspecialchars($item['path'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                <?php else: ?>
                    <i class="fa-solid <?= \App\Core\Html::fileIcon($item['mime_type']) ?>"></i>
                <?php endif; ?>
            </div>
            <span class="media-list-name"><?= htmlspecialchars($item['original_name'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="media-list-meta d-none d-md-inline"><?= htmlspecialchars($item['mime_type'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="media-list-meta"><?= $formatMediaSize((int) $item['size']) ?></span>
            <span class="media-list-meta d-none d-sm-inline"><?= date('d/m/Y', strtotime($item['created_at'])) ?></span>
            <div class="media-card-actions media-list-actions">
                <a
                    class="media-card-action"
                    href="<?= htmlspecialchars($item['path'], ENT_QUOTES, 'UTF-8') ?>"
                    download="<?= htmlspecialchars($item['original_name'], ENT_QUOTES, 'UTF-8') ?>"
                    title="Baixar"
                >
                    <i class="fa-solid fa-download"></i>
                </a>
                <button type="button" class="media-card-action media-card-action-delete" title="Excluir">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>
        <?php
    };
?>

<?php if ($view === 'grouped'): ?>
    <div class="media-groups" id="media-grid">
        <?php if (empty($groups)): ?>
            <p class="text-secondary">Nenhum arquivo encontrado.</p>
        <?php endif; ?>
        <?php foreach ($groups as $index => $group): ?>
            <details class="media-month-group" <?= $index === 0 ? 'open' : '' ?>>
                <summary>
                    <?= htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') ?>
                    <span class="text-secondary">(<?= count($group['items']) ?>)</span>
                </summary>
                <div class="media-grid">
                    <?php foreach ($group['items'] as $item) {
                        $renderMediaCard($item);
                    } ?>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
<?php elseif ($view === 'list'): ?>
    <div class="media-list" id="media-grid">
        <?php if (empty($mediaItems)): ?>
            <p class="text-secondary">Nenhum arquivo encontrado.</p>
        <?php else: ?>
            <?php foreach ($mediaItems as $item) {
                $renderMediaListRow($item);
            } ?>
        <?php endif; ?>
    </div>
    <?php require __DIR__ . '/pagination.php'; ?>
<?php else: ?>
    <div class="media-grid" id="media-grid">
        <?php if (empty($mediaItems)): ?>
            <p class="text-secondary">Nenhum arquivo encontrado.</p>
        <?php else: ?>
            <?php foreach ($mediaItems as $item) {
                $renderMediaCard($item);
            } ?>
        <?php endif; ?>
    </div>
    <?php require __DIR__ . '/pagination.php'; ?>
<?php endif; ?>
