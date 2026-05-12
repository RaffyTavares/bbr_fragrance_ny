-- ============================================================
-- BBR Fragance - Datos Iniciales (Seed)
-- ============================================================

USE bbr_fragance;

-- ============================================================
-- USUARIO ADMIN
-- ============================================================
INSERT INTO users (username, password_hash, full_name, email, role, is_active) VALUES
('admin', '$2y$10$Y1LUz/24Hslcd3/PNmPUlerMnj2cYP37pksyZaMe2Iuk/KR7rLLT.', 'Administrador', 'bbrperfume@gmail.com', 'admin', 1);

-- ============================================================
-- CATEGORIAS
-- ============================================================
INSERT INTO categories (name, slug, description, icon, sort_order) VALUES
('Mujer', 'mujer', 'Fragancias femeninas', 'fa-venus', 1),
('Hombre', 'hombre', 'Fragancias masculinas', 'fa-mars', 2),
('Unisex', 'unisex', 'Fragancias unisex', 'fa-venus-mars', 3),
('Arabes', 'arabes', 'Fragancias arabes', 'fa-moon', 4);

-- ============================================================
-- FAMILIAS OLFATIVAS
-- ============================================================
INSERT INTO olfactory_families (name, slug, description, icon, gradient_from, gradient_to, sort_order) VALUES
('Dulce', 'dulce', 'Notas dulces y golosas', 'fa-candy-cane', 'from-pink-900', 'to-purple-900', 1),
('Amaderado', 'amaderado', 'Notas de madera y tierra', 'fa-tree', 'from-amber-900', 'to-yellow-900', 2),
('Citrico', 'citrico', 'Notas citricas y frescas', 'fa-lemon', 'from-yellow-800', 'to-green-800', 3),
('Oriental', 'oriental', 'Notas orientales y especiadas', 'fa-star-and-crescent', 'from-red-900', 'to-orange-900', 4),
('Fresco', 'fresco', 'Notas frescas y acuaticas', 'fa-water', 'from-cyan-900', 'to-blue-900', 5),
('Intenso', 'intenso', 'Notas intensas y profundas', 'fa-fire', 'from-gray-900', 'to-red-900', 6);

-- ============================================================
-- MARCAS
-- ============================================================
INSERT INTO brands (name, slug) VALUES
('Dior', 'dior'),
('Chanel', 'chanel'),
('Giorgio Armani', 'giorgio-armani'),
('Versace', 'versace'),
('Yves Saint Laurent', 'yves-saint-laurent'),
('Paco Rabanne', 'paco-rabanne'),
('Dolce & Gabbana', 'dolce-gabbana'),
('Creed', 'creed'),
('Montblanc', 'montblanc'),
('Carolina Herrera', 'carolina-herrera'),
('Lancome', 'lancome'),
('Viktor & Rolf', 'viktor-rolf'),
('Tom Ford', 'tom-ford'),
('Calvin Klein', 'calvin-klein'),
('Le Labo', 'le-labo'),
('Maison Francis Kurkdjian', 'maison-francis-kurkdjian'),
('Escentric Molecules', 'escentric-molecules'),
('Jo Malone', 'jo-malone'),
('Thierry Mugler', 'thierry-mugler'),
('Marc Jacobs', 'marc-jacobs'),
('Diptyque', 'diptyque');

-- ============================================================
-- CATEGORIAS DE GASTOS
-- ============================================================
INSERT INTO expense_categories (name, slug, icon, color) VALUES
('Alquiler', 'alquiler', 'fa-building', '#EF4444'),
('Insumos', 'insumos', 'fa-box', '#F59E0B'),
('Salarios', 'salarios', 'fa-users', '#3B82F6'),
('Servicios', 'servicios', 'fa-bolt', '#8B5CF6'),
('Marketing', 'marketing', 'fa-bullhorn', '#EC4899'),
('Transporte', 'transporte', 'fa-truck', '#10B981'),
('Otros', 'otros', 'fa-ellipsis-h', '#6B7280');

