-- 1) Crear base de datos y seleccionar el esquema
CREATE DATABASE IF NOT EXISTS takab_inventario
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;
USE takab_inventario;

-- (Opcional) Crear usuario de aplicación y otorgar permisos
-- REEMPLAZA 'app_user' y 'StrongPassword!' según necesites.
-- CREATE USER IF NOT EXISTS 'app_user'@'%' IDENTIFIED BY 'StrongPassword!';
-- GRANT ALL PRIVILEGES ON takab_inventario.* TO 'app_user'@'%';
-- FLUSH PRIVILEGES;

-- 2) Ajustes de sesión y transacción (compatibles con MySQL 8.0)
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = '+00:00';
SELECT NOW(), @@time_zone;


SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;
SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;
SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;
SET NAMES utf8mb4;

-- ============================================
-- Estructura e inserciones (del volcado fuente)
-- ============================================

--
-- Base de datos: takab_inventario
--
-- Estructura de tabla para la tabla alertas_configuradas

CREATE TABLE alertas_configuradas (
id int(11) NOT NULL,
tipo_alerta varchar(100) NOT NULL,
valor_umbral int(11) NOT NULL,
activa tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla alertas_configuradas

INSERT INTO alertas_configuradas (id, tipo_alerta, valor_umbral, activa) VALUES
(1, 'Stock mínimo cinta aislante', 10, 1),
(2, 'Préstamos vencidos', 1, 1);

--
-- Estructura de tabla para la tabla almacenes

CREATE TABLE almacenes (
id int(11) NOT NULL,
nombre varchar(100) NOT NULL,
ubicacion varchar(200) DEFAULT NULL,
responsable_id int(11) DEFAULT NULL,
es_principal tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla almacenes

INSERT INTO almacenes (id, nombre, ubicacion, responsable_id, es_principal) VALUES
(1, 'Almacén Principal', 'Planta baja, Calle 1', 2, 1),
(5, 'Almacen 3', 'Blvd 5 de mayo', 6, 0);

--
-- Estructura de tabla para la tabla categorias

CREATE TABLE categorias (
id int(11) NOT NULL,
nombre varchar(100) NOT NULL,
descripcion text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla categorias

INSERT INTO categorias (id, nombre, descripcion) VALUES
(1, 'Herramientas eléctricas', 'Herramientas para instalaciones eléctricas'),
(2, 'Material de instalación', 'Material consumible para instalaciones');

--
-- Estructura de tabla para la tabla clientes

CREATE TABLE clientes (
id int(11) NOT NULL,
nombre varchar(150) NOT NULL,
contacto varchar(100) DEFAULT NULL,
telefono varchar(20) DEFAULT NULL,
email varchar(100) DEFAULT NULL,
direccion varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla clientes

INSERT INTO clientes (id, nombre, contacto, telefono, email, direccion) VALUES
(1, 'Constructora Alfa', 'Carlos Pérez', '555-1234', 'contacto@alfa.com', 'Av. Reforma 100'),
(2, 'Servicios Beta', 'María López', '555-5678', 'ventas@beta.com', 'Calle Industrial 45');

--
-- Estructura de tabla para la tabla detalle_ordenes

CREATE TABLE detalle_ordenes (
id int(11) NOT NULL,
orden_id int(11) NOT NULL,
producto_id int(11) NOT NULL,
cantidad int(11) NOT NULL,
precio_unitario decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla detalle_ordenes

INSERT INTO detalle_ordenes (id, orden_id, producto_id, cantidad, precio_unitario) VALUES
(1, 1, 1, 2, 600.00);

--
-- Estructura de tabla para la tabla detalle_solicitud

CREATE TABLE detalle_solicitud (
id int(11) NOT NULL,
solicitud_id int(11) NOT NULL,
producto_id int(11) NOT NULL,
cantidad decimal(10,2) NOT NULL,
observacion text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla detalle_solicitud

INSERT INTO detalle_solicitud (id, solicitud_id, producto_id, cantidad, observacion) VALUES
(1, 1, 1, 1.00, NULL),
(2, 1, 2, 2.00, NULL),
(3, 2, 3, 3.00, NULL),
(4, 2, 4, 10.00, NULL),
(7, 4, 3, 1.00, NULL),
(8, 4, 1, 1.00, NULL),
(9, 5, 20, 2.00, NULL),
(10, 5, 1, 1.00, NULL),
(11, 6, 4, 12.00, NULL),
(12, 6, 1, 1.00, NULL),
(13, 7, 3, 1.00, ''),
(14, 7, 1, 1.00, ''),
(15, 9, 4, 300.00, 'pa mi casa'),
(16, 9, 1, 2.00, 'por si se rompe el mio'),
(17, 11, 4, 500.00, 'pa mi casa'),
(18, 11, 1, 3.00, 'por si se rompe el mio'),
(19, 12, 4, 50.00, 'pa mi casa'),
(20, 12, 1, 3.00, 'por si se rompe el mio'),
(21, 13, 3, 1.00, 'pa mi casa'),
(22, 13, 2, 3.00, 'por si se rompe el mio'),
(23, 13, 4, 50.00, 'pa mi casa'),
(24, 15, 4, 50.00, ''),
(25, 15, 2, 1.00, ''),
(26, 16, 20, 2.00, ''),
(27, 16, 1, 1.00, ''),
(28, 17, 4, 20.00, ''),
(29, 17, 2, 4.00, ''),
(30, 18, 1, 2.00, ''),
(31, 18, 2, 1.00, ''),
(32, 19, 1, 1.00, ''),
(33, 19, 2, 1.00, ''),
(34, 19, 2, 1.00, ''),
(35, 20, 21, 20.00, 'pa firmar'),
(36, 20, 2, 2.00, 'pa pincear'),
(37, 21, 1, 5.00, ''),
(38, 21, 2, 5.00, '');

--
-- Estructura de tabla para la tabla estados_producto_activo

CREATE TABLE estados_producto_activo (
id int(11) NOT NULL,
nombre varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla estados_producto_activo

INSERT INTO estados_producto_activo (id, nombre) VALUES
(1, 'Activo'),
(2, 'Desactivado');

--
-- Estructura de tabla para la tabla movimientos_inventario

CREATE TABLE movimientos_inventario (
id int(11) NOT NULL,
producto_id int(11) NOT NULL,
tipo enum('Entrada','Salida','Préstamo','Devolución','Transferencia') NOT NULL,
cantidad int(11) NOT NULL,
fecha datetime NOT NULL DEFAULT current_timestamp(),
usuario_id int(11) NOT NULL,
almacen_origen_id int(11) DEFAULT NULL,
almacen_destino_id int(11) DEFAULT NULL,
observaciones text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla movimientos_inventario

INSERT INTO movimientos_inventario (id, producto_id, tipo, cantidad, fecha, usuario_id, almacen_origen_id, almacen_destino_id, observaciones) VALUES
(1, 1, 'Entrada', 4, '2025-07-14 09:08:14', 2, NULL, 1, 'Compra inicial de taladros'),
(2, 2, 'Entrada', 12, '2025-07-14 09:08:14', 2, NULL, 1, 'Compra inicial de pinzas'),
(3, 3, 'Entrada', 25, '2025-07-14 09:08:14', 2, NULL, 1, 'Compra inicial de cinta'),
(4, 4, 'Entrada', 500, '2025-07-14 09:08:14', 2, NULL, 1, 'Compra inicial de tornillos'),
(5, 4, 'Salida', 100, '2025-07-24 16:50:54', 1, 1, NULL, 'pruba1'),
(6, 4, 'Salida', 400, '2025-07-24 16:51:26', 1, 1, NULL, 'pruba1'),
(7, 4, 'Salida', 50, '2025-07-24 16:51:41', 1, 1, NULL, ''),
(8, 1, 'Entrada', 5, '2025-08-04 17:37:11', 1, NULL, 1, 'llegaron nuevos'),
(9, 1, 'Salida', 1, '2025-08-04 17:37:37', 1, 1, NULL, 'dañado'),
(10, 21, 'Entrada', 5, '2025-08-08 12:06:18', 1, NULL, 5, 'se compraron nuevas plumas');

--
-- Estructura de tabla para la tabla ordenes_compra

CREATE TABLE ordenes_compra (
id int(11) NOT NULL,
proveedor_id int(11) NOT NULL,
solicitud_id int(11) DEFAULT NULL,
fecha datetime NOT NULL DEFAULT current_timestamp(),
estado enum('Pendiente','Enviada','Recibida','Cancelada') DEFAULT 'Pendiente',
total decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla ordenes_compra

INSERT INTO ordenes_compra (id, proveedor_id, solicitud_id, fecha, estado, total) VALUES
(1, 1, 3, '2025-07-14 09:08:14', 'Pendiente', 1200.00);

--
-- Estructura de tabla para la tabla prestamos

CREATE TABLE prestamos (
id int(11) NOT NULL,
producto_id int(11) NOT NULL,
empleado_id int(11) NOT NULL,
autorizado_by_user_id int(11) NOT NULL,
fecha_prestamo datetime NOT NULL DEFAULT current_timestamp(),
fecha_estimada_devolucion datetime DEFAULT NULL,
fecha_devolucion datetime DEFAULT NULL,
estado enum('Prestado','Devuelto') NOT NULL DEFAULT 'Prestado',
estado_devolucion enum('Bueno','Dañado','Perdido') DEFAULT NULL,
observaciones text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla prestamos

INSERT INTO prestamos (id, producto_id, empleado_id, autorizado_by_user_id, fecha_prestamo, fecha_estimada_devolucion, fecha_devolucion, estado, estado_devolucion, observaciones) VALUES
(1, 1, 3, 2, '2025-07-14 09:08:14', '2025-07-21 09:08:14', '2025-08-07 19:52:09', 'Devuelto', 'Dañado', 'llego roto del mango'),
(2, 2, 4, 2, '2025-07-14 09:08:14', '2025-07-17 09:08:14', NULL, 'Devuelto', NULL, 'Ya se terminó la tarea'),
(3, 1, 3, 1, '2025-08-07 12:14:59', NULL, '2025-08-07 00:00:00', 'Devuelto', 'Dañado', ''),
(4, 1, 3, 1, '2025-08-07 12:24:49', NULL, '2025-08-07 00:00:00', 'Devuelto', 'Dañado', 'registro devolver'),
(5, 1, 3, 1, '2025-08-07 12:24:49', NULL, '2025-08-07 16:03:10', 'Devuelto', 'Bueno', ''),
(6, 2, 3, 1, '2025-08-07 12:24:49', NULL, '2025-08-07 16:03:16', 'Devuelto', 'Bueno', 'hola'),
(7, 2, 3, 1, '2025-08-07 13:24:14', NULL, '2025-08-07 13:24:55', 'Devuelto', 'Bueno', 'observacion de devolucion'),
(8, 2, 3, 1, '2025-08-07 13:24:14', NULL, '2025-08-07 14:01:32', 'Devuelto', 'Bueno', ''),
(9, 2, 3, 1, '2025-08-07 13:24:14', NULL, '2025-08-07 14:01:52', 'Devuelto', 'Bueno', ''),
(10, 2, 3, 1, '2025-08-07 13:24:14', NULL, '2025-08-07 14:14:22', 'Devuelto', 'Bueno', ''),
(11, 1, 3, 1, '2025-08-07 16:04:48', NULL, NULL, 'Prestado', NULL, NULL),
(12, 1, 3, 1, '2025-08-07 16:04:57', NULL, '2025-08-07 16:05:03', 'Devuelto', 'Bueno', ''),
(13, 2, 3, 1, '2025-08-07 16:04:57', NULL, '2025-08-07 16:05:08', 'Devuelto', 'Dañado', 'efsf'),
(14, 2, 3, 1, '2025-08-07 16:04:57', NULL, NULL, 'Prestado', NULL, ''),
(15, 21, 3, 1, '2025-08-08 15:31:37', NULL, '2025-08-08 15:32:49', 'Devuelto', 'Dañado', 'orale master, que paso?'),
(16, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(17, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(18, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(19, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(20, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(21, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(22, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(23, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(24, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(25, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(26, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(27, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(28, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(29, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(30, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(31, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(32, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(33, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(34, 21, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa firmar'),
(35, 2, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa pincear'),
(36, 2, 3, 1, '2025-08-08 15:31:37', NULL, NULL, 'Prestado', NULL, 'pa pincear'),
(37, 1, 3, 1, '2025-08-08 17:33:31', NULL, '2025-08-08 17:34:01', 'Devuelto', 'Bueno', ''),
(38, 1, 3, 1, '2025-08-08 17:33:31', NULL, NULL, 'Prestado', NULL, ''),
(39, 1, 3, 1, '2025-08-08 17:33:31', NULL, NULL, 'Prestado', NULL, ''),
(40, 1, 3, 1, '2025-08-08 17:33:31', NULL, NULL, 'Prestado', NULL, ''),
(41, 1, 3, 1, '2025-08-08 17:33:31', NULL, NULL, 'Prestado', NULL, ''),
(42, 2, 3, 1, '2025-08-08 17:33:31', NULL, NULL, 'Prestado', NULL, ''),
(43, 2, 3, 1, '2025-08-08 17:33:31', NULL, NULL, 'Prestado', NULL, ''),
(44, 2, 3, 1, '2025-08-08 17:33:31', NULL, NULL, 'Prestado', NULL, ''),
(45, 2, 3, 1, '2025-08-08 17:33:31', NULL, NULL, 'Prestado', NULL, ''),
(46, 2, 3, 1, '2025-08-08 17:33:31', NULL, NULL, 'Prestado', NULL, '');

--
-- Estructura de tabla para la tabla productos

CREATE TABLE productos (
id int(11) NOT NULL,
codigo varchar(50) DEFAULT NULL,
nombre varchar(150) NOT NULL,
descripcion text DEFAULT NULL,
proveedor_id int(11) DEFAULT NULL,
categoria_id int(11) DEFAULT NULL,
peso decimal(10,2) DEFAULT NULL,
ancho decimal(10,2) DEFAULT NULL,
alto decimal(10,2) DEFAULT NULL,
profundidad decimal(10,2) DEFAULT NULL,
unidad_medida_id int(11) DEFAULT NULL,
clase_categoria varchar(100) DEFAULT NULL,
marca varchar(100) DEFAULT NULL,
color varchar(50) DEFAULT NULL,
forma varchar(50) DEFAULT NULL,
especificaciones_tecnicas text DEFAULT NULL,
origen varchar(100) DEFAULT NULL,
costo_compra decimal(10,2) DEFAULT NULL,
precio_venta decimal(10,2) DEFAULT NULL,
stock_minimo int(11) DEFAULT 0,
stock_actual int(11) DEFAULT 0,
almacen_id int(11) DEFAULT NULL,
estado enum('Nuevo','Usado','Dañado','En reparación') DEFAULT 'Nuevo',
activo_id int(11) NOT NULL DEFAULT 1,
tipo enum('Consumible','Herramienta') NOT NULL,
imagen_url varchar(255) DEFAULT NULL,
last_requested_by_user_id int(11) DEFAULT NULL,
last_request_date datetime DEFAULT NULL,
tags varchar(255) DEFAULT NULL,
created_at datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla productos

INSERT INTO productos (id, codigo, nombre, descripcion, proveedor_id, categoria_id, peso, ancho, alto, profundidad, unidad_medida_id, clase_categoria, marca, color, forma, especificaciones_tecnicas, origen, costo_compra, precio_venta, stock_minimo, stock_actual, almacen_id, estado, activo_id, tipo, imagen_url, last_requested_by_user_id, last_request_date, tags, created_at) VALUES
(1, 'H001', 'Taladro Eléctrico', 'Taladro de impacto 600W', 2, 1, 2.50, 30.00, 20.00, 8.00, 1, 'Herramienta', 'Bosch', 'Azul', 'Rectangular', '600W, 220V', 'México', 1500.00, 1800.00, 1, 10, 1, 'Usado', 1, 'Herramienta', 'https://http2.mlstatic.com/D_NQ_NP_917518-MLA44737119055_012021-O-taladro-electrico-de-10mm-bosch-gbm-6-re-350w-127v.webp', NULL, NULL, 'taladro, eléctrico', '2025-07-14 09:08:14'),
(2, 'H002', 'Pinzas de Electricista', 'Pinzas aisladas 1000V', 1, 1, 0.50, 20.00, 5.00, 2.00, 1, 'Herramienta', 'Truper', 'Naranja', 'Recta', 'Aisladas, 1000V', 'México', 100.00, 150.00, 5, 15, 1, 'Nuevo', 1, 'Herramienta', NULL, NULL, NULL, 'pinzas, electricista', '2025-07-14 09:08:14'),
(3, 'C001', 'Cinta Aislante', 'Cinta para aislar cables', 2, 2, 0.10, 10.00, 1.00, 1.00, 1, 'Consumible', '3M', 'Negro', 'Rollo', 'PVC, 19mm', 'China', 12.00, 25.00, 10, 23, 1, 'Nuevo', 1, 'Consumible', NULL, NULL, NULL, 'cinta, aislante', '2025-07-14 09:08:14'),
(4, 'C002', 'Tornillos 1"', 'Tornillo galvanizado', 2, 2, 0.00, 0.00, 0.00, 0.00, 1, '', 'Tornimex', 'Plateado', '', '', '', 1.00, 2.00, 300, 98, 1, 'Nuevo', 1, 'Consumible', NULL, NULL, NULL, 'tornillo, instalación', '2025-07-14 09:08:14'),
(20, 'C0011', 'Cable VGA2', 'gfggadfg', 1, 2, 10.00, 1.00, 10.00, 1.00, 2, '0sdf', 'manhattan', 'Negro', 'dsf', 'asdfdsfafdasf', 'dsf', 200.00, 300.00, 70, 792, 1, 'Nuevo', 1, 'Consumible', NULL, NULL, NULL, 'cable', '2025-07-24 12:36:15'),
(21, 'C0014', 'Plumas verdes', 'plumas verdes', 1, 1, 10.00, 1.00, 10.00, 1.00, 1, '0sdf', 'manhattan', 'Negro', 'dsf', '', 'dsf', 200.00, 300.00, 70, 56, 1, 'Nuevo', 1, 'Herramienta', NULL, NULL, NULL, 'dibujo', '2025-08-08 10:35:15');

--
-- Estructura de tabla para la tabla proveedores

CREATE TABLE proveedores (
id int(11) NOT NULL,
nombre varchar(150) NOT NULL,
contacto varchar(100) DEFAULT NULL,
telefono varchar(20) DEFAULT NULL,
email varchar(100) DEFAULT NULL,
direccion varchar(200) DEFAULT NULL,
condiciones_pago text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla proveedores

INSERT INTO proveedores (id, nombre, contacto, telefono, email, direccion, condiciones_pago) VALUES
(1, 'ElectroSuministros', 'Juan Rivera', '555-1000', 'contacto@electrosum.com', 'Calle Falsa 123', 'Contado'),
(2, 'MaterialesPro', 'Maria Gomez', '555-2000', 'ventas@materialespro.com', 'Av. Central 200', 'Crédito 30 días');

--
-- Estructura de tabla para la tabla solicitudes

CREATE TABLE solicitudes (
id int(11) NOT NULL,
usuario_id int(11) NOT NULL,
tipo_solicitud enum('Inventario existente','Peticion de compra') NOT NULL,
producto_id int(11) DEFAULT NULL,
cantidad int(11) NOT NULL,
tipo_producto enum('Consumible','Herramienta') NOT NULL,
especificaciones_pedido text DEFAULT NULL,
comentario_destino text DEFAULT NULL,
almacen_origen_id int(11) NOT NULL,
estado enum('Pendiente','Aprobada','Entregada','Cancelada','Rechazada') DEFAULT 'Pendiente',
approved_by_user_id int(11) DEFAULT NULL,
entregado_by_user_id int(11) DEFAULT NULL,
created_at datetime NOT NULL DEFAULT current_timestamp(),
updated_at datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla solicitudes

INSERT INTO solicitudes (id, usuario_id, tipo_solicitud, producto_id, cantidad, tipo_producto, especificaciones_pedido, comentario_destino, almacen_origen_id, estado, approved_by_user_id, entregado_by_user_id, created_at, updated_at) VALUES
(1, 3, 'Inventario existente', 1, 1, 'Herramienta', NULL, 'Para proyecto Luz24', 1, 'Pendiente', NULL, NULL, '2025-07-14 09:08:14', '2025-07-14 09:08:14'),
(2, 4, 'Inventario existente', 3, 3, 'Consumible', NULL, 'Mantenimiento semanal', 1, 'Pendiente', NULL, NULL, '2025-07-14 09:08:14', '2025-07-14 09:08:14'),
(3, 3, 'Peticion de compra', NULL, 2, 'Herramienta', 'Buscapolos digital, rango 12-1000V, marca Fluke', 'No hay buscapolos, se requiere urgente para instalaciones.', 1, 'Pendiente', NULL, NULL, '2025-07-14 09:08:14', '2025-07-14 09:08:14');

--
-- Estructura de tabla para la tabla solicitudes_material

CREATE TABLE solicitudes_material (
id int(11) NOT NULL,
usuario_id int(11) NOT NULL,
tipo enum('Consumible','Herramienta') NOT NULL,
estado enum('pendiente','aprobada','entregada','cancelada','rechazada') DEFAULT 'pendiente',
comentario text DEFAULT NULL,
fecha_solicitud datetime DEFAULT current_timestamp(),
fecha_respuesta datetime DEFAULT NULL,
usuario_responde_id int(11) DEFAULT NULL,
observaciones_respuesta text DEFAULT NULL,
usuario_aprueba_id int(11) DEFAULT NULL,
fecha_aprobacion datetime DEFAULT NULL,
usuario_entrega_id int(11) DEFAULT NULL,
fecha_entrega datetime DEFAULT NULL,
observaciones_entrega text DEFAULT NULL,
extras text DEFAULT NULL,
observacion text DEFAULT NULL,
tipo_solicitud enum('Servicio','General') NOT NULL DEFAULT 'Servicio'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla solicitudes_material

INSERT INTO solicitudes_material (id, usuario_id, tipo, estado, comentario, fecha_solicitud, fecha_respuesta, usuario_responde_id, observaciones_respuesta, usuario_aprueba_id, fecha_aprobacion, usuario_entrega_id, fecha_entrega, observaciones_entrega, extras, observacion, tipo_solicitud) VALUES
(1, 3, 'Herramienta', 'rechazada', 'que te importa', '2025-07-16 17:33:38', '2025-07-29 09:40:15', NULL, '', 1, NULL, NULL, NULL, NULL, NULL, NULL, 'Servicio'),
(2, 3, 'Consumible', 'rechazada', '', '2025-07-17 09:41:50', '2025-07-29 09:40:22', NULL, '', 1, NULL, NULL, NULL, NULL, NULL, NULL, 'Servicio'),
(3, 3, 'Consumible', 'entregada', 'Proyecto: Proyecto Takab', '2025-07-24 13:07:44', '2025-07-29 09:40:41', NULL, '', 1, NULL, 1, NULL, NULL, NULL, NULL, 'Servicio'),
(4, 3, '', 'entregada', '1', '2025-07-24 13:32:17', '2025-07-29 09:40:46', NULL, '', 1, NULL, 1, NULL, NULL, NULL, NULL, 'Servicio'),
(5, 3, '', 'entregada', 'prueba2', '2025-07-24 13:43:24', '2025-08-07 12:14:59', NULL, '', 1, NULL, 1, NULL, NULL, NULL, NULL, 'Servicio'),
(6, 3, '', 'entregada', 'prueba3', '2025-07-24 13:46:33', '2025-08-07 16:04:48', NULL, '', 1, NULL, 1, NULL, NULL, '[{"descripcion":"podadora","cantidad":"1"}]', NULL, 'Servicio'),
(7, 3, '', 'entregada', 'prueba4', '2025-07-24 14:02:10', '2025-07-24 16:35:35', NULL, '', 1, NULL, 1, NULL, NULL, '[{"descripcion":"taza para cage","cantidad":"5","observacion":"pa desayunar"}]', 'tambien quiero un pancito', 'Servicio'),
(8, 3, '', 'rechazada', 'hay que proteger la obra de los maleantes', '2025-07-24 14:19:19', '2025-07-24 16:34:51', NULL, 'mejor un lobo', 1, NULL, NULL, NULL, NULL, '[{"descripcion":"perro","cantidad":"2","observacion":"para cuidar la obra"}]', '', 'Servicio'),
(9, 3, '', 'entregada', 'obligatorio', '2025-07-24 16:41:22', '2025-07-24 16:45:13', NULL, 'ahi estan tus cosas', 1, NULL, 1, NULL, NULL, '[{"descripcion":"taquetes rojo","cantidad":"300","observacion":"ni modo que con que los pego"}]', 'opcional', 'Servicio'),
(10, 3, '', 'entregada', 'necesito un gato', '2025-07-24 16:43:12', '2025-08-07 14:23:47', NULL, '', 1, NULL, 1, NULL, NULL, '[{"descripcion":"gato","cantidad":"2","observacion":"con credito"}]', 'por favor', 'General'),
(11, 3, '', 'entregada', 'obligatorio2', '2025-07-24 16:49:40', '2025-07-24 16:50:24', NULL, '', 1, NULL, 1, NULL, NULL, NULL, 'opcional1', 'Servicio'),
(12, 3, '', 'entregada', 'obligatorio3', '2025-07-24 17:14:53', '2025-07-24 17:22:56', NULL, '', 1, NULL, 1, NULL, NULL, NULL, 'opcional3', 'Servicio'),
(13, 3, 'Consumible', 'entregada', 'Cu2', '2025-07-25 17:51:56', '2025-07-25 17:55:45', NULL, '', 1, NULL, 1, NULL, NULL, '[{"descripcion":"martillo","cantidad":"2","observacion":"matillo cabeza cirtular de bola"}]', 'opcional3', 'Servicio'),
(14, 3, '', 'entregada', 'para mi', '2025-07-25 17:53:33', '2025-08-07 12:11:08', NULL, '', 1, NULL, 1, NULL, NULL, '[{"descripcion":"Talado","cantidad":"1","observacion":"se me rompio"}]', '', 'General'),
(15, 9, '', 'rechazada', 'Biologia', '2025-08-04 17:26:56', '2025-08-04 17:29:01', NULL, 'lo que pides no hay', 1, NULL, NULL, NULL, NULL, '[{"descripcion":"martillo","cantidad":"10","observacion":"se ocupa para el servicio"}]', 'material para el salon fc01', 'Servicio'),
(16, 9, '', 'entregada', 'Biologia', '2025-08-04 17:30:05', '2025-08-04 17:31:30', NULL, '', 1, NULL, 1, NULL, NULL, '[{"descripcion":"telefono","cantidad":"2","observacion":"con credito"}]', 'material para el salon fc03', 'Servicio'),
(17, 3, '', 'entregada', 'Biologia', '2025-08-07 12:02:24', '2025-08-07 13:24:14', NULL, 'observacion entrega biologia', 1, NULL, 1, NULL, NULL, NULL, 'material para el salon fc03', 'Servicio'),
(18, 3, 'Herramienta', 'entregada', 'comentario obligatorio', '2025-08-07 12:23:52', '2025-08-07 12:24:49', NULL, 'entregado', 1, NULL, 1, NULL, NULL, NULL, 'comentario opcional', 'Servicio'),
(19, 3, 'Herramienta', 'entregada', '1', '2025-08-07 16:04:25', '2025-08-07 16:04:57', NULL, '', 1, NULL, 1, NULL, NULL, NULL, '2', 'Servicio'),
(20, 3, 'Herramienta', 'entregada', 'proyecto buap', '2025-08-08 15:30:22', '2025-08-08 15:31:37', NULL, 'entregado', 1, NULL, 1, NULL, NULL, NULL, 'hola', 'Servicio'),
(21, 3, 'Herramienta', 'entregada', 'proyecto buap', '2025-08-08 17:31:50', '2025-08-08 17:33:31', NULL, 'entregado pachas', 1, NULL, 1, NULL, NULL, NULL, 'opcional', 'Servicio');

--
-- Estructura de tabla para la tabla unidades_medida

CREATE TABLE unidades_medida (
id int(11) NOT NULL,
nombre varchar(100) NOT NULL,
abreviacion varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla unidades_medida

INSERT INTO unidades_medida (id, nombre, abreviacion) VALUES
(1, 'Pieza', 'pz'),
(2, 'Metro', 'm'),
(3, 'Caja', 'cj'),
(4, 'Litro', 'l');

--
-- Estructura de tabla para la tabla usuarios

CREATE TABLE usuarios (
id int(11) NOT NULL,
username varchar(100) NOT NULL,
password varchar(255) NOT NULL,
nombre_completo varchar(150) NOT NULL,
role enum('Administrador','Almacen','Empleado') NOT NULL,
activo tinyint(1) NOT NULL DEFAULT 1,
created_at datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla usuarios

INSERT INTO usuarios (id, username, password, nombre_completo, role, activo, created_at) VALUES
(1, 'admin', '$2y$10$XYLZmze1GckxFLTvvjTAjul1a8.aeB/oHFm1c8v.eaYUSyB2HP3qm', 'Administrador General', 'Administrador', 1, '2025-07-14 09:08:14'),
(2, 'almacen', '$2y$10$XYLZmze1GckxFLTvvjTAjul1a8.aeB/oHFm1c8v.eaYUSyB2HP3qm', 'Encargado de Almacén', 'Almacen', 1, '2025-07-14 09:08:14'),
(3, 'luis', '$2y$10$XYLZmze1GckxFLTvvjTAjul1a8.aeB/oHFm1c8v.eaYUSyB2HP3qm', 'Luis Pérez', 'Empleado', 1, '2025-07-14 09:08:14'),
(4, 'mau', '$2y$10$Bhrduk.2p/fS5fnMe/GGrOb/HIJ46jMnCOwh9SQSj0sLqnnNy7qhC', 'Mauricio Bautista', 'Administrador', 1, '2025-07-14 09:08:14'),
(5, 'Fer', '$2y$10$F8fqtGE/X0LCe7GNM2vba.jso/6y7y6ChZyPxYujyT66a3HYd3AEK', 'Fernanda', 'Empleado', 1, '2025-07-17 14:31:13'),
(6, 'Palomino', '$2y$10$Wp45d.821SHAIbF8xwkhzuF0.JAxw95e8ul8BFqhKYY0vlVUQg98.', 'Paulino', 'Empleado', 0, '2025-07-17 16:04:15'),
(7, 'DieguitoMaradona', '$2y$10$lx.JOrx4yMwlA5Vxkzh92.oMeV8j1JslheOS/d8mcfygnXjjwSMFq', 'Diego Armando Maradona', 'Almacen', 1, '2025-07-17 16:28:53'),
(8, 'a', '$2y$10$b7kuc5M0dzOFUBccHzLXWuHUBkrn77aIDGwFArDDN3LG7XuX8s6Ma', 'a', 'Empleado', 0, '2025-07-17 16:41:09'),
(9, 'armando', '$2y$10$bzGsJ4a2wcwu.JicWA6wIOB0CehMNPpTBdWDSWrG8ky/YrPxj2Lc6', 'armando', 'Empleado', 1, '2025-08-04 17:24:35');

--
-- Índices para tablas volcadas

ALTER TABLE alertas_configuradas
ADD PRIMARY KEY (id);

ALTER TABLE almacenes
ADD PRIMARY KEY (id),
ADD KEY responsable_id (responsable_id);

ALTER TABLE categorias
ADD PRIMARY KEY (id);

ALTER TABLE clientes
ADD PRIMARY KEY (id);

ALTER TABLE detalle_ordenes
ADD PRIMARY KEY (id),
ADD KEY orden_id (orden_id),
ADD KEY producto_id (producto_id);

ALTER TABLE estados_producto_activo
ADD PRIMARY KEY (id),
ADD UNIQUE KEY nombre (nombre);

ALTER TABLE movimientos_inventario
ADD PRIMARY KEY (id),
ADD KEY producto_id (producto_id),
ADD KEY usuario_id (usuario_id),
ADD KEY almacen_origen_id (almacen_origen_id),
ADD KEY almacen_destino_id (almacen_destino_id);

ALTER TABLE ordenes_compra
ADD PRIMARY KEY (id),
ADD KEY proveedor_id (proveedor_id),
ADD KEY solicitud_id (solicitud_id);

ALTER TABLE prestamos
ADD PRIMARY KEY (id),
ADD KEY producto_id (producto_id),
ADD KEY empleado_id (empleado_id),
ADD KEY autorizado_by_user_id (autorizado_by_user_id);

ALTER TABLE productos
ADD PRIMARY KEY (id),
ADD UNIQUE KEY codigo (codigo),
ADD KEY proveedor_id (proveedor_id),
ADD KEY categoria_id (categoria_id),
ADD KEY unidad_medida_id (unidad_medida_id),
ADD KEY almacen_id (almacen_id),
ADD KEY last_requested_by_user_id (last_requested_by_user_id),
ADD KEY fk_producto_activo (activo_id);

ALTER TABLE proveedores
ADD PRIMARY KEY (id);

ALTER TABLE solicitudes
ADD PRIMARY KEY (id),
ADD KEY usuario_id (usuario_id),
ADD KEY producto_id (producto_id),
ADD KEY almacen_origen_id (almacen_origen_id),
ADD KEY approved_by_user_id (approved_by_user_id),
ADD KEY entregado_by_user_id (entregado_by_user_id);

ALTER TABLE solicitudes_material
ADD PRIMARY KEY (id),
ADD KEY usuario_id (usuario_id),
ADD KEY usuario_responde_id (usuario_responde_id);

ALTER TABLE unidades_medida
ADD PRIMARY KEY (id);

ALTER TABLE usuarios
ADD PRIMARY KEY (id),
ADD UNIQUE KEY username (username);

--
-- AUTO_INCREMENT de las tablas volcadas

ALTER TABLE alertas_configuradas
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE almacenes
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE categorias
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE clientes
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE detalle_ordenes
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE detalle_solicitud
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

ALTER TABLE estados_producto_activo
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE movimientos_inventario
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

ALTER TABLE ordenes_compra
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE prestamos
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

ALTER TABLE productos
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

ALTER TABLE proveedores
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE solicitudes
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE solicitudes_material
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

ALTER TABLE unidades_medida
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE usuarios
MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Restricciones (FOREIGN KEYS)

ALTER TABLE almacenes
ADD CONSTRAINT almacenes_ibfk_1 FOREIGN KEY (responsable_id) REFERENCES usuarios (id);

ALTER TABLE detalle_ordenes
ADD CONSTRAINT detalle_ordenes_ibfk_1 FOREIGN KEY (orden_id) REFERENCES ordenes_compra (id),
ADD CONSTRAINT detalle_ordenes_ibfk_2 FOREIGN KEY (producto_id) REFERENCES productos (id);

ALTER TABLE detalle_solicitud
ADD CONSTRAINT detalle_solicitud_ibfk_1 FOREIGN KEY (solicitud_id) REFERENCES solicitudes_material (id),
ADD CONSTRAINT detalle_solicitud_ibfk_2 FOREIGN KEY (producto_id) REFERENCES productos (id);

ALTER TABLE movimientos_inventario
ADD CONSTRAINT movimientos_inventario_ibfk_1 FOREIGN KEY (producto_id) REFERENCES productos (id),
ADD CONSTRAINT movimientos_inventario_ibfk_2 FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
ADD CONSTRAINT movimientos_inventario_ibfk_3 FOREIGN KEY (almacen_origen_id) REFERENCES almacenes (id),
ADD CONSTRAINT movimientos_inventario_ibfk_4 FOREIGN KEY (almacen_destino_id) REFERENCES almacenes (id);

ALTER TABLE ordenes_compra
ADD CONSTRAINT ordenes_compra_ibfk_1 FOREIGN KEY (proveedor_id) REFERENCES proveedores (id),
ADD CONSTRAINT ordenes_compra_ibfk_2 FOREIGN KEY (solicitud_id) REFERENCES solicitudes (id);

ALTER TABLE prestamos
ADD CONSTRAINT prestamos_ibfk_1 FOREIGN KEY (producto_id) REFERENCES productos (id),
ADD CONSTRAINT prestamos_ibfk_2 FOREIGN KEY (empleado_id) REFERENCES usuarios (id),
ADD CONSTRAINT prestamos_ibfk_3 FOREIGN KEY (autorizado_by_user_id) REFERENCES usuarios (id);

ALTER TABLE productos
ADD CONSTRAINT fk_producto_activo FOREIGN KEY (activo_id) REFERENCES estados_producto_activo (id),
ADD CONSTRAINT productos_ibfk_1 FOREIGN KEY (proveedor_id) REFERENCES proveedores (id),
ADD CONSTRAINT productos_ibfk_2 FOREIGN KEY (categoria_id) REFERENCES categorias (id),
ADD CONSTRAINT productos_ibfk_3 FOREIGN KEY (unidad_medida_id) REFERENCES unidades_medida (id),
ADD CONSTRAINT productos_ibfk_4 FOREIGN KEY (almacen_id) REFERENCES almacenes (id),
ADD CONSTRAINT productos_ibfk_5 FOREIGN KEY (last_requested_by_user_id) REFERENCES usuarios (id);

ALTER TABLE solicitudes
ADD CONSTRAINT solicitudes_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
ADD CONSTRAINT solicitudes_ibfk_2 FOREIGN KEY (producto_id) REFERENCES productos (id),
ADD CONSTRAINT solicitudes_ibfk_3 FOREIGN KEY (almacen_origen_id) REFERENCES almacenes (id),
ADD CONSTRAINT solicitudes_ibfk_4 FOREIGN KEY (approved_by_user_id) REFERENCES usuarios (id),
ADD CONSTRAINT solicitudes_ibfk_5 FOREIGN KEY (entregado_by_user_id) REFERENCES usuarios (id);

ALTER TABLE solicitudes_material
ADD CONSTRAINT solicitudes_material_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
ADD CONSTRAINT solicitudes_material_ibfk_2 FOREIGN KEY (usuario_responde_id) REFERENCES usuarios (id);

COMMIT;

-- Restaurar settings de sesión
SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;
SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;
SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;