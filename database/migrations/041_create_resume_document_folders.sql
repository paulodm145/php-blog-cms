-- Pastas livres pra documentos nao ligados a nenhuma experiencia
-- profissional, organizadas em arvore (auto-referencia via parent_id).

CREATE TABLE IF NOT EXISTS resume_document_folders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rdf_parent FOREIGN KEY (parent_id) REFERENCES resume_document_folders(id) ON DELETE CASCADE,
    INDEX idx_rdf_parent_id (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE resume_experience_documents
    MODIFY experience_id INT UNSIGNED NULL,
    ADD COLUMN folder_id INT UNSIGNED NULL AFTER experience_id,
    ADD CONSTRAINT fk_red_folder FOREIGN KEY (folder_id) REFERENCES resume_document_folders(id) ON DELETE CASCADE,
    ADD INDEX idx_red_folder_id (folder_id);
