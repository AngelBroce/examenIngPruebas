-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-07-2025 a las 07:49:31
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `negocio_fotografia`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `agenda`
--

CREATE TABLE `agenda` (
  `id_reserva` int(4) NOT NULL,
  `tipo_sesion` enum('cobertura de evento','tematica','estudio','exterior','otro') NOT NULL,
  `descripcion_sesion` text DEFAULT NULL,
  `duracion_sesion` varchar(50) DEFAULT NULL,
  `lugar_sesion` varchar(255) DEFAULT NULL,
  `estilo_fotografia` varchar(100) DEFAULT NULL,
  `servicios_adicionales` text DEFAULT NULL,
  `resto_por_pagar` decimal(10,2) DEFAULT NULL,
  `total_pagar` decimal(10,2) DEFAULT NULL,
  `otros_datos` varchar(200) DEFAULT NULL,
  `lugar` varchar(100) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `cedula_cliente` varchar(13) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Volcado de datos para la tabla `agenda`
--

INSERT INTO `agenda` (`id_reserva`, `tipo_sesion`, `descripcion_sesion`, `duracion_sesion`, `lugar_sesion`, `estilo_fotografia`, `servicios_adicionales`, `resto_por_pagar`, `total_pagar`, `otros_datos`, `lugar`, `fecha`, `hora`, `cedula_cliente`) VALUES
(33, 'cobertura de evento', 'Fotografía profesional para eventos especiales', '4h 0min', 'domicilio', 'clásico', 'maquillaje,vestuario,edicion,impresiones', 451.50, 645.00, 'Musica casual', '', '2025-07-30', '15:04:00', '8-545-5454'),
(34, '', 'Fotografías profesionales para uso empresarial', '1h 0min', 'domicilio', 'creativo', 'maquillaje,vestuario,edicion,impresiones', 241.50, 345.00, 'un poco de comida', '', '2025-07-29', '15:49:00', '8-545-5454'),
(35, 'cobertura de evento', 'Fotografía profesional para eventos especiales', '4h 0min', 'domicilio', 'creativo', 'maquillaje,vestuario,edicion,impresiones', 451.50, 645.00, 'rhaaaag', '', '2025-07-29', '15:46:00', '8-535-64566'),
(36, 'cobertura de evento', 'Fotografía profesional para eventos especiales', '4h 0min', 'estudio', 'clásico', 'maquillaje,edicion', 413.00, 590.00, 'cafegbhsdzjmnzdshd', '', '2025-07-30', '17:34:00', '8-545-5454'),
(37, 'cobertura de evento', 'Fotografía profesional para eventos especiales', '4h 0min', 'exterior', 'moderno', 'maquillaje,vestuario,edicion,impresiones', 451.50, 645.00, 'mrn', '', '2025-07-31', '14:23:00', '8-545-5454');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `agenda`
--
ALTER TABLE `agenda`
  ADD PRIMARY KEY (`id_reserva`),
  ADD KEY `FK_cedula_cliente` (`cedula_cliente`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `agenda`
--
ALTER TABLE `agenda`
  MODIFY `id_reserva` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `agenda`
--
ALTER TABLE `agenda`
  ADD CONSTRAINT `FK_cedula_cliente` FOREIGN KEY (`cedula_cliente`) REFERENCES `cliente` (`cedula`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
