-- Documentos privados por experiencia profissional (contratos, notas
-- fiscais, etc.) e compartilhamento temporario via link com token.

CREATE TABLE IF NOT EXISTS resume_experience_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    experience_id INT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size INT UNSIGNED NOT NULL,
    caption VARCHAR(255) NULL,
    uploaded_by INT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_red_experience FOREIGN KEY (experience_id) REFERENCES resume_experience(id) ON DELETE CASCADE,
    INDEX idx_red_experience_id (experience_id),
    INDEX idx_red_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resume_document_shares (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_rds_token (token),
    INDEX idx_rds_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resume_document_share_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    share_id INT UNSIGNED NOT NULL,
    document_id INT UNSIGNED NOT NULL,
    CONSTRAINT fk_rdsi_share FOREIGN KEY (share_id) REFERENCES resume_document_shares(id) ON DELETE CASCADE,
    CONSTRAINT fk_rdsi_document FOREIGN KEY (document_id) REFERENCES resume_experience_documents(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_rdsi_share_document (share_id, document_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
