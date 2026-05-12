-- ============================================================
-- BBR Fragance - Migracion: Checkout Profesional
-- Settings para SMTP, datos bancarios y monto minimo
-- ============================================================

USE bbr_fragance;

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('smtp_host', ''),
('smtp_port', '587'),
('smtp_user', ''),
('smtp_pass', ''),
('smtp_from_name', 'BBR Fragance'),
('smtp_from_email', ''),
('bank_name', ''),
('bank_account_number', ''),
('bank_account_holder', ''),
('bank_account_type', 'Ahorros'),
('min_order_amount', '0');
