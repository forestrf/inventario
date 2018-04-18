-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-04-2018 a las 14:42:11
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
-- Estructura de tabla para la tabla `almacen`
--

CREATE TABLE `almacen` (
  `id` int(11) NOT NULL,
  `padre` int(11) DEFAULT NULL,
  `nombre` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `file`
--

CREATE TABLE `file` (
  `id` varchar(32) COLLATE utf8_bin NOT NULL COMMENT 'md5 del blob',
  `bin` mediumblob,
  `mimetype` varchar(32) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- TIPOS MIME PARA LA TABLA `file`:
--   `bin`
--       `Image_JPEG`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `objeto`
--

CREATE TABLE `objeto` (
  `id` int(11) NOT NULL,
  `nombre` text COLLATE utf8_bin NOT NULL,
  `minimo` int(11) NOT NULL,
  `imagen` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `tags` text COLLATE utf8_bin NOT NULL,
  `version` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- RELACIONES PARA LA TABLA `objeto`:
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `variables`
--

CREATE TABLE `variables` (
  `name` varchar(128) COLLATE utf8_bin NOT NULL,
  `value` text COLLATE utf8_bin NOT NULL,
  `version` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Indices de la tabla `almacen`
--
ALTER TABLE `almacen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_padre` (`padre`);

--
-- Indices de la tabla `file`
--
ALTER TABLE `file`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `objeto`
--
ALTER TABLE `objeto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `imagen` (`imagen`);

--
-- Indices de la tabla `variables`
--
ALTER TABLE `variables`
  ADD PRIMARY KEY (`name`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `objeto`
--
ALTER TABLE `objeto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `almacen`
  ADD CONSTRAINT `id_padre` FOREIGN KEY (`padre`) REFERENCES `almacen` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;


--
-- Metadatos
--
USE `phpmyadmin`;

--
-- Volcado de datos para la tabla `pma__column_info`
--

INSERT INTO `pma__column_info` (`db_name`, `table_name`, `column_name`, `comment`, `mimetype`, `transformation`, `transformation_options`, `input_transformation`, `input_transformation_options`) VALUES
('almacen', 'file', 'bin', '', 'image_jpeg', '', '', '', '');

COMMIT;
