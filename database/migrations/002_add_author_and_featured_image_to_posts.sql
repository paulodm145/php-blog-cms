ALTER TABLE posts
    ADD COLUMN author_name VARCHAR(120) NOT NULL DEFAULT 'Equipe Editorial' AFTER excerpt,
    ADD COLUMN featured_image VARCHAR(255) NOT NULL DEFAULT '/assets/images/blog-feature.svg' AFTER author_name;

