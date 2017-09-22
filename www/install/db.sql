-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-09-2017 a las 11:22:36
-- Versión del servidor: 10.1.25-MariaDB
-- Versión de PHP: 7.1.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Base de datos: `almacen`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acceso`
--

CREATE TABLE `acceso` (
  `clave` text COLLATE utf8_bin NOT NULL COMMENT 'password',
  `fecha` date NOT NULL COMMENT 'cuando se creó',
  `descripcion` text COLLATE utf8_bin NOT NULL COMMENT 'Para o por qué se creó esta clave con privilegios',
  `admin_clave` tinyint(1) NOT NULL COMMENT 'Permisos para crear y borrar claves. La clave maestra se encuentra en un php fuera de la base de datos',
  `admin_almacen` tinyint(1) NOT NULL COMMENT 'crear, modificar y borrar almacenes y secciones',
  `admin_objeto` tinyint(1) NOT NULL COMMENT 'crear, modificar y borrar objetos',
  `admin_etiquetas` tinyint(1) NOT NULL COMMENT 'crear, modificar y borrar etiquetas para los objetos',
  `admin_cantidad` tinyint(1) NOT NULL COMMENT 'cambiar cantidades de objetos en cualquier almacen'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `almacen`
--

CREATE TABLE `almacen` (
  `id` int(11) NOT NULL,
  `nombre` text COLLATE utf8_bin NOT NULL,
  `descripcion` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `nombre` text COLLATE utf8_bin NOT NULL,
  `contenido` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historico_objeto`
--

CREATE TABLE `historico_objeto` (
  `id_objeto` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `objeto`
--

CREATE TABLE `objeto` (
  `id` int(11) NOT NULL,
  `nombre` text COLLATE utf8_bin NOT NULL,
  `descripcion` text COLLATE utf8_bin NOT NULL,
  `minimo_alerta` int(11) NOT NULL,
  `imagen` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `objeto_seccion`
--

CREATE TABLE `objeto_seccion` (
  `id_objeto` int(11) NOT NULL,
  `id_seccion` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Disparadores `objeto_seccion`
--
DELIMITER $$
CREATE TRIGGER `objeto actualizado` AFTER UPDATE ON `objeto_seccion` FOR EACH ROW INSERT INTO `historico_objeto`
(id_objeto, 
 fecha,
 cantidad)
 
 SELECT 
 id_objeto,
 NOW(),
 SUM(`cantidad`) 
  from `objeto_seccion_almacen`
  WHERE `objeto_seccion_almacen`.`id_objeto` = id_objeto
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seccion_almacen`
--

CREATE TABLE `seccion_almacen` (
  `id` int(11) NOT NULL,
  `nombre` text COLLATE utf8_bin NOT NULL,
  `descripcion` text COLLATE utf8_bin NOT NULL,
  `id_almacen` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tag`
--

CREATE TABLE `tag` (
  `id` int(11) NOT NULL,
  `nombre` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tag_objeto`
--

CREATE TABLE `tag_objeto` (
  `id_tag` int(11) NOT NULL,
  `id_objeto` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `almacen`
--
ALTER TABLE `almacen`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `historico_objeto`
--
ALTER TABLE `historico_objeto`
  ADD KEY `id_objeto` (`id_objeto`);

--
-- Indices de la tabla `objeto`
--
ALTER TABLE `objeto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `objeto_ibfk_1` (`imagen`);

--
-- Indices de la tabla `objeto_seccion`
--
ALTER TABLE `objeto_seccion`
  ADD PRIMARY KEY (`id_objeto`,`id_seccion`),
  ADD KEY `id_seccion_almacen` (`id_seccion`);

--
-- Indices de la tabla `seccion_almacen`
--
ALTER TABLE `seccion_almacen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_almacen` (`id_almacen`);

--
-- Indices de la tabla `tag`
--
ALTER TABLE `tag`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tag_objeto`
--
ALTER TABLE `tag_objeto`
  ADD PRIMARY KEY (`id_tag`,`id_objeto`),
  ADD KEY `id_objeto` (`id_objeto`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `almacen`
--
ALTER TABLE `almacen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT de la tabla `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT de la tabla `objeto`
--
ALTER TABLE `objeto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT de la tabla `seccion_almacen`
--
ALTER TABLE `seccion_almacen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT de la tabla `tag`
--
ALTER TABLE `tag`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `historico_objeto`
--
ALTER TABLE `historico_objeto`
  ADD CONSTRAINT `historico_objeto_ibfk_1` FOREIGN KEY (`id_objeto`) REFERENCES `objeto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `objeto`
--
ALTER TABLE `objeto`
  ADD CONSTRAINT `objeto_ibfk_1` FOREIGN KEY (`imagen`) REFERENCES `files` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `objeto_seccion`
--
ALTER TABLE `objeto_seccion`
  ADD CONSTRAINT `objeto_seccion_ibfk_1` FOREIGN KEY (`id_objeto`) REFERENCES `objeto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `objeto_seccion_ibfk_2` FOREIGN KEY (`id_seccion`) REFERENCES `seccion_almacen` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `seccion_almacen`
--
ALTER TABLE `seccion_almacen`
  ADD CONSTRAINT `seccion_almacen_ibfk_1` FOREIGN KEY (`id_almacen`) REFERENCES `almacen` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tag_objeto`
--
ALTER TABLE `tag_objeto`
  ADD CONSTRAINT `tag_objeto_ibfk_1` FOREIGN KEY (`id_objeto`) REFERENCES `objeto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tag_objeto_ibfk_2` FOREIGN KEY (`id_tag`) REFERENCES `tag` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;
