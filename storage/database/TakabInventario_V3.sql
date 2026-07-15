CREATE DATABASE  IF NOT EXISTS `takab_inventario` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;



USE `takab_inventario`;



-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)



--



-- Host: localhost    Database: takab_inventario



-- ------------------------------------------------------



-- Server version	5.5.5-10.11.13-MariaDB-0ubuntu0.24.04.1







/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;



/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;



/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;



/*!50503 SET NAMES utf8 */;



/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;



/*!40103 SET TIME_ZONE='+00:00' */;



/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;



/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;



/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;



/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;







--



-- Table structure for table `alertas_configuradas`



--







DROP TABLE IF EXISTS `alertas_configuradas`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `alertas_configuradas` (



  `id` int(11) NOT NULL AUTO_INCREMENT,



  `tipo_alerta` varchar(100) NOT NULL,



  `valor_umbral` int(11) NOT NULL,



  `activa` tinyint(1) NOT NULL DEFAULT 1,



  PRIMARY KEY (`id`)



) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `alertas_configuradas`



--







LOCK TABLES `alertas_configuradas` WRITE;



/*!40000 ALTER TABLE `alertas_configuradas` DISABLE KEYS */;



INSERT INTO `alertas_configuradas` VALUES (1,'Stock mínimo cinta aislante',10,1),(2,'Préstamos vencidos',1,1);



/*!40000 ALTER TABLE `alertas_configuradas` ENABLE KEYS */;



UNLOCK TABLES;







--



-- Table structure for table `almacenes`



--







DROP TABLE IF EXISTS `almacenes`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `almacenes` (



  `id` int(11) NOT NULL AUTO_INCREMENT,



  `nombre` varchar(100) NOT NULL,



  `ubicacion` varchar(200) DEFAULT NULL,



  `responsable_id` int(11) DEFAULT NULL,



  `es_principal` tinyint(1) NOT NULL DEFAULT 0,



  PRIMARY KEY (`id`),



  KEY `responsable_id` (`responsable_id`)



) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `almacenes`



--







LOCK TABLES `almacenes` WRITE;



/*!40000 ALTER TABLE `almacenes` DISABLE KEYS */;



INSERT INTO `almacenes` VALUES (1,'Almacén Principal','Planta baja, Calle 1',2,1),(5,'Almacen 3','Blvd 5 de mayo',6,0);



/*!40000 ALTER TABLE `almacenes` ENABLE KEYS */;



UNLOCK TABLES;







--



-- Table structure for table `categorias`



--







DROP TABLE IF EXISTS `categorias`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `categorias` (



  `id` int(11) NOT NULL AUTO_INCREMENT,



  `nombre` varchar(100) NOT NULL,



  `descripcion` text DEFAULT NULL,



  PRIMARY KEY (`id`)



) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `categorias`



--







LOCK TABLES `categorias` WRITE;



/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;



INSERT INTO `categorias` VALUES (1,'Herramientas eléctricas','Herramientas para instalaciones eléctricas'),(2,'Materiales','Material consumible para instalaciones'),(3,'Herramientas Mecánicas','Herramientas para facilitar el uso en las tareas'),(4,'Caja de Herramientas','Herramienta asignada'),(5,'Redes','Asignado a redes'),(6,'Generadores','Asignado a generadores'),(7,'Aires Acondicionados','Asignado a Aires Acondicionados'),(8,'Equipo de Protección Personal','Equipo para salvar la integridad del trabajador'),(9,'Electrico','Enfocado a Eléctrico');



/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;



UNLOCK TABLES;







--



-- Table structure for table `clientes`



--







DROP TABLE IF EXISTS `clientes`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `clientes` (



  `id` int(11) NOT NULL AUTO_INCREMENT,



  `nombre` varchar(150) NOT NULL,



  `contacto` varchar(100) DEFAULT NULL,



  `telefono` varchar(20) DEFAULT NULL,



  `email` varchar(100) DEFAULT NULL,



  `direccion` varchar(200) DEFAULT NULL,



  PRIMARY KEY (`id`)



) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `clientes`



--







LOCK TABLES `clientes` WRITE;



/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;



INSERT INTO `clientes` VALUES (1,'Constructora Alfa','Carlos Pérez','555-1234','contacto@alfa.com','Av. Reforma 100'),(2,'Servicios Beta','María López','555-5678','ventas@beta.com','Calle Industrial 45');



/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;



UNLOCK TABLES;







--



-- Table structure for table `detalle_ordenes`



--







