CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(80) NOT NULL PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value) VALUES
('blog_name', 'Blog PHP'),
('blog_description', 'Artigos sobre desenvolvimento, arquitetura e boas praticas em PHP.'),
('github_url', ''),
('linkedin_url', '')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);
