<?php
/**
 * Grade publica de miniaturas de uma galeria — reutilizada tanto pelo
 * shortcode [@slug@] (via GalleryRepository::expandShortcodes()) quanto
 * pela galeria de screenshots de projeto (project_images), unificando as
 * duas sob o mesmo lightbox generico (public/assets/js/gallery-lightbox.js).
 *
 * Espera no escopo:
 * - $galleryKey (string): identifica o grupo pro lightbox (data-gallery) —
 *   distingue miniaturas de galerias diferentes na mesma pagina.
 * - $photos (array): cada item com 'url' (arquivo original, usado no
 *   lightbox em tela cheia) e 'thumbnail_url' (miniatura, usada aqui).
 */
?>
<div class="row g-2">
    <?php foreach ($photos as $index => $photo): ?>
        <div class="col-4 col-md-3">
            <button
                type="button"
                class="gallery-grid-thumb cover d-block w-100"
                style="height:90px"
                data-gallery="<?= htmlspecialchars($galleryKey, ENT_QUOTES, 'UTF-8') ?>"
                data-index="<?= (int) $index ?>"
                data-url="<?= htmlspecialchars($photo['url'], ENT_QUOTES, 'UTF-8') ?>"
            >
                <img src="<?= htmlspecialchars($photo['thumbnail_url'] ?: $photo['url'], ENT_QUOTES, 'UTF-8') ?>" alt="">
            </button>
        </div>
    <?php endforeach; ?>
</div>
