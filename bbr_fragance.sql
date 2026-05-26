-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-05-2026 a las 17:41:21
-- Versión del servidor: 10.4.27-MariaDB
-- Versión de PHP: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `bbr_fragance`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 14:50:13'),
(2, 1, 'create', 'cash_register', 1, 'Caja abierta con monto inicial de RD$ 5,000.00', '::1', '2026-03-25 14:52:15'),
(3, 1, 'create', 'sale', 1, 'Venta creada: VTA-20260325-001 por RD$ 20,060.00', '::1', '2026-03-25 14:52:15'),
(4, 1, 'create', 'customer', 1, 'Cliente creado: Juan Perez', '::1', '2026-03-25 14:52:15'),
(5, 1, 'create', 'expense', 1, 'Gasto creado: Bolsas de regalo por RD$ 1,500.00', '::1', '2026-03-25 14:52:15'),
(6, 1, 'create', 'order', 1, 'Pedido creado: PED-20260325-001 por RD$ 11,446.00', '::1', '2026-03-25 14:52:15'),
(7, 1, 'update', 'cash_register', 1, 'Caja cerrada. Esperado: RD$ 3,500.00, Contado: RD$ 23,500.00, Diferencia: RD$ 20,000.00', '::1', '2026-03-25 14:52:27'),
(8, 1, 'update_status', 'order', 1, 'Pedido PED-20260325-001 cambiado de \'pending\' a \'confirmed\'', '::1', '2026-03-25 14:52:27'),
(9, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-25 14:52:27'),
(10, 1, 'create', 'cash_register', 2, 'Caja abierta con monto inicial de RD$ 0.00', '::1', '2026-03-25 15:03:35'),
(11, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 15:20:38'),
(12, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 15:21:40'),
(13, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 15:32:20'),
(14, NULL, 'create', 'order', 2, 'Pedido creado: PED-20260325-002 por RD$ 10,030.00', '::1', '2026-03-25 15:39:03'),
(15, NULL, 'create', 'order', 3, 'Pedido creado: PED-20260325-003 por RD$ 22,184.00', '::1', '2026-03-25 15:40:35'),
(16, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 15:51:14'),
(17, 1, 'create', 'order', 4, 'Pedido creado: PED-20260325-004 por RD$ 11,092.00', '::1', '2026-03-25 15:54:36'),
(18, 1, 'create', 'sale', 2, 'Venta creada: VTA-20260325-002 por RD$ 10,030.00', '::1', '2026-03-25 16:04:47'),
(19, 1, 'update', 'cash_register', 2, 'Caja cerrada. Esperado: RD$ 8,530.00, Contado: RD$ 7,500.00, Diferencia: RD$ -1,030.00', '::1', '2026-03-25 16:05:55'),
(20, 1, 'create', 'cash_register', 3, 'Caja abierta con monto inicial de RD$ 0.00', '::1', '2026-03-25 16:06:18'),
(21, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-25 16:07:42'),
(22, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-25 16:09:12'),
(23, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 16:09:58'),
(24, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 16:12:10'),
(25, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 16:12:40'),
(26, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 16:13:44'),
(27, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-25 16:14:24'),
(28, 1, 'update_status', 'order', 4, 'Pedido PED-20260325-004 cambiado de \'pending\' a \'confirmed\'', '::1', '2026-03-25 16:16:23'),
(29, 1, 'create', 'expense', 2, 'Gasto creado: productos de limpieza por RD$ 1,300.00', '::1', '2026-03-25 16:17:34'),
(30, 1, 'create', 'sale', 3, 'Venta creada: VTA-20260325-003 por RD$ 8,500.00', '::1', '2026-03-25 16:19:40'),
(31, 1, 'create', 'sale', 4, 'Venta creada: VTA-20260325-004 por RD$ 8,500.00', '::1', '2026-03-25 16:20:03'),
(32, 1, 'create', 'customer', 2, 'Cliente creado: Rafael Tavares', '::1', '2026-03-25 16:22:28'),
(33, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 16:36:40'),
(34, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 16:37:12'),
(35, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-03-25 16:44:56'),
(36, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 16:45:10'),
(37, 1, 'create', 'sale', 5, 'Venta creada: VTA-20260325-005 por RD$ 8,500.00', '::1', '2026-03-25 16:45:37'),
(38, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 21:49:47'),
(39, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 21:58:38'),
(40, 1, 'create', 'user', 2, 'Usuario creado: vendedor1', '::1', '2026-03-25 22:01:09'),
(41, 2, 'login', 'user', 2, 'Inicio de sesion: vendedor1', '::1', '2026-03-25 22:04:42'),
(42, 1, 'update', 'user', 2, 'Usuario desactivado: vendedor1', '::1', '2026-03-25 22:05:56'),
(43, 1, 'update', 'user', 2, 'Usuario activado: vendedor1', '::1', '2026-03-25 22:05:56'),
(44, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 22:07:40'),
(45, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-25 22:08:15'),
(46, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-26 02:48:01'),
(47, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-26 02:48:34'),
(48, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-03-26 02:52:27'),
(49, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-26 02:52:34'),
(50, 1, 'create', 'sale', 6, 'Venta creada: VTA-20260326-001 por RD$ 7,990.00', '::1', '2026-03-26 02:59:46'),
(51, 1, 'create', 'sale', 7, 'Venta creada: VTA-20260326-002 por RD$ 19,900.00', '::1', '2026-03-26 03:28:48'),
(52, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-26 03:39:20'),
(53, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-26 03:41:38'),
(54, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-26 03:56:35'),
(55, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-26 14:08:57'),
(56, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-26 15:00:10'),
(57, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-26 15:01:04'),
(58, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-26 15:07:43'),
(59, 1, 'create', 'sale', 8, 'Venta creada: VTA-20260326-003 por RD$ 8,500.00', '::1', '2026-03-26 15:09:06'),
(60, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-26 15:26:38'),
(61, 1, 'update', 'product', 2, 'Producto actualizado: Bleu de Chanel', '::1', '2026-03-26 15:39:06'),
(62, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-26 15:43:27'),
(63, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-26 15:45:24'),
(64, 1, 'update', 'product', 3, 'Producto actualizado: Acqua di Gio Profumo', '::1', '2026-03-26 15:47:57'),
(65, 1, 'update', 'product', 3, 'Producto actualizado: Acqua di Gio Profumo', '::1', '2026-03-26 15:48:17'),
(66, 1, 'update', 'product', 4, 'Producto actualizado: Eros', '::1', '2026-03-26 15:51:30'),
(67, 1, 'update', 'product', 5, 'Producto actualizado: Y Eau de Parfum', '::1', '2026-03-26 15:59:54'),
(68, 1, 'update', 'product', 6, 'Producto actualizado: 1 Million', '::1', '2026-03-26 16:04:27'),
(69, 1, 'update', 'product', 7, 'Producto actualizado: The One', '::1', '2026-03-26 16:05:34'),
(70, 1, 'update', 'product', 8, 'Producto actualizado: Aventus', '::1', '2026-03-26 16:08:54'),
(71, 1, 'update', 'product', 9, 'Producto actualizado: Explorer', '::1', '2026-03-26 16:10:23'),
(72, 1, 'update', 'product', 10, 'Producto actualizado: Light Blue Pour Homme', '::1', '2026-03-26 16:13:40'),
(73, 1, 'update', 'product', 11, 'Producto actualizado: Invictus', '::1', '2026-03-26 16:15:48'),
(74, 1, 'update', 'product', 12, 'Producto actualizado: Bad Boy', '::1', '2026-03-26 16:17:04'),
(75, 1, 'update', 'product', 12, 'Producto actualizado: Bad Boy', '::1', '2026-03-26 16:18:07'),
(76, 1, 'create', 'sale', 9, 'Venta creada: VTA-20260326-004 por RD$ 9,212.00', '::1', '2026-03-26 18:12:57'),
(77, 1, 'update', 'role', NULL, 'Permisos actualizados para rol: vendedor (11 permisos)', '::1', '2026-03-26 21:11:39'),
(78, 1, 'update', 'role', NULL, 'Permisos actualizados para rol: vendedor (12 permisos)', '::1', '2026-03-26 21:11:50'),
(79, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-03-26 21:24:03'),
(80, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-26 21:24:48'),
(81, 1, 'update', 'user', 2, 'Contrasena restablecida: vendedor1', '::1', '2026-03-26 21:25:32'),
(82, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-03-26 21:26:03'),
(83, 2, 'login', 'user', 2, 'Inicio de sesion: vendedor1', '::1', '2026-03-26 21:26:17'),
(84, 2, 'logout', 'user', 2, 'Cierre de sesion', '::1', '2026-03-26 21:26:44'),
(85, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-26 21:31:45'),
(86, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-27 02:29:12'),
(87, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-27 02:36:25'),
(88, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-27 02:41:18'),
(89, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-27 02:43:51'),
(90, 1, 'update', 'cash_register', 3, 'Caja cerrada. Esperado: RD$ 45,602.00, Contado: RD$ 45,602.00, Diferencia: RD$ 0.00', '::1', '2026-03-27 02:49:48'),
(91, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-27 11:44:09'),
(92, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-27 15:05:41'),
(93, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-27 15:05:51'),
(94, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-27 15:06:07'),
(95, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-27 15:06:17'),
(96, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-27 15:06:46'),
(97, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-27 15:07:52'),
(98, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-27 15:08:38'),
(99, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-27 15:18:35'),
(100, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-27 15:26:56'),
(101, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-27 15:33:20'),
(102, 1, 'update', 'settings', NULL, 'Imagen de promocion actualizada', '::1', '2026-03-27 15:33:50'),
(103, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-27 15:33:56'),
(104, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-27 15:37:16'),
(105, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-27 15:39:44'),
(106, 1, 'update', 'product', 13, 'Producto actualizado: Coco Mademoiselle', '::1', '2026-03-27 15:44:15'),
(107, 1, 'update', 'product', 14, 'Producto actualizado: J\'adore', '::1', '2026-03-27 15:44:22'),
(108, 1, 'update', 'product', 15, 'Producto actualizado: La Vie Est Belle', '::1', '2026-03-27 15:44:32'),
(109, 1, 'update', 'product', 25, 'Producto actualizado: Black Orchid', '::1', '2026-03-27 15:44:44'),
(110, 1, 'update', 'product', 29, 'Producto actualizado: Baccarat Rouge 540', '::1', '2026-03-27 15:45:07'),
(111, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-27 15:52:32'),
(112, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-27 15:55:43'),
(113, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-27 15:56:28'),
(114, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-27 15:58:34'),
(115, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-27 15:59:21'),
(116, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-27 16:05:18'),
(117, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-27 16:08:02'),
(118, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-27 16:09:05'),
(119, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-27 16:10:51'),
(120, 1, 'create', 'sale', 10, 'Venta creada: VTA-20260327-001 por RD$ 9,400.00', '::1', '2026-03-27 16:53:23'),
(121, 1, 'create', 'cash_register', 4, 'Caja abierta con monto inicial de RD$ 0.00', '::1', '2026-03-27 16:54:02'),
(122, 1, 'cancel', 'sale', 10, 'Venta cancelada: VTA-20260327-001', '::1', '2026-03-27 16:54:26'),
(123, 1, 'create', 'sale', 11, 'Venta creada: VTA-20260327-002 por RD$ 9,400.00', '::1', '2026-03-27 16:55:02'),
(124, 1, 'create', 'sale', 12, 'Venta creada: VTA-20260327-003 por RD$ 8,500.00', '::1', '2026-03-27 16:57:15'),
(125, 1, 'create', 'sale', 13, 'Venta creada: VTA-20260327-004 por RD$ 8,500.00', '::1', '2026-03-27 16:57:55'),
(126, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-27 17:02:34'),
(127, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-27 17:03:03'),
(128, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-27 17:03:25'),
(129, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-27 17:04:26'),
(130, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-28 03:57:07'),
(131, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-28 03:59:06'),
(132, 1, 'update', 'product', 25, 'Producto actualizado: Black Orchid', '::1', '2026-03-28 04:06:43'),
(133, 1, 'update', 'cash_register', 4, 'Caja cerrada. Esperado: RD$ 14,500.00, Contado: RD$ 14,500.00, Diferencia: RD$ 0.00', '::1', '2026-03-29 03:50:48'),
(134, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-03-29 13:57:36'),
(135, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-29 13:57:46'),
(136, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-29 13:58:16'),
(137, 1, 'create', 'sale', 14, 'Venta creada: VTA-20260330-001 por RD$ 9,528.50', '::1', '2026-03-30 17:44:33'),
(138, 1, 'create', 'cash_register', 5, 'Caja abierta con monto inicial de RD$ 2,800.00', '::1', '2026-03-30 17:51:39'),
(139, 1, 'update', 'cash_register', 5, 'Caja cerrada. Esperado: RD$ 2,800.00, Contado: RD$ 2,000.00, Diferencia: RD$ -800.00', '::1', '2026-03-30 17:52:14'),
(140, 1, 'create', 'cash_register', 6, 'Caja abierta con monto inicial de RD$ 5,000.00', '::1', '2026-03-30 17:54:34'),
(141, 1, 'update', 'cash_register', 6, 'Caja cerrada. Esperado: RD$ 5,000.00, Contado: RD$ 6,000.00, Diferencia: RD$ 1,000.00', '::1', '2026-03-30 17:55:03'),
(142, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-03-30 17:57:39'),
(143, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-30 17:57:52'),
(144, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-03-30 17:58:15'),
(145, 2, 'login', 'user', 2, 'Inicio de sesion: vendedor1', '::1', '2026-03-30 17:58:25'),
(146, 2, 'logout', 'user', 2, 'Cierre de sesion', '::1', '2026-03-30 17:58:49'),
(147, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-30 17:58:57'),
(148, 1, 'update', 'role', NULL, 'Permisos actualizados para rol: vendedor (9 permisos)', '::1', '2026-03-30 17:59:54'),
(149, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-03-30 17:59:59'),
(150, 2, 'login', 'user', 2, 'Inicio de sesion: vendedor1', '::1', '2026-03-30 18:00:06'),
(151, 2, 'logout', 'user', 2, 'Cierre de sesion', '::1', '2026-03-30 18:01:50'),
(152, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-30 18:01:56'),
(153, 1, 'update', 'role', NULL, 'Permisos actualizados para rol: vendedor (8 permisos)', '::1', '2026-03-30 18:02:09'),
(154, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-03-30 18:02:14'),
(155, 2, 'login', 'user', 2, 'Inicio de sesion: vendedor1', '::1', '2026-03-30 18:02:20'),
(156, 2, 'logout', 'user', 2, 'Cierre de sesion', '::1', '2026-03-30 18:05:07'),
(157, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-03-30 18:05:14'),
(158, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-30 18:06:08'),
(159, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-30 18:07:33'),
(160, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-30 18:08:16'),
(161, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-03-30 18:09:47'),
(162, 1, 'update', 'product', 11, 'Producto actualizado: Invictus', '::1', '2026-03-30 18:10:19'),
(163, 1, 'create', 'ncf_sequence', 1, 'Secuencia NCF creada: B02 (1-500)', '::1', '2026-03-31 02:52:15'),
(164, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-31 02:52:32'),
(165, 1, 'create', 'sale', 15, 'Venta creada: VTA-20260331-001 por RD$ 11,092.00', '::1', '2026-03-31 02:53:37'),
(166, 1, 'create', 'sale', 16, 'Venta creada: VTA-20260331-002 por RD$ 9,322.00', '::1', '2026-03-31 02:56:15'),
(167, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-03-31 02:58:32'),
(168, 1, 'create', 'sale', 17, 'Venta creada: VTA-20260331-003 por RD$ 7,198.00', '::1', '2026-03-31 04:13:29'),
(169, 1, 'create', 'sale', 18, 'Venta creada: VTA-2026-03-31-004 por RD$ 7,198.00', '::1', '2026-03-31 04:15:02'),
(170, 1, 'create', 'cash_register', 7, 'Caja abierta con monto inicial de RD$ 0.00', '::1', '2026-03-31 22:33:13'),
(171, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-04-03 22:16:01'),
(172, 1, 'update', 'role', NULL, 'Permisos actualizados para rol: vendedor (6 permisos)', '::1', '2026-04-03 22:16:56'),
(173, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-04-03 22:17:00'),
(174, 2, 'login', 'user', 2, 'Inicio de sesion: vendedor1', '::1', '2026-04-03 22:17:07'),
(175, 2, 'logout', 'user', 2, 'Cierre de sesion', '::1', '2026-04-03 22:17:46'),
(176, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-04-03 22:18:03'),
(177, NULL, 'create', 'order', 5, 'Pedido creado: PED-20260404-001 por RD$ 11,092.00', '::1', '2026-04-03 23:37:44'),
(178, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-04-03 23:38:18'),
(179, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-04-03 23:40:00'),
(180, 1, 'update_status', 'order', 5, 'Pedido PED-20260404-001 cambiado de \'pending\' a \'processing\'', '::1', '2026-04-03 23:40:57'),
(181, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-04-03 23:46:32'),
(182, 1, 'create', 'order', 6, 'Pedido creado: PED-20260404-002 por RD$ 6,100.00', '::1', '2026-04-03 23:47:18'),
(183, 1, 'update_status', 'order', 6, 'Pedido PED-20260404-002 cambiado de \'pending\' a \'processing\'', '::1', '2026-04-03 23:49:02'),
(184, 1, 'update_status', 'order', 6, 'Pedido PED-20260404-002 cambiado de \'processing\' a \'shipped\'', '::1', '2026-04-03 23:49:28'),
(185, 1, 'create', 'sale', 19, 'Venta creada: VTA-20260404-001 por RD$ 6,100.00', '::1', '2026-04-03 23:51:28'),
(186, 1, 'create', 'order', 7, 'Pedido creado: PED-20260404-003 por RD$ 6,100.00', '::1', '2026-04-04 02:11:00'),
(187, 1, 'update_status', 'order', 7, 'Pedido PED-20260404-003 cambiado de \'pending\' a \'shipped\'', '::1', '2026-04-04 02:11:36'),
(188, 1, 'update_status', 'order', 7, 'Pedido PED-20260404-003 cambiado de \'shipped\' a \'delivered\'', '::1', '2026-04-04 02:12:20'),
(189, 1, 'update_status', 'order', 7, 'Pedido PED-20260404-003 cambiado de \'delivered\' a \'confirmed\'', '::1', '2026-04-04 02:17:31'),
(190, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-04-04 02:56:13'),
(191, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-04-04 02:56:25'),
(192, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-04-04 02:57:13'),
(193, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-04-05 03:35:02'),
(194, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-04-05 03:42:52'),
(195, 1, 'create', 'order', 8, 'Pedido creado: PED-20260405-001 por RD$ 9,400.00', '::1', '2026-04-05 03:44:14'),
(196, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-04-08 15:09:38'),
(197, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-04-08 15:09:45'),
(198, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-12 14:30:30'),
(199, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-05-12 14:30:46'),
(200, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-12 14:34:14'),
(201, 1, 'update', 'settings', NULL, 'Medio de promocion actualizado (video)', '::1', '2026-05-12 14:48:28'),
(202, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-12 14:48:30'),
(203, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-12 14:48:34'),
(204, 1, 'update', 'settings', NULL, 'Medio de promocion actualizado (image)', '::1', '2026-05-12 14:54:03'),
(205, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-12 14:54:06'),
(206, 1, 'update', 'product', 1, 'Producto actualizado: Sauvage', '::1', '2026-05-12 15:05:04'),
(207, 1, 'update', 'product', 2, 'Producto actualizado: Bleu de Chanel', '::1', '2026-05-12 20:42:37'),
(208, 1, 'update', 'product', 2, 'Producto actualizado: Bleu de Chanel', '::1', '2026-05-12 20:42:37'),
(209, 1, 'create', 'sale', 20, 'Venta creada: VTA-20260512-001 por RD$ 5,700.00', '::1', '2026-05-12 20:44:51'),
(210, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-12 20:46:44'),
(211, 1, 'create', 'order', 9, 'Pedido creado: PED-20260512-001 por RD$ 8,500.00', '::1', '2026-05-12 20:59:16'),
(212, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-05-12 21:13:30'),
(213, NULL, 'create', 'order', 10, 'Pedido creado: PED-20260512-002 por RD$ 8,500.00', '::1', '2026-05-12 21:14:10'),
(214, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-12 21:15:33'),
(215, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-12 21:17:18'),
(216, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-12 21:18:30'),
(217, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-13 14:58:08'),
(218, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-14 02:11:08'),
(219, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-14 02:11:54'),
(220, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-14 02:13:27'),
(221, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-14 02:23:05'),
(222, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-14 02:23:22'),
(223, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-14 02:23:28'),
(224, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-14 02:23:46'),
(225, 1, 'create', 'order', 11, 'Pedido creado: PED-20260514-001 por RD$ 8,500.00', '::1', '2026-05-14 02:24:58'),
(226, 1, 'create', 'order', 12, 'Pedido creado: PED-20260514-002 por RD$ 8,500.00', '::1', '2026-05-14 02:26:04'),
(227, 1, 'create', 'order', 13, 'Pedido creado: PED-20260514-003 por RD$ 8,500.00', '::1', '2026-05-14 02:31:24'),
(228, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-14 02:36:20'),
(229, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-14 02:36:48'),
(230, 1, 'create', 'order', 14, 'Pedido creado: PED-20260514-004 por RD$ 8,500.00', '::1', '2026-05-14 02:41:40'),
(231, 1, 'payment_session', 'order', 14, 'Sesion Cardnet creada para pedido PED-20260514-004: SIM-42E5C3ADFE431704', '::1', '2026-05-14 02:41:43'),
(232, 1, 'payment_approved', 'order', 14, 'Pago Cardnet aprobado: PED-20260514-004 Auth:SIM457717', '::1', '2026-05-14 02:41:43'),
(233, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-14 02:44:32'),
(234, 1, 'create', 'order', 15, 'Pedido creado: PED-20260514-005 por RD$ 7,600.00', '::1', '2026-05-14 02:45:10'),
(235, 1, 'payment_session', 'order', 15, 'Sesion Cardnet creada para pedido PED-20260514-005: SIM-C586B6FDD6E212F4', '::1', '2026-05-14 02:45:13'),
(236, 1, 'payment_approved', 'order', 15, 'Pago Cardnet aprobado: PED-20260514-005 Auth:SIM802237', '::1', '2026-05-14 02:45:13'),
(237, NULL, 'create', 'order', 16, 'Pedido creado: PED-20260514-006 por RD$ 8,500.00', '::1', '2026-05-14 02:51:14'),
(238, NULL, 'payment_session', 'order', 16, 'Sesion Cardnet creada para pedido PED-20260514-006: SIM-450976364A467F45', '::1', '2026-05-14 02:52:06'),
(239, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-14 02:58:08'),
(240, 1, 'create', 'order', 17, 'Pedido creado: PED-20260514-007 por RD$ 8,500.00', '::1', '2026-05-14 02:58:43'),
(241, 1, 'payment_session', 'order', 17, 'Sesion Cardnet creada para pedido PED-20260514-007: SIM-8D6776C0AF4B3F80', '::1', '2026-05-14 02:58:46'),
(242, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-14 03:03:13'),
(243, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-14 03:03:27'),
(244, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-14 03:04:31'),
(245, 1, 'create', 'order', 18, 'Pedido creado: PED-20260514-008 por RD$ 17,000.00', '::1', '2026-05-14 03:04:53'),
(246, 1, 'payment_session', 'order', 18, 'Sesion Cardnet creada para pedido PED-20260514-008: SIM-9221ED6D78572B2A', '::1', '2026-05-14 03:04:56'),
(247, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-14 03:17:38'),
(248, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-14 03:17:54'),
(249, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-14 03:19:15'),
(250, 1, 'create', 'order', 19, 'Pedido creado: PED-20260514-009 por RD$ 7,900.00', '::1', '2026-05-14 03:19:36'),
(251, 1, 'payment_session', 'order', 19, 'Sesion Cardnet creada para pedido PED-20260514-009: SIM-EDFF254D9E16CAB1', '::1', '2026-05-14 03:19:39'),
(252, 1, 'payment_failed', 'order', 19, 'Pago Cardnet fallido: PED-20260514-009 - Pago rechazado (simulador, codigo 05)', '::1', '2026-05-14 03:20:28'),
(253, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-14 03:22:35'),
(254, 1, 'create', 'order', 20, 'Pedido creado: PED-20260514-010 por RD$ 9,400.00', '::1', '2026-05-14 03:23:03'),
(255, 1, 'payment_session', 'order', 20, 'Sesion Cardnet creada para pedido PED-20260514-010: SIM-6BDC488E5AE626AA', '::1', '2026-05-14 03:23:09'),
(256, 1, 'payment_failed', 'order', 20, 'Pago Cardnet fallido: PED-20260514-010 - Pago rechazado (simulador, codigo 05)', '::1', '2026-05-14 03:23:56'),
(257, 1, 'create', 'order', 21, 'Pedido creado: PED-20260514-011 por RD$ 9,400.00', '::1', '2026-05-14 03:24:23'),
(258, 1, 'payment_session', 'order', 21, 'Sesion Cardnet creada para pedido PED-20260514-011: SIM-5929035FA8ED6356', '::1', '2026-05-14 03:24:28'),
(259, 1, 'payment_failed', 'order', 21, 'Pago Cardnet fallido: PED-20260514-011 - Pago rechazado (simulador, codigo 91)', '::1', '2026-05-14 03:24:45'),
(260, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-14 03:25:57'),
(261, 1, 'create', 'order', 22, 'Pedido creado: PED-20260514-012 por RD$ 9,400.00', '::1', '2026-05-14 03:26:32'),
(262, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-16 02:57:14'),
(263, 1, 'update', 'cash_register', 7, 'Caja cerrada. Esperado: RD$ 11,800.00, Contado: RD$ 11,800.00, Diferencia: RD$ 0.00', '::1', '2026-05-16 02:58:07'),
(264, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-16 04:01:13'),
(265, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-16 04:08:11'),
(266, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-05-16 04:08:23'),
(267, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-16 04:14:42'),
(268, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-05-16 04:18:34'),
(269, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-16 04:19:22'),
(270, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-05-16 04:19:29'),
(271, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-16 04:24:18'),
(272, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-05-16 04:24:38'),
(273, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-16 04:25:20'),
(274, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-05-16 04:25:28'),
(275, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-16 04:31:02'),
(276, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-05-16 04:31:13'),
(277, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-16 04:34:29'),
(278, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-16 04:40:45'),
(279, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-16 04:42:19'),
(280, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-16 04:43:30'),
(281, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-16 04:44:15'),
(282, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-05-16 13:22:37'),
(283, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-16 14:55:35'),
(284, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-16 14:56:01'),
(285, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-05-16 15:01:10'),
(286, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-16 15:03:23'),
(287, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-16 15:04:15'),
(288, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-16 15:04:55'),
(289, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-16 15:16:32'),
(290, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-16 15:17:41'),
(291, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-16 15:27:46'),
(292, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-16 15:28:28'),
(293, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-16 15:29:51'),
(294, 1, 'create', 'order', 23, 'Pedido creado: PED-20260516-001 por RD$ 22,200.00', '::1', '2026-05-16 15:35:20'),
(295, 1, 'update_status', 'order', 23, 'Pedido PED-20260516-001 cambiado de \'pending\' a \'confirmed\'', '::1', '2026-05-16 15:37:33'),
(296, 1, 'update_status', 'order', 23, 'Pedido PED-20260516-001 cambiado de \'confirmed\' a \'shipped\'', '::1', '2026-05-16 15:37:43'),
(297, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-16 15:44:37'),
(298, 1, 'update_status', 'order', 23, 'Pedido PED-20260516-001 cambiado de \'shipped\' a \'confirmed\'', '::1', '2026-05-16 16:51:04'),
(299, 1, 'update_status', 'order', 23, 'Pedido PED-20260516-001 cambiado de \'confirmed\' a \'delivered\'', '::1', '2026-05-16 16:52:15'),
(300, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-16 16:54:00'),
(301, 1, 'create', 'order', 24, 'Pedido creado: PED-20260516-002 por RD$ 7,600.00', '::1', '2026-05-16 16:54:21'),
(302, 1, 'payment_session', 'order', 24, 'Sesion Cardnet creada para pedido PED-20260516-002: SIM-22C56ABCC52D8D53', '::1', '2026-05-16 16:54:24'),
(303, 1, 'payment_failed', 'order', 24, 'Pago Cardnet fallido: PED-20260516-002 - Pago rechazado (simulador, codigo 05)', '::1', '2026-05-16 16:54:30'),
(304, 1, 'create', 'order', 25, 'Pedido creado: PED-20260516-003 por RD$ 7,600.00', '::1', '2026-05-16 16:54:45'),
(305, 1, 'payment_session', 'order', 25, 'Sesion Cardnet creada para pedido PED-20260516-003: SIM-13ECD2E6F7CED136', '::1', '2026-05-16 16:54:47'),
(306, 1, 'payment_failed', 'order', 25, 'Pago Cardnet fallido: PED-20260516-003 - Pago rechazado (simulador, codigo 05)', '::1', '2026-05-16 16:55:26'),
(307, 1, 'payment_confirmed', 'order', 23, 'Pago manual confirmado para pedido PED-20260516-001', '::1', '2026-05-16 17:00:17'),
(308, 1, 'create', 'order', 26, 'Pedido creado: PED-20260516-004 por RD$ 7,600.00', '::1', '2026-05-16 17:02:26'),
(309, 1, 'payment_session', 'order', 26, 'Sesion Cardnet creada para pedido PED-20260516-004: SIM-08F20538325B7C11', '::1', '2026-05-16 17:02:30'),
(310, 1, 'payment_approved', 'order', 26, 'Pago Cardnet aprobado: PED-20260516-004 Auth:SIM573450', '::1', '2026-05-16 17:02:40'),
(311, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-05-16 17:19:43'),
(312, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-16 17:20:14'),
(313, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-16 17:21:30'),
(314, 1, 'update_status', 'order', 16, 'Pedido PED-20260514-006 cambiado de \'cancelled\' a \'pending\'', '::1', '2026-05-16 17:24:42'),
(315, 1, 'create', 'order', 27, 'Pedido creado: PED-20260516-005 por RD$ 8,800.00', '::1', '2026-05-16 17:25:53'),
(316, 1, 'update_status', 'order', 27, 'Pedido PED-20260516-005 cambiado de \'pending\' a \'shipped\'', '::1', '2026-05-16 17:26:56'),
(317, 1, 'payment_confirmed', 'order', 27, 'Pago manual confirmado para pedido PED-20260516-005', '::1', '2026-05-16 17:27:09'),
(318, 1, 'create', 'sale', 21, 'Venta web VTA-20260516-001 registrada desde pedido PED-20260516-005', '::1', '2026-05-16 17:27:09'),
(319, 1, 'update_status', 'order', 27, 'Pedido PED-20260516-005 cambiado de \'shipped\' a \'delivered\'', '::1', '2026-05-16 17:28:05'),
(320, 1, 'update_status', 'order', 27, 'Pedido PED-20260516-005 cambiado de \'delivered\' a \'cancelled\'', '::1', '2026-05-16 17:28:58'),
(321, 1, 'payment_refunded', 'order', 15, 'Reembolso procesado: PED-20260514-005 - j', '::1', '2026-05-16 17:30:25'),
(322, 1, 'create', 'cash_register', 8, 'Caja abierta con monto inicial de RD$ 0.00', '::1', '2026-05-16 17:31:34'),
(323, 1, 'create', 'order', 28, 'Pedido creado: PED-20260516-006 por RD$ 14,200.00', '::1', '2026-05-16 17:32:22'),
(324, 1, 'update_status', 'order', 28, 'Pedido PED-20260516-006 cambiado de \'pending\' a \'delivered\'', '::1', '2026-05-16 17:32:50'),
(325, 1, 'payment_confirmed', 'order', 28, 'Pago manual confirmado para pedido PED-20260516-006', '::1', '2026-05-16 17:32:59'),
(326, 1, 'create', 'sale', 22, 'Venta web VTA-20260516-002 registrada desde pedido PED-20260516-006', '::1', '2026-05-16 17:32:59'),
(327, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-17 14:46:40'),
(328, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-17 14:52:09'),
(329, 1, 'update', 'role', NULL, 'Permisos actualizados para rol: vendedor (6 permisos)', '::1', '2026-05-17 14:52:39'),
(330, 1, 'create', 'order', 29, 'Pedido creado: PED-20260517-001 por RD$ 7,900.00', '::1', '2026-05-17 14:58:06'),
(331, 1, 'update_status', 'order', 29, 'Pedido PED-20260517-001 cambiado de \'pending\' a \'confirmed\'', '::1', '2026-05-17 14:58:40'),
(332, 1, 'payment_confirmed', 'order', 29, 'Pago manual confirmado para pedido PED-20260517-001', '::1', '2026-05-17 15:00:00'),
(333, 1, 'create', 'sale', 23, 'Venta web VTA-20260517-001 registrada desde pedido PED-20260517-001', '::1', '2026-05-17 15:00:00'),
(334, 1, 'create', 'sale', 24, 'Venta creada: VTA-2026-05-17-002 por RD$ 11,092.00', '::1', '2026-05-17 15:02:15'),
(335, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-17 15:09:15'),
(336, 1, 'create', 'order', 30, 'Pedido creado: PED-20260517-002 por RD$ 6,300.00', '::1', '2026-05-17 15:11:11'),
(337, 1, 'test_email', 'settings', NULL, 'Email de prueba enviado a rafael_tavares02@hotmail.com', '::1', '2026-05-17 15:17:58'),
(338, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-17 15:20:31'),
(339, 1, 'create', 'order', 31, 'Pedido creado: PED-20260517-003 por RD$ 6,300.00', '::1', '2026-05-17 15:20:57'),
(340, 1, 'update_status', 'order', 31, 'Pedido PED-20260517-003 cambiado de \'pending\' a \'cancelled\'', '::1', '2026-05-17 15:28:46'),
(341, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-18 02:36:03'),
(342, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-18 02:59:55'),
(343, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-18 03:03:59'),
(344, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-18 03:08:50'),
(345, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-18 13:14:39'),
(346, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-18 13:16:32'),
(347, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-18 13:30:11'),
(348, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-18 13:31:14'),
(349, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-18 13:31:45'),
(350, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-18 13:39:10'),
(351, 1, 'update', 'settings', NULL, 'Configuracion actualizada', '::1', '2026-05-18 13:39:47'),
(352, 1, 'logout', 'user', 1, 'Cierre de sesion', '::1', '2026-05-18 13:52:11'),
(353, 1, 'login', 'user', 1, 'Inicio de sesion: admin', '::1', '2026-05-18 14:00:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `is_active`, `created_at`) VALUES
(1, 'Dior', 'dior', 1, '2026-03-25 14:49:23'),
(2, 'Chanel', 'chanel', 1, '2026-03-25 14:49:23'),
(3, 'Giorgio Armani', 'giorgio-armani', 1, '2026-03-25 14:49:23'),
(4, 'Versace', 'versace', 1, '2026-03-25 14:49:23'),
(5, 'Yves Saint Laurent', 'yves-saint-laurent', 1, '2026-03-25 14:49:23'),
(6, 'Paco Rabanne', 'paco-rabanne', 1, '2026-03-25 14:49:23'),
(7, 'Dolce & Gabbana', 'dolce-gabbana', 1, '2026-03-25 14:49:23'),
(8, 'Creed', 'creed', 1, '2026-03-25 14:49:23'),
(9, 'Montblanc', 'montblanc', 1, '2026-03-25 14:49:23'),
(10, 'Carolina Herrera', 'carolina-herrera', 1, '2026-03-25 14:49:23'),
(11, 'Lancome', 'lancome', 1, '2026-03-25 14:49:23'),
(12, 'Viktor & Rolf', 'viktor-rolf', 1, '2026-03-25 14:49:23'),
(13, 'Tom Ford', 'tom-ford', 1, '2026-03-25 14:49:23'),
(14, 'Calvin Klein', 'calvin-klein', 1, '2026-03-25 14:49:23'),
(15, 'Le Labo', 'le-labo', 1, '2026-03-25 14:49:23'),
(16, 'Maison Francis Kurkdjian', 'maison-francis-kurkdjian', 1, '2026-03-25 14:49:23'),
(17, 'Escentric Molecules', 'escentric-molecules', 1, '2026-03-25 14:49:23'),
(18, 'Jo Malone', 'jo-malone', 1, '2026-03-25 14:49:23'),
(19, 'Thierry Mugler', 'thierry-mugler', 1, '2026-03-25 14:49:23'),
(20, 'Marc Jacobs', 'marc-jacobs', 1, '2026-03-25 14:49:23'),
(21, 'Diptyque', 'diptyque', 1, '2026-03-25 14:49:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cash_register_sessions`
--

CREATE TABLE `cash_register_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `opening_amount` decimal(12,2) NOT NULL,
  `closing_amount` decimal(12,2) DEFAULT NULL,
  `expected_amount` decimal(12,2) DEFAULT NULL,
  `difference` decimal(12,2) DEFAULT NULL,
  `total_cash_sales` decimal(12,2) DEFAULT 0.00,
  `total_card_sales` decimal(12,2) DEFAULT 0.00,
  `total_transfer_sales` decimal(12,2) DEFAULT 0.00,
  `total_sales_count` int(11) DEFAULT 0,
  `total_expenses` decimal(12,2) DEFAULT 0.00,
  `status` enum('open','closed') DEFAULT 'open',
  `opened_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` datetime DEFAULT NULL,
  `closing_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cash_register_sessions`
--

INSERT INTO `cash_register_sessions` (`id`, `user_id`, `opening_amount`, `closing_amount`, `expected_amount`, `difference`, `total_cash_sales`, `total_card_sales`, `total_transfer_sales`, `total_sales_count`, `total_expenses`, `status`, `opened_at`, `closed_at`, `closing_notes`) VALUES
(1, 1, '5000.00', '23500.00', '3500.00', '20000.00', '0.00', '0.00', '0.00', 0, '1500.00', 'closed', '2026-03-25 14:52:15', '2026-03-25 10:52:27', 'Cuadre de prueba'),
(2, 1, '0.00', '7500.00', '8530.00', '-1030.00', '10030.00', '0.00', '0.00', 1, '1500.00', 'closed', '2026-03-25 15:03:35', '2026-03-25 12:05:55', NULL),
(3, 1, '0.00', '45602.00', '45602.00', '0.00', '45602.00', '17000.00', '8500.00', 7, '0.00', 'closed', '2026-03-25 16:06:18', '2026-03-26 22:49:48', NULL),
(4, 1, '0.00', '14500.00', '14500.00', '0.00', '14500.00', '11900.00', '0.00', 3, '0.00', 'closed', '2026-03-27 16:54:02', '2026-03-28 23:50:48', NULL),
(5, 1, '2800.00', '2000.00', '2800.00', '-800.00', '0.00', '0.00', '0.00', 0, '0.00', 'closed', '2026-03-30 17:51:39', '2026-03-30 13:52:14', NULL),
(6, 1, '5000.00', '6000.00', '5000.00', '1000.00', '0.00', '0.00', '0.00', 0, '0.00', 'closed', '2026-03-30 17:54:34', '2026-03-30 13:55:03', NULL),
(7, 1, '0.00', '11800.00', '11800.00', '0.00', '11800.00', '0.00', '0.00', 2, '0.00', 'closed', '2026-03-31 22:33:13', '2026-05-15 22:58:07', NULL),
(8, 1, '0.00', NULL, NULL, NULL, '0.00', '11092.00', '0.00', 1, '0.00', 'open', '2026-05-16 17:31:34', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'Mujer', 'mujer', 'Fragancias femeninas', 'fa-venus', 1, 1, '2026-03-25 14:49:23'),
(2, 'Hombre', 'hombre', 'Fragancias masculinas', 'fa-mars', 2, 1, '2026-03-25 14:49:23'),
(3, 'Unisex', 'unisex', 'Fragancias unisex', 'fa-venus-mars', 3, 1, '2026-03-25 14:49:23'),
(4, 'Arabes', 'arabes', 'Fragancias arabes', 'fa-moon', 4, 1, '2026-03-25 14:49:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `rnc` varchar(11) DEFAULT NULL,
  `cedula` varchar(13) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `total_purchases` decimal(12,2) DEFAULT 0.00,
  `visit_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `customers`
--

INSERT INTO `customers` (`id`, `name`, `rnc`, `cedula`, `phone`, `email`, `address`, `notes`, `total_purchases`, `visit_count`, `created_at`, `updated_at`) VALUES
(1, 'Juan Perez', NULL, NULL, '8091234567', 'juan@test.com', NULL, NULL, '0.00', 0, '2026-03-25 14:52:15', '2026-03-25 14:52:15'),
(2, 'Rafael Tavares', NULL, NULL, '8293012054', 'rafael_tavares02@hotmail.com', NULL, NULL, '27240.50', 3, '2026-03-25 16:22:28', '2026-03-30 17:44:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_category_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `payment_method` enum('cash','card','transfer') DEFAULT 'cash',
  `receipt_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `expenses`
--

INSERT INTO `expenses` (`id`, `expense_category_id`, `user_id`, `description`, `amount`, `expense_date`, `payment_method`, `receipt_number`, `notes`, `created_at`) VALUES
(1, 2, 1, 'Bolsas de regalo', '1500.00', '2026-03-25', 'cash', NULL, NULL, '2026-03-25 14:52:15'),
(2, 2, 1, 'productos de limpieza', '1300.00', '2026-03-25', 'cash', NULL, NULL, '2026-03-25 16:17:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `name`, `slug`, `icon`, `color`, `is_active`, `created_at`) VALUES
(1, 'Alquiler', 'alquiler', 'fa-building', '#EF4444', 1, '2026-03-25 14:49:23'),
(2, 'Insumos', 'insumos', 'fa-box', '#F59E0B', 1, '2026-03-25 14:49:23'),
(3, 'Salarios', 'salarios', 'fa-users', '#3B82F6', 1, '2026-03-25 14:49:23'),
(4, 'Servicios', 'servicios', 'fa-bolt', '#8B5CF6', 1, '2026-03-25 14:49:23'),
(5, 'Marketing', 'marketing', 'fa-bullhorn', '#EC4899', 1, '2026-03-25 14:49:23'),
(6, 'Transporte', 'transporte', 'fa-truck', '#10B981', 1, '2026-03-25 14:49:23'),
(7, 'Otros', 'otros', 'fa-ellipsis-h', '#6B7280', 1, '2026-03-25 14:49:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ncf_sequences`
--

CREATE TABLE `ncf_sequences` (
  `id` int(11) NOT NULL,
  `ncf_type` varchar(3) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `prefix` varchar(3) NOT NULL,
  `current_number` int(11) NOT NULL DEFAULT 0,
  `start_number` int(11) NOT NULL DEFAULT 1,
  `end_number` int(11) NOT NULL,
  `expiration_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ncf_sequences`
--

INSERT INTO `ncf_sequences` (`id`, `ncf_type`, `type_name`, `prefix`, `current_number`, `start_number`, `end_number`, `expiration_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'B02', 'Consumo', 'B02', 3, 1, 500, '2026-12-30', 1, '2026-03-31 02:52:15', '2026-05-17 15:02:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `olfactory_families`
--

CREATE TABLE `olfactory_families` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `gradient_from` varchar(30) DEFAULT NULL,
  `gradient_to` varchar(30) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `olfactory_families`
--

INSERT INTO `olfactory_families` (`id`, `name`, `slug`, `description`, `icon`, `gradient_from`, `gradient_to`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'Dulce', 'dulce', 'Notas dulces y golosas', 'fa-candy-cane', 'from-pink-900', 'to-purple-900', 1, 1, '2026-03-25 14:49:23'),
(2, 'Amaderado', 'amaderado', 'Notas de madera y tierra', 'fa-tree', 'from-amber-900', 'to-yellow-900', 2, 1, '2026-03-25 14:49:23'),
(3, 'Citrico', 'citrico', 'Notas citricas y frescas', 'fa-lemon', 'from-yellow-800', 'to-green-800', 3, 1, '2026-03-25 14:49:23'),
(4, 'Oriental', 'oriental', 'Notas orientales y especiadas', 'fa-star-and-crescent', 'from-red-900', 'to-orange-900', 4, 1, '2026-03-25 14:49:23'),
(5, 'Fresco', 'fresco', 'Notas frescas y acuaticas', 'fa-water', 'from-cyan-900', 'to-blue-900', 5, 1, '2026-03-25 14:49:23'),
(6, 'Intenso', 'intenso', 'Notas intensas y profundas', 'fa-fire', 'from-gray-900', 'to-red-900', 6, 1, '2026-03-25 14:49:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_gateway` varchar(30) DEFAULT NULL,
  `payment_session_key` varchar(255) DEFAULT NULL,
  `payment_transaction_id` varchar(100) DEFAULT NULL,
  `payment_authorization` varchar(50) DEFAULT NULL,
  `payment_response_code` varchar(10) DEFAULT NULL,
  `payment_response_raw` text DEFAULT NULL,
  `payment_paid_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_phone`, `customer_email`, `customer_address`, `subtotal`, `discount_amount`, `shipping_cost`, `tax_amount`, `total`, `payment_method`, `payment_status`, `payment_gateway`, `payment_session_key`, `payment_transaction_id`, `payment_authorization`, `payment_response_code`, `payment_response_raw`, `payment_paid_at`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'PED-20260325-001', NULL, 'Maria Lopez', '8099876543', 'maria@test.com', 'Santo Domingo', '9700.00', '0.00', '0.00', '1746.00', '11446.00', 'transfer', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', NULL, '2026-03-25 14:52:15', '2026-03-25 14:52:27'),
(2, 'PED-20260325-002', NULL, 'Juan Perez', '8091234567', NULL, NULL, '8500.00', '0.00', '0.00', '1530.00', '10030.00', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, '2026-03-25 15:39:03', '2026-04-04 02:08:09'),
(3, 'PED-20260325-003', NULL, 'Maria Garcia', '8095551234', NULL, NULL, '18800.00', '0.00', '0.00', '3384.00', '22184.00', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, '2026-03-25 15:40:35', '2026-04-04 02:08:09'),
(4, 'PED-20260325-004', NULL, 'rt', '968756454', NULL, NULL, '9400.00', '0.00', '0.00', '1692.00', '11092.00', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', NULL, '2026-03-25 15:54:36', '2026-03-25 16:16:23'),
(5, 'PED-20260404-001', NULL, 'rafael tavares', '8293012054', 'rafael_tavares02@hotmail.com', 'kywucxgsvhbixwvhgsvdkcVKCHV', '9400.00', '0.00', '0.00', '1692.00', '11092.00', 'cash', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'processing', NULL, '2026-04-03 23:37:44', '2026-04-03 23:40:57'),
(6, 'PED-20260404-002', NULL, 'rafael tavares', '8293012054', 'rafael_tavares02@hotmail.com', 'hjcgfhvvgctrdcytjckydtykf', '6100.00', '0.00', '0.00', '0.00', '6100.00', 'transfer', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'shipped', NULL, '2026-04-03 23:47:18', '2026-04-03 23:49:28'),
(7, 'PED-20260404-003', NULL, 'rafael tavares', '8293012054', 'rafael_tavares02@hotmail.com', 'tyfgvhjb vgtufygjhb', '6100.00', '0.00', '0.00', '0.00', '6100.00', 'transfer', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', NULL, '2026-04-04 02:11:00', '2026-04-04 02:17:31'),
(8, 'PED-20260405-001', NULL, 'rafael tavares', '8293012054', 'rafael_tavares02@hotmail.com', 'fgvfcd', '9400.00', '0.00', '0.00', '1433.90', '9400.00', 'card', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, '2026-04-05 03:44:14', '2026-05-12 15:13:18'),
(9, 'PED-20260512-001', NULL, 'rt', '8293012054', 'rafael_tavares02@hotmail.com', 'hh', '8500.00', '0.00', '0.00', '1296.61', '8500.00', 'card', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, '2026-05-12 20:59:16', '2026-05-16 02:58:21'),
(10, 'PED-20260512-002', NULL, 'rt', '8293012054', 'rafael_tavares02@hotmail.com', 'rvgbhngb', '8500.00', '0.00', '0.00', '1296.61', '8500.00', 'card', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, '2026-05-12 21:14:10', '2026-05-16 02:58:21'),
(11, 'PED-20260514-001', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', 'tygcvkuyhgc', '8500.00', '0.00', '0.00', '1296.61', '8500.00', 'card_online', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, '2026-05-14 02:24:58', '2026-05-16 02:58:21'),
(12, 'PED-20260514-002', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', 'tygcvkuyhgc', '8500.00', '0.00', '0.00', '1296.61', '8500.00', 'card_online', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, '2026-05-14 02:26:04', '2026-05-16 02:58:21'),
(13, 'PED-20260514-003', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', 'jkhgcf', '8500.00', '0.00', '0.00', '1296.61', '8500.00', 'card_online', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, '2026-05-14 02:31:24', '2026-05-16 02:58:21'),
(14, 'PED-20260514-004', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', 'ytdrxfcgh', '8500.00', '0.00', '0.00', '1296.61', '8500.00', 'card_online', 'paid', 'cardnet', 'SIM-42E5C3ADFE431704', 'SIM-42E5C3ADFE431704', 'SIM457717', '00', '{\"simulator\":true,\"session\":\"SIM-42E5C3ADFE431704\",\"code\":\"00\",\"amount\":850000}', '2026-05-14 02:41:43', 'confirmed', NULL, '2026-05-14 02:41:40', '2026-05-14 02:41:43'),
(15, 'PED-20260514-005', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', 'gtvfc', '7600.00', '0.00', '0.00', '1159.32', '7600.00', 'card_online', 'refunded', 'cardnet', 'SIM-C586B6FDD6E212F4', 'SIM-C586B6FDD6E212F4', 'SIM802237', '00', '{\"simulator\":true,\"transaction_id\":\"SIM-C586B6FDD6E212F4\"}', '2026-05-14 02:45:13', 'cancelled', NULL, '2026-05-14 02:45:10', '2026-05-16 17:30:25'),
(16, 'PED-20260514-006', NULL, 'Test', '8095559999', NULL, 'x', '8500.00', '0.00', '0.00', '1296.61', '8500.00', 'card_online', 'pending', 'cardnet', 'SIM-450976364A467F45', NULL, NULL, NULL, NULL, NULL, 'pending', NULL, '2026-05-14 02:51:14', '2026-05-16 17:24:42'),
(17, 'PED-20260514-007', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', 'fgtrfdc', '8500.00', '0.00', '0.00', '1296.61', '8500.00', 'card_online', 'pending', 'cardnet', 'SIM-8D6776C0AF4B3F80', NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, '2026-05-14 02:58:43', '2026-05-16 15:35:42'),
(18, 'PED-20260514-008', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', 'dc', '17000.00', '0.00', '0.00', '2593.22', '17000.00', 'card_online', 'pending', 'cardnet', 'SIM-9221ED6D78572B2A', NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, '2026-05-14 03:04:53', '2026-05-16 15:35:42'),
(19, 'PED-20260514-009', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', NULL, '7900.00', '0.00', '0.00', '1205.08', '7900.00', 'card_online', 'failed', 'cardnet', 'SIM-EDFF254D9E16CAB1', NULL, NULL, '05', '{\"simulator\":true,\"session\":\"SIM-EDFF254D9E16CAB1\",\"code\":\"05\",\"amount\":790000}', NULL, 'cancelled', NULL, '2026-05-14 03:19:36', '2026-05-16 15:35:42'),
(20, 'PED-20260514-010', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', 'xcvfcdxs', '9400.00', '0.00', '0.00', '1433.90', '9400.00', 'card_online', 'failed', 'cardnet', 'SIM-6BDC488E5AE626AA', NULL, NULL, '05', '{\"simulator\":true,\"session\":\"SIM-6BDC488E5AE626AA\",\"code\":\"05\",\"amount\":940000}', NULL, 'cancelled', NULL, '2026-05-14 03:23:03', '2026-05-16 15:35:42'),
(21, 'PED-20260514-011', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', NULL, '9400.00', '0.00', '0.00', '1433.90', '9400.00', 'card_online', 'failed', 'cardnet', 'SIM-5929035FA8ED6356', NULL, NULL, '91', '{\"simulator\":true,\"session\":\"SIM-5929035FA8ED6356\",\"code\":\"91\",\"amount\":940000}', NULL, 'cancelled', NULL, '2026-05-14 03:24:23', '2026-05-16 15:35:42'),
(22, 'PED-20260514-012', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', 'ww', '9400.00', '0.00', '0.00', '1433.90', '9400.00', 'card_online', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, '2026-05-14 03:26:32', '2026-05-16 15:35:42'),
(23, 'PED-20260516-001', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', 'dfg', '22200.00', '0.00', '0.00', '3386.44', '22200.00', 'transfer', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-16 17:00:17', 'delivered', NULL, '2026-05-16 15:35:20', '2026-05-16 17:00:17'),
(24, 'PED-20260516-002', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', 'ff', '7600.00', '0.00', '0.00', '1159.32', '7600.00', 'card_online', 'failed', 'cardnet', 'SIM-22C56ABCC52D8D53', NULL, NULL, '05', '{\"simulator\":true,\"session\":\"SIM-22C56ABCC52D8D53\",\"code\":\"05\",\"amount\":760000}', NULL, 'pending', NULL, '2026-05-16 16:54:21', '2026-05-16 16:54:30'),
(25, 'PED-20260516-003', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', 'dd', '7600.00', '0.00', '0.00', '1159.32', '7600.00', 'card_online', 'failed', 'cardnet', 'SIM-13ECD2E6F7CED136', NULL, NULL, '05', '{\"simulator\":true,\"session\":\"SIM-13ECD2E6F7CED136\",\"code\":\"05\",\"amount\":760000}', NULL, 'pending', NULL, '2026-05-16 16:54:45', '2026-05-16 16:55:26'),
(26, 'PED-20260516-004', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', 'gg', '7600.00', '0.00', '0.00', '1159.32', '7600.00', 'card_online', 'paid', 'cardnet', 'SIM-08F20538325B7C11', 'SIM-08F20538325B7C11', 'SIM573450', '00', '{\"simulator\":true,\"session\":\"SIM-08F20538325B7C11\",\"code\":\"00\",\"amount\":760000}', '2026-05-16 17:02:40', 'confirmed', NULL, '2026-05-16 17:02:26', '2026-05-16 17:02:40'),
(27, 'PED-20260516-005', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', NULL, '8800.00', '0.00', '0.00', '1342.37', '8800.00', 'cash', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-16 17:27:09', 'cancelled', NULL, '2026-05-16 17:25:53', '2026-05-16 17:28:58'),
(28, 'PED-20260516-006', NULL, 'rt', '0987654321', 'rafael_tavares02@hotmail.com', NULL, '14200.00', '0.00', '0.00', '2166.10', '14200.00', 'cash', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-16 17:32:59', 'delivered', NULL, '2026-05-16 17:32:22', '2026-05-16 17:32:59'),
(29, 'PED-20260517-001', NULL, 'rafael tavares', '0987654321', 'rafael_tavares02@hotmail.com', 'hh', '7900.00', '0.00', '0.00', '1205.08', '7900.00', 'transfer', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-17 15:00:00', 'confirmed', '\n[Pago confirmado] 7654345678', '2026-05-17 14:58:06', '2026-05-17 15:00:00'),
(30, 'PED-20260517-002', NULL, 'rafael tavares', '0987654321', 'rafael_tavares02@hotmail.com', 'hh', '6300.00', '0.00', '0.00', '961.02', '6300.00', 'cash', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, '2026-05-17 15:11:11', '2026-05-17 15:11:11'),
(31, 'PED-20260517-003', NULL, 'rafael tavares', '0987654321', 'rafael_tavares02@hotmail.com', 'hh', '6300.00', '0.00', '0.00', '961.02', '6300.00', 'cash', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, '2026-05-17 15:20:57', '2026-05-17 15:28:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `product_brand` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_brand`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 13, 'Coco Mademoiselle', 'Chanel', 1, '9700.00', '9700.00'),
(2, 2, 1, 'Sauvage', 'Dior', 1, '8500.00', '8500.00'),
(3, 3, 2, 'Bleu de Chanel', 'Chanel', 2, '9400.00', '18800.00'),
(4, 4, 2, 'Bleu de Chanel', 'Chanel', 1, '9400.00', '9400.00'),
(5, 5, 2, 'Bleu de Chanel', 'Chanel', 1, '9400.00', '9400.00'),
(6, 6, 6, '1 Million', 'Paco Rabanne', 1, '6100.00', '6100.00'),
(7, 7, 6, '1 Million', 'Paco Rabanne', 1, '6100.00', '6100.00'),
(8, 8, 2, 'Bleu de Chanel', 'Chanel', 1, '9400.00', '9400.00'),
(9, 9, 1, 'Sauvage', 'Dior', 1, '8500.00', '8500.00'),
(10, 10, 1, 'Sauvage', 'Dior', 1, '8500.00', '8500.00'),
(11, 11, 1, 'Sauvage', 'Dior', 1, '8500.00', '8500.00'),
(12, 12, 1, 'Sauvage', 'Dior', 1, '8500.00', '8500.00'),
(13, 13, 1, 'Sauvage', 'Dior', 1, '8500.00', '8500.00'),
(14, 14, 1, 'Sauvage', 'Dior', 1, '8500.00', '8500.00'),
(15, 15, 3, 'Acqua di Gio Profumo', 'Giorgio Armani', 1, '7600.00', '7600.00'),
(16, 16, 1, 'Sauvage', 'Dior', 1, '8500.00', '8500.00'),
(17, 17, 1, 'Sauvage', 'Dior', 1, '8500.00', '8500.00'),
(18, 18, 1, 'Sauvage', 'Dior', 2, '8500.00', '17000.00'),
(19, 19, 5, 'Y Eau de Parfum', 'Yves Saint Laurent', 1, '7900.00', '7900.00'),
(20, 20, 2, 'Bleu de Chanel', 'Chanel', 1, '9400.00', '9400.00'),
(21, 21, 2, 'Bleu de Chanel', 'Chanel', 1, '9400.00', '9400.00'),
(22, 22, 2, 'Bleu de Chanel', 'Chanel', 1, '9400.00', '9400.00'),
(23, 23, 8, 'Aventus', 'Creed', 1, '22200.00', '22200.00'),
(24, 24, 3, 'Acqua di Gio Profumo', 'Giorgio Armani', 1, '7600.00', '7600.00'),
(25, 25, 3, 'Acqua di Gio Profumo', 'Giorgio Armani', 1, '7600.00', '7600.00'),
(26, 26, 3, 'Acqua di Gio Profumo', 'Giorgio Armani', 1, '7600.00', '7600.00'),
(27, 27, 9, 'Explorer', 'Montblanc', 2, '4400.00', '8800.00'),
(28, 28, 1, 'Sauvage', 'Dior', 1, '8500.00', '8500.00'),
(29, 28, 4, 'Eros', 'Versace', 1, '5700.00', '5700.00'),
(30, 29, 5, 'Y Eau de Parfum', 'Yves Saint Laurent', 1, '7900.00', '7900.00'),
(31, 30, 12, 'Bad Boy', 'Carolina Herrera', 1, '6300.00', '6300.00'),
(32, 31, 12, 'Bad Boy', 'Carolina Herrera', 1, '6300.00', '6300.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `permission_key` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `module` varchar(50) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permissions`
--

INSERT INTO `permissions` (`id`, `permission_key`, `name`, `description`, `module`, `sort_order`) VALUES
(1, 'dashboard.view', 'Ver Dashboard', 'Acceso al panel de resumen', 'dashboard', 1),
(2, 'pos.access', 'Acceso al POS', 'Puede realizar ventas en punto de venta', 'pos', 10),
(3, 'pos.apply_discount', 'Aplicar descuentos', 'Puede aplicar descuentos en ventas', 'pos', 11),
(4, 'products.view', 'Ver productos', 'Puede ver el listado de productos', 'products', 20),
(5, 'products.create', 'Crear productos', 'Puede agregar nuevos productos', 'products', 21),
(6, 'products.edit', 'Editar productos', 'Puede modificar productos existentes', 'products', 22),
(7, 'products.delete', 'Eliminar productos', 'Puede eliminar productos', 'products', 23),
(8, 'orders.view', 'Ver pedidos', 'Puede ver el listado de pedidos', 'orders', 30),
(9, 'orders.manage', 'Gestionar pedidos', 'Puede cambiar estado de pedidos', 'orders', 31),
(10, 'expenses.view', 'Ver gastos', 'Puede ver el listado de gastos', 'expenses', 40),
(11, 'expenses.manage', 'Gestionar gastos', 'Puede crear, editar y eliminar gastos', 'expenses', 41),
(12, 'cash_register.access', 'Acceso a caja', 'Puede abrir y operar la caja registradora', 'cash_register', 50),
(13, 'cash_register.close', 'Cerrar caja', 'Puede realizar el cierre de caja', 'cash_register', 51),
(14, 'reports.view', 'Ver reportes', 'Acceso a reportes y estadisticas', 'reports', 60),
(15, 'customers.view', 'Ver clientes', 'Puede ver el listado de clientes', 'customers', 70),
(16, 'customers.manage', 'Gestionar clientes', 'Puede crear, editar clientes', 'customers', 71),
(17, 'credits.view', 'Ver creditos', 'Puede ver cuentas de credito', 'credits', 80),
(18, 'credits.manage', 'Gestionar creditos', 'Puede abrir cuentas y registrar pagos', 'credits', 81),
(19, 'settings.view', 'Ver configuracion', 'Puede ver la configuracion del sistema', 'settings', 90),
(20, 'settings.manage', 'Gestionar configuracion', 'Puede modificar la configuracion', 'settings', 91),
(21, 'users.view', 'Ver usuarios', 'Puede ver el listado de usuarios', 'users', 100),
(22, 'users.manage', 'Gestionar usuarios', 'Puede crear, editar y desactivar usuarios', 'users', 101),
(23, 'roles.manage', 'Gestionar roles', 'Puede modificar permisos de los roles', 'roles', 110),
(24, 'ncf.manage', 'Gestionar NCF', 'Puede administrar secuencias de comprobantes fiscales', 'settings', 92);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `family_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `min_stock` int(11) DEFAULT 5,
  `barcode` varchar(50) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `volume_ml` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`id`, `name`, `brand_id`, `category_id`, `family_id`, `price`, `original_price`, `cost`, `stock`, `min_stock`, `barcode`, `sku`, `description`, `volume_ml`, `status`, `is_featured`, `created_at`, `updated_at`) VALUES
(1, 'Sauvage', 1, 2, 2, '8500.00', '10000.00', '4500.00', 14, 5, '787777', NULL, 'Fragancia masculina salvaje y refinada con notas de bergamota y pimienta.', 100, 'active', 0, '2026-03-25 14:49:23', '2026-05-12 15:05:04'),
(2, 'Bleu de Chanel', 2, 2, 2, '9400.00', NULL, '5200.00', 11, 5, NULL, NULL, 'Una fragancia amaderada aromatica que encarna la libertad.', 100, 'active', 1, '2026-03-25 14:49:23', '2026-05-17 15:02:15'),
(3, 'Acqua di Gio Profumo', 3, 2, 5, '7600.00', NULL, '4000.00', 22, 5, NULL, NULL, 'Una reinterpretacion mas intensa y sofisticada del clasico Acqua di Gio.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-26 15:48:17'),
(4, 'Eros', 4, 2, 1, '5700.00', '6500.00', '3000.00', 27, 5, NULL, NULL, 'Inspirado en el dios griego del amor, una fragancia fresca y seductora.', NULL, 'active', 1, '2026-03-25 14:49:23', '2026-05-12 20:44:51'),
(5, 'Y Eau de Parfum', 5, 2, 2, '7900.00', NULL, '4200.00', 13, 5, NULL, NULL, 'Una fragancia intensa y audaz para el hombre moderno.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-05-17 14:58:40'),
(6, '1 Million', 6, 2, 1, '6100.00', NULL, '3200.00', 23, 5, NULL, NULL, 'Una fragancia dorada, fresca y especiada para el hombre ambicioso.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-04-04 02:17:31'),
(7, 'The One', 7, 2, 4, '6400.00', NULL, '3400.00', 20, 5, NULL, NULL, 'Una fragancia elegante con notas de jengibre, cedro y tabaco.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-26 16:05:34'),
(8, 'Aventus', 8, 2, 5, '22200.00', NULL, '12000.00', 7, 5, NULL, NULL, 'La fragancia de nicho mas iconica, simbolo de exito y poder.', NULL, 'active', 1, '2026-03-25 14:49:23', '2026-05-16 15:37:33'),
(9, 'Explorer', 9, 2, 2, '4400.00', NULL, '2300.00', 35, 5, NULL, NULL, 'Inspirada en el espiritu aventurero, una fragancia fresca y terrosa.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-26 16:10:23'),
(10, 'Light Blue Pour Homme', 7, 2, 3, '5100.00', NULL, '2700.00', 32, 5, NULL, NULL, 'Una fragancia vibrante que captura la esencia del Mediterraneo.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-26 16:13:40'),
(11, 'Invictus', 6, 2, 5, '5500.00', '8000.00', '2900.00', 27, 5, NULL, NULL, 'Una fragancia fresca y poderosa para el hombre victorioso.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-30 18:10:19'),
(12, 'Bad Boy', 10, 2, 4, '6300.00', NULL, '3300.00', 22, 5, NULL, NULL, 'Una fragancia oscura y seductora que rompe las reglas.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-26 16:18:07'),
(13, 'Coco Mademoiselle', 2, 1, 4, '9700.00', NULL, '5400.00', 19, 5, NULL, NULL, 'Una fragancia fresca y oriental para la mujer moderna e independiente.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-27 15:44:15'),
(14, 'J&#039;adore', 1, 1, 1, '9100.00', NULL, '5000.00', 15, 5, NULL, NULL, 'Un bouquet floral absoluto, simbolo de feminidad y elegancia.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-27 15:44:22'),
(15, 'La Vie Est Belle', 11, 1, 1, '8200.00', '9500.00', '4500.00', 18, 5, NULL, NULL, 'Un iris goloso que celebra la belleza de la vida.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-27 15:44:32'),
(16, 'Good Girl', 10, 1, 4, '7500.00', NULL, '4000.00', 24, 5, NULL, NULL, 'La dualidad de la mujer moderna en un frasco de tacon.', NULL, 'active', 1, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(17, 'Flowerbomb', 12, 1, 1, '8700.00', NULL, '4800.00', 12, 5, NULL, NULL, 'Una explosion floral adictiva que transforma lo negativo en positivo.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(18, 'Black Opium', 5, 1, 4, '8100.00', NULL, '4400.00', 16, 5, NULL, NULL, 'Una fragancia rock and roll con cafe negro y flores blancas.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(19, 'Miss Dior', 1, 1, 5, '8300.00', NULL, '4600.00', 19, 5, NULL, NULL, 'Un homenaje al amor y a la alta costura de Dior.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(20, 'Chance Eau Tendre', 2, 1, 1, '8900.00', NULL, '4900.00', 14, 5, NULL, NULL, 'Una fragancia floral frutal llena de ternura y delicadeza.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(21, 'Alien', 19, 1, 4, '6700.00', NULL, '3600.00', 21, 5, NULL, NULL, 'Una fragancia solar amaderada, misteriosa y magnifica.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(22, 'Daisy', 20, 1, 3, '5500.00', NULL, '2900.00', 30, 5, NULL, NULL, 'Una fragancia fresca y femenina con un toque vintage.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(23, 'Si Passione', 3, 1, 4, '7300.00', NULL, '3900.00', 17, 5, NULL, NULL, 'La intensidad de la pasion en una fragancia irresistible.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(24, 'Very Good Girl', 10, 1, 1, '7700.00', NULL, '4100.00', 23, 5, NULL, NULL, 'La nueva generacion de Good Girl, mas atrevida y glam.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(25, 'Black Orchid', 13, 3, 6, '9700.00', '11000.00', '5400.00', 10, 5, NULL, NULL, 'Lujosa y sensual, una mezcla de orquidea negra y especias.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-28 04:06:43'),
(26, 'Oud Wood', 13, 3, 2, '16700.00', NULL, '9000.00', 7, 5, NULL, NULL, 'Una composicion sofisticada de oud, santal y vetiver.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(27, 'CK One', 14, 3, 3, '3200.00', NULL, '1700.00', 40, 5, NULL, NULL, 'El iconico aroma unisex fresco y limpio de los 90s.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(28, 'Santal 33', 15, 3, 2, '18200.00', NULL, '10000.00', 5, 5, NULL, NULL, 'Una fragancia de culto con notas de santal, cuero y cardamomo.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(29, 'Baccarat Rouge 540', 16, 3, 4, '20500.00', NULL, '11000.00', 6, 5, NULL, NULL, 'Una fragancia excepcional, luminosa y cristalina como el cristal.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-27 15:45:07'),
(30, 'Tobacco Vanille', 13, 3, 6, '16100.00', NULL, '8800.00', 9, 5, NULL, NULL, 'Una mezcla opulenta de tabaco, vainilla y cacao.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(31, 'Another 13', 15, 3, 2, '17000.00', NULL, '9200.00', 4, 5, NULL, NULL, 'Moleculas de almizcle que crean una firma olfativa unica.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(32, 'Molecule 01', 17, 3, 2, '7900.00', NULL, '4300.00', 15, 5, NULL, NULL, 'Una fragancia molecular que funciona con tu quimica corporal.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(33, 'Neroli Portofino', 13, 3, 3, '15200.00', NULL, '8200.00', 8, 5, NULL, NULL, 'Una explosiva mezcla citrica inspirada en la Riviera italiana.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(34, 'Wood Sage & Sea Salt', 18, 3, 5, '8500.00', NULL, '4600.00', 13, 5, NULL, NULL, 'Un escape a la costa con salvia y sal marina.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(35, 'Libre', 5, 3, 4, '8100.00', NULL, '4400.00', 20, 5, NULL, NULL, 'La libertad femenina en una fragancia de lavanda y naranja.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23'),
(36, 'Philosykos', 21, 3, 2, '10200.00', NULL, '5600.00', 11, 5, NULL, NULL, 'Un viaje sensorial a una higuera griega bajo el sol.', NULL, 'active', 0, '2026-03-25 14:49:23', '2026-03-25 14:49:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `filename`, `original_name`, `is_primary`, `sort_order`, `created_at`) VALUES
(1, 1, 'product_1_1774539924_ba1a0f12.jpeg', 'Dior Sauvage Parfum.jpeg', 1, 0, '2026-03-26 15:07:43'),
(2, 2, 'product_2_1774539546_a7ed991c.jpeg', 'Bleu de Chanel Perfume Review _ Best Luxury Fragrance for Men 2026.jpeg', 1, 0, '2026-03-26 15:39:06'),
(3, 3, 'product_3_1774540077_d317dacc.jpeg', 'Giorgio Armani Acqua Di Gio Profumo Eau de Parfum 100ML For Men.jpeg', 1, 0, '2026-03-26 15:47:57'),
(4, 4, 'product_4_1774540290_1dad91c8.jpeg', '_🚨 LAST CHANCE! Versace Eros 50% OFF – Best-Selling Men’s Cologne!.jpeg', 1, 0, '2026-03-26 15:51:30'),
(5, 5, 'product_5_1774540794_734f7927.jpeg', 'Yves Saint Laurent Beaute Men\'s Y Eau de Parfum _ Dillard\'s.jpeg', 1, 0, '2026-03-26 15:59:54'),
(6, 6, 'product_6_1774541067_0212a2c3.jpeg', 'RABANNE 1 Million Parfum Parfum _ Parfum.jpeg', 1, 0, '2026-03-26 16:04:27'),
(7, 7, 'product_7_1774541134_2bfa24b6.jpeg', 'Dolce&Gabbana The One, Eau De Parfum Spray, For Men.jpeg', 1, 0, '2026-03-26 16:05:34'),
(8, 8, 'product_8_1774541334_bfdb7b7f.jpeg', 'Creed Aventus, Men\'s Luxury Cologne, Dry Woods, Fresh & Citrus Fruity Fragrance.jpeg', 1, 0, '2026-03-26 16:08:54'),
(9, 9, 'product_9_1774541423_9d99f4f0.jpeg', 'Mont Blanc.jpeg', 1, 0, '2026-03-26 16:10:23'),
(10, 10, 'product_10_1774541620_dd761f35.jpeg', '_ (2).jpeg', 1, 0, '2026-03-26 16:13:40'),
(11, 11, 'product_11_1774541748_bb7c29a4.jpeg', '#HappyFathersDay.jpeg', 1, 0, '2026-03-26 16:15:48'),
(12, 12, 'product_12_1774541887_9aa82452.jpeg', 'Official Website.jpeg', 1, 0, '2026-03-26 16:17:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role` varchar(20) NOT NULL,
  `permission_key` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role`, `permission_key`) VALUES
(1, 'admin', 'cash_register.access'),
(2, 'admin', 'cash_register.close'),
(3, 'admin', 'credits.manage'),
(4, 'admin', 'credits.view'),
(5, 'admin', 'customers.manage'),
(6, 'admin', 'customers.view'),
(7, 'admin', 'dashboard.view'),
(8, 'admin', 'expenses.manage'),
(9, 'admin', 'expenses.view'),
(92, 'admin', 'ncf.manage'),
(10, 'admin', 'orders.manage'),
(11, 'admin', 'orders.view'),
(12, 'admin', 'pos.access'),
(13, 'admin', 'pos.apply_discount'),
(14, 'admin', 'products.create'),
(15, 'admin', 'products.delete'),
(16, 'admin', 'products.edit'),
(17, 'admin', 'products.view'),
(18, 'admin', 'reports.view'),
(19, 'admin', 'roles.manage'),
(20, 'admin', 'settings.manage'),
(21, 'admin', 'settings.view'),
(22, 'admin', 'users.manage'),
(23, 'admin', 'users.view'),
(47, 'cajero', 'cash_register.access'),
(48, 'cajero', 'customers.view'),
(43, 'cajero', 'dashboard.view'),
(46, 'cajero', 'orders.view'),
(44, 'cajero', 'pos.access'),
(45, 'cajero', 'products.view'),
(49, 'tecnico', 'dashboard.view'),
(51, 'tecnico', 'products.edit'),
(50, 'tecnico', 'products.view'),
(105, 'vendedor', 'credits.view'),
(104, 'vendedor', 'expenses.manage'),
(103, 'vendedor', 'expenses.view'),
(102, 'vendedor', 'orders.manage'),
(101, 'vendedor', 'orders.view'),
(100, 'vendedor', 'pos.access');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `source` enum('pos','web') NOT NULL DEFAULT 'pos',
  `order_id` int(11) DEFAULT NULL,
  `sale_number` varchar(20) NOT NULL,
  `ncf_number` varchar(13) DEFAULT NULL,
  `ncf_type` varchar(3) DEFAULT NULL,
  `customer_rnc` varchar(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `register_session_id` int(11) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `tax_percent` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','card','transfer','mixed','card_online','pending') NOT NULL,
  `cash_received` decimal(12,2) DEFAULT NULL,
  `cash_change` decimal(12,2) DEFAULT NULL,
  `card_reference` varchar(50) DEFAULT NULL,
  `status` enum('completed','cancelled','refunded') DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sales`
--

INSERT INTO `sales` (`id`, `source`, `order_id`, `sale_number`, `ncf_number`, `ncf_type`, `customer_rnc`, `customer_id`, `user_id`, `register_session_id`, `subtotal`, `discount_amount`, `discount_percent`, `tax_percent`, `tax_amount`, `total`, `payment_method`, `cash_received`, `cash_change`, `card_reference`, `status`, `notes`, `created_at`) VALUES
(1, 'pos', NULL, 'VTA-20260325-001', NULL, NULL, NULL, NULL, 1, NULL, '17000.00', '0.00', '0.00', '18.00', '3060.00', '20060.00', 'cash', '25000.00', '4940.00', NULL, 'completed', 'Venta de prueba', '2026-03-25 14:52:15'),
(2, 'pos', NULL, 'VTA-20260325-002', NULL, NULL, NULL, NULL, 1, 2, '8500.00', '0.00', '0.00', '18.00', '1530.00', '10030.00', 'cash', '11000.00', '970.00', NULL, 'completed', NULL, '2026-03-25 16:04:47'),
(3, 'pos', NULL, 'VTA-20260325-003', NULL, NULL, NULL, NULL, 1, 3, '8500.00', '0.00', '0.00', '0.00', '0.00', '8500.00', 'card', NULL, NULL, NULL, 'completed', NULL, '2026-03-25 16:19:40'),
(4, 'pos', NULL, 'VTA-20260325-004', NULL, NULL, NULL, NULL, 1, 3, '8500.00', '0.00', '0.00', '0.00', '0.00', '8500.00', 'transfer', NULL, NULL, NULL, 'completed', NULL, '2026-03-25 16:20:03'),
(5, 'pos', NULL, 'VTA-20260325-005', NULL, NULL, NULL, NULL, 1, 3, '8500.00', '0.00', '0.00', '0.00', '0.00', '8500.00', 'cash', '9000.00', '500.00', NULL, 'completed', NULL, '2026-03-25 16:45:37'),
(6, 'pos', NULL, 'VTA-20260326-001', NULL, NULL, NULL, NULL, 1, 3, '9400.00', '1410.00', '15.00', '0.00', '0.00', '7990.00', 'cash', '8000.00', '10.00', NULL, 'completed', NULL, '2026-03-26 02:59:46'),
(7, 'pos', NULL, 'VTA-20260326-002', NULL, NULL, NULL, NULL, 1, 3, '19900.00', '0.00', '0.00', '0.00', '0.00', '19900.00', 'cash', '20000.00', '100.00', NULL, 'completed', NULL, '2026-03-26 03:28:48'),
(8, 'pos', NULL, 'VTA-20260326-003', NULL, NULL, NULL, 2, 1, 3, '8500.00', '0.00', '0.00', '0.00', '0.00', '8500.00', 'card', NULL, NULL, NULL, 'completed', NULL, '2026-03-26 15:09:06'),
(9, 'pos', NULL, 'VTA-20260326-004', NULL, NULL, NULL, 2, 1, 3, '9400.00', '188.00', '2.00', '0.00', '0.00', '9212.00', 'cash', '10000.00', '788.00', NULL, 'completed', NULL, '2026-03-26 18:12:57'),
(10, 'pos', NULL, 'VTA-20260327-001', NULL, NULL, NULL, NULL, 1, NULL, '9400.00', '0.00', '0.00', '0.00', '0.00', '9400.00', 'mixed', '4000.00', '0.00', NULL, 'cancelled', NULL, '2026-03-27 16:53:23'),
(11, 'pos', NULL, 'VTA-20260327-002', NULL, NULL, NULL, NULL, 1, 4, '9400.00', '0.00', '0.00', '0.00', '0.00', '9400.00', 'mixed', '6000.00', '0.00', NULL, 'completed', NULL, '2026-03-27 16:55:02'),
(12, 'pos', NULL, 'VTA-20260327-003', NULL, NULL, NULL, NULL, 1, 4, '8500.00', '0.00', '0.00', '0.00', '0.00', '8500.00', 'cash', '8500.00', '0.00', NULL, 'completed', NULL, '2026-03-27 16:57:15'),
(13, 'pos', NULL, 'VTA-20260327-004', NULL, NULL, NULL, NULL, 1, 4, '8500.00', '0.00', '0.00', '0.00', '0.00', '8500.00', 'card', NULL, NULL, NULL, 'completed', NULL, '2026-03-27 16:57:55'),
(14, 'pos', NULL, 'VTA-20260330-001', NULL, NULL, NULL, 2, 1, NULL, '8500.00', '425.00', '5.00', '18.00', '1453.50', '9528.50', 'cash', '10000.00', '471.50', NULL, 'completed', NULL, '2026-03-30 17:44:33'),
(15, 'pos', NULL, 'VTA-20260331-001', 'B0200000001', 'B02', NULL, NULL, 1, NULL, '9400.00', '0.00', '0.00', '18.00', '1692.00', '11092.00', 'cash', '11500.00', '408.00', NULL, 'completed', NULL, '2026-03-31 02:53:37'),
(16, 'pos', NULL, 'VTA-20260331-002', NULL, NULL, NULL, NULL, 1, NULL, '7900.00', '0.00', '0.00', '18.00', '1422.00', '9322.00', 'cash', '9500.00', '178.00', NULL, 'completed', NULL, '2026-03-31 02:56:15'),
(17, 'pos', NULL, 'VTA-20260331-003', NULL, NULL, NULL, NULL, 1, NULL, '6100.00', '0.00', '0.00', '18.00', '1098.00', '7198.00', 'cash', '7500.00', '302.00', NULL, 'completed', NULL, '2026-03-31 04:13:29'),
(18, 'pos', NULL, 'VTA-2026-03-31-004', 'B0200000002', 'B02', NULL, NULL, 1, NULL, '6100.00', '0.00', '0.00', '18.00', '1098.00', '7198.00', 'cash', '7500.00', '302.00', NULL, 'completed', NULL, '2026-03-31 04:15:02'),
(19, 'pos', NULL, 'VTA-20260404-001', NULL, NULL, NULL, NULL, 1, 7, '6100.00', '0.00', '0.00', '0.00', '0.00', '6100.00', 'cash', '6100.00', '0.00', NULL, 'completed', NULL, '2026-04-03 23:51:28'),
(20, 'pos', NULL, 'VTA-20260512-001', NULL, NULL, NULL, NULL, 1, 7, '5700.00', '0.00', '0.00', '18.00', '869.49', '5700.00', 'cash', '6000.00', '300.00', NULL, 'completed', NULL, '2026-05-12 20:44:51'),
(21, 'web', 27, 'VTA-20260516-001', NULL, NULL, NULL, NULL, NULL, NULL, '8800.00', '0.00', '0.00', '18.00', '1342.37', '8800.00', 'cash', NULL, NULL, NULL, 'completed', NULL, '2026-05-16 17:27:09'),
(22, 'web', 28, 'VTA-20260516-002', NULL, NULL, NULL, NULL, NULL, NULL, '14200.00', '0.00', '0.00', '18.00', '2166.10', '14200.00', 'cash', NULL, NULL, NULL, 'completed', NULL, '2026-05-16 17:32:59'),
(23, 'web', 29, 'VTA-20260517-001', NULL, NULL, NULL, NULL, NULL, NULL, '7900.00', '0.00', '0.00', '18.00', '1205.08', '7900.00', 'transfer', NULL, NULL, NULL, 'completed', NULL, '2026-05-17 15:00:00'),
(24, 'pos', NULL, 'VTA-2026-05-17-002', 'B0200000003', 'B02', NULL, NULL, 1, 8, '9400.00', '0.00', '0.00', '18.00', '1692.00', '11092.00', 'card', NULL, NULL, NULL, 'completed', NULL, '2026-05-17 15:02:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `product_brand` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `product_name`, `product_brand`, `quantity`, `unit_price`, `unit_cost`, `discount`, `subtotal`) VALUES
(1, 1, 1, 'Sauvage', 'Dior', 2, '8500.00', '4500.00', '0.00', '17000.00'),
(2, 2, 1, 'Sauvage', 'Dior', 1, '8500.00', '4500.00', '0.00', '8500.00'),
(3, 3, 1, 'Sauvage', 'Dior', 1, '8500.00', '4500.00', '0.00', '8500.00'),
(4, 4, 1, 'Sauvage', 'Dior', 1, '8500.00', '4500.00', '0.00', '8500.00'),
(5, 5, 1, 'Sauvage', 'Dior', 1, '8500.00', '4500.00', '0.00', '8500.00'),
(6, 6, 2, 'Bleu de Chanel', 'Chanel', 1, '9400.00', '5200.00', '0.00', '9400.00'),
(7, 7, 1, 'Sauvage', 'Dior', 1, '8500.00', '4500.00', '0.00', '8500.00'),
(8, 7, 4, 'Eros', 'Versace', 2, '5700.00', '3000.00', '0.00', '11400.00'),
(9, 8, 1, 'Sauvage', 'Dior', 1, '8500.00', '4500.00', '0.00', '8500.00'),
(10, 9, 2, 'Bleu de Chanel', 'Chanel', 1, '9400.00', '5200.00', '0.00', '9400.00'),
(11, 10, 2, 'Bleu de Chanel', 'Chanel', 1, '9400.00', '5200.00', '0.00', '9400.00'),
(12, 11, 2, 'Bleu de Chanel', 'Chanel', 1, '9400.00', '5200.00', '0.00', '9400.00'),
(13, 12, 1, 'Sauvage', 'Dior', 1, '8500.00', '4500.00', '0.00', '8500.00'),
(14, 13, 1, 'Sauvage', 'Dior', 1, '8500.00', '4500.00', '0.00', '8500.00'),
(15, 14, 1, 'Sauvage', 'Dior', 1, '8500.00', '4500.00', '0.00', '8500.00'),
(16, 15, 2, 'Bleu de Chanel', 'Chanel', 1, '9400.00', '5200.00', '0.00', '9400.00'),
(17, 16, 5, 'Y Eau de Parfum', 'Yves Saint Laurent', 1, '7900.00', '4200.00', '0.00', '7900.00'),
(18, 17, 6, '1 Million', 'Paco Rabanne', 1, '6100.00', '3200.00', '0.00', '6100.00'),
(19, 18, 6, '1 Million', 'Paco Rabanne', 1, '6100.00', '3200.00', '0.00', '6100.00'),
(20, 19, 6, '1 Million', 'Paco Rabanne', 1, '6100.00', '3200.00', '0.00', '6100.00'),
(21, 20, 4, 'Eros', 'Versace', 1, '5700.00', '3000.00', '0.00', '5700.00'),
(22, 21, 9, 'Explorer', 'Montblanc', 2, '4400.00', '2300.00', '0.00', '8800.00'),
(23, 22, 1, 'Sauvage', 'Dior', 1, '8500.00', '4500.00', '0.00', '8500.00'),
(24, 22, 4, 'Eros', 'Versace', 1, '5700.00', '3000.00', '0.00', '5700.00'),
(25, 23, 5, 'Y Eau de Parfum', 'Yves Saint Laurent', 1, '7900.00', '4200.00', '0.00', '7900.00'),
(26, 24, 2, 'Bleu de Chanel', 'Chanel', 1, '9400.00', '5200.00', '0.00', '9400.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'store_name', 'BBR Fragrance', '2026-05-18 13:16:32'),
(2, 'whatsapp_number', '8094855693', '2026-05-18 13:16:32'),
(3, 'contact_email', 'ventas@bbrfragrance.es', '2026-05-18 13:16:32'),
(4, 'contact_phone', '+18095261115', '2026-05-18 13:16:32'),
(5, 'address', 'Av. Winston Churchill, esq. Roberto P., Plaza las Americas 1 local 10AA, Santo Domingo, R.D.', '2026-05-18 13:16:32'),
(6, 'currency_symbol', 'RD$', '2026-03-25 14:49:23'),
(7, 'currency_code', 'DOP', '2026-03-25 14:49:23'),
(8, 'tax_name', 'ITBIS', '2026-05-18 13:16:32'),
(9, 'tax_percent', '18', '2026-05-18 13:16:32'),
(10, 'tax_enabled', '1', '2026-05-18 13:16:32'),
(11, 'min_free_shipping', '4999.96', '2026-05-18 13:16:32'),
(12, 'store_hours', 'Lun-Vie: 10AM-6:30PM | Sab: 10AM-6PM | Dom: Cerrado', '2026-05-18 13:16:32'),
(25, 'promo_active', '0', '2026-05-18 03:08:50'),
(26, 'promo_title', 'Promoción del mes de Mayo', '2026-05-18 03:08:50'),
(27, 'promo_subtitle', 'Hasta 30% de descuento en perfumes seleccionados + envio gratis en compras mayores a RD$ 5,000.00', '2026-05-18 03:08:50'),
(28, 'promo_link', 'pages/productos.html', '2026-05-18 03:08:50'),
(29, 'promo_bullets', '[\"Combos 2x1 en fragancias seleccionadas en la tienda\",\"Regalo sorpresa en compras mayores a RD$ 10,000.00\",\"Muestras gratis con cada pedido superior a RD$ 5,000.00\"]', '2026-05-18 03:08:50'),
(65, 'promo_image', '/BBR_FRAGANCE/uploads/promo/promo_1778597643_ee1cde19.jpeg', '2026-05-12 14:54:03'),
(141, 'store_rnc', '40221120976', '2026-05-18 13:16:32'),
(142, 'ncf_enabled', '1', '2026-05-18 13:16:32'),
(169, 'smtp_host', 'smtp.hostinger.com', '2026-05-18 13:16:32'),
(170, 'smtp_port', '465', '2026-05-18 13:16:32'),
(171, 'smtp_user', 'ventas@bbrfragrance.es', '2026-05-18 13:16:32'),
(172, 'smtp_pass', '@BBRfragrance123', '2026-05-18 13:16:32'),
(173, 'smtp_from_name', 'BBR Fragrance', '2026-05-18 13:16:32'),
(174, 'smtp_from_email', 'ventas@bbrfragrance.es', '2026-05-18 13:16:32'),
(175, 'bank_name', '', '2026-05-16 15:04:55'),
(176, 'bank_account_number', '', '2026-05-16 15:04:55'),
(177, 'bank_account_holder', '', '2026-05-16 15:04:55'),
(178, 'bank_account_type', 'Ahorros', '2026-05-16 15:04:55'),
(179, 'min_order_amount', '5000.03', '2026-05-18 13:39:47'),
(226, 'checkout_pay_cash', '0', '2026-05-18 13:39:47'),
(227, 'checkout_pay_card', '0', '2026-05-18 13:39:47'),
(228, 'checkout_pay_transfer', '1', '2026-05-18 13:39:47'),
(282, 'promo_media_type', 'image', '2026-05-12 14:54:03'),
(320, 'cardnet_environment', 'simulator', '2026-05-18 13:39:10'),
(321, 'cardnet_currency_code', '', '2026-05-18 13:39:10'),
(322, 'cardnet_merchant_number', '', '2026-05-18 13:39:10'),
(323, 'cardnet_merchant_terminal', '', '2026-05-18 13:39:10'),
(324, 'cardnet_merchant_name', '', '2026-05-18 13:39:10'),
(325, 'cardnet_merchant_type', '', '2026-05-18 13:39:10'),
(326, 'cardnet_acquiring_inst_code', '', '2026-05-18 13:39:10'),
(327, 'cardnet_return_page', '', '2026-05-18 13:39:10'),
(328, 'cardnet_cancel_page', '', '2026-05-18 13:39:10'),
(332, 'cardnet_enabled', '1', '2026-05-18 13:39:10'),
(336, 'checkout_pay_card_online', '1', '2026-05-18 13:39:47'),
(596, 'cardnet_secret_key', '', '2026-05-14 02:26:37'),
(645, 'order_rate_limit_enabled', '0', '2026-05-14 02:39:04'),
(1209, 'bank_accounts', '[{\"bank_name\":\"BHD\",\"bank_account_type\":\"Ahorros\",\"bank_account_number\":\"33397080012\",\"bank_account_holder\":\"LAURA SIRI\"},{\"bank_name\":\"BANRESERVAS\",\"bank_account_type\":\"Ahorros\",\"bank_account_number\":\"9609085984\",\"bank_account_holder\":\"LAURA SIRI\"}]', '2026-05-16 15:28:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','vendedor','cajero','tecnico') DEFAULT 'cajero',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `email`, `phone`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$Y1LUz/24Hslcd3/PNmPUlerMnj2cYP37pksyZaMe2Iuk/KR7rLLT.', 'Administrador', 'bbrperfume@gmail.com', NULL, 'admin', 1, '2026-05-18 10:00:45', '2026-03-25 14:49:23', '2026-05-18 14:00:45'),
(2, 'vendedor1', '$2y$10$YIQn9GzfFEhYOv7M1qdyDOPBILyckrWkHDdt9adeegiPW.7irZQ4O', 'Juan Vendedor', 'juan@test.com', '809-555-0001', 'vendedor', 1, '2026-04-03 18:17:07', '2026-03-25 22:01:09', '2026-04-03 22:17:07');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_date` (`created_at`),
  ADD KEY `idx_action` (`action`);

--
-- Indices de la tabla `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indices de la tabla `cash_register_sessions`
--
ALTER TABLE `cash_register_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_opened` (`opened_at`);

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indices de la tabla `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_rnc` (`rnc`);

--
-- Indices de la tabla `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_date` (`expense_date`),
  ADD KEY `idx_category` (`expense_category_id`);

--
-- Indices de la tabla `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indices de la tabla `ncf_sequences`
--
ALTER TABLE `ncf_sequences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`ncf_type`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indices de la tabla `olfactory_families`
--
ALTER TABLE `olfactory_families`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indices de la tabla `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`created_at`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_payment_session` (`payment_session_key`);

--
-- Indices de la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permission_key` (`permission_key`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `family_id` (`family_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_brand` (`brand_id`),
  ADD KEY `idx_barcode` (`barcode`),
  ADD KEY `idx_featured` (`is_featured`);

--
-- Indices de la tabla `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_perm` (`role`,`permission_key`),
  ADD KEY `idx_role` (`role`);

--
-- Indices de la tabla `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sale_number` (`sale_number`),
  ADD UNIQUE KEY `uq_sales_order_id` (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `idx_date` (`created_at`),
  ADD KEY `idx_sale_number` (`sale_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_ncf` (`ncf_number`),
  ADD KEY `idx_source` (`source`);

--
-- Indices de la tabla `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indices de la tabla `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=354;

--
-- AUTO_INCREMENT de la tabla `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `cash_register_sessions`
--
ALTER TABLE `cash_register_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `ncf_sequences`
--
ALTER TABLE `ncf_sequences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `olfactory_families`
--
ALTER TABLE `olfactory_families`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de la tabla `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT de la tabla `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1401;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `cash_register_sessions`
--
ALTER TABLE `cash_register_sessions`
  ADD CONSTRAINT `cash_register_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`),
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Filtros para la tabla `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`family_id`) REFERENCES `olfactory_families` (`id`);

--
-- Filtros para la tabla `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
