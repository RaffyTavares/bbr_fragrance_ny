-- ============================================================
-- BBR Fragance - Promo Settings Migration
-- Run this ONCE to add promo settings to existing database
-- ============================================================

USE bbr_fragance;

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('promo_active', '1'),
('promo_title', 'Promocion del Mes'),
('promo_subtitle', 'Hasta 30% de descuento en perfumes seleccionados + envio gratis en compras mayores a $100'),
('promo_link', 'pages/productos.html?offers=1'),
('promo_bullets', '["Combos 2x1 en fragancias seleccionadas","Regalo sorpresa en compras mayores a $200","Muestras gratis con cada pedido"]');
