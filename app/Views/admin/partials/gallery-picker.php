<?php if (count($galleries) === 0): ?>
    <p class="text-secondary mb-0">Nenhuma galeria cadastrada ainda. <a href="/admin/galerias/create" target="_blank" rel="noopener">Criar uma</a>.</p>
<?php else: ?>
    <div class="d-flex flex-column gap-2">
        <?php foreach ($galleries as $gallery): ?>
            <button type="button" class="gallery-picker-item d-flex align-items-center gap-2" data-slug="<?= htmlspecialchars($gallery['slug'], ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($gallery['cover_thumbnail_url'])): ?>
                    <img src="<?= htmlspecialchars($gallery['cover_thumbnail_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                <?php else: ?>
                    <span class="d-inline-flex align-items-center justify-content-center text-secondary" style="width: 40px; height: 40px; border-radius: 4px; background: var(--admin-bg);">
                        <i class="fa-solid <?= $gallery['kind'] === 'video' ? 'fa-clapperboard' : 'fa-photo-film' ?>"></i>
                    </span>
                <?php endif; ?>
                <span>
                    <?= htmlspecialchars($gallery['name'], ENT_QUOTES, 'UTF-8') ?>
                    <span class="text-secondary small d-block">
                        <?= (int) $gallery['item_count'] ?>
                        <?php if ($gallery['kind'] === 'video'): ?>
                            vídeo<?= (int) $gallery['item_count'] === 1 ? '' : 's' ?>
                        <?php else: ?>
                            foto<?= (int) $gallery['item_count'] === 1 ? '' : 's' ?>
                        <?php endif; ?>
                    </span>
                </span>
            </button>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