-- ============================================================
-- CONFIGURACION
-- ============================================================
INSERT INTO settings (setting_key, setting_value) VALUES
('store_name', 'Bbr_Fragance'),
('whatsapp_number', '+18094855693'),
('contact_email', 'bbrperfume@gmail.com'),
('contact_phone', '+18095261115'),
('address', 'Av. Winston Churchill, esq. Roberto P., Plaza las Americas 1 local 10AA, Santo Domingo, R.D.'),
('currency_symbol', 'RD$'),
('currency_code', 'DOP'),
('tax_name', 'ITBIS'),
('tax_percent', '18'),
('tax_enabled', '1'),
('min_free_shipping', '5000'),
('store_hours', 'Lun-Vie: 10AM-6:30PM | Sab: 10AM-6PM | Dom: Cerrado'),
('promo_active', '1'),
('promo_title', 'Promocion del Mes'),
('promo_subtitle', 'Hasta 30% de descuento en perfumes seleccionados + envio gratis en compras mayores a $100'),
('promo_link', 'pages/productos.html?offers=1'),
('promo_bullets', '["Combos 2x1 en fragancias seleccionadas","Regalo sorpresa en compras mayores a $200","Muestras gratis con cada pedido"]'),
('store_rnc', ''),
('ncf_enabled', '0'),
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
('min_order_amount', '0'),
('checkout_pay_cash', '1'),
('checkout_pay_card', '1'),
('checkout_pay_transfer', '1');

-- ============================================================
-- PRODUCTOS (36 productos del catalogo original)
-- ============================================================

-- === HOMBRE (category_id=2) ===
INSERT INTO products (name, brand_id, category_id, family_id, price, original_price, cost, stock, description, status, is_featured) VALUES
('Sauvage', 1, 2, 2, 8500.00, 9800.00, 4500.00, 25, 'Fragancia masculina salvaje y refinada con notas de bergamota y pimienta.', 'active', 1),
('Bleu de Chanel', 2, 2, 2, 9400.00, NULL, 5200.00, 18, 'Una fragancia amaderada aromatica que encarna la libertad.', 'active', 1),
('Acqua di Gio Profumo', 3, 2, 5, 7600.00, NULL, 4000.00, 22, 'Una reinterpretacion mas intensa y sofisticada del clasico Acqua di Gio.', 'active', 0),
('Eros', 4, 2, 1, 5700.00, 6500.00, 3000.00, 30, 'Inspirado en el dios griego del amor, una fragancia fresca y seductora.', 'active', 1),
('Y Eau de Parfum', 5, 2, 2, 7900.00, NULL, 4200.00, 15, 'Una fragancia intensa y audaz para el hombre moderno.', 'active', 0),
('1 Million', 6, 2, 1, 6100.00, NULL, 3200.00, 28, 'Una fragancia dorada, fresca y especiada para el hombre ambicioso.', 'active', 0),
('The One', 7, 2, 4, 6400.00, NULL, 3400.00, 20, 'Una fragancia elegante con notas de jengibre, cedro y tabaco.', 'active', 0),
('Aventus', 8, 2, 5, 22200.00, NULL, 12000.00, 8, 'La fragancia de nicho mas iconica, simbolo de exito y poder.', 'active', 1),
('Explorer', 9, 2, 2, 4400.00, NULL, 2300.00, 35, 'Inspirada en el espiritu aventurero, una fragancia fresca y terrosa.', 'active', 0),
('Light Blue Pour Homme', 7, 2, 3, 5100.00, NULL, 2700.00, 32, 'Una fragancia vibrante que captura la esencia del Mediterraneo.', 'active', 0),
('Invictus', 6, 2, 5, 5500.00, NULL, 2900.00, 27, 'Una fragancia fresca y poderosa para el hombre victorioso.', 'active', 0),
('Bad Boy', 10, 2, 4, 6300.00, NULL, 3300.00, 22, 'Una fragancia oscura y seductora que rompe las reglas.', 'active', 0);

-- === MUJER (category_id=1) ===
INSERT INTO products (name, brand_id, category_id, family_id, price, original_price, cost, stock, description, status, is_featured) VALUES
('Coco Mademoiselle', 2, 1, 4, 9700.00, NULL, 5400.00, 20, 'Una fragancia fresca y oriental para la mujer moderna e independiente.', 'active', 1),
('J''adore', 1, 1, 1, 9100.00, NULL, 5000.00, 15, 'Un bouquet floral absoluto, simbolo de feminidad y elegancia.', 'active', 1),
('La Vie Est Belle', 11, 1, 1, 8200.00, 9500.00, 4500.00, 18, 'Un iris goloso que celebra la belleza de la vida.', 'active', 1),
('Good Girl', 10, 1, 4, 7500.00, NULL, 4000.00, 24, 'La dualidad de la mujer moderna en un frasco de tacon.', 'active', 1),
('Flowerbomb', 12, 1, 1, 8700.00, NULL, 4800.00, 12, 'Una explosion floral adictiva que transforma lo negativo en positivo.', 'active', 0),
('Black Opium', 5, 1, 4, 8100.00, NULL, 4400.00, 16, 'Una fragancia rock and roll con cafe negro y flores blancas.', 'active', 0),
('Miss Dior', 1, 1, 5, 8300.00, NULL, 4600.00, 19, 'Un homenaje al amor y a la alta costura de Dior.', 'active', 0),
('Chance Eau Tendre', 2, 1, 1, 8900.00, NULL, 4900.00, 14, 'Una fragancia floral frutal llena de ternura y delicadeza.', 'active', 0),
('Alien', 19, 1, 4, 6700.00, NULL, 3600.00, 21, 'Una fragancia solar amaderada, misteriosa y magnifica.', 'active', 0),
('Daisy', 20, 1, 3, 5500.00, NULL, 2900.00, 30, 'Una fragancia fresca y femenina con un toque vintage.', 'active', 0),
('Si Passione', 3, 1, 4, 7300.00, NULL, 3900.00, 17, 'La intensidad de la pasion en una fragancia irresistible.', 'active', 0),
('Very Good Girl', 10, 1, 1, 7700.00, NULL, 4100.00, 23, 'La nueva generacion de Good Girl, mas atrevida y glam.', 'active', 0);

