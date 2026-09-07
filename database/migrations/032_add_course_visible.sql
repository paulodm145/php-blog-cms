-- Controle de visibilidade por curso: liga/desliga se aparece na pagina
-- publica /curriculo e no PDF gerado, sem precisar excluir o registro.
-- Comeca visivel (1) pra nao esconder cursos ja cadastrados.

ALTER TABLE resume_courses
    ADD COLUMN visible TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 AFTER certificate_url;
