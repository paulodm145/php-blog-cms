INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Pr2 Análise e Desenvolvimento de Software'),
('contact_email', ''),
('whatsapp_number', '')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);
