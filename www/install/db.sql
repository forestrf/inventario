-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-07-2017 a las 12:52:18
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
-- Estructura de tabla para la tabla `almacen`
--

CREATE TABLE `almacen` (
  `id` int(11) NOT NULL,
  `nombre` text COLLATE utf8_bin NOT NULL,
  `descripcion` text COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Volcado de datos para la tabla `almacen`
--

INSERT INTO `almacen` (`id`, `nombre`, `descripcion`) VALUES
(1, 'asdasda', 'fawfawfawfawf');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etiqueta`
--

CREATE TABLE `etiqueta` (
  `id` int(11) NOT NULL,
  `nombre` int(11) NOT NULL,
  `id_objeto` int(11) NOT NULL
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

--
-- Volcado de datos para la tabla `historico_objeto`
--

INSERT INTO `historico_objeto` (`id_objeto`, `fecha`, `cantidad`) VALUES
(1, '2017-07-24', 3),
(1, '2017-07-24', 20);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `objeto`
--

CREATE TABLE `objeto` (
  `id` int(11) NOT NULL,
  `nombre` text COLLATE utf8_bin NOT NULL,
  `descripcion` text COLLATE utf8_bin NOT NULL,
  `minimo_alerta` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Volcado de datos para la tabla `objeto`
--

INSERT INTO `objeto` (`id`, `nombre`, `descripcion`, `minimo_alerta`) VALUES
(1, 'test', 'testestset', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `objeto_seccion_almacen`
--

CREATE TABLE `objeto_seccion_almacen` (
  `id_objeto` int(11) NOT NULL,
  `id_seccion_almacen` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Volcado de datos para la tabla `objeto_seccion_almacen`
--

INSERT INTO `objeto_seccion_almacen` (`id_objeto`, `id_seccion_almacen`, `cantidad`) VALUES
(1, 2, 20);

--
-- Disparadores `objeto_seccion_almacen`
--
DELIMITER $$
CREATE TRIGGER `objeto actualizado` AFTER UPDATE ON `objeto_seccion_almacen` FOR EACH ROW INSERT INTO `historico_objeto`
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

--
-- Volcado de datos para la tabla `seccion_almacen`
--

INSERT INTO `seccion_almacen` (`id`, `nombre`, `descripcion`, `id_almacen`) VALUES
(2, 'kjeofjeioj', 'iojsoefjioajfalwf', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `almacen`
--
ALTER TABLE `almacen`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `etiqueta`
--
ALTER TABLE `etiqueta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_objeto` (`id_objeto`);

--
-- Indices de la tabla `historico_objeto`
--
ALTER TABLE `historico_objeto`
  ADD KEY `id_objeto` (`id_objeto`);

--
-- Indices de la tabla `objeto`
--
ALTER TABLE `objeto`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `objeto_seccion_almacen`
--
ALTER TABLE `objeto_seccion_almacen`
  ADD PRIMARY KEY (`id_objeto`,`id_seccion_almacen`),
  ADD KEY `id_seccion_almacen` (`id_seccion_almacen`);

--
-- Indices de la tabla `seccion_almacen`
--
ALTER TABLE `seccion_almacen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_almacen` (`id_almacen`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `almacen`
--
ALTER TABLE `almacen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT de la tabla `etiqueta`
--
ALTER TABLE `etiqueta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT de la tabla `objeto`
--
ALTER TABLE `objeto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT de la tabla `seccion_almacen`
--
ALTER TABLE `seccion_almacen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `etiqueta`
--
ALTER TABLE `etiqueta`
  ADD CONSTRAINT `etiqueta_ibfk_1` FOREIGN KEY (`id_objeto`) REFERENCES `objeto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `historico_objeto`
--
ALTER TABLE `historico_objeto`
  ADD CONSTRAINT `historico_objeto_ibfk_1` FOREIGN KEY (`id_objeto`) REFERENCES `objeto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `objeto_seccion_almacen`
--
ALTER TABLE `objeto_seccion_almacen`
  ADD CONSTRAINT `objeto_seccion_almacen_ibfk_1` FOREIGN KEY (`id_objeto`) REFERENCES `objeto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `objeto_seccion_almacen_ibfk_2` FOREIGN KEY (`id_seccion_almacen`) REFERENCES `seccion_almacen` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `seccion_almacen`
--
ALTER TABLE `seccion_almacen`
  ADD CONSTRAINT `seccion_almacen_ibfk_1` FOREIGN KEY (`id_almacen`) REFERENCES `almacen` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