-- === UNISEX (category_id=3) ===
INSERT INTO products (name, brand_id, category_id, family_id, price, original_price, cost, stock, description, status, is_featured) VALUES
('Black Orchid', 13, 3, 6, 9700.00, NULL, 5400.00, 10, 'Lujosa y sensual, una mezcla de orquidea negra y especias.', 'active', 1),
('Oud Wood', 13, 3, 2, 16700.00, NULL, 9000.00, 7, 'Una composicion sofisticada de oud, santal y vetiver.', 'active', 0),
('CK One', 14, 3, 3, 3200.00, NULL, 1700.00, 40, 'El iconico aroma unisex fresco y limpio de los 90s.', 'active', 0),
('Santal 33', 15, 3, 2, 18200.00, NULL, 10000.00, 5, 'Una fragancia de culto con notas de santal, cuero y cardamomo.', 'active', 0),
('Baccarat Rouge 540', 16, 3, 4, 20500.00, NULL, 11000.00, 6, 'Una fragancia excepcional, luminosa y cristalina como el cristal.', 'active', 1),
('Tobacco Vanille', 13, 3, 6, 16100.00, NULL, 8800.00, 9, 'Una mezcla opulenta de tabaco, vainilla y cacao.', 'active', 0),
('Another 13', 15, 3, 2, 17000.00, NULL, 9200.00, 4, 'Moleculas de almizcle que crean una firma olfativa unica.', 'active', 0),
('Molecule 01', 17, 3, 2, 7900.00, NULL, 4300.00, 15, 'Una fragancia molecular que funciona con tu quimica corporal.', 'active', 0),
('Neroli Portofino', 13, 3, 3, 15200.00, NULL, 8200.00, 8, 'Una explosiva mezcla citrica inspirada en la Riviera italiana.', 'active', 0),
('Wood Sage & Sea Salt', 18, 3, 5, 8500.00, NULL, 4600.00, 13, 'Un escape a la costa con salvia y sal marina.', 'active', 0),
('Libre', 5, 3, 4, 8100.00, NULL, 4400.00, 20, 'La libertad femenina en una fragancia de lavanda y naranja.', 'active', 0),
('Philosophykos', 21, 3, 2, 10200.00, NULL, 5600.00, 11, 'Un viaje sensorial a una higuera griega bajo el sol.', 'active', 0);

-- ============================================================
-- PERMISOS DEL SISTEMA
-- ============================================================
INSERT INTO permissions (permission_key, name, description, module, sort_order) VALUES
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
('roles.manage', 'Gestionar roles', 'Puede modificar permisos de los roles', 'roles', 110),
('ncf.manage', 'Gestionar NCF', 'Puede administrar secuencias de comprobantes fiscales', 'settings', 92);

-- ============================================================
-- PERMISOS POR ROL
-- ============================================================
-- Admin: todos los permisos
INSERT INTO role_permissions (role, permission_key)
SELECT 'admin', permission_key FROM permissions;

-- Vendedor
INSERT INTO role_permissions (role, permission_key) VALUES
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
INSERT INTO role_permissions (role, permission_key) VALUES
('cajero', 'dashboard.view'),
('cajero', 'pos.access'),
('cajero', 'products.view'),
('cajero', 'orders.view'),
('cajero', 'cash_register.access'),
('cajero', 'customers.view');

-- Tecnico
INSERT INTO role_permissions (role, permission_key) VALUES
('tecnico', 'dashboard.view'),
('tecnico', 'products.view'),
('tecnico', 'products.edit');
