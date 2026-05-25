-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 08-04-2026 a las 18:36:05
-- Versión del servidor: 11.8.6-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u780114275_cat_ink`
--
CREATE DATABASE IF NOT EXISTS `cat_ink` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cat_ink`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE IF NOT EXISTS `categorias` (
  `id_c` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id_c`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `correos_publicitarios`
--

DROP TABLE IF EXISTS `correos_publicitarios`;
CREATE TABLE IF NOT EXISTS `correos_publicitarios` (
  `id_correo` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) DEFAULT NULL,
  `contenido` longtext DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `url_c` varchar(255) NOT NULL,
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  `envio` datetime DEFAULT NULL,
  PRIMARY KEY (`id_correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `noticias`
--

DROP TABLE IF EXISTS `noticias`;
CREATE TABLE IF NOT EXISTS `noticias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `autor` int(11) NOT NULL,
  `crop1` varchar(255) DEFAULT NULL,
  `crop2` varchar(255) DEFAULT NULL,
  `crop3` varchar(255) DEFAULT NULL,
  `contenido` longtext NOT NULL,
  `fecha_publicacion` datetime NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `vistas` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `autor` (`autor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `noticias_stats`
--

DROP TABLE IF EXISTS `noticias_stats`;
CREATE TABLE IF NOT EXISTS `noticias_stats` (
  `id_s` int(11) NOT NULL AUTO_INCREMENT,
  `noticia_id` int(11) NOT NULL,
  `tiempo_segundos` int(11) DEFAULT 0,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_s`),
  KEY `idx_stats_noticia` (`noticia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `noticia_categoria`
--

DROP TABLE IF EXISTS `noticia_categoria`;
CREATE TABLE IF NOT EXISTS `noticia_categoria` (
  `noticia_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  PRIMARY KEY (`noticia_id`,`categoria_id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `idx_noticia_categoria` (`noticia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `noticia_likes`
--

DROP TABLE IF EXISTS `noticia_likes`;
CREATE TABLE IF NOT EXISTS `noticia_likes` (
  `id_l` int(11) NOT NULL AUTO_INCREMENT,
  `noticia_id` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `pais` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_l`),
  UNIQUE KEY `unique_like` (`noticia_id`,`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paginas`
--

DROP TABLE IF EXISTS `paginas`;
CREATE TABLE IF NOT EXISTS `paginas` (
  `id_pag` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_pag` varchar(100) NOT NULL,
  `contenido_pag` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_pag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `programacion_correos`
--

DROP TABLE IF EXISTS `programacion_correos`;
CREATE TABLE IF NOT EXISTS `programacion_correos` (
  `id_programacion` int(11) NOT NULL AUTO_INCREMENT,
  `hora` time NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultima_ejecucion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_programacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publicidad`
--

DROP TABLE IF EXISTS `publicidad`;
CREATE TABLE IF NOT EXISTS `publicidad` (
  `id_pub` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `imagen` varchar(255) NOT NULL,
  `tipo` tinyint(1) DEFAULT 1,
  `url` varchar(255) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pub`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publicidad_categoria`
--

DROP TABLE IF EXISTS `publicidad_categoria`;
CREATE TABLE IF NOT EXISTS `publicidad_categoria` (
  `publicidad_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  PRIMARY KEY (`publicidad_id`,`categoria_id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `idx_publicidad_categoria` (`publicidad_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publicidad_clicks`
--

DROP TABLE IF EXISTS `publicidad_clicks`;
CREATE TABLE IF NOT EXISTS `publicidad_clicks` (
  `id_click` int(11) NOT NULL AUTO_INCREMENT,
  `publicidad_id` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `pais` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_click`),
  KEY `idx_clicks_pub` (`publicidad_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publicidad_views`
--

DROP TABLE IF EXISTS `publicidad_views`;
CREATE TABLE IF NOT EXISTS `publicidad_views` (
  `id_view` int(11) NOT NULL AUTO_INCREMENT,
  `publicidad_id` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `tiempo_segundos` int(11) DEFAULT 0,
  `pais` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_view`),
  KEY `idx_views_pub` (`publicidad_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secciones`
--

DROP TABLE IF EXISTS `secciones`;
CREATE TABLE IF NOT EXISTS `secciones` (
  `id_s` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(55) NOT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  `creado` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_s`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suscripciones`
--

DROP TABLE IF EXISTS `suscripciones`;
CREATE TABLE IF NOT EXISTS `suscripciones` (
  `id_sub` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(255) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `sexo` varchar(50) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `pais` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_sub`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_u` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) DEFAULT NULL,
  `usuario` varchar(255) DEFAULT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `pass` varchar(255) DEFAULT NULL,
  `registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `perm_categorias` tinyint(4) DEFAULT 0,
  `perm_noticias` tinyint(4) DEFAULT 0,
  `perm_publicidad` tinyint(4) DEFAULT 0,
  `perm_suscripciones` tinyint(4) DEFAULT 0,
  `perm_usuarios` tinyint(4) DEFAULT 0,
  `perm_correos` tinyint(4) DEFAULT 0,
  `perm_videos` tinyint(4) NOT NULL,
  PRIMARY KEY (`id_u`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `videos`
--

DROP TABLE IF EXISTS `videos`;
CREATE TABLE IF NOT EXISTS `videos` (
  `id_v` int(11) NOT NULL AUTO_INCREMENT,
  `url_v` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_v`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `noticias`
--
ALTER TABLE `noticias`
  ADD CONSTRAINT `noticias_ibfk_1` FOREIGN KEY (`autor`) REFERENCES `usuarios` (`id_u`);

--
-- Filtros para la tabla `noticias_stats`
--
ALTER TABLE `noticias_stats`
  ADD CONSTRAINT `noticias_stats_ibfk_1` FOREIGN KEY (`noticia_id`) REFERENCES `noticias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `noticia_categoria`
--
ALTER TABLE `noticia_categoria`
  ADD CONSTRAINT `noticia_categoria_ibfk_1` FOREIGN KEY (`noticia_id`) REFERENCES `noticias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `noticia_categoria_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id_c`) ON DELETE CASCADE;

--
-- Filtros para la tabla `noticia_likes`
--
ALTER TABLE `noticia_likes`
  ADD CONSTRAINT `noticia_likes_ibfk_1` FOREIGN KEY (`noticia_id`) REFERENCES `noticias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `publicidad_categoria`
--
ALTER TABLE `publicidad_categoria`
  ADD CONSTRAINT `publicidad_categoria_ibfk_1` FOREIGN KEY (`publicidad_id`) REFERENCES `publicidad` (`id_pub`) ON DELETE CASCADE,
  ADD CONSTRAINT `publicidad_categoria_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id_c`) ON DELETE CASCADE;

--
-- Filtros para la tabla `publicidad_clicks`
--
ALTER TABLE `publicidad_clicks`
  ADD CONSTRAINT `publicidad_clicks_ibfk_1` FOREIGN KEY (`publicidad_id`) REFERENCES `publicidad` (`id_pub`) ON DELETE CASCADE;

--
-- Filtros para la tabla `publicidad_views`
--
ALTER TABLE `publicidad_views`
  ADD CONSTRAINT `publicidad_views_ibfk_1` FOREIGN KEY (`publicidad_id`) REFERENCES `publicidad` (`id_pub`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
