ALTER TABLE posts
    ADD COLUMN author_id INT UNSIGNED NULL AFTER author_name,
    ADD CONSTRAINT fk_posts_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL;

UPDATE posts
LEFT JOIN users ON users.name = posts.author_name
SET posts.author_id = users.id
WHERE posts.author_id IS NULL
  AND users.id IS NOT NULL;

UPDATE posts
SET author_id = (SELECT users.id FROM users ORDER BY users.id LIMIT 1)
WHERE author_id IS NULL;

CREATE TABLE IF NOT EXISTS post_category (
    post_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (post_id, category_id),
    CONSTRAINT fk_post_category_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_post_category_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO post_category (post_id, category_id)
SELECT id, category_id
FROM posts
WHERE category_id IS NOT NULL;
