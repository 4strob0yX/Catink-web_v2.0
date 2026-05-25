-- =============================================
-- SISTEMA DE COMENTARIOS + MODERACIÓN DE LENGUAJE
-- 6 tablas nuevas para el módulo de comentarios
-- =============================================

USE `cat_ink`;

-- --------------------------------------------------------
-- 1. COMENTARIOS
-- Comentarios en noticias (soporta hilos con parent_id)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `comentarios`;
CREATE TABLE IF NOT EXISTS `comentarios` (
  `id_com` int(11) NOT NULL AUTO_INCREMENT,
  `noticia_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `contenido` text NOT NULL,
  `contenido_original` text DEFAULT NULL,
  `estado` enum('pendiente','aprobado','rechazado','censurado') NOT NULL DEFAULT 'pendiente',
  `ip` varchar(45) DEFAULT NULL,
  `pais` varchar(255) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `likes` int(11) DEFAULT 0,
  `dislikes` int(11) DEFAULT 0,
  `editado` tinyint(1) DEFAULT 0,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_com`),
  KEY `idx_com_noticia` (`noticia_id`),
  KEY `idx_com_parent` (`parent_id`),
  KEY `idx_com_estado` (`estado`),
  KEY `idx_com_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 2. COMENTARIOS_REACCIONES
-- Likes/dislikes en comentarios (1 por IP por comentario)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `comentarios_reacciones`;
CREATE TABLE IF NOT EXISTS `comentarios_reacciones` (
  `id_reaccion` int(11) NOT NULL AUTO_INCREMENT,
  `comentario_id` int(11) NOT NULL,
  `tipo` enum('like','dislike') NOT NULL,
  `ip` varchar(45) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_reaccion`),
  UNIQUE KEY `unique_reaccion` (`comentario_id`, `ip`),
  KEY `idx_reaccion_comentario` (`comentario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 3. COMENTARIOS_REPORTES
-- Reportes de usuarios sobre comentarios inapropiados
-- --------------------------------------------------------
DROP TABLE IF EXISTS `comentarios_reportes`;
CREATE TABLE IF NOT EXISTS `comentarios_reportes` (
  `id_reporte` int(11) NOT NULL AUTO_INCREMENT,
  `comentario_id` int(11) NOT NULL,
  `motivo` enum('spam','ofensivo','irrelevante','acoso','otro') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `estado` enum('pendiente','revisado','descartado') NOT NULL DEFAULT 'pendiente',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_reporte`),
  KEY `idx_reporte_comentario` (`comentario_id`),
  KEY `idx_reporte_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 4. PALABRAS_PROHIBIDAS
-- Diccionario de palabras censuradas para filtro automático
-- --------------------------------------------------------
DROP TABLE IF EXISTS `palabras_prohibidas`;
CREATE TABLE IF NOT EXISTS `palabras_prohibidas` (
  `id_palabra` int(11) NOT NULL AUTO_INCREMENT,
  `palabra` varchar(100) NOT NULL,
  `severidad` enum('baja','media','alta') NOT NULL DEFAULT 'media',
  `accion` enum('censurar','rechazar','revisar') NOT NULL DEFAULT 'censurar',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_palabra`),
  UNIQUE KEY `unique_palabra` (`palabra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 5. MODERACION_LOG
-- Auditoría de acciones de moderación
-- --------------------------------------------------------
DROP TABLE IF EXISTS `moderacion_log`;
CREATE TABLE IF NOT EXISTS `moderacion_log` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `comentario_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` enum('aprobar','rechazar','censurar','eliminar','restaurar') NOT NULL,
  `motivo` text DEFAULT NULL,
  `palabras_detectadas` text DEFAULT NULL,
  `automatico` tinyint(1) NOT NULL DEFAULT 0,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_log`),
  KEY `idx_log_comentario` (`comentario_id`),
  KEY `idx_log_usuario` (`usuario_id`),
  KEY `idx_log_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 6. MODERACION_CONFIG
-- Configuración del sistema de moderación
-- --------------------------------------------------------
DROP TABLE IF EXISTS `moderacion_config`;
CREATE TABLE IF NOT EXISTS `moderacion_config` (
  `id_config` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL,
  `valor` text NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `actualizado` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_config`),
  UNIQUE KEY `unique_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================
-- FOREIGN KEYS
-- =============================================
ALTER TABLE `comentarios`
  ADD CONSTRAINT `fk_com_noticia` FOREIGN KEY (`noticia_id`) REFERENCES `noticias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_com_parent` FOREIGN KEY (`parent_id`) REFERENCES `comentarios` (`id_com`) ON DELETE CASCADE;

ALTER TABLE `comentarios_reacciones`
  ADD CONSTRAINT `fk_reaccion_comentario` FOREIGN KEY (`comentario_id`) REFERENCES `comentarios` (`id_com`) ON DELETE CASCADE;

ALTER TABLE `comentarios_reportes`
  ADD CONSTRAINT `fk_reporte_comentario` FOREIGN KEY (`comentario_id`) REFERENCES `comentarios` (`id_com`) ON DELETE CASCADE;

ALTER TABLE `moderacion_log`
  ADD CONSTRAINT `fk_log_comentario` FOREIGN KEY (`comentario_id`) REFERENCES `comentarios` (`id_com`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_log_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_u`) ON DELETE SET NULL;

-- =============================================
-- DATOS SEED: Configuración por defecto
-- =============================================
INSERT INTO `moderacion_config` (`clave`, `valor`, `descripcion`) VALUES
('auto_aprobar', '0', 'Aprobar comentarios automáticamente sin revisión (0=no, 1=sí)'),
('filtro_activo', '1', 'Activar filtro de palabras prohibidas (0=no, 1=sí)'),
('max_reportes_auto', '3', 'Número de reportes para ocultar un comentario automáticamente'),
('permitir_invitados', '1', 'Permitir comentarios de usuarios no registrados (0=no, 1=sí)'),
('max_longitud', '1000', 'Longitud máxima de un comentario en caracteres'),
('cooldown_segundos', '60', 'Segundos mínimos entre comentarios de una misma IP');

-- =============================================
-- DATOS SEED: Palabras prohibidas (ejemplo)
-- =============================================
INSERT INTO `palabras_prohibidas` (`palabra`, `severidad`, `accion`) VALUES
('idiota', 'media', 'censurar'),
('estupido', 'media', 'censurar'),
('pendejo', 'alta', 'censurar'),
('puto', 'alta', 'censurar'),
('mierda', 'media', 'censurar'),
('chingar', 'alta', 'censurar'),
('verga', 'alta', 'censurar'),
('perra', 'alta', 'censurar'),
('marica', 'alta', 'rechazar'),
('nazi', 'alta', 'rechazar'),
('terrorista', 'alta', 'revisar'),
('spam', 'baja', 'revisar'),
('viagra', 'baja', 'rechazar'),
('casino', 'baja', 'revisar');
