-- ============================================================
-- BBR Fragrance - Migracion: Pasarela Cardnet (Pago en Linea)
-- ============================================================
-- Ejecutar UNA SOLA VEZ sobre la base de datos `BBR Fragrance`.
-- Agrega:
--   * Settings para credenciales Cardnet
--   * Toggle del metodo de pago "card_online" en checkout
--   * Columnas en `orders` para guardar la respuesta de Cardnet
-- ============================================================

-- 1) Settings (clave-valor) para Cardnet
INSERT INTO settings (setting_key, setting_value) VALUES
    ('cardnet_enabled',                  '0'),
    ('cardnet_environment',              'sandbox'),  -- sandbox | production
    ('cardnet_merchant_number',          ''),         -- Numero de comercio (15 digitos)
    ('cardnet_merchant_terminal',        ''),         -- Numero de terminal (8 digitos)
    ('cardnet_merchant_name',            'BBR Fragrance'),
    ('cardnet_merchant_type',            ''),         -- MCC (Merchant Category Code)
    ('cardnet_secret_key',               ''),         -- Clave secreta (HMAC)
    ('cardnet_currency_code',            '214'),      -- 214 = DOP
    ('cardnet_acquiring_inst_code',      '349'),      -- 349 = Cardnet
    ('cardnet_return_page',              ''),         -- URL absoluta de retorno (autocalculada si vacio)
    ('cardnet_cancel_page',              ''),         -- URL absoluta de cancelacion (autocalculada si vacio)
    ('checkout_pay_card_online',         '0')         -- Mostrar opcion en el checkout
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- 2) Columnas en orders para rastrear el pago online
ALTER TABLE orders
    ADD COLUMN payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending' AFTER payment_method,
    ADD COLUMN payment_gateway VARCHAR(30) NULL AFTER payment_status,
    ADD COLUMN payment_session_key VARCHAR(255) NULL AFTER payment_gateway,
    ADD COLUMN payment_transaction_id VARCHAR(100) NULL AFTER payment_session_key,
    ADD COLUMN payment_authorization VARCHAR(50) NULL AFTER payment_transaction_id,
    ADD COLUMN payment_response_code VARCHAR(10) NULL AFTER payment_authorization,
    ADD COLUMN payment_response_raw TEXT NULL AFTER payment_response_code,
    ADD COLUMN payment_paid_at TIMESTAMP NULL AFTER payment_response_raw,
    ADD INDEX idx_payment_status (payment_status),
    ADD INDEX idx_payment_session (payment_session_key);

-- 3) Permitir pagos online en el ENUM de payment_method (ya es VARCHAR(50), no requiere cambio)
-- Se usaran los valores: cash | card | transfer | card_online | pending
