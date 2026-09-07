-- Carga horaria do curso precisa de precisao de minutos (ex: "1h29min"),
-- nao so horas inteiras. Guarda o total em minutos; o form tem dois campos
-- (horas + minutos) que a aplicacao combina antes de gravar.
--
-- A coluna antiga "hours" guardava horas inteiras — se algum curso ja foi
-- salvo com ela, o UPDATE abaixo converte pra minutos antes do RENAME, pra
-- nao virar um numero de minutos 60x menor que o real.

UPDATE resume_courses SET hours = hours * 60 WHERE hours IS NOT NULL;

ALTER TABLE resume_courses
    CHANGE COLUMN hours duration_minutes SMALLINT UNSIGNED NULL;
