ALTER TABLE posts
    ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'published' AFTER category_id;

UPDATE posts
SET status = CASE
    WHEN published_at IS NULL THEN 'draft'
    ELSE 'published'
END;

CREATE INDEX idx_posts_status_published_at ON posts (status, published_at);

