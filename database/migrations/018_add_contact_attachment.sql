ALTER TABLE contact_messages
    ADD COLUMN attachment VARCHAR(255) NULL,
    ADD COLUMN attachment_name VARCHAR(160) NULL;
