ALTER TABLE posts
    ADD COLUMN deleted_at DATETIME NULL AFTER status;

CREATE INDEX idx_posts_deleted_at ON posts (deleted_at);

