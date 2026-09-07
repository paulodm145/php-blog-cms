-- Secao "Projetos": subsistema novo (nao existe nenhum resquicio da antiga
-- tabela `projects` da epoca institucional, derrubada em 020). Portfolio
-- estilo LinkedIn: cada projeto tem periodo, capa (Biblioteca de Midia),
-- tecnologias, links, e um corpo longo (Quill) igual a posts/paginas.

CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    tagline VARCHAR(255) NOT NULL DEFAULT '',
    content TEXT NOT NULL,
    cover_media_id INT UNSIGNED NULL,
    technologies VARCHAR(255) NOT NULL DEFAULT '',
    role VARCHAR(160) NULL,
    project_type VARCHAR(20) NULL,
    live_url VARCHAR(255) NULL,
    source_url VARCHAR(255) NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    featured TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    sort_order INT NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_projects_cover_media FOREIGN KEY (cover_media_id) REFERENCES media(id) ON DELETE SET NULL,
    INDEX idx_projects_status (status),
    INDEX idx_projects_featured (featured),
    INDEX idx_projects_deleted_at (deleted_at),
    INDEX idx_projects_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
