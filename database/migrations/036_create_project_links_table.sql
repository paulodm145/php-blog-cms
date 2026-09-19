-- Repositorios de um projeto podem ser mais de um (backend + frontend,
-- por exemplo) — antes so cabia uma URL em projects.source_url. Vira uma
-- tabela de associacao, mesmo padrao ja usado em project_images: quantos
-- links quiser, com rotulo opcional, na ordem em que foram adicionados.

CREATE TABLE IF NOT EXISTS project_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    label VARCHAR(80) NULL,
    url VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_project_links_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_links_project (project_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO project_links (project_id, label, url, sort_order)
SELECT id, NULL, source_url, 0
FROM projects
WHERE source_url IS NOT NULL AND source_url != '';

ALTER TABLE projects
    DROP COLUMN source_url;
