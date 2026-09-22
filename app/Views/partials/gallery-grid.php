<?php
/**
 * Grade publica de miniaturas de uma galeria — reutilizada tanto pelo
 * shortcode [@slug@] (via GalleryRepository::expandShortcodes()) quanto
 * pela galeria de screenshots de projeto (project_images), unificando as
 * duas sob o mesmo lightbox generico (public/assets/js/gallery-lightbox.js).
 * Aceita foto e video no mesmo grid, cada item marcado com 'type'
 * ('image'/'video') — o lightbox decide <img> ou <iframe> por isso.
 *
 * Espera no escopo:
 * - $galleryKey (string): identifica o grupo pro lightbox (data-gallery) —
 *   distingue miniaturas de galerias diferentes na mesma pagina.
 * - $items (array): cada item com 'type', 'url' (foto original ou embed
 *   de video, usado no lightbox em tela cheia), 'thumbnail_url' (pode
 *   ser null num video sem miniatura resolvida — cai no placeholder de
 *   play) e 'name' (usado como alt/title).
 */
?>
<div class="row g-2">
    <?php foreach ($items as $index => $item): ?>
        <div class="col-4 col-md-3">
            <button
                type="button"
                class="gallery-grid-thumb cover d-block w-100"
                style="height:90px"
                data-gallery="<?= htmlspecialchars($galleryKey, ENT_QUOTES, 'UTF-8') ?>"
                data-index="<?= (int) $index ?>"
                data-type="<?= htmlspecialchars($item['type'], ENT_QUOTES, 'UTF-8') ?>"
                data-url="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>"
            >
                <?php if (!empty($item['thumbnail_url'])): ?>
                    <img src="<?= htmlspecialchars($item['thumbnail_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <?php else: ?>
                    <span class="gallery-grid-thumb-placeholder d-flex align-items-center justify-content-center w-100 h-100">
                        <i class="fa-solid fa-circle-play"></i>
                    </span>
                <?php endif; ?>
            </button>
        </div>
    <?php endforeach; ?>
</div>
