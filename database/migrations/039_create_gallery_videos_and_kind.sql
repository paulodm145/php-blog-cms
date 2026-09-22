-- Galeria de video: mesmo conceito da galeria de foto (shortcode
-- [@slug@], reordenavel), mas sem upload nenhum — so embed de YouTube
-- ou Google Drive. "kind" faz uma galeria existente (so foto ate aqui)
-- virar tambem um tipo video; uma galeria e sempre so de um tipo.

ALTER TABLE galleries ADD COLUMN kind ENUM('photo', 'video') NOT NULL DEFAULT 'photo' AFTER slug;

CREATE TABLE IF NOT EXISTS gallery_videos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gallery_id INT UNSIGNED NOT NULL,
    url VARCHAR(500) NOT NULL,
    provider ENUM('youtube', 'google_drive') NOT NULL,
    external_id VARCHAR(100) NOT NULL,
    title VARCHAR(200) NULL,
    thumbnail_media_id INT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_gallery_videos_gallery FOREIGN KEY (gallery_id) REFERENCES galleries(id) ON DELETE CASCADE,
    CONSTRAINT fk_gallery_videos_thumbnail FOREIGN KEY (thumbnail_media_id) REFERENCES media(id) ON DELETE SET NULL,
    INDEX idx_gallery_videos_gallery (gallery_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
