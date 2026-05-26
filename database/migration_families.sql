-- Active: 1778727422854@@127.0.0.1@3306@bbr_fragance
-- Migración: Nuevas familias olfativas
-- Ejecutar sobre la base de datos existente

INSERT INTO `olfactory_families` (`id`, `name`, `slug`, `description`, `icon`, `gradient_from`, `gradient_to`, `sort_order`, `is_active`) VALUES
(7,  'Floral',              'floral',              'Notas florales delicadas y románticas',              'fa-spa',           'from-pink-800',    'to-rose-900',     7,  1),
(8,  'Frutal',              'frutal',              'Notas de frutas frescas y vibrantes',                'fa-apple-alt',     'from-orange-700',  'to-yellow-800',   8,  1),
(9,  'Ámbar',               'ambar',               'Notas cálidas y envolventes de ámbar',              'fa-gem',           'from-amber-700',   'to-orange-800',   9,  1),
(10, 'Fougère',             'fougere',             'Notas clásicas de helecho y lavanda',               'fa-leaf',          'from-green-800',   'to-emerald-900',  10, 1),
(11, 'Chipre',              'chipre',              'Notas terrosas de musgo y bergamota',               'fa-mountain',      'from-lime-800',    'to-green-900',    11, 1),
(12, 'Aromática',           'aromatica',           'Notas herbales y especiadas frescas',               'fa-mortar-pestle', 'from-teal-700',    'to-cyan-900',     12, 1),
(13, 'Floral Afrutada',     'floral-afrutada',     'Notas florales con toque frutal dulce',             'fa-seedling',      'from-fuchsia-800', 'to-pink-900',     13, 1),
(14, 'Oriental Amaderada',  'oriental-amaderada',  'Notas orientales profundas con base amaderada',    'fa-yin-yang',      'from-orange-900',  'to-red-900',      14, 1),
(15, 'Gourmand',            'gourmand',            'Notas golosas inspiradas en postres',               'fa-cookie-bite',   'from-amber-800',   'to-red-900',      15, 1),
(16, 'Cuero',               'cuero',               'Notas animales y ahumadas de cuero',               'fa-scroll',        'from-stone-700',   'to-neutral-900',  16, 1);
