-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-01-2018 a las 13:21:50
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
  `nombre` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- RELACIONES PARA LA TABLA `almacen`:
--

--
-- Disparadores `almacen`
--
DELIMITER $$
CREATE TRIGGER `almacen_delete` AFTER DELETE ON `almacen` FOR EACH ROW INSERT INTO historico
(ACCION, I1, T1)
VALUES
("DELETE ALMACEN", OLD.id, OLD.nombre)
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `almacen_insert` AFTER INSERT ON `almacen` FOR EACH ROW INSERT INTO historico
(ACCION, I1, T1)
VALUES
("INSERT ALMACEN", NEW.id, NEW.nombre)
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `almacen_update` AFTER UPDATE ON `almacen` FOR EACH ROW INSERT INTO historico
(ACCION, I1, T1, T2)
VALUES
("UPDATE ALMACEN", NEW.id, OLD.nombre, NEW.nombre)
$$
DELIMITER ;

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

--
-- RELACIONES PARA LA TABLA `file`:
--

--
-- Disparadores `file`
--
DELIMITER $$
CREATE TRIGGER `file_delete` AFTER DELETE ON `file` FOR EACH ROW INSERT INTO historico
(ACCION, I1, B1, T1)
VALUES
("DELETE FILE", OLD.id, OLD.bin, OLD.mimetype)
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `file_insert` AFTER INSERT ON `file` FOR EACH ROW INSERT INTO historico
(ACCION, I1, B1, T1)
VALUES
("INSERT FILE", NEW.id, NEW.bin, NEW.mimetype)
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historico`
--

CREATE TABLE `historico` (
  `ID` int(11) NOT NULL,
  `Fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ACCION` text COLLATE utf8_bin NOT NULL,
  `T1` text COLLATE utf8_bin,
  `T2` text COLLATE utf8_bin,
  `T3` text COLLATE utf8_bin,
  `T4` text COLLATE utf8_bin,
  `T5` text COLLATE utf8_bin,
  `T6` text COLLATE utf8_bin,
  `I1` int(11) DEFAULT NULL,
  `I2` int(11) DEFAULT NULL,
  `I3` int(11) DEFAULT NULL,
  `I4` int(11) DEFAULT NULL,
  `I5` int(11) DEFAULT NULL,
  `I6` int(11) DEFAULT NULL,
  `B1` int(11) DEFAULT NULL,
  `B2` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Toda acción INSERT, DELETE y UPDATE deben de crear una entrada en esta tabla con la consulta realizada y una consulta que desharía el cambio. Ninguna fila debe de borrarse';

--
-- RELACIONES PARA LA TABLA `historico`:
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
  `tags` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- RELACIONES PARA LA TABLA `objeto`:
--

--
-- Disparadores `objeto`
--
DELIMITER $$
CREATE TRIGGER `objeto_delete` AFTER DELETE ON `objeto` FOR EACH ROW INSERT INTO historico
(ACCION, I1, T1, I2, T2, T3)
VALUES
("DELETE OBJETO", OLD.id, OLD.nombre, OLD.minimo, OLD.imagen, OLD.tags)
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `objeto_insert` AFTER INSERT ON `objeto` FOR EACH ROW INSERT INTO historico
(ACCION, I1, T1, I2, T2, T3)
VALUES
("INSERT OBJETO", NEW.id, NEW.nombre, NEW.minimo, NEW.imagen, NEW.tags)
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `objeto_update` AFTER UPDATE ON `objeto` FOR EACH ROW INSERT INTO historico
(ACCION, I1,
 T1, T2,
 I2, I3,
 T3, T4,
 T5, T6)
VALUES
("UPDATE OBJETO", NEW.id,
 OLD.nombre, NEW.nombre,
 OLD.minimo, NEW.minimo,
 OLD.imagen, NEW.imagen,
 OLD.tags, NEW.tags)
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `objeto_seccion`
--

CREATE TABLE `objeto_seccion` (
  `id_objeto` int(11) NOT NULL,
  `id_seccion` int(11) NOT NULL,
  `cantidad` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- RELACIONES PARA LA TABLA `objeto_seccion`:
--

--
-- Disparadores `objeto_seccion`
--
DELIMITER $$
CREATE TRIGGER `objeto_seccion_delete` AFTER DELETE ON `objeto_seccion` FOR EACH ROW INSERT INTO historico
(ACCION, I1, I2, I3)
VALUES
("DELETE OBJETO_SECCION", OLD.id_objeto, OLD.id_seccion, OLD.cantidad)
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `objeto_seccion_insert` AFTER INSERT ON `objeto_seccion` FOR EACH ROW INSERT INTO historico
(ACCION, I1, I2, I3)
VALUES
("INSERT OBJETO_SECCION", NEW.id_objeto, NEW.id_seccion, NEW.cantidad)
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `objeto_seccion_update` AFTER UPDATE ON `objeto_seccion` FOR EACH ROW INSERT INTO historico
(ACCION, I1, I2, I3, I4)
VALUES
("UPDATE OBJETO_SECCION", NEW.id_objeto, NEW.id_seccion, OLD.cantidad, NEW.cantidad)
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seccion`
--

CREATE TABLE `seccion` (
  `id` int(11) NOT NULL,
  `nombre` text COLLATE utf8_bin NOT NULL,
  `id_almacen` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- RELACIONES PARA LA TABLA `seccion`:
--

--
-- Disparadores `seccion`
--
DELIMITER $$
CREATE TRIGGER `seccion_delete` AFTER DELETE ON `seccion` FOR EACH ROW INSERT INTO historico
(ACCION, I1, T1, I2)
VALUES
("DELETE SECCION", OLD.id, OLD.nombre, OLD.id_almacen)
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `seccion_insert` AFTER INSERT ON `seccion` FOR EACH ROW INSERT INTO historico
(ACCION, I1, T1, I2)
VALUES
("INSERT SECCION", NEW.id, NEW.nombre, NEW.id_almacen)
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `seccion_update` AFTER UPDATE ON `seccion` FOR EACH ROW INSERT INTO historico
(ACCION, I1, T1, T2, I2, I3)
VALUES
("UPDATE SECCION", NEW.id, OLD.nombre, NEW.nombre, OLD.id_almacen, NEW.id_almacen)
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `variables`
--

CREATE TABLE `variables` (
  `name` varchar(128) COLLATE utf8_bin NOT NULL,
  `value` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- RELACIONES PARA LA TABLA `variables`:
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
-- Indices de la tabla `file`
--
ALTER TABLE `file`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `historico`
--
ALTER TABLE `historico`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `B1` (`B1`),
  ADD KEY `B2` (`B2`);

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
-- Indices de la tabla `variables`
--
ALTER TABLE `variables`
  ADD PRIMARY KEY (`name`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `almacen`
--
ALTER TABLE `almacen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT de la tabla `historico`
--
ALTER TABLE `historico`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;
--
-- AUTO_INCREMENT de la tabla `objeto`
--
ALTER TABLE `objeto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;
--
-- AUTO_INCREMENT de la tabla `seccion`
--
ALTER TABLE `seccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Metadatos
--
USE `phpmyadmin`;

--
-- Metadatos para la tabla almacen
--

--
-- Metadatos para la tabla file
--

--
-- Metadatos para la tabla historico
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
-- Metadatos para la tabla variables
--

--
-- Metadatos para la base de datos almacen
--
COMMIT;
