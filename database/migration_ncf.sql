-- ============================================================
-- BBR Fragrance - Migracion: Comprobantes Fiscales (NCF)
-- Ejecutar sobre base de datos existente
-- ============================================================

USE BBR Fragrance;

-- 1. Tabla de secuencias NCF
CREATE TABLE IF NOT EXISTS ncf_sequences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ncf_type VARCHAR(3) NOT NULL,
    type_name VARCHAR(50) NOT NULL,
    prefix VARCHAR(3) NOT NULL,
    current_number INT NOT NULL DEFAULT 0,
    start_number INT NOT NULL DEFAULT 1,
    end_number INT NOT NULL,
    expiration_date DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (ncf_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Agregar RNC y Cedula a clientes (compatible con MySQL 5.7+)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'BBR Fragrance' AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'rnc');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN rnc VARCHAR(11) NULL AFTER name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'BBR Fragrance' AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'cedula');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE customers ADD COLUMN cedula VARCHAR(13) NULL AFTER rnc', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'BBR Fragrance' AND TABLE_NAME = 'customers' AND INDEX_NAME = 'idx_rnc');
SET @sql = IF(@idx_exists = 0, 'ALTER TABLE customers ADD INDEX idx_rnc (rnc)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Agregar campos NCF a ventas
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'BBR Fragrance' AND TABLE_NAME = 'sales' AND COLUMN_NAME = 'ncf_number');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE sales ADD COLUMN ncf_number VARCHAR(13) NULL AFTER sale_number', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'BBR Fragrance' AND TABLE_NAME = 'sales' AND COLUMN_NAME = 'ncf_type');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE sales ADD COLUMN ncf_type VARCHAR(3) NULL AFTER ncf_number', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'BBR Fragrance' AND TABLE_NAME = 'sales' AND COLUMN_NAME = 'customer_rnc');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE sales ADD COLUMN customer_rnc VARCHAR(11) NULL AFTER ncf_type', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'BBR Fragrance' AND TABLE_NAME = 'sales' AND INDEX_NAME = 'idx_ncf');
SET @sql = IF(@idx_exists = 0, 'ALTER TABLE sales ADD INDEX idx_ncf (ncf_number)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Settings de NCF
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('store_rnc', ''),
('ncf_enabled', '0');

-- 5. Permiso de NCF
INSERT IGNORE INTO permissions (permission_key, name, description, module, sort_order) VALUES
('ncf.manage', 'Gestionar NCF', 'Puede administrar secuencias de comprobantes fiscales', 'settings', 92);

-- Otorgar a admin
INSERT IGNORE INTO role_permissions (role, permission_key) VALUES ('admin', 'ncf.manage');