DROP TABLE IF EXISTS `detalle_ordenes`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `detalle_ordenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orden_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_detalle_orden_orden` (`orden_id`),
  KEY `idx_detalle_orden_producto` (`producto_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `detalle_ordenes`



--







LOCK TABLES `detalle_ordenes` WRITE;



/*!40000 ALTER TABLE `detalle_ordenes` DISABLE KEYS */;



INSERT INTO `detalle_ordenes` VALUES (1,1,1,2,600.00);



/*!40000 ALTER TABLE `detalle_ordenes` ENABLE KEYS */;



UNLOCK TABLES;







--



-- Table structure for table `detalle_solicitud`



--







DROP TABLE IF EXISTS `detalle_solicitud`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `detalle_solicitud` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `solicitud_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `observacion` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_detalle_solicitud_solicitud` (`solicitud_id`),
  KEY `idx_detalle_solicitud_producto` (`producto_id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `detalle_solicitud`



--







LOCK TABLES `detalle_solicitud` WRITE;



/*!40000 ALTER TABLE `detalle_solicitud` DISABLE KEYS */;



INSERT INTO `detalle_solicitud` VALUES (1,1,1,1.00,NULL),(2,1,2,2.00,NULL),(3,2,3,3.00,NULL),(4,2,4,10.00,NULL),(7,4,3,1.00,NULL),(8,4,1,1.00,NULL),(9,5,20,2.00,NULL),(10,5,1,1.00,NULL),(11,6,4,12.00,NULL),(12,6,1,1.00,NULL),(13,7,3,1.00,''),(14,7,1,1.00,''),(15,9,4,300.00,'pa mi casa'),(16,9,1,2.00,'por si se rompe el mio'),(17,11,4,500.00,'pa mi casa'),(18,11,1,3.00,'por si se rompe el mio'),(19,12,4,50.00,'pa mi casa'),(20,12,1,3.00,'por si se rompe el mio'),(21,13,3,1.00,'pa mi casa'),(22,13,2,3.00,'por si se rompe el mio'),(23,13,4,50.00,'pa mi casa'),(24,15,4,50.00,''),(25,15,2,1.00,''),(26,16,20,2.00,''),(27,16,1,1.00,''),(28,17,4,20.00,''),(29,17,2,4.00,''),(30,18,1,2.00,''),(31,18,2,1.00,''),(32,19,1,1.00,''),(33,19,2,1.00,''),(34,19,2,1.00,''),(35,20,21,20.00,'pa firmar'),(36,20,2,2.00,'pa pincear'),(37,21,1,5.00,''),(38,21,2,5.00,''),(39,22,29,5.00,'pa mi casa');



/*!40000 ALTER TABLE `detalle_solicitud` ENABLE KEYS */;



UNLOCK TABLES;







--



-- Table structure for table `estados_producto_activo`



--







DROP TABLE IF EXISTS `estados_producto_activo`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `estados_producto_activo` (



  `id` int(11) NOT NULL AUTO_INCREMENT,



  `nombre` varchar(30) NOT NULL,



  PRIMARY KEY (`id`),



  UNIQUE KEY `nombre` (`nombre`)



) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `estados_producto_activo`



--







LOCK TABLES `estados_producto_activo` WRITE;



/*!40000 ALTER TABLE `estados_producto_activo` DISABLE KEYS */;



INSERT INTO `estados_producto_activo` VALUES (1,'Activo'),(2,'Desactivado');



/*!40000 ALTER TABLE `estados_producto_activo` ENABLE KEYS */;



UNLOCK TABLES;







--



-- Table structure for table `logs_actividad`



--







DROP TABLE IF EXISTS `logs_actividad`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `logs_actividad` (



  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,



  `usuario_id` int(11) DEFAULT NULL,



  `accion` varchar(100) NOT NULL,



  `descripcion` text DEFAULT NULL,



  `ip` varchar(45) DEFAULT NULL,



  `user_agent` varchar(255) DEFAULT NULL,



  `created_at` datetime NOT NULL DEFAULT current_timestamp(),



  PRIMARY KEY (`id`),



  KEY `idx_accion` (`accion`),



  KEY `idx_created_at` (`created_at`),



  KEY `idx_usuario` (`usuario_id`)



) ENGINE=InnoDB AUTO_INCREMENT=179 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `logs_actividad`



--







LOCK TABLES `logs_actividad` WRITE;



/*!40000 ALTER TABLE `logs_actividad` DISABLE KEYS */;



INSERT INTO `logs_actividad` VALUES (1,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 14:01:06'),(2,1,'logout','Cierre de sesión','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 14:07:35'),(3,12,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 14:07:47'),(4,12,'logout','Cierre de sesión','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 14:08:02'),(5,3,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 14:08:06'),(6,3,'logout','Cierre de sesión','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 14:08:56'),(7,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 14:09:01'),(8,1,'plantilla_productos','Descarga de plantilla de productos','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 14:09:08'),(9,1,'productos_import','Importación de productos finalizada {\"exitosos\":2,\"procesados\":2,\"omitidos\":0}','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 14:10:34'),(10,1,'producto_eliminado','Se eliminó el producto Taladro percutor {\"codigo\":\"HERR-001\"}','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 14:10:59'),(11,1,'producto_eliminado','Se eliminó el producto Cinta aislante {\"codigo\":\"CON-200\"}','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 14:11:18'),(12,1,'login','Inicio de sesión exitoso','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 15:29:15'),(13,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 15:31:07'),(14,1,'plantilla_productos','Descarga de plantilla de productos','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 15:33:32'),(15,1,'productos_import','Importación de productos finalizada {\"exitosos\":2,\"procesados\":2,\"omitidos\":0}','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 15:34:24'),(16,1,'producto_eliminado','Se eliminó el producto Cinta aislante {\"codigo\":\"CON-200\"}','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 15:37:05'),(17,1,'producto_eliminado','Se eliminó el producto Taladro percutor {\"codigo\":\"HERR-001\"}','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 15:38:03'),(18,3,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 17:35:03'),(19,3,'logout','Cierre de sesión','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 17:36:33'),(20,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-21 17:36:38'),(21,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 08:48:12'),(22,1,'login','Inicio de sesión exitoso','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 09:54:10'),(23,1,'producto_actualizado','Se actualizó el producto Escalera dieléctrica {\"codigo\":\" C-3017-10n\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 09:55:50'),(24,1,'producto_actualizado','Se actualizó el producto Escalera Tipo Tijera {\"codigo\":\" C-3217-08\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 09:56:37'),(25,1,'plantilla_productos','Descarga de plantilla de productos','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 09:58:20'),(26,1,'productos_import','Importación de productos finalizada {\"exitosos\":0,\"procesados\":1,\"omitidos\":0}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 10:03:04'),(27,1,'producto_actualizado','Se actualizó el producto Cemento Gris {\"codigo\":\"30111601\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 10:04:24'),(28,1,'producto_creado','Se registró el producto Cal {\"codigo\":\"checar126\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 10:09:37'),(29,1,'producto_creado','Se registró el producto Piedras decorativas para Jardín {\"codigo\":\"checae098\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 10:17:51'),(30,1,'producto_creado','Se registró el producto Escobas {\"codigo\":\"que1234\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 10:19:34'),(31,1,'producto_creado','Se registró el producto Recogedor {\"codigo\":\"che123456\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 10:21:34'),(32,1,'producto_creado','Se registró el producto Form Cleaner {\"codigo\":\"checar 12345678\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 10:37:12'),(33,1,'producto_actualizado','Se actualizó el producto Pintura blanca {\"codigo\":\"por checar1\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 10:38:33'),(34,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 10:41:18'),(35,1,'producto_creado','Se registró el producto asdfaf {\"codigo\":\"asdfasd\"}','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 10:44:39'),(36,1,'producto_actualizado','Se actualizó el producto Escalera dieléctrica {\"codigo\":\" C-3017-10n\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 10:47:20'),(37,1,'producto_eliminado','Se eliminó el producto asdfaf {\"codigo\":\"asdfasd\"}','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 10:47:41'),(38,1,'producto_creado','Se registró el producto Coil Cleaner {\"codigo\":\"des1234\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 10:53:26'),(39,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-22 10:55:04'),(40,1,'producto_creado','Se registró el producto Escalera plegable {\"codigo\":\"Checar 23456\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 11:35:43'),(41,1,'producto_creado','Se registró el producto Etiquetadora MP200 {\"codigo\":\"Checae75645\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 11:44:08'),(42,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 11:55:19'),(43,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-22 12:12:16'),(44,1,'producto_actualizado','Se actualizó el producto Etiquetadora MP200 {\"codigo\":\"45101512\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 12:21:48'),(45,1,'producto_creado','Se registró el producto Etiquetadora {\"codigo\":\"MP75\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 12:27:00'),(46,1,'producto_actualizado','Se actualizó el producto Etiquetadora MP200 {\"codigo\":\"MP200\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 12:27:30'),(47,1,'producto_creado','Se registró el producto Cable desnudo Cal.2 {\"codigo\":\"deesco123\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 12:31:58'),(48,1,'producto_creado','Se registró el producto Cable desnudo cal.10 {\"codigo\":\"desco234\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 12:33:59'),(49,NULL,'logout','Cierre de sesión','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 12:51:19'),(50,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 12:51:24'),(51,1,'producto_creado','Se registró el producto dfgfdg {\"codigo\":\"asfdasdf\"}','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 12:55:47'),(52,1,'producto_eliminado','Se eliminó el producto dfgfdg {\"codigo\":\"asfdasdf\"}','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 12:56:47'),(53,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 16:40:02'),(54,1,'logout','Cierre de sesión','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 16:40:07'),(55,3,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 16:40:13'),(56,3,'logout','Cierre de sesión','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 16:42:57'),(57,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-22 16:43:05'),(58,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:24:24'),(59,1,'producto_estado','Se cambió la disponibilidad del producto {\"producto_id\":29,\"activo\":false}','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:26:53'),(60,1,'logout','Cierre de sesión','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:27:36'),(61,12,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:27:44'),(62,12,'logout','Cierre de sesión','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:28:08'),(63,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:28:17'),(64,1,'logout','Cierre de sesión','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:28:31'),(65,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:28:35'),(66,1,'logout','Cierre de sesión','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:28:37'),(67,12,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:28:40'),(68,12,'logout','Cierre de sesión','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:29:39'),(69,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:29:44'),(70,1,'producto_actualizado','Se actualizó el producto Aceite 15W-40 {\"codigo\":\"por checar2\"}','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:32:06'),(71,1,'producto_estado','Se cambió la disponibilidad del producto {\"producto_id\":29,\"activo\":true}','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:33:27'),(72,1,'login','Inicio de sesión exitoso','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:36:03'),(73,1,'producto_creado','Se registró el producto Manometros {\"codigo\":\"desco23409\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 09:42:27'),(74,1,'producto_creado','Se registró el producto Gas de Propileno {\"codigo\":\"CB-1000\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 10:01:22'),(75,1,'producto_actualizado','Se actualizó el producto Gas de Propileno {\"codigo\":\"CB-1000\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 10:02:46'),(76,1,'producto_actualizado','Se actualizó el producto Form Cleaner {\"codigo\":\"ACRE-0135\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 10:09:00'),(77,1,'producto_actualizado','Se actualizó el producto Form Cleaner {\"codigo\":\"ACRE-0135\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 10:09:51'),(78,1,'producto_actualizado','Se actualizó el producto Manometros {\"codigo\":\"CT-536\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 10:15:25'),(79,1,'producto_creado','Se registró el producto Tubo de cobre 1/4 (2.6m) {\"codigo\":\"BCT41103001825-Takab1\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 10:28:45'),(80,1,'producto_creado','Se registró el producto Tubo de cobre 1/4 (1m) {\"codigo\":\"BCT41103001825-Takab2\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 10:34:20'),(81,1,'producto_estado','Se cambió la disponibilidad del producto {\"producto_id\":67,\"activo\":false}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 10:34:31'),(82,1,'producto_estado','Se cambió la disponibilidad del producto {\"producto_id\":66,\"activo\":false}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 10:34:40'),(83,1,'producto_creado','Se registró el producto Tubo de cobre (12.6m) {\"codigo\":\"BCT41103001825-Takab3\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 10:37:54'),(84,1,'producto_creado','Se registró el producto Tubo de cobre 1/4 (99cm) {\"codigo\":\"BCT41103001825-Takab4\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 10:42:06'),(85,1,'producto_creado','Se registró el producto Tubo de cobre 1/4 (1.6m) {\"codigo\":\"BCT41103001825-Takab5\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 10:45:57'),(86,1,'producto_estado','Se cambió la disponibilidad del producto {\"producto_id\":69,\"activo\":false}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 10:46:13'),(87,1,'producto_estado','Se cambió la disponibilidad del producto {\"producto_id\":70,\"activo\":false}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 10:46:21'),(88,1,'producto_creado','Se registró el producto Tubo de cobre 1/4 (1.2m) {\"codigo\":\"BCT41103001825-Takab7\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 11:13:04'),(89,1,'producto_creado','Se registró el producto Tubo de cobre 1/4 (3.5m) {\"codigo\":\"BCT41103001825-Takab8\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 11:25:32'),(90,1,'producto_actualizado','Se actualizó el producto Tubo de cobre 1/4 (99cm) {\"codigo\":\"BCT41103001825-Takab4\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 11:25:46'),(91,1,'producto_creado','Se registró el producto Tubo de cobre 1/4 (2.9m) {\"codigo\":\"BCT41103001825-Takab9\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 11:27:55'),(92,1,'producto_creado','Se registró el producto Tubo de cobre 1/4 (1.18m) {\"codigo\":\"BCT41103001825-Takab6\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 11:30:27'),(93,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:01:04'),(94,1,'login','Inicio de sesión exitoso','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:09:46'),(95,1,'producto_estado','Se cambió la disponibilidad del producto {\"producto_id\":72,\"activo\":false}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:10:05'),(96,1,'producto_estado','Se cambió la disponibilidad del producto {\"producto_id\":73,\"activo\":false}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:10:21'),(97,1,'producto_estado','Se cambió la disponibilidad del producto {\"producto_id\":71,\"activo\":false}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:10:34'),(98,1,'producto_estado','Se cambió la disponibilidad del producto {\"producto_id\":74,\"activo\":false}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:10:44'),(99,1,'producto_estado','Se cambió la disponibilidad del producto {\"producto_id\":68,\"activo\":false}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:10:53'),(100,1,'producto_creado','Se registró el producto Tubo de cobre 1/4 (70cm) {\"codigo\":\"BCT41103001825-Takab10\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:13:55'),(101,1,'producto_estado','Se cambió la disponibilidad del producto {\"producto_id\":75,\"activo\":false}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:14:14'),(102,1,'producto_actualizado','Se actualizó el producto Tubo de cobre 1/4 (99cm) {\"codigo\":\"BCT41103001825-Takab4\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:14:27'),(103,1,'producto_actualizado','Se actualizó el producto Tubo de cobre 1/4 (3.5m) {\"codigo\":\"BCT41103001825-Takab8\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:14:44'),(104,1,'producto_actualizado','Se actualizó el producto Tubo de cobre 1/4 (2.9m) {\"codigo\":\"BCT41103001825-Takab9\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:15:00'),(105,1,'producto_actualizado','Se actualizó el producto Tubo de cobre 1/4 (2.9m) {\"codigo\":\"BCT41103001825-Takab9\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:15:23'),(106,1,'producto_actualizado','Se actualizó el producto Tubo de cobre 1/4 (2.6m) {\"codigo\":\"BCT41103001825-Takab1\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:15:35'),(107,1,'producto_actualizado','Se actualizó el producto Tubo de cobre 1/4 (1m) {\"codigo\":\"BCT41103001825-Takab2\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:15:49'),(108,1,'producto_actualizado','Se actualizó el producto Tubo de cobre 1/4 (1.6m) {\"codigo\":\"BCT41103001825-Takab5\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:16:04'),(109,1,'producto_actualizado','Se actualizó el producto Tubo de cobre 1/4 (1.2m) {\"codigo\":\"BCT41103001825-Takab7\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:16:20'),(110,1,'producto_creado','Se registró el producto Tubo de cobre 1/4 (2m) {\"codigo\":\"BCT41103001825-Takab11\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:17:47'),(111,1,'producto_actualizado','Se actualizó el producto Tubo de cobre 1/4 (1.6m) {\"codigo\":\"BCT41103001825-Takab5\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:19:13'),(112,1,'producto_creado','Se registró el producto Fumigador 1 litro {\"codigo\":\"10929\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:22:53'),(113,1,'logout','Cierre de sesión','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 12:23:30'),(114,1,'login','Inicio de sesión exitoso','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 15:13:20'),(115,1,'login','Inicio de sesión exitoso','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 16:25:45'),(116,1,'producto_creado','Se registró el producto Control Aire Acondicionado {\"codigo\":\"Takab1\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 17:01:02'),(117,1,'producto_actualizado','Se actualizó el producto Control Aire Acondicionado {\"codigo\":\"Takab1\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 17:01:40'),(118,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 17:02:57'),(119,1,'producto_creado','Se registró el producto Control Aires Acondicionados {\"codigo\":\"Takab2\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 17:06:15'),(120,1,'login','Inicio de sesión exitoso','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-23 17:33:56'),(121,1,'login','Inicio de sesión exitoso','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 09:22:04'),(122,1,'producto_actualizado','Se actualizó el producto Aceite 15W-40 {\"codigo\":\"VL876146\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 09:42:15'),(123,1,'producto_actualizado','Se actualizó el producto Aceite 15W-40 premium blue 7800 {\"codigo\":\"Vl876146(1)\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 09:43:59'),(124,1,'producto_actualizado','Se actualizó el producto Cemento Gris {\"codigo\":\"30111601\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 09:45:39'),(125,1,'producto_actualizado','Se actualizó el producto Cal {\"codigo\":\"checar126\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 09:49:12'),(126,1,'producto_actualizado','Se actualizó el producto Refrigerante  {\"codigo\":\" CC2848\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 09:52:13'),(127,1,'producto_actualizado','Se actualizó el producto RD-MIX {\"codigo\":\"19ACL009\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 09:55:34'),(128,1,'producto_actualizado','Se actualizó el producto RD-MIX {\"codigo\":\"19ACL009\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 09:56:05'),(129,1,'producto_actualizado','Se actualizó el producto Esmalte Verde {\"codigo\":\"por checar\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:01:32'),(130,1,'producto_actualizado','Se actualizó el producto Esmalte Verde {\"codigo\":\"por checar\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:01:32'),(131,1,'producto_estado','Se cambió la disponibilidad del producto {\"producto_id\":76,\"activo\":false}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:02:01'),(132,1,'producto_actualizado','Se actualizó el producto Coil Cleaner {\"codigo\":\"FCC1\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:04:40'),(133,1,'producto_actualizado','Se actualizó el producto Coil Cleaner {\"codigo\":\"FCC1\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:04:40'),(134,1,'producto_actualizado','Se actualizó el producto Pegazulejo {\"codigo\":\"30164\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:08:22'),(135,1,'producto_actualizado','Se actualizó el producto Pegazulejo {\"codigo\":\"30164\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:09:16'),(136,1,'producto_actualizado','Se actualizó el producto Pegazulejo {\"codigo\":\"30164\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:10:04'),(137,1,'producto_actualizado','Se actualizó el producto Pegazulejo {\"codigo\":\"30164\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:10:28'),(138,1,'producto_actualizado','Se actualizó el producto Etiquetadora MP200 {\"codigo\":\"MP200\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:10:55'),(139,1,'producto_actualizado','Se actualizó el producto Etiquetadora {\"codigo\":\"MP75\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:11:54'),(140,1,'producto_actualizado','Se actualizó el producto Esmalte Negro {\"codigo\":\"por checar8\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:26:00'),(141,1,'producto_actualizado','Se actualizó el producto Esmalte Gris {\"codigo\":\"por checar7\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:26:34'),(142,1,'producto_creado','Se registró el producto Aceite Sintético Mineral 150 {\"codigo\":\"MIN150-L\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:38:01'),(143,1,'producto_actualizado','Se actualizó el producto Aceite Sintético Mineral 150 {\"codigo\":\"MIN150-L\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:39:57'),(144,1,'producto_creado','Se registró el producto Aceite Sintetico ISO 68  {\"codigo\":\" VAC-L\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:45:04'),(145,1,'producto_actualizado','Se actualizó el producto Aceite Sintetico ISO 68  {\"codigo\":\" VAC-L\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:45:40'),(146,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-24 10:50:01'),(147,1,'producto_creado','Se registró el producto Dobladores de tubo flexible resorte (4 piezas) {\"codigo\":\"desco7899\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 10:59:14'),(148,1,'producto_creado','Se registró el producto Fundente para Plata {\"codigo\":\"SOLD-0105\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 11:06:19'),(149,1,'login','Inicio de sesión exitoso','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 11:43:08'),(150,1,'producto_creado','Se registró el producto Shellac 56 G {\"codigo\":\" 5-A\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 11:54:34'),(151,1,'producto_actualizado','Se actualizó el producto Shellac 56 G {\"codigo\":\" 5-A\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 12:04:20'),(152,1,'producto_creado','Se registró el producto Shellac (56 G) {\"codigo\":\"6300064\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 12:08:16'),(153,1,'producto_creado','Se registró el producto Adaptador para refrigerante 410A {\"codigo\":\"descon123\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 12:16:15'),(154,1,'producto_creado','Se registró el producto Alcohol Solido (250ml) {\"codigo\":\"86612\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 12:19:03'),(155,1,'producto_creado','Se registró el producto Refrigerante 66a (400g) {\"codigo\":\"IGAS600G\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 12:24:46'),(156,1,'producto_creado','Se registró el producto Gas Refrigerante 134a (100g) {\"codigo\":\"NA123456\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 12:30:41'),(157,1,'producto_creado','Se registró el producto Cinta Momia PVC {\"codigo\":\"XREF256\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 12:34:39'),(158,1,'login','Inicio de sesión exitoso','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 13:12:15'),(159,1,'producto_creado','Se registró el producto Gas Refrigerante R-22 (250g) {\"codigo\":\"GASF-0076(1)\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 13:18:47'),(160,1,'producto_creado','Se registró el producto Gas Refrigerante R-22 (100g) {\"codigo\":\"GASF-0076(2)\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 13:21:03'),(161,1,'producto_creado','Se registró el producto Gas Refrigerante R-22 (200g) {\"codigo\":\"GASF-0076(3)\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 13:22:33'),(162,1,'producto_creado','Se registró el producto Boquilla sencilla con encendido automático {\"codigo\":\"HT802B\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 13:25:11'),(163,1,'producto_creado','Se registró el producto Aerosol protector antioxidante (370g) {\"codigo\":\"TG-ANTIOX\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 13:38:25'),(164,1,'producto_creado','Se registró el producto Gomas para Mini split {\"codigo\":\"DEsc1256\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 13:50:13'),(165,1,'producto_actualizado','Se actualizó el producto Gomas para Mini split (4 piezas) {\"codigo\":\"DEsc1256\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 13:51:14'),(166,1,'producto_creado','Se registró el producto Tabletas para charolas de condensadora {\"codigo\":\"Bio Tab 30 -1\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 13:58:26'),(167,1,'producto_creado','Se registró el producto Control Aire acondicionado {\"codigo\":\"Descon2378\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 14:00:57'),(168,1,'producto_creado','Se registró el producto Cepillos para serpentines {\"codigo\":\"FCR6\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 14:06:24'),(169,1,'login','Inicio de sesión exitoso','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 15:14:56'),(170,1,'producto_creado','Se registró el producto Juego De Avellanador Súper Completo C/corta Tubo {\"codigo\":\" Complete Countersunk Set with Stub Tube negro\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 15:18:18'),(171,1,'producto_creado','Se registró el producto Juego De Avellanador Y Expansor 1/8 A 3/4 {\"codigo\":\" REUN215\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 15:20:56'),(172,1,'producto_creado','Se registró el producto Inyector de grasa 10,00 PSI {\"codigo\":\"14861\"}','192.168.1.147','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 15:24:10'),(173,1,'login','Inicio de sesión exitoso','192.168.1.201','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 16:51:39'),(174,1,'logout','Cierre de sesión','192.168.1.201','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 16:53:24'),(175,NULL,'login_fallido','Intento de inicio de sesión fallido {\"username\":\"empleado\"}','192.168.1.201','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 16:53:31'),(176,12,'login','Inicio de sesión exitoso','192.168.1.201','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 16:53:38'),(177,12,'logout','Cierre de sesión','192.168.1.201','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-24 17:02:51'),(178,1,'login','Inicio de sesión exitoso','192.168.1.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-25 13:23:40');



/*!40000 ALTER TABLE `logs_actividad` ENABLE KEYS */;



UNLOCK TABLES;







--



-- Table structure for table `movimientos_inventario`



--







DROP TABLE IF EXISTS `movimientos_inventario`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `movimientos_inventario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) NOT NULL,
  `tipo` enum('Entrada','Salida','Préstamo','Devolución','Transferencia') NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL,
  `almacen_origen_id` int(11) DEFAULT NULL,
  `almacen_destino_id` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mov_producto_fecha` (`producto_id`,`fecha`),
  KEY `idx_mov_tipo` (`tipo`),
  KEY `idx_mov_usuario` (`usuario_id`),
  KEY `idx_mov_origen` (`almacen_origen_id`),
  KEY `idx_mov_destino` (`almacen_destino_id`),
  KEY `idx_mov_producto_fecha_tipo` (`producto_id`,`fecha`,`tipo`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `movimientos_inventario`



--







LOCK TABLES `movimientos_inventario` WRITE;



/*!40000 ALTER TABLE `movimientos_inventario` DISABLE KEYS */;



INSERT INTO `movimientos_inventario` VALUES (1,1,'Entrada',4,'2025-07-14 09:08:14',2,NULL,1,'Compra inicial de taladros'),(2,2,'Entrada',12,'2025-07-14 09:08:14',2,NULL,1,'Compra inicial de pinzas'),(3,3,'Entrada',25,'2025-07-14 09:08:14',2,NULL,1,'Compra inicial de cinta'),(4,4,'Entrada',500,'2025-07-14 09:08:14',2,NULL,1,'Compra inicial de tornillos'),(5,4,'Salida',100,'2025-07-24 16:50:54',1,1,NULL,'pruba1'),(6,4,'Salida',400,'2025-07-24 16:51:26',1,1,NULL,'pruba1'),(7,4,'Salida',50,'2025-07-24 16:51:41',1,1,NULL,''),(8,1,'Entrada',5,'2025-08-04 17:37:11',1,NULL,1,'llegaron nuevos'),(9,1,'Salida',1,'2025-08-04 17:37:37',1,1,NULL,'dañado'),(10,21,'Entrada',5,'2025-08-08 12:06:18',1,NULL,5,'se compraron nuevas plumas'),(11,20,'Entrada',5,'2025-10-16 15:52:11',1,NULL,1,''),(12,3,'Salida',5,'2025-10-16 15:52:49',1,1,NULL,'');



/*!40000 ALTER TABLE `movimientos_inventario` ENABLE KEYS */;



UNLOCK TABLES;







--



-- Table structure for table `ordenes_compra`



--







DROP TABLE IF EXISTS `ordenes_compra`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `ordenes_compra` (



  `id` int(11) NOT NULL AUTO_INCREMENT,



  `proveedor_id` int(11) NOT NULL,



  `usuario_id` int(11) DEFAULT NULL,



  `solicitud_id` int(11) DEFAULT NULL,



  `rfc` varchar(13) DEFAULT NULL,



  `numero_factura` varchar(30) DEFAULT NULL,



  `fecha` datetime NOT NULL DEFAULT current_timestamp(),



  `estado` enum('Pendiente','Enviada','Recibida','Cancelada') DEFAULT 'Pendiente',



  `total` decimal(12,2) DEFAULT NULL,



  `almacen_destino_id` int(11) DEFAULT NULL,



  PRIMARY KEY (`id`),



  KEY `proveedor_id` (`proveedor_id`),



  KEY `usuario_id` (`usuario_id`),



  KEY `almacen_destino_id` (`almacen_destino_id`),



  KEY `solicitud_id` (`solicitud_id`)



) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `ordenes_compra`



--







LOCK TABLES `ordenes_compra` WRITE;



/*!40000 ALTER TABLE `ordenes_compra` DISABLE KEYS */;



INSERT INTO `ordenes_compra` VALUES (1,1,NULL,3,NULL,NULL,'2025-07-14 09:08:14','Pendiente',1200.00,NULL);



/*!40000 ALTER TABLE `ordenes_compra` ENABLE KEYS */;



UNLOCK TABLES;







--



-- Table structure for table `prestamos`



--







DROP TABLE IF EXISTS `prestamos`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `prestamos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) NOT NULL,
  `empleado_id` int(11) NOT NULL,
  `autorizado_by_user_id` int(11) NOT NULL,
  `fecha_prestamo` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_estimada_devolucion` datetime DEFAULT NULL,
  `fecha_devolucion` datetime DEFAULT NULL,
  `estado` enum('Prestado','Devuelto') NOT NULL DEFAULT 'Prestado',
  `estado_devolucion` enum('Bueno','Dañado','Perdido') DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_prestamo_producto` (`producto_id`),
  KEY `idx_prestamo_empleado` (`empleado_id`),
  KEY `idx_prestamo_autorizado` (`autorizado_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `prestamos`



--







LOCK TABLES `prestamos` WRITE;



/*!40000 ALTER TABLE `prestamos` DISABLE KEYS */;



INSERT INTO `prestamos` VALUES (1,1,3,2,'2025-07-14 09:08:14','2025-07-21 09:08:14','2025-08-07 19:52:09','Devuelto','Dañado','llego roto del mango'),(2,2,4,2,'2025-07-14 09:08:14','2025-07-17 09:08:14',NULL,'Devuelto',NULL,'Ya se terminó la tarea'),(3,1,3,1,'2025-08-07 12:14:59',NULL,'2025-08-07 00:00:00','Devuelto','Dañado',''),(4,1,3,1,'2025-08-07 12:24:49',NULL,'2025-08-07 00:00:00','Devuelto','Dañado','registro devolver'),(5,1,3,1,'2025-08-07 12:24:49',NULL,'2025-08-07 16:03:10','Devuelto','Bueno',''),(6,2,3,1,'2025-08-07 12:24:49',NULL,'2025-08-07 16:03:16','Devuelto','Bueno','hola'),(7,2,3,1,'2025-08-07 13:24:14',NULL,'2025-08-07 13:24:55','Devuelto','Bueno','observacion de devolucion'),(8,2,3,1,'2025-08-07 13:24:14',NULL,'2025-08-07 14:01:32','Devuelto','Bueno',''),(9,2,3,1,'2025-08-07 13:24:14',NULL,'2025-08-07 14:01:52','Devuelto','Bueno',''),(10,2,3,1,'2025-08-07 13:24:14',NULL,'2025-08-07 14:14:22','Devuelto','Bueno',''),(11,1,3,1,'2025-08-07 16:04:48',NULL,'2025-10-21 15:50:26','Devuelto','Bueno',''),(12,1,3,1,'2025-08-07 16:04:57',NULL,'2025-08-07 16:05:03','Devuelto','Bueno',''),(13,2,3,1,'2025-08-07 16:04:57',NULL,'2025-08-07 16:05:08','Devuelto','Dañado','efsf'),(14,2,3,1,'2025-08-07 16:04:57',NULL,'2025-10-21 15:50:24','Devuelto','Bueno',''),(15,21,3,1,'2025-08-08 15:31:37',NULL,'2025-08-08 15:32:49','Devuelto','Dañado','orale master, que paso?'),(16,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:49:38','Devuelto','Bueno',''),(17,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:49:41','Devuelto','Bueno',''),(18,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:49:43','Devuelto','Bueno',''),(19,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:49:45','Devuelto','Bueno',''),(20,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-20 17:49:13','Devuelto','Dañado',''),(21,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:49:48','Devuelto','Bueno',''),(22,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:49:50','Devuelto','Bueno',''),(23,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:49:55','Devuelto','Bueno',''),(24,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:49:57','Devuelto','Bueno',''),(25,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:50:00','Devuelto','Bueno',''),(26,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:50:03','Devuelto','Bueno',''),(27,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:50:05','Devuelto','Bueno',''),(28,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:50:08','Devuelto','Bueno',''),(29,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:50:10','Devuelto','Bueno',''),(30,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:50:12','Devuelto','Bueno',''),(31,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:50:15','Devuelto','Bueno',''),(32,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:50:17','Devuelto','Bueno',''),(33,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:50:18','Devuelto','Bueno',''),(34,21,3,1,'2025-08-08 15:31:37',NULL,'2025-10-21 15:50:20','Devuelto','Bueno',''),(35,2,3,1,'2025-08-08 15:31:37',NULL,'2025-10-20 17:47:37','Devuelto','Bueno',''),(36,2,3,1,'2025-08-08 15:31:37',NULL,'2025-10-20 17:47:43','Devuelto','Bueno',''),(37,1,3,1,'2025-08-08 17:33:31',NULL,'2025-08-08 17:34:01','Devuelto','Bueno',''),(38,1,3,1,'2025-08-08 17:33:31',NULL,'2025-10-14 17:02:46','Devuelto','Bueno',''),(39,1,3,1,'2025-08-08 17:33:31',NULL,'2025-10-15 17:07:03','Devuelto','Bueno',''),(40,1,3,1,'2025-08-08 17:33:31',NULL,'2025-10-15 17:28:48','Devuelto','Bueno',''),(41,1,3,1,'2025-08-08 17:33:31',NULL,'2025-10-16 15:56:24','Devuelto','Bueno',''),(42,2,3,1,'2025-08-08 17:33:31',NULL,'2025-10-18 10:19:55','Devuelto','Bueno',''),(43,2,3,1,'2025-08-08 17:33:31',NULL,'2025-10-20 17:47:09','Devuelto','Bueno',''),(44,2,3,1,'2025-08-08 17:33:31',NULL,'2025-10-20 17:47:24','Devuelto','Bueno',''),(45,2,3,1,'2025-08-08 17:33:31',NULL,'2025-10-20 17:47:28','Devuelto','Bueno',''),(46,2,3,1,'2025-08-08 17:33:31',NULL,'2025-10-20 17:47:31','Devuelto','Bueno','');



/*!40000 ALTER TABLE `prestamos` ENABLE KEYS */;



UNLOCK TABLES;







--



-- Table structure for table `productos`



--







DROP TABLE IF EXISTS `productos`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `productos` (



  `id` int(11) NOT NULL AUTO_INCREMENT,



  `codigo` varchar(50) NOT NULL,



  `codigo_barras` varchar(64) DEFAULT NULL,



  `nombre` varchar(150) NOT NULL,



  `descripcion` text DEFAULT NULL,



  `proveedor_id` int(11) DEFAULT NULL,



  `categoria_id` int(11) DEFAULT NULL,



  `peso` decimal(10,2) DEFAULT NULL,



  `ancho` decimal(10,2) DEFAULT NULL,



  `alto` decimal(10,2) DEFAULT NULL,



  `profundidad` decimal(10,2) DEFAULT NULL,



  `unidad_medida_id` int(11) DEFAULT NULL,



  `clase_categoria` varchar(100) DEFAULT NULL,



  `marca` varchar(100) DEFAULT NULL,



  `color` varchar(50) DEFAULT NULL,



  `forma` varchar(50) DEFAULT NULL,



  `especificaciones_tecnicas` text DEFAULT NULL,



  `origen` varchar(100) DEFAULT NULL,



  `costo_compra` decimal(10,2) DEFAULT NULL,



  `precio_venta` decimal(10,2) DEFAULT NULL,



  `stock_minimo` decimal(10,2) DEFAULT 0.00,



  `stock_actual` decimal(10,2) DEFAULT 0.00,



  `almacen_id` int(11) DEFAULT NULL,



  `estado` enum('Nuevo','Usado','Dañado','En reparación') DEFAULT 'Nuevo',



  `activo_id` int(11) NOT NULL DEFAULT 1,



  `tipo` enum('Consumible','Herramienta','Equipo') NOT NULL,



  `imagen_url` varchar(255) DEFAULT NULL,



  `last_requested_by_user_id` int(11) DEFAULT NULL,



  `last_request_date` datetime DEFAULT NULL,



  `tags` varchar(255) DEFAULT NULL,



  `created_at` datetime NOT NULL DEFAULT current_timestamp(),



  PRIMARY KEY (`id`),



  UNIQUE KEY `codigo` (`codigo`),



  UNIQUE KEY `codigo_barras` (`codigo_barras`),



  KEY `proveedor_id` (`proveedor_id`),



  KEY `categoria_id` (`categoria_id`),



  KEY `unidad_medida_id` (`unidad_medida_id`),



  KEY `almacen_id` (`almacen_id`),



  KEY `last_requested_by_user_id` (`last_requested_by_user_id`),



  KEY `fk_producto_activo` (`activo_id`),



  KEY `idx_productos_nombre` (`nombre`),



  KEY `idx_productos_tipo` (`tipo`)



) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `productos`



--







LOCK TABLES `productos` WRITE;



/*!40000 ALTER TABLE `productos` DISABLE KEYS */;



INSERT INTO `productos` (`id`, `codigo`, `nombre`, `descripcion`, `proveedor_id`, `categoria_id`, `peso`, `ancho`, `alto`, `profundidad`, `unidad_medida_id`, `clase_categoria`, `marca`, `color`, `forma`, `especificaciones_tecnicas`, `origen`, `costo_compra`, `precio_venta`, `stock_minimo`, `stock_actual`, `almacen_id`, `estado`, `activo_id`, `tipo`, `imagen_url`, `last_requested_by_user_id`, `last_request_date`, `tags`, `created_at`) VALUES



/*!40000 ALTER TABLE `productos` ENABLE KEYS */;



UNLOCK TABLES;







--




--

-- Table structure for table `stock_almacen`

--


DROP TABLE IF EXISTS `stock_almacen`;


/*!40101 SET @saved_cs_client     = @@character_set_client */;


/*!50503 SET character_set_client = utf8mb4 */;


CREATE TABLE `stock_almacen` (
  `producto_id` int(11) NOT NULL,
  `almacen_id` int(11) NOT NULL,
  `stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`producto_id`,`almacen_id`),
  KEY `idx_stock_almacen_prod` (`producto_id`),
  KEY `idx_stock_almacen_alm` (`almacen_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


/*!40101 SET character_set_client = @saved_cs_client */;



--

-- Dumping data for table `stock_almacen`

--


LOCK TABLES `stock_almacen` WRITE;


/*!40000 ALTER TABLE `stock_almacen` DISABLE KEYS */;


/*!40000 ALTER TABLE `stock_almacen` ENABLE KEYS */;


UNLOCK TABLES;
-- Table structure for table `proveedores`



--







DROP TABLE IF EXISTS `proveedores`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `proveedores` (



  `id` int(11) NOT NULL AUTO_INCREMENT,



  `nombre` varchar(150) NOT NULL,



  `contacto` varchar(100) DEFAULT NULL,



  `telefono` varchar(20) DEFAULT NULL,



  `email` varchar(100) DEFAULT NULL,



  `direccion` varchar(200) DEFAULT NULL,



  `condiciones_pago` text DEFAULT NULL,



  PRIMARY KEY (`id`)



) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `proveedores`



--







LOCK TABLES `proveedores` WRITE;



/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;



INSERT INTO `proveedores` VALUES (1,'The Home Depot Villa Verde','Desconocido','2222137600','','Carretera a Tehuacán #5643, Av. Justo Sierra esq. Av. Defensores de la República. Puebla, Puebla C.P. 72160','Contado y tarjeta'),(2,'Forte Herramientas','María Guadalupe Ramirez Castellanos','2222113177','','Av 25 Pte 1907, Los Volcanes 72410 Puebla, Puebla México','Contado y tarjeta'),(6,'Refaccionaria Autozone','Desconocido','2222366131','','Av. 18 de Noviembre #22, 72300 Puebla, Puebla','Contado y tarjeta'),(7,'Pintumex ','Desconocido','2222533106','pintumexchapultepec@skygroup.mx','Av. Independencia 289, Chapultepec, 72320 Heroica Puebla de Zaragoza, Pue.','contado, tarjeta y transferencia'),(8,'Sin especificar','xxxxxx','123456','','123456','contado'),(9,'Distribuidora Tamex','Bryan','2222463757','','4 Poniente 1902, Calle 19 Nte. Esq-Local A, Col. Centro, 72000 Heroica Puebla de Zaragoza, Pue.','transferencia y contado'),(10,'Grupo Medeti de Puebla','JUAN CARLOS SALAZAR CANDIA','2229409125','amalucan@grupomereti.com','Av. Tecamachalco. 5826-B Fracc, Plazas Amalucan, 72310 Heroica Puebla de Zaragoza, Pue.','Transferencia y contado'),(11,'Eléctrico','Elizabeth Hijuit Juarez','2222426053','cxpuebla@electrico.com.mx','Av. 4 Pte. 1505, Centro, 72000 Heroica Puebla de Zaragoza, Pue.','Transferencia y contado'),(12,'Tornillos y clavos Alhondiga','Desconocido','2222467492','tornillosyclavospbravo@hotmail.com','8 poniente 718 Local 9 centro, Puebla','Contado'),(13,'Desarrollo de Infraestructura Eléctrica Civil, S.A de C.V','Desconocido','2222856525','diecsa10@prodigy.net.mx','Carr. Fed. Puebla-Atlixco No.3549 Col. Concepcion la Cruz C.P 72810 San Andrés Cholula, Pue.','Transferencia y contado'),(14,'Industria Electrica Nacional S.A de C.V','Luis Elias','2211795958','','AV. 3 PonienteNo.3114 Col. La Paz Puebla, C.P. 72160','Transferencia y contado'),(15,'Coel de Puebla','Silvia Martínez','2222494999','smartinezr@coelpuebla.com.mx','C. 25 Nte. 210, Amor, 72140 Heroica Puebla de Zaragoza, Pue.','Transferencia y contado'),(16,'Comex','Desconocido','2222530047','','Av. Independencia 217, Chapultepec, 72320 Heroica Puebla de Zaragoza, Pue.','Transferencia y contado'),(17,'Lumi Satelite','IGNACIO VARELA','2222744510','','Carretera Federal a Tehuacán Sur 89, Los Ãlamos, 72320 Heroica Puebla de Zaragoza, Pue.','Transferencia y contado'),(18,'AMESA PUEBLA','DENISSE PEREZ','2222646001','','Calle 15 Nte 3, Centro histórico de Puebla, 72000 Heroica Puebla de Zaragoza, Pue.','Transferencia y contado'),(19,'ALIANZA ESPECIALISTAS EN MEDIA TENSIÃ“N','CARLOS TOXQUI','2228137984','','Río Lerma, Sanctorum, 72730 Sanctorum, Pue.','Transferencia y contado'),(20,'FRILAV PUEBLA','GILDA QUIROZ GARCIA','2222461444','frilavpuebla@gmail.com','Av. 4 Pte. 908-local A B, centro, 72000 Heroica Puebla de Zaragoza, Pue.','Transferencia y contado'),(21,'DECSA DISPOSITIVOS ELECTRÃ“NICOS Y DE CONTROL','BENJAMIN AGUILAR VITE','5591571600','benjamin.aguilar@decsa-mexico.com','Cedro No. 512 Col. Atlampa C.P. 06450','transferencia y contado'),(22,'MUSICAL FAMA','Desconocido','2221816012','online@musicalfama.com','6 Poniente 905 Colonia Centro, Puebla, Puebla, C.P. 72000','Transferencia y contado'),(23,'AUDIOVISUAL RENTA INNOVACION EN RENTAS','Desconocido','2222270620','ventas@inovacionenrentaspc.com','C. 12 Sur 11506-interior 14, Los Héroes de Puebla, 72590 Heroica Puebla de Zaragoza, Pue','transferencia y contado'),(24,'CONTINENTAL ELECTRIC','MONICA NAVARRETE MERLIN','556223 4115','monica@continentalelectric.com.mx',' CALLE ESCAPE No. 10 Y 12-B FRACC. INDUSTRIAL ALCE BLANCO, NAUCALPAN EDO. DE MÃ‰XICO C.P. 53370','Transferencia'),(25,'MIRAGE PUEBLA','Desconocido',' 222-305-7718','aireref@hotmail.com','','Transferencia y contado'),(26,'MacTools','Desconocido','2222968000','','25 Poniente 1917-E, Los Volcanes C.P.72410','transferencia y contado'),(27,'Pumps Puebla','Torre Artea','2223362269','ventas@pumpspuebla.com','Calle 117-A Pte 1712, Paseos del rio, CP: 72484, Puebla, puebla','transferencia y contado'),(28,'Tudogar Independencia','Desconocido','2221222204','','Av. Chapultepec 109, Los Ãlamos, 72320 Heroica Puebla de Zaragoza, Pue.','Transferencia y contado'),(31,'SYSCOM','Ivonne García Segura','614-4152525 Ext:2725','ivonne.garcia@syscom.mx','Calle 28 Nte 215, Col, Resurgimiento Cd. Nte, 72373 Heroica Puebla de Zaragoza, Pue.','Transferencia y contado'),(32,'Materiales Paredes','Desconocido','2222361336','','Av. Educadores 5216, Unidad Satélite Magisterial, 72320 Heroica Puebla de Zaragoza, Pue.','Transferencia y contado'),(33,'FERRETERÃAS EL CHARRITO SATÃ‰LITE','Desconocido','2226026521','','Descartes 5010, Unidad Satélite Magisterial, 72320 Heroica Puebla de Zaragoza, Pue.','Transferencia y contado'),(34,'Rasa ','Alejandro Coria','5558778257','ventas@rasalubricantes.com','Miguel Hidalgo No. 32, Plan de Guadalupe Cuautitlán Izcalli, Estado de México C.P. 54760','Transferencia');



/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;



UNLOCK TABLES;







--



-- Table structure for table `solicitudes`



--







DROP TABLE IF EXISTS `solicitudes`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `solicitudes` (



  `id` int(11) NOT NULL AUTO_INCREMENT,



  `usuario_id` int(11) NOT NULL,



  `tipo_solicitud` enum('Inventario existente','Peticion de compra') NOT NULL,



  `producto_id` int(11) DEFAULT NULL,



  `cantidad` decimal(10,2) NOT NULL,



  `tipo_producto` enum('Consumible','Herramienta','Equipo') NOT NULL,



  `especificaciones_pedido` text DEFAULT NULL,



  `comentario_destino` text DEFAULT NULL,



  `almacen_origen_id` int(11) NOT NULL,



  `estado` enum('Pendiente','Aprobada','Entregada','Cancelada','Rechazada') DEFAULT 'Pendiente',



  `approved_by_user_id` int(11) DEFAULT NULL,



  `entregado_by_user_id` int(11) DEFAULT NULL,



  `created_at` datetime NOT NULL DEFAULT current_timestamp(),



  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),



  PRIMARY KEY (`id`),



  KEY `usuario_id` (`usuario_id`),



  KEY `producto_id` (`producto_id`),



  KEY `almacen_origen_id` (`almacen_origen_id`),



  KEY `approved_by_user_id` (`approved_by_user_id`),



  KEY `entregado_by_user_id` (`entregado_by_user_id`)



) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `solicitudes`



--







LOCK TABLES `solicitudes` WRITE;



/*!40000 ALTER TABLE `solicitudes` DISABLE KEYS */;



INSERT INTO `solicitudes` VALUES (1,3,'Inventario existente',1,1,'Herramienta',NULL,'Para proyecto Luz24',1,'Pendiente',NULL,NULL,'2025-07-14 09:08:14','2025-07-14 09:08:14'),(2,4,'Inventario existente',3,3,'Consumible',NULL,'Mantenimiento semanal',1,'Pendiente',NULL,NULL,'2025-07-14 09:08:14','2025-07-14 09:08:14'),(3,3,'Peticion de compra',NULL,2,'Herramienta','Buscapolos digital, rango 12-1000V, marca Fluke','No hay buscapolos, se requiere urgente para instalaciones.',1,'Pendiente',NULL,NULL,'2025-07-14 09:08:14','2025-07-14 09:08:14');



/*!40000 ALTER TABLE `solicitudes` ENABLE KEYS */;



UNLOCK TABLES;







--



-- Table structure for table `solicitudes_material`



--







DROP TABLE IF EXISTS `solicitudes_material`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `solicitudes_material` (



  `id` int(11) NOT NULL AUTO_INCREMENT,



  `usuario_id` int(11) NOT NULL,



  `tipo` enum('Consumible','Herramienta','Equipo') NOT NULL,



  `estado` enum('pendiente','aprobada','entregada','cancelada','rechazada') DEFAULT 'pendiente',



  `comentario` text DEFAULT NULL,



  `fecha_solicitud` datetime DEFAULT current_timestamp(),



  `fecha_respuesta` datetime DEFAULT NULL,



  `usuario_responde_id` int(11) DEFAULT NULL,



  `observaciones_respuesta` text DEFAULT NULL,



  `usuario_aprueba_id` int(11) DEFAULT NULL,



  `fecha_aprobacion` datetime DEFAULT NULL,



  `usuario_entrega_id` int(11) DEFAULT NULL,



  `fecha_entrega` datetime DEFAULT NULL,



  `observaciones_entrega` text DEFAULT NULL,



  `extras` text DEFAULT NULL,



  `observacion` text DEFAULT NULL,



  `tipo_solicitud` enum('Servicio','General') NOT NULL DEFAULT 'Servicio',



  PRIMARY KEY (`id`),



  KEY `usuario_id` (`usuario_id`),



  KEY `usuario_responde_id` (`usuario_responde_id`)



) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `solicitudes_material`



--







LOCK TABLES `solicitudes_material` WRITE;



/*!40000 ALTER TABLE `solicitudes_material` DISABLE KEYS */;



INSERT INTO `solicitudes_material` VALUES (1,3,'Herramienta','rechazada','que te importa','2025-07-16 17:33:38','2025-07-29 09:40:15',NULL,'',1,NULL,NULL,NULL,NULL,NULL,NULL,'Servicio'),(2,3,'Consumible','rechazada','','2025-07-17 09:41:50','2025-07-29 09:40:22',NULL,'',1,NULL,NULL,NULL,NULL,NULL,NULL,'Servicio'),(3,3,'Consumible','entregada','Proyecto: Proyecto Takab','2025-07-24 13:07:44','2025-07-29 09:40:41',NULL,'',1,NULL,1,NULL,NULL,NULL,NULL,'Servicio'),(4,3,'','entregada','1','2025-07-24 13:32:17','2025-07-29 09:40:46',NULL,'',1,NULL,1,NULL,NULL,NULL,NULL,'Servicio'),(5,3,'','entregada','prueba2','2025-07-24 13:43:24','2025-08-07 12:14:59',NULL,'',1,NULL,1,NULL,NULL,NULL,NULL,'Servicio'),(6,3,'','entregada','prueba3','2025-07-24 13:46:33','2025-08-07 16:04:48',NULL,'',1,NULL,1,NULL,NULL,'[{\"descripcion\":\"podadora\",\"cantidad\":\"1\"}]',NULL,'Servicio'),(7,3,'','entregada','prueba4','2025-07-24 14:02:10','2025-07-24 16:35:35',NULL,'',1,NULL,1,NULL,NULL,'[{\"descripcion\":\"taza para cage\",\"cantidad\":\"5\",\"observacion\":\"pa desayunar\"}]','tambien quiero un pancito','Servicio'),(8,3,'','rechazada','hay que proteger la obra de los maleantes','2025-07-24 14:19:19','2025-07-24 16:34:51',NULL,'mejor un lobo',1,NULL,NULL,NULL,NULL,'[{\"descripcion\":\"perro\",\"cantidad\":\"2\",\"observacion\":\"para cuidar la obra\"}]','','Servicio'),(9,3,'','entregada','obligatorio','2025-07-24 16:41:22','2025-07-24 16:45:13',NULL,'ahi estan tus cosas',1,NULL,1,NULL,NULL,'[{\"descripcion\":\"taquetes rojo\",\"cantidad\":\"300\",\"observacion\":\"ni modo que con que los pego\"}]','opcional','Servicio'),(10,3,'','entregada','necesito un gato','2025-07-24 16:43:12','2025-08-07 14:23:47',NULL,'',1,NULL,1,NULL,NULL,'[{\"descripcion\":\"gato\",\"cantidad\":\"2\",\"observacion\":\"con credito\"}]','por favor','General'),(11,3,'','entregada','obligatorio2','2025-07-24 16:49:40','2025-07-24 16:50:24',NULL,'',1,NULL,1,NULL,NULL,NULL,'opcional1','Servicio'),(12,3,'','entregada','obligatorio3','2025-07-24 17:14:53','2025-07-24 17:22:56',NULL,'',1,NULL,1,NULL,NULL,NULL,'opcional3','Servicio'),(13,3,'Consumible','entregada','Cu2','2025-07-25 17:51:56','2025-07-25 17:55:45',NULL,'',1,NULL,1,NULL,NULL,'[{\"descripcion\":\"martillo\",\"cantidad\":\"2\",\"observacion\":\"matillo cabeza cirtular de bola\"}]','opcional3','Servicio'),(14,3,'','entregada','para mi','2025-07-25 17:53:33','2025-08-07 12:11:08',NULL,'',1,NULL,1,NULL,NULL,'[{\"descripcion\":\"Talado\",\"cantidad\":\"1\",\"observacion\":\"se me rompio\"}]','','General'),(15,9,'','rechazada','Biologia','2025-08-04 17:26:56','2025-08-04 17:29:01',NULL,'lo que pides no hay',1,NULL,NULL,NULL,NULL,'[{\"descripcion\":\"martillo\",\"cantidad\":\"10\",\"observacion\":\"se ocupa para el servicio\"}]','material para el salon fc01','Servicio'),(16,9,'','entregada','Biologia','2025-08-04 17:30:05','2025-08-04 17:31:30',NULL,'',1,NULL,1,NULL,NULL,'[{\"descripcion\":\"telefono\",\"cantidad\":\"2\",\"observacion\":\"con credito\"}]','material para el salon fc03','Servicio'),(17,3,'','entregada','Biologia','2025-08-07 12:02:24','2025-08-07 13:24:14',NULL,'observacion entrega biologia',1,NULL,1,NULL,NULL,NULL,'material para el salon fc03','Servicio'),(18,3,'Herramienta','entregada','comentario obligatorio','2025-08-07 12:23:52','2025-08-07 12:24:49',NULL,'entregado',1,NULL,1,NULL,NULL,NULL,'comentario opcional','Servicio'),(19,3,'Herramienta','entregada','1','2025-08-07 16:04:25','2025-08-07 16:04:57',NULL,'',1,NULL,1,NULL,NULL,NULL,'2','Servicio'),(20,3,'Herramienta','entregada','proyecto buap','2025-08-08 15:30:22','2025-08-08 15:31:37',NULL,'entregado',1,NULL,1,NULL,NULL,NULL,'hola','Servicio'),(21,3,'Herramienta','entregada','proyecto buap','2025-08-08 17:31:50','2025-08-08 17:33:31',NULL,'entregado pachas',1,NULL,1,NULL,NULL,NULL,'opcional','Servicio'),(22,12,'Consumible','entregada','mi casa zum zum zum','2025-10-23 09:29:37','2025-10-23 09:31:45',NULL,'',1,NULL,1,NULL,NULL,NULL,'se pide amablemente que me empresten algo de azeite','Servicio');



/*!40000 ALTER TABLE `solicitudes_material` ENABLE KEYS */;



UNLOCK TABLES;







--



-- Table structure for table `unidades_medida`



--







DROP TABLE IF EXISTS `unidades_medida`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `unidades_medida` (



  `id` int(11) NOT NULL AUTO_INCREMENT,



  `nombre` varchar(100) NOT NULL,



  `abreviacion` varchar(10) DEFAULT NULL,



  PRIMARY KEY (`id`)



) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `unidades_medida`



--







LOCK TABLES `unidades_medida` WRITE;



/*!40000 ALTER TABLE `unidades_medida` DISABLE KEYS */;



INSERT INTO `unidades_medida` VALUES (1,'Pieza','pz'),(2,'Metro','m'),(3,'Caja','cj'),(4,'Litro','l'),(5,'Pares','Par'),(6,'kilogramo','Kg'),(7,'Cubeta','cub'),(8,'Juego','Jgo'),(9,'mililitros','ml'),(10,'Gramos','g');



/*!40000 ALTER TABLE `unidades_medida` ENABLE KEYS */;



UNLOCK TABLES;







--



-- Table structure for table `usuarios`



--







DROP TABLE IF EXISTS `usuarios`;



/*!40101 SET @saved_cs_client     = @@character_set_client */;



/*!50503 SET character_set_client = utf8mb4 */;



CREATE TABLE `usuarios` (



  `id` int(11) NOT NULL AUTO_INCREMENT,



  `username` varchar(100) NOT NULL,



  `password` varchar(255) NOT NULL,



  `nombre_completo` varchar(150) NOT NULL,



  `role` enum('Administrador','Almacen','Empleado','Compras') NOT NULL,



  `activo` tinyint(1) NOT NULL DEFAULT 1,



  `created_at` datetime NOT NULL DEFAULT current_timestamp(),



  PRIMARY KEY (`id`),



  UNIQUE KEY `username` (`username`)



) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



/*!40101 SET character_set_client = @saved_cs_client */;







--



-- Dumping data for table `usuarios`



--







LOCK TABLES `usuarios` WRITE;



/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;



INSERT INTO `usuarios` VALUES (1,'admin','$2y$10$XYLZmze1GckxFLTvvjTAjul1a8.aeB/oHFm1c8v.eaYUSyB2HP3qm','Administrador General','Administrador',1,'2025-07-14 09:08:14'),(2,'almacen','$2y$10$XYLZmze1GckxFLTvvjTAjul1a8.aeB/oHFm1c8v.eaYUSyB2HP3qm','Encargado de Almacén','Almacen',1,'2025-07-14 09:08:14'),(3,'luis','$2y$10$XYLZmze1GckxFLTvvjTAjul1a8.aeB/oHFm1c8v.eaYUSyB2HP3qm','Luis Pérez','Empleado',1,'2025-07-14 09:08:14'),(4,'mau','$2y$10$Bhrduk.2p/fS5fnMe/GGrOb/HIJ46jMnCOwh9SQSj0sLqnnNy7qhC','Mauricio Bautista','Administrador',1,'2025-07-14 09:08:14'),(6,'Palomino','$2y$10$Wp45d.821SHAIbF8xwkhzuF0.JAxw95e8ul8BFqhKYY0vlVUQg98.','Paulino','Empleado',1,'2025-07-17 16:04:15'),(12,'empleado','$2y$10$fgLDhLMb3fJGM4j06NFJDOpv7TyD1hk8OgUlyJOcHQy0xJQRL2W8e','empleado','Empleado',1,'2025-10-16 17:21:58');



/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;



UNLOCK TABLES;



/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;








--
-- Foreign key constraints and indexes
--

ALTER TABLE `detalle_ordenes`
  ADD CONSTRAINT `fk_detalle_ordenes_orden` FOREIGN KEY (`orden_id`) REFERENCES `ordenes_compra` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_ordenes_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `detalle_solicitud`
  ADD CONSTRAINT `fk_detalle_solicitud_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_solicitud_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `fk_movimientos_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimientos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimientos_origen` FOREIGN KEY (`almacen_origen_id`) REFERENCES `almacenes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimientos_destino` FOREIGN KEY (`almacen_destino_id`) REFERENCES `almacenes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `prestamos`
  ADD CONSTRAINT `fk_prestamos_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prestamos_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prestamos_autorizado` FOREIGN KEY (`autorizado_by_user_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `stock_almacen`
  ADD CONSTRAINT `fk_stock_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stock_almacen` FOREIGN KEY (`almacen_id`) REFERENCES `almacenes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;



/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;



/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;



/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;



/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;



/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;







-- Dump completed on 2025-10-25 13:34:03



