-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-11-2017 a las 12:05:10
-- Versión del servidor: 10.1.25-MariaDB
-- Versión de PHP: 7.1.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `almacen`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acceso`
--
-- Creación: 24-07-2017 a las 11:24:50
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

--
-- RELACIONES PARA LA TABLA `acceso`:
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `almacen`
--
-- Creación: 24-07-2017 a las 10:21:31
--

CREATE TABLE `almacen` (
  `id` int(11) NOT NULL,
  `nombre` text COLLATE utf8_bin NOT NULL,
  `descripcion` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- RELACIONES PARA LA TABLA `almacen`:
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `files`
--
-- Creación: 28-11-2017 a las 11:01:15
--

CREATE TABLE `files` (
  `id` varchar(32) COLLATE utf8_bin NOT NULL COMMENT 'md5 del blob',
  `bin` mediumblob,
  `mimetype` varchar(32) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- TIPOS MIME PARA LA TABLA `files`:
--   `bin`
--       `Image_JPEG`
--

--
-- RELACIONES PARA LA TABLA `files`:
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historico_objeto`
--
-- Creación: 24-07-2017 a las 10:27:52
--

CREATE TABLE `historico_objeto` (
  `id_objeto` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- RELACIONES PARA LA TABLA `historico_objeto`:
--   `id_objeto`
--       `objeto` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `objeto`
--
-- Creación: 13-10-2017 a las 10:09:34
--

CREATE TABLE `objeto` (
  `id` int(11) NOT NULL,
  `nombre` text COLLATE utf8_bin NOT NULL,
  `minimo_alerta` int(11) NOT NULL,
  `imagen` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `tags` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- RELACIONES PARA LA TABLA `objeto`:
--   `imagen`
--       `files` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `objeto_seccion`
--
-- Creación: 22-09-2017 a las 09:11:51
--

CREATE TABLE `objeto_seccion` (
  `id_objeto` int(11) NOT NULL,
  `id_seccion` int(11) NOT NULL,
  `cantidad` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- RELACIONES PARA LA TABLA `objeto_seccion`:
--   `id_objeto`
--       `objeto` -> `id`
--   `id_seccion`
--       `seccion` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seccion`
--
-- Creación: 14-11-2017 a las 11:18:48
--

CREATE TABLE `seccion` (
  `id` int(11) NOT NULL,
  `nombre` text COLLATE utf8_bin NOT NULL,
  `descripcion` text COLLATE utf8_bin NOT NULL,
  `id_almacen` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- RELACIONES PARA LA TABLA `seccion`:
--   `id_almacen`
--       `almacen` -> `id`
--

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
  ADD KEY `imagen` (`imagen`);

--
-- Indices de la tabla `objeto_seccion`
--
ALTER TABLE `objeto_seccion`
  ADD PRIMARY KEY (`id_objeto`,`id_seccion`),
  ADD KEY `id_seccion_almacen` (`id_seccion`);

--
-- Indices de la tabla `seccion`
--
ALTER TABLE `seccion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_almacen` (`id_almacen`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `almacen`
--
ALTER TABLE `almacen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT de la tabla `objeto`
--
ALTER TABLE `objeto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT de la tabla `seccion`
--
ALTER TABLE `seccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
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
  ADD CONSTRAINT `objeto_seccion_ibfk_2` FOREIGN KEY (`id_seccion`) REFERENCES `seccion` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `seccion`
--
ALTER TABLE `seccion`
  ADD CONSTRAINT `seccion_ibfk_1` FOREIGN KEY (`id_almacen`) REFERENCES `almacen` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;


--
-- Metadatos
--
USE `phpmyadmin`;

--
-- Metadatos para la tabla acceso
--

--
-- Metadatos para la tabla almacen
--

--
-- Metadatos para la tabla files
--

--
-- Volcado de datos para la tabla `pma__column_info`
--

INSERT INTO `pma__column_info` (`db_name`, `table_name`, `column_name`, `comment`, `mimetype`, `transformation`, `transformation_options`, `input_transformation`, `input_transformation_options`) VALUES
('almacen', 'files', 'bin', '', 'image_jpeg', 'output/image_jpeg_inline.php', '', '', '');

--
-- Metadatos para la tabla historico_objeto
--

--
-- Metadatos para la tabla objeto
--

--
-- Metadatos para la tabla objeto_seccion
--

--
-- Metadatos para la tabla seccion
--

--
-- Metadatos para la base de datos almacen
--
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
