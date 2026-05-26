-- ============================================================
-- BBR Fragrance - Migracion: Comprobantes Fiscales (NCF)
-- Ejecutar sobre base de datos existente
-- ============================================================

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

-- 2. Agregar RNC y Cedula a clientes
ALTER TABLE customers ADD COLUMN rnc VARCHAR(11) NULL AFTER name;
ALTER TABLE customers ADD COLUMN cedula VARCHAR(13) NULL AFTER rnc;
ALTER TABLE customers ADD INDEX idx_rnc (rnc);

-- 3. Agregar volume_ml a productos (faltaba en schema inicial)
ALTER TABLE products ADD COLUMN volume_ml INT NULL AFTER description;

-- 4. Agregar campos NCF a ventas
ALTER TABLE sales ADD COLUMN ncf_number VARCHAR(13) NULL AFTER sale_number;
ALTER TABLE sales ADD COLUMN ncf_type VARCHAR(3) NULL AFTER ncf_number;
ALTER TABLE sales ADD COLUMN customer_rnc VARCHAR(11) NULL AFTER ncf_type;
ALTER TABLE sales ADD INDEX idx_ncf (ncf_number);

-- 5. Settings de NCF
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('store_rnc', ''),
('ncf_enabled', '0');

-- 6. Permiso de NCF
INSERT IGNORE INTO permissions (permission_key, name, description, module, sort_order) VALUES
('ncf.manage', 'Gestionar NCF', 'Puede administrar secuencias de comprobantes fiscales', 'settings', 92);

-- Otorgar a admin
INSERT IGNORE INTO role_permissions (role, permission_key) VALUES ('admin', 'ncf.manage');
