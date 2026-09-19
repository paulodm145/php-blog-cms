-- Galerias de fotos reutilizaveis (shortcode [@slug@]) — nao pertencem a
-- nenhum post/projeto especifico, sao um recurso independente resolvido
-- so na hora de exibir a pagina. Mesmo padrao de associacao ja usado em
-- projects/project_images.

CREATE TABLE IF NOT EXISTS galleries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_galleries_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gallery_media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gallery_id INT UNSIGNED NOT NULL,
    media_id INT UNSIGNED NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_gallery_media_gallery FOREIGN KEY (gallery_id) REFERENCES galleries(id) ON DELETE CASCADE,
    CONSTRAINT fk_gallery_media_media FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE,
    INDEX idx_gallery_media_gallery (gallery_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
