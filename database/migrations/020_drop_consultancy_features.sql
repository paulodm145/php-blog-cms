-- Remove as funcionalidades do site institucional (Pr2 Consultoria) que nao
-- fazem sentido num blog pessoal: projetos com galeria, clientes, mensagens
-- de contato/orcamento e a configuracao de SMTP usada so pelo formulario de
-- contato.

DROP TABLE IF EXISTS project_images;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS contact_messages;

DELETE FROM settings WHERE setting_key IN (
    'blog_name',
    'contact_email',
    'whatsapp_number',
    'smtp_host',
    'smtp_port',
    'smtp_encryption',
    'smtp_username',
    'smtp_password',
    'smtp_from_email'
);
