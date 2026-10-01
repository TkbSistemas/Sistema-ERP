-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 01-10-2026 a las 05:50:10
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `erp_takab`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `agentes_proveedores`
--

CREATE TABLE `agentes_proveedores` (
  `id` int(11) NOT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `telefono` varchar(100) DEFAULT NULL,
  `activo` tinyint(4) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `almacenes`
--

CREATE TABLE `almacenes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `ubicacion` varchar(255) NOT NULL,
  `responsable_id` int(11) NOT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `almacenes`
--

INSERT INTO `almacenes` (`id`, `nombre`, `ubicacion`, `responsable_id`, `principal`, `activo`, `updated_at`, `created_at`) VALUES
(1, 'Almacén Takab', 'Privada, C. 48 Nte. 1250, Agrícola Resurgimiento, 72370', 2, 1, 1, '2026-07-20 12:56:02', '2026-07-20 12:55:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogo_categorias_inventario`
--

CREATE TABLE `catalogo_categorias_inventario` (
  `id` int(5) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `catalogo_categorias_inventario`
--

INSERT INTO `catalogo_categorias_inventario` (`id`, `nombre`, `descripcion`, `created_by`, `updated_at`, `created_at`) VALUES
(1, 'Herramientas Eléctricas', 'Herramientas para Instalaciones Eléctricas', NULL, NULL, '2026-07-20 20:52:41'),
(2, 'Materiales', 'Material Consumible para Instalaciones', NULL, NULL, '2026-07-20 20:53:07'),
(3, 'Herraminetas Mecánicas', 'Herramientas para facilitar el uso en las tareas', NULL, NULL, '2026-10-01 03:48:08'),
(4, 'Redes', 'Asignado a redes', NULL, NULL, '2026-10-01 03:48:08'),
(5, 'Generadores', 'Asignado a generadores', NULL, NULL, '2026-10-01 03:48:08'),
(6, 'Aires Acondicionados', 'Asignado a Aires Acondicionados', NULL, NULL, '2026-10-01 03:48:08'),
(7, 'Equipo de Protección Personal', 'Equipo para salvar la integridad del trabajador', NULL, NULL, '2026-10-01 03:48:08'),
(8, 'Eléctrico', 'Enfocado a Eléctrico', NULL, NULL, '2026-10-01 03:48:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogo_proveedores`
--

CREATE TABLE `catalogo_proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `categoria` enum('Ferretería','Electrica','CCTV','Computación','Papelería','Material de Oficina','Alimentos') DEFAULT NULL,
  `partner_activo` tinyint(4) DEFAULT 0,
  `url_tienda` varchar(255) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `telefono` varchar(100) DEFAULT NULL,
  `ubicacion_fisica` varchar(255) DEFAULT NULL,
  `rfc` varchar(50) DEFAULT NULL,
  `razon_social` varchar(100) DEFAULT NULL,
  `activo` tinyint(4) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogo_unidades_medida`
--

CREATE TABLE `catalogo_unidades_medida` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apodo` varchar(5) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `sistema` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `catalogo_unidades_medida`
--

INSERT INTO `catalogo_unidades_medida` (`id`, `nombre`, `apodo`, `descripcion`, `sistema`, `created_by`, `created_at`) VALUES
(1, 'Kilogramo', 'kg', NULL, 'Sistema Internacional', NULL, '2026-07-21 10:21:49'),
(2, 'Gramo', 'g', NULL, 'Sistema Internacional', NULL, '2026-07-21 10:21:49'),
(3, 'Miligramo', 'mg', NULL, 'Sistema Internacional', NULL, '2026-07-21 10:21:49'),
(4, 'Metro', 'mt', NULL, 'Sistema Internacional', NULL, '2026-07-21 10:21:49'),
(5, 'Centimetro', 'cm', NULL, 'Sistema Internacional', NULL, '2026-07-21 10:21:49'),
(6, 'Milimetro', 'mm', NULL, 'Sistema Internacional', NULL, '2026-07-21 10:21:49'),
(7, 'Kilometro', 'km', NULL, 'Sistema Internacional', NULL, '2026-07-21 10:21:49'),
(8, 'Mol', 'mol', NULL, 'Sistema Internacional', NULL, '2026-07-21 10:21:49'),
(9, 'Pulgada', 'in', NULL, 'Sistema Imperial', NULL, '2026-07-21 10:21:49'),
(10, 'Pie', 'ft', NULL, 'Sistema Imperial', NULL, '2026-07-21 10:21:49'),
(11, 'Yarda', 'yd', NULL, 'Sistema Imperial', NULL, '2026-07-21 10:21:49'),
(12, 'Milla', 'mi', NULL, 'Sistema Imperial', NULL, '2026-07-21 10:21:49'),
(13, 'Libra', 'lb', NULL, 'Sistema Imperial', NULL, '2026-07-21 10:21:49'),
(14, 'Onza', 'oz', NULL, 'Sistema Imperial', NULL, '2026-07-21 10:21:49'),
(15, 'Pinta', 'pt', NULL, 'Sistema Imperial', NULL, '2026-07-21 10:21:49'),
(16, 'Cuarto', 'qt', NULL, 'Sistema Imperial', NULL, '2026-07-21 10:21:49'),
(17, 'Galón', 'gal', NULL, 'Sistema Imperial', NULL, '2026-07-21 10:21:49'),
(18, 'Onza Líquida', 'fl oz', NULL, 'Sistema Imperial', NULL, '2026-07-21 10:21:49'),
(19, 'Pieza', 'pz', NULL, 'Otro', NULL, '2026-07-21 10:21:49'),
(20, 'Juego', 'juego', NULL, 'Otro', NULL, '2026-07-21 10:21:49'),
(21, 'Kit', 'kit', NULL, 'Otro', NULL, '2026-07-21 10:21:49'),
(22, 'Caja', 'cj', NULL, 'Otro', NULL, '2026-07-21 10:21:49'),
(23, 'Pares', 'Par', NULL, 'Otro', NULL, '2026-07-21 10:21:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas_compras`
--

CREATE TABLE `facturas_compras` (
  `id` int(11) NOT NULL,
  `orden_id` int(11) NOT NULL,
  `folio_fiscal` varchar(100) NOT NULL,
  `monto_total` decimal(14,2) NOT NULL,
  `fecha_emision` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `id` int(11) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `nomenclatura` varchar(50) DEFAULT NULL,
  `codigo_fabricante` varchar(100) DEFAULT NULL,
  `num_serie` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`num_serie`)),
  `codigo_sat` varchar(50) DEFAULT NULL,
  `codigos_barras` varchar(100) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tipo` enum('Herramienta','Equipo','Consumible') DEFAULT NULL,
  `categoria_id` int(11) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `unidad_medida_id` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `precio_iva` decimal(10,2) DEFAULT NULL,
  `precio_beneficio` decimal(10,2) DEFAULT NULL,
  `pais_origen` varchar(100) DEFAULT NULL,
  `stock_minimo` float DEFAULT 0,
  `color` enum('Blanco','Negro','Gris','Azul','Rojo','Verde','Amarillo','Morado','Naranja','Café','Rosa') DEFAULT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `activo` enum('1','0') DEFAULT '1',
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario`
--

INSERT INTO `inventario` (`id`, `sku`, `nomenclatura`, `codigo_fabricante`, `num_serie`, `codigo_sat`, `codigos_barras`, `nombre`, `descripcion`, `tipo`, `categoria_id`, `marca`, `modelo`, `unidad_medida_id`, `precio_unitario`, `precio_iva`, `precio_beneficio`, `pais_origen`, `stock_minimo`, `color`, `imagen_url`, `activo`, `updated_at`, `created_at`) VALUES
(1, 'TAKAB-00001', 'PRU-EBA-01', 'COD-PRUEBA-001', NULL, NULL, NULL, 'Prueba Consumible', 'Item para Pruebas', 'Consumible', 2, 'MiMarca', 'MiModelo', 2, 100.00, 116.00, 150.80, 'Alemania', 2, 'Blanco', 'uploads/catalogo/prueba1.webp', '1', '2026-09-22 23:10:21', '2026-07-20 14:31:03'),
(2, 'TAKAB-00002', 'PRU-EBA-02', 'COD-PRUEBA-002', NULL, NULL, NULL, 'Prueba Herramienta', '', 'Herramienta', 1, 'Truper', 'MiModelo', 23, 50.00, 0.00, 0.00, 'Taiwan', 2, '', 'uploads/catalogo/prueba1.webp', '1', '2026-09-22 23:10:21', '2026-07-21 14:37:44'),
(4, 'TAKAB-00003', 'PRU-EBA-03', 'COD-PRUEBA-003', NULL, NULL, NULL, 'Prueba Equipo', '', 'Equipo', 2, 'Pretul', 'MiModelo', 18, 20.00, 0.00, 0.00, 'China', 1, 'Negro', 'uploads/catalogo/prueba1.webp', '1', '2026-09-22 23:10:21', '2026-07-21 14:37:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_actividad`
--

CREATE TABLE `logs_actividad` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `folio_solicitud` varchar(100) DEFAULT NULL,
  `tipo` enum('Entrada','Salida','Préstamo','Devolución','Transferencia') NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `responsable_id` int(11) NOT NULL,
  `almacen_id` int(11) NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes_compra`
--

CREATE TABLE `ordenes_compra` (
  `id` int(11) NOT NULL,
  `id_almacen` int(11) DEFAULT NULL,
  `folio` varchar(100) DEFAULT NULL,
  `estatus` enum('Aprobada','Cancelada','Pendiente','Rechazada','Parcial','Completa','Recibida','Incompleta') NOT NULL DEFAULT 'Pendiente',
  `proyecto_id` int(11) DEFAULT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `fecha_compra` date NOT NULL,
  `metodo_entrega` enum('Reparto','Recolección','Por Confirmar') DEFAULT 'Por Confirmar',
  `created_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes_compra_detalles`
--

CREATE TABLE `ordenes_compra_detalles` (
  `id` int(11) NOT NULL,
  `orden_compra_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `cantidad_solicitada` float NOT NULL,
  `precio_unitario` float DEFAULT NULL,
  `cantidad_confirmada` float DEFAULT NULL,
  `precio_confirmado` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proyectos`
--

CREATE TABLE `proyectos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recepciones_almacen`
--

CREATE TABLE `recepciones_almacen` (
  `id` int(11) NOT NULL,
  `folio_entrada` varchar(100) NOT NULL,
  `orden_id` int(11) DEFAULT NULL,
  `estatus` enum('Pendiente','Completa','Parcial','Auditoría') DEFAULT 'Pendiente',
  `fecha_recepcion` date DEFAULT NULL,
  `responsable_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recepciones_detalles`
--

CREATE TABLE `recepciones_detalles` (
  `id` int(11) NOT NULL,
  `recepcion_id` int(11) DEFAULT NULL,
  `producto_id` varchar(100) DEFAULT NULL,
  `detalle_orden_id` int(11) DEFAULT NULL,
  `cantidad_recibida` float DEFAULT NULL,
  `cantidad_faltante` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_bajas`
--

CREATE TABLE `solicitudes_bajas` (
  `id` int(11) NOT NULL,
  `folio` varchar(50) NOT NULL,
  `estatus` enum('Pendiente','Aprobada','Rechazada','Cancelada','Auditoría') NOT NULL DEFAULT 'Pendiente',
  `solicitante_id` int(11) NOT NULL,
  `almacen_id` int(11) NOT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_bajas_detalles`
--

CREATE TABLE `solicitudes_bajas_detalles` (
  `id` int(11) NOT NULL,
  `solicitud_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `motivos` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_herramienta`
--

CREATE TABLE `solicitudes_herramienta` (
  `id` int(11) NOT NULL,
  `folio` varchar(50) NOT NULL,
  `solicitud_id` int(11) DEFAULT NULL,
  `solicitante_id` int(11) NOT NULL,
  `estatus` enum('Pendiente','Aprobada','Vencida','Rechazada','Cancelada','Devuelta') NOT NULL DEFAULT 'Pendiente',
  `proyecto_id` int(11) DEFAULT NULL,
  `fecha_solicitud` date NOT NULL,
  `fecha_devolucion` date NOT NULL,
  `fecha_respuesta` date DEFAULT NULL,
  `responsable_id` int(11) DEFAULT NULL,
  `comentario_solicitante` varchar(255) DEFAULT NULL,
  `comentario_responsable` varchar(255) DEFAULT NULL,
  `comentario_entrega` varchar(255) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `ruta_pdf` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_herramienta_detalles`
--

CREATE TABLE `solicitudes_herramienta_detalles` (
  `id` int(11) NOT NULL,
  `solicitud_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_material`
--

CREATE TABLE `solicitudes_material` (
  `id` int(11) NOT NULL,
  `folio` varchar(50) NOT NULL,
  `solicitante_id` int(11) NOT NULL,
  `estatus` enum('Pendiente','Cancelada','Aprobada','Rechazada','Entregada') NOT NULL DEFAULT 'Pendiente',
  `proyecto_id` int(11) DEFAULT NULL,
  `fecha_solicitud` date NOT NULL,
  `fecha_requerida` date DEFAULT NULL,
  `fecha_respuesta` date DEFAULT NULL,
  `fecha_entregado` date DEFAULT NULL,
  `responsable_id` int(11) DEFAULT NULL,
  `comentario_solicitante` varchar(255) DEFAULT NULL,
  `comentario_responsable` varchar(255) DEFAULT NULL,
  `ruta_pdf` varchar(255) DEFAULT NULL,
  `activo` tinyint(4) NOT NULL DEFAULT 1,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_material_detalles`
--

CREATE TABLE `solicitudes_material_detalles` (
  `id` int(11) NOT NULL,
  `solicitud_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_material_noregistrados`
--

CREATE TABLE `solicitudes_material_noregistrados` (
  `id` int(11) NOT NULL,
  `solicitud_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `dimensiones` varchar(50) DEFAULT NULL,
  `unidad_medida` enum('Pieza','Metro','Litro','Kilogramo') NOT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT 1.00,
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock_almacen`
--

CREATE TABLE `stock_almacen` (
  `producto_id` int(11) NOT NULL,
  `almacen_id` int(11) NOT NULL,
  `stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ubicacion_fisica` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(25) NOT NULL,
  `password` varchar(100) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `role` enum('Administrador','Almacen','Empleado','Compras','Proyectos') NOT NULL DEFAULT 'Empleado',
  `baja` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_baja` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre`, `role`, `baja`, `activo`, `fecha_baja`, `created_at`) VALUES
(1, 'admin', '$2y$10$RzgFN65tsUBmPeJLyn.cROMhVrAIAfVjcqjfOUruEW/ryo8uJVBHq', 'Administrador General', 'Administrador', 0, 1, NULL, '2026-06-16 00:00:00'),
(2, 'ArmandoG', '$2y$10$RzgFN65tsUBmPeJLyn.cROMhVrAIAfVjcqjfOUruEW/ryo8uJVBHq', 'Armando Galeana', 'Almacen', 0, 1, NULL, '2026-07-20 10:49:40'),
(3, 'StephanieS', '$2y$10$RzgFN65tsUBmPeJLyn.cROMhVrAIAfVjcqjfOUruEW/ryo8uJVBHq', 'Stephanie Sosa', 'Compras', 0, 1, NULL, '2026-07-20 10:49:40'),
(4, 'PaulinoP', '$2y$10$RzgFN65tsUBmPeJLyn.cROMhVrAIAfVjcqjfOUruEW/ryo8uJVBHq', 'Paulino Palomino', 'Empleado', 0, 1, NULL, '2026-07-20 10:49:40');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `agentes_proveedores`
--
ALTER TABLE `agentes_proveedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_agentes_proveedores` (`proveedor_id`);

--
-- Indices de la tabla `almacenes`
--
ALTER TABLE `almacenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_encargado_almacen` (`responsable_id`);

--
-- Indices de la tabla `catalogo_categorias_inventario`
--
ALTER TABLE `catalogo_categorias_inventario`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `catalogo_proveedores`
--
ALTER TABLE `catalogo_proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `catalogo_unidades_medida`
--
ALTER TABLE `catalogo_unidades_medida`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `facturas_compras`
--
ALTER TABLE `facturas_compras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_facturas_compras_orden` (`orden_id`),
  ADD UNIQUE KEY `uq_facturas_compras_folio` (`folio_fiscal`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sku` (`sku`),
  ADD UNIQUE KEY `inventario_codigo_fabricante` (`codigo_fabricante`),
  ADD UNIQUE KEY `uk_codigo_barras` (`codigos_barras`),
  ADD KEY `fk_categoria_inventario` (`categoria_id`);

--
-- Indices de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_accion` (`accion`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_usuario` (`usuario_id`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orden_compra_proveedor_fk` (`proveedor_id`),
  ADD KEY `orden_compra_proyecto_fk` (`proyecto_id`),
  ADD KEY `idx_ordenes_compra_almacen` (`id_almacen`);

--
-- Indices de la tabla `ordenes_compra_detalles`
--
ALTER TABLE `ordenes_compra_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orden_detalles_fk` (`orden_compra_id`);

--
-- Indices de la tabla `proyectos`
--
ALTER TABLE `proyectos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `recepciones_almacen`
--
ALTER TABLE `recepciones_almacen`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `recepciones_detalles`
--
ALTER TABLE `recepciones_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recepciones_detalles_recepciones_almacen_FK` (`recepcion_id`);

--
-- Indices de la tabla `solicitudes_bajas`
--
ALTER TABLE `solicitudes_bajas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_id_solicitante` (`solicitante_id`);

--
-- Indices de la tabla `solicitudes_bajas_detalles`
--
ALTER TABLE `solicitudes_bajas_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_id_producto` (`producto_id`),
  ADD KEY `solicitudes_bajas_detalles_solicitudes_bajas_FK` (`solicitud_id`);

--
-- Indices de la tabla `solicitudes_herramienta`
--
ALTER TABLE `solicitudes_herramienta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prestamo_solicitud` (`solicitud_id`),
  ADD KEY `fk_prestamos_usuarios` (`solicitante_id`);

--
-- Indices de la tabla `solicitudes_herramienta_detalles`
--
ALTER TABLE `solicitudes_herramienta_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_solicitud_h` (`solicitud_id`),
  ADD KEY `fk_producto_h` (`producto_id`);

--
-- Indices de la tabla `solicitudes_material`
--
ALTER TABLE `solicitudes_material`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `solicitudes_folio` (`folio`),
  ADD KEY `fk_solicitante_id` (`solicitante_id`),
  ADD KEY `fk_solicitudes_proyecto` (`proyecto_id`);

--
-- Indices de la tabla `solicitudes_material_detalles`
--
ALTER TABLE `solicitudes_material_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_solicitud` (`solicitud_id`),
  ADD KEY `fk_producto` (`producto_id`);

--
-- Indices de la tabla `solicitudes_material_noregistrados`
--
ALTER TABLE `solicitudes_material_noregistrados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_noregistrados_solicitud` (`solicitud_id`);

--
-- Indices de la tabla `stock_almacen`
--
ALTER TABLE `stock_almacen`
  ADD PRIMARY KEY (`producto_id`,`almacen_id`),
  ADD KEY `idx_stock_almacen_prod` (`producto_id`),
  ADD KEY `idx_stock_almacen_alm` (`almacen_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuarios_username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `agentes_proveedores`
--
ALTER TABLE `agentes_proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `almacenes`
--
ALTER TABLE `almacenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `catalogo_categorias_inventario`
--
ALTER TABLE `catalogo_categorias_inventario`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `catalogo_proveedores`
--
ALTER TABLE `catalogo_proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `catalogo_unidades_medida`
--
ALTER TABLE `catalogo_unidades_medida`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `facturas_compras`
--
ALTER TABLE `facturas_compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario`
--
ALTER TABLE `inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ordenes_compra_detalles`
--
ALTER TABLE `ordenes_compra_detalles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proyectos`
--
ALTER TABLE `proyectos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recepciones_almacen`
--
ALTER TABLE `recepciones_almacen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recepciones_detalles`
--
ALTER TABLE `recepciones_detalles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_bajas`
--
ALTER TABLE `solicitudes_bajas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_bajas_detalles`
--
ALTER TABLE `solicitudes_bajas_detalles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_herramienta`
--
ALTER TABLE `solicitudes_herramienta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_herramienta_detalles`
--
ALTER TABLE `solicitudes_herramienta_detalles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_material`
--
ALTER TABLE `solicitudes_material`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_material_detalles`
--
ALTER TABLE `solicitudes_material_detalles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_material_noregistrados`
--
ALTER TABLE `solicitudes_material_noregistrados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `agentes_proveedores`
--
ALTER TABLE `agentes_proveedores`
  ADD CONSTRAINT `fk_agentes_proveedores` FOREIGN KEY (`proveedor_id`) REFERENCES `catalogo_proveedores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `almacenes`
--
ALTER TABLE `almacenes`
  ADD CONSTRAINT `fk_encargado_almacen` FOREIGN KEY (`responsable_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `facturas_compras`
--
ALTER TABLE `facturas_compras`
  ADD CONSTRAINT `facturas_compras_orden_fk` FOREIGN KEY (`orden_id`) REFERENCES `ordenes_compra` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD CONSTRAINT `fk_categoria_inventario` FOREIGN KEY (`categoria_id`) REFERENCES `catalogo_categorias_inventario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  ADD CONSTRAINT `orden_compra_almacen_fk` FOREIGN KEY (`id_almacen`) REFERENCES `almacenes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `orden_compra_proveedor_fk` FOREIGN KEY (`proveedor_id`) REFERENCES `catalogo_proveedores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orden_compra_proyecto_fk` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ordenes_compra_detalles`
--
ALTER TABLE `ordenes_compra_detalles`
  ADD CONSTRAINT `orden_detalles_fk` FOREIGN KEY (`orden_compra_id`) REFERENCES `ordenes_compra` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `recepciones_detalles`
--
ALTER TABLE `recepciones_detalles`
  ADD CONSTRAINT `recepciones_detalles_recepciones_almacen_FK` FOREIGN KEY (`recepcion_id`) REFERENCES `recepciones_almacen` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `solicitudes_bajas`
--
ALTER TABLE `solicitudes_bajas`
  ADD CONSTRAINT `fk_id_solicitante` FOREIGN KEY (`solicitante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `solicitudes_bajas_detalles`
--
ALTER TABLE `solicitudes_bajas_detalles`
  ADD CONSTRAINT `fk_id_producto` FOREIGN KEY (`producto_id`) REFERENCES `inventario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `solicitudes_bajas_detalles_solicitudes_bajas_FK` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes_bajas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `solicitudes_herramienta`
--
ALTER TABLE `solicitudes_herramienta`
  ADD CONSTRAINT `fk_prestamo_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes_material` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prestamos_usuarios` FOREIGN KEY (`solicitante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `solicitudes_herramienta_detalles`
--
ALTER TABLE `solicitudes_herramienta_detalles`
  ADD CONSTRAINT `fk_detalles_producto_h` FOREIGN KEY (`producto_id`) REFERENCES `inventario` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalles_solicitud_h` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes_herramienta` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `solicitudes_material`
--
ALTER TABLE `solicitudes_material`
  ADD CONSTRAINT `fk_solicitante_id` FOREIGN KEY (`solicitante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_solicitudes_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `solicitudes_material_detalles`
--
ALTER TABLE `solicitudes_material_detalles`
  ADD CONSTRAINT `fk_detalles_producto` FOREIGN KEY (`producto_id`) REFERENCES `inventario` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalles_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes_material` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `solicitudes_material_noregistrados`
--
ALTER TABLE `solicitudes_material_noregistrados`
  ADD CONSTRAINT `fk_noregistrados_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes_material` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
