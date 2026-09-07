-- Cursos ganham data inicial/final estruturadas (em vez do texto livre de
-- periodo), carga horaria e um certificado: um PDF anexado (via biblioteca
-- de midia) e/ou uma URL de emissao/verificacao, no mesmo espirito do
-- credential_url que resume_certifications ja tinha.

ALTER TABLE resume_courses
    DROP COLUMN period,
    ADD COLUMN start_date DATE NULL AFTER institution,
    ADD COLUMN end_date DATE NULL AFTER start_date,
    ADD COLUMN hours SMALLINT UNSIGNED NULL AFTER end_date,
    ADD COLUMN certificate_media_id INT UNSIGNED NULL AFTER hours,
    ADD COLUMN certificate_url VARCHAR(255) NULL AFTER certificate_media_id,
    ADD CONSTRAINT fk_resume_courses_certificate_media
        FOREIGN KEY (certificate_media_id) REFERENCES media(id) ON DELETE SET NULL;
