-- ============================================================
-- BBR Fragrance - Fase 1 Migration: Roles, Permisos, Usuarios
-- Run this ONCE against existing database
-- ============================================================

USE BBR Fragrance;

-- 1. Update users table: expand role ENUM and add phone column
ALTER TABLE users MODIFY role ENUM('admin', 'vendedor', 'cajero', 'tecnico') DEFAULT 'cajero';
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL AFTER email;

-- 2. Create permissions table
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    module VARCHAR(50) NOT NULL,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create role_permissions table
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(20) NOT NULL,
    permission_key VARCHAR(50) NOT NULL,
    UNIQUE KEY unique_role_perm (role, permission_key),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Insert permissions (ignore duplicates)
INSERT IGNORE INTO permissions (permission_key, name, description, module, sort_order) VALUES
('dashboard.view', 'Ver Dashboard', 'Acceso al panel de resumen', 'dashboard', 1),
('pos.access', 'Acceso al POS', 'Puede realizar ventas en punto de venta', 'pos', 10),
('pos.apply_discount', 'Aplicar descuentos', 'Puede aplicar descuentos en ventas', 'pos', 11),
('products.view', 'Ver productos', 'Puede ver el listado de productos', 'products', 20),
('products.create', 'Crear productos', 'Puede agregar nuevos productos', 'products', 21),
('products.edit', 'Editar productos', 'Puede modificar productos existentes', 'products', 22),
('products.delete', 'Eliminar productos', 'Puede eliminar productos', 'products', 23),
('orders.view', 'Ver pedidos', 'Puede ver el listado de pedidos', 'orders', 30),
('orders.manage', 'Gestionar pedidos', 'Puede cambiar estado de pedidos', 'orders', 31),
('expenses.view', 'Ver gastos', 'Puede ver el listado de gastos', 'expenses', 40),
('expenses.manage', 'Gestionar gastos', 'Puede crear, editar y eliminar gastos', 'expenses', 41),
('cash_register.access', 'Acceso a caja', 'Puede abrir y operar la caja registradora', 'cash_register', 50),
('cash_register.close', 'Cerrar caja', 'Puede realizar el cierre de caja', 'cash_register', 51),
('reports.view', 'Ver reportes', 'Acceso a reportes y estadisticas', 'reports', 60),
('customers.view', 'Ver clientes', 'Puede ver el listado de clientes', 'customers', 70),
('customers.manage', 'Gestionar clientes', 'Puede crear, editar clientes', 'customers', 71),
('credits.view', 'Ver creditos', 'Puede ver cuentas de credito', 'credits', 80),
('credits.manage', 'Gestionar creditos', 'Puede abrir cuentas y registrar pagos', 'credits', 81),
('settings.view', 'Ver configuracion', 'Puede ver la configuracion del sistema', 'settings', 90),
('settings.manage', 'Gestionar configuracion', 'Puede modificar la configuracion', 'settings', 91),
('users.view', 'Ver usuarios', 'Puede ver el listado de usuarios', 'users', 100),
('users.manage', 'Gestionar usuarios', 'Puede crear, editar y desactivar usuarios', 'users', 101),
('roles.manage', 'Gestionar roles', 'Puede modificar permisos de los roles', 'roles', 110);

-- 5. Assign permissions to roles (ignore duplicates)
-- Admin: all permissions
INSERT IGNORE INTO role_permissions (role, permission_key)
SELECT 'admin', permission_key FROM permissions;

-- Vendedor
INSERT IGNORE INTO role_permissions (role, permission_key) VALUES
('vendedor', 'dashboard.view'),
('vendedor', 'pos.access'),
('vendedor', 'pos.apply_discount'),
('vendedor', 'products.view'),
('vendedor', 'products.create'),
('vendedor', 'products.edit'),
('vendedor', 'orders.view'),
('vendedor', 'orders.manage'),
('vendedor', 'customers.view'),
('vendedor', 'customers.manage'),
('vendedor', 'credits.view');

-- Cajero
INSERT IGNORE INTO role_permissions (role, permission_key) VALUES
('cajero', 'dashboard.view'),
('cajero', 'pos.access'),
('cajero', 'products.view'),
('cajero', 'orders.view'),
('cajero', 'cash_register.access'),
('cajero', 'customers.view');

-- Tecnico
INSERT IGNORE INTO role_permissions (role, permission_key) VALUES
('tecnico', 'dashboard.view'),
('tecnico', 'products.view'),
('tecnico', 'products.edit');

SELECT 'Migration Fase 1 completed successfully!' AS result;
