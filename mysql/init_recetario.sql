CREATE DATABASE IF NOT EXISTS `recetario` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `recetario`;
SET NAMES utf8mb4;

CREATE TABLE `locales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `slug` varchar(60) NOT NULL,
  `mostrar_precios` tinyint(1) NOT NULL DEFAULT '0',
  `creado` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `locales` (`id`, `nombre`, `slug`) VALUES (1, 'Local de ejemplo', 'local-de-ejemplo');

CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `local_id` int NOT NULL,
  `usuario` varchar(30) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `rol` enum('dueno','chef','ayudante') NOT NULL,
  `clave_hash` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `intentos` int NOT NULL DEFAULT '0',
  `bloqueado_hasta` datetime DEFAULT NULL,
  `creado` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_local` (`local_id`, `usuario`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`local_id`) REFERENCES `locales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `carpetas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `local_id` int NOT NULL DEFAULT '1',
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#4a9eff',
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `local_id` (`local_id`),
  CONSTRAINT `carpetas_ibfk_1` FOREIGN KEY (`local_id`) REFERENCES `locales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `recetas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `carpeta_id` int DEFAULT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text,
  `porciones_base` int DEFAULT '4',
  `altitud` tinyint(1) DEFAULT '0',
  `notas` text,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `local_id` int NOT NULL DEFAULT '1',
  `creado_por` int DEFAULT NULL,
  `precio_venta` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `carpeta_id` (`carpeta_id`),
  KEY `local_id` (`local_id`),
  KEY `creado_por` (`creado_por`),
  CONSTRAINT `recetas_ibfk_1` FOREIGN KEY (`carpeta_id`) REFERENCES `carpetas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recetas_ibfk_2` FOREIGN KEY (`local_id`) REFERENCES `locales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recetas_ibfk_3` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ingredientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `receta_id` int NOT NULL,
  `orden` int DEFAULT '0',
  `nombre` varchar(200) NOT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receta_id` (`receta_id`),
  CONSTRAINT `ingredientes_ibfk_1` FOREIGN KEY (`receta_id`) REFERENCES `recetas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `pasos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `receta_id` int NOT NULL,
  `orden` int DEFAULT '0',
  `titulo` varchar(200) DEFAULT NULL,
  `descripcion` text NOT NULL,
  `timer` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receta_id` (`receta_id`),
  CONSTRAINT `pasos_ibfk_1` FOREIGN KEY (`receta_id`) REFERENCES `recetas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `errores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `receta_id` int NOT NULL,
  `descripcion` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `receta_id` (`receta_id`),
  CONSTRAINT `errores_ibfk_1` FOREIGN KEY (`receta_id`) REFERENCES `recetas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `precios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `local_id` int NOT NULL DEFAULT '1',
  `nombre` varchar(200) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `unidad` varchar(40) NOT NULL,
  `categoria` varchar(50) NOT NULL DEFAULT 'Otros',
  `notas` text,
  `actualizado` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `local_id` (`local_id`),
  CONSTRAINT `precios_ibfk_1` FOREIGN KEY (`local_id`) REFERENCES `locales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `menu_dia` (
  `receta_id` int NOT NULL,
  `fecha` date NOT NULL,
  PRIMARY KEY (`receta_id`, `fecha`),
  CONSTRAINT `menu_dia_ibfk_1` FOREIGN KEY (`receta_id`) REFERENCES `recetas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
