-- Active: 1778727422854@@127.0.0.1@3306@bbr_fragrance
-- ============================================================
-- Migracion: Registro automatico de ventas web en tabla sales
-- Ejecutar una sola vez contra la BD BBR_Fragrance
-- ============================================================

-- 1. Permitir user_id NULL para ventas generadas por el sistema (web)
ALTER TABLE sales MODIFY COLUMN user_id INT NULL;

-- 2. Ampliar ENUM payment_method para incluir tipos de pago web
ALTER TABLE sales MODIFY COLUMN payment_method
    ENUM('cash','card','transfer','mixed','card_online','pending') NOT NULL;

-- 3. Columna que identifica el origen de la venta (POS fisico vs tienda web)
ALTER TABLE sales ADD COLUMN source ENUM('pos','web') NOT NULL DEFAULT 'pos' AFTER id;

-- 4. Vinculo con el pedido web original (UNIQUE previene registros duplicados)
ALTER TABLE sales ADD COLUMN order_id INT NULL DEFAULT NULL AFTER source;
ALTER TABLE sales ADD UNIQUE KEY uq_sales_order_id (order_id);
ALTER TABLE sales ADD CONSTRAINT fk_sales_order
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL;

-- 5. Indice de apoyo para filtrar por origen
ALTER TABLE sales ADD INDEX idx_source (source);
