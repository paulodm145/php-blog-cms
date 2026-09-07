-- Currículo editável: experiência e formação viram tabelas proprias;
-- tagline/resumo/habilidades/foto/pdf viram chaves em settings, seguindo o
-- mesmo padrao ja usado pro resto do site.

CREATE TABLE IF NOT EXISTS resume_experience (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(160) NOT NULL,
    company VARCHAR(160) NOT NULL,
    period VARCHAR(80) NOT NULL,
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_resume_experience_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resume_education (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course VARCHAR(160) NOT NULL,
    institution VARCHAR(160) NOT NULL,
    period VARCHAR(80) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_resume_education_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value) VALUES
('resume_tagline', 'Desenvolvedor fullstack com sólida experiência em PHP (Laravel, Symfony), bancos de dados relacionais e front-end moderno (React, Vue, TypeScript). Pós-graduado em Inteligência Artificial e Ciência de Dados.'),
('resume_skills', 'PHP, Laravel, Symfony, MySQL, PostgreSQL, Node.js, Express, TypeScript, JavaScript, React, Vue.js, AngularJS, IA / RAG, API OpenAI, SQL Server'),
('resume_photo', ''),
('resume_pdf', '/assets/files/curriculo-paulo-bolsanello.pdf')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO resume_experience (role, company, period, description, sort_order) VALUES
('Analista Programador PHP', 'Impacta Soluções WEB', 'nov/2024 — jul/2026', 'Manutenção e desenvolvimento de novas features em aplicações legadas com PHP 7.4 puro e Laravel 8. Desenvolvimento de aplicações de IA/RAG utilizando Laravel 12, integração de aplicações com a API da OpenAI.', 1),
('Analista Programador PHP', 'Dotse — Software Engineer', 'desde out/2022', 'Desenvolvimento de aplicações backend com PHP, Laravel e PostgreSQL. Desenvolvimento e manutenção de aplicações frontend com JavaScript/TypeScript e React.', 2),
('Analista Programador PHP', 'Advance Telecomunicações Ltda', 'ago/2022 — set/2022', 'Desenvolvimento de aplicações backend com MySQL, PHP e Laravel. Frontend com JavaScript, Node.js, Vue.js e Express.', 3),
('Programador PHP', 'Basis Tecnologia da Informação S.A.', 'nov/2021 — jul/2022', 'Desenvolvedor fullstack com PHP, Symfony, Zend Framework, jQuery, AngularJS e PrimeNG. Bancos de dados PostgreSQL e SQL Server 2005.', 4)
ON DUPLICATE KEY UPDATE role = VALUES(role);

INSERT INTO resume_education (course, institution, period, sort_order) VALUES
('Pós-graduação em Inteligência Artificial e Ciência de Dados', 'Universidade Federal do Espírito Santo (UFES)', '2024 — 2025', 1),
('Tecnologia em Análise de Sistemas', 'Multivix', '2018 — 2022', 2),
('Licenciatura em Informática', 'Instituto Federal do Espírito Santo (Ifes) — Campus Cachoeiro de Itapemirim', '2017 — 2024', 3),
('Técnico em Manutenção e Suporte em Informática', 'Escola Estadual Honório Fraga', '2019 — 2021', 4),
('Técnico em Informática', 'Escola Estadual Honório Fraga', '2017 — 2018', 5)
ON DUPLICATE KEY UPDATE course = VALUES(course);
