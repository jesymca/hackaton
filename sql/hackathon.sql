-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-08-2026 a las 01:28:13
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
-- Base de datos: `hackathon`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_hackathon`
--

CREATE TABLE `configuracion_hackathon` (
  `id` int(11) NOT NULL,
  `hackathon_iniciado` tinyint(1) DEFAULT 0,
  `tiempo_inicio_global` datetime DEFAULT NULL,
  `duracion_minutos` int(11) DEFAULT 90,
  `creado_en` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

--
-- Volcado de datos para la tabla `configuracion_hackathon`
--

INSERT INTO `configuracion_hackathon` (`id`, `hackathon_iniciado`, `tiempo_inicio_global`, `duracion_minutos`, `creado_en`) VALUES
(1, 1, '2026-08-10 22:30:04', 50, '2025-10-30 13:44:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `desafios_completados`
--

CREATE TABLE `desafios_completados` (
  `id` int(11) NOT NULL,
  `equipo_id` int(11) DEFAULT NULL,
  `desafio_id` varchar(50) DEFAULT NULL,
  `completado_en` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos`
--

CREATE TABLE `equipos` (
  `id` int(11) NOT NULL,
  `nombre_equipo` varchar(100) NOT NULL,
  `codigo_equipo` varchar(10) NOT NULL,
  `tiempo_inicio` datetime DEFAULT NULL,
  `puntuacion_total` int(11) DEFAULT 0,
  `inicio_tardio` tinyint(1) DEFAULT 0,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  `estado` tinyint(4) DEFAULT 0 COMMENT '0: En espera, 1: Compitiendo',
  `actualizado_en` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tiempo_acumulado` int(11) DEFAULT 0,
  `tiempo_finalizacion` datetime DEFAULT NULL,
  `desafios_completados` int(11) DEFAULT 0,
  `completado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

--
-- Volcado de datos para la tabla `equipos`
--

INSERT INTO `equipos` (`id`, `nombre_equipo`, `codigo_equipo`, `tiempo_inicio`, `puntuacion_total`, `inicio_tardio`, `creado_en`, `estado`, `actualizado_en`, `tiempo_acumulado`, `tiempo_finalizacion`, `desafios_completados`, `completado`) VALUES
(33, 'ANGELES DE INFORMATICA', 'WY4UEQ', '2026-08-10 22:30:04', 0, 0, '2025-11-11 14:40:01', 1, '2026-08-10 20:30:04', 0, NULL, 0, 0),
(34, 'Prueba', 'BVPFGD', '2026-08-10 22:30:04', 0, 0, '2025-11-11 17:54:56', 1, '2026-08-10 20:30:04', 0, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participantes`
--

CREATE TABLE `participantes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `equipo_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

--
-- Volcado de datos para la tabla `participantes`
--

INSERT INTO `participantes` (`id`, `nombre`, `cedula`, `equipo_id`, `creado_en`) VALUES
(64, 'jose', '10101010', 33, '2025-11-11 14:40:01'),
(65, 'angel', '20202020', 33, '2025-11-11 14:40:01'),
(66, 'OTRO', '303030', 33, '2025-11-11 14:40:01'),
(67, 'OTROS', '505050', 33, '2025-11-11 14:40:01'),
(68, 'Jsudbsj', '30692052', 34, '2025-11-11 17:54:56'),
(69, 'Bcksbxj', '98765555', 34, '2025-11-11 17:54:56'),
(70, 'Kslakdosj', '1738172', 34, '2025-11-11 17:54:56'),
(71, 'Oaldmaal', '99988877', 34, '2025-11-11 17:54:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sys_cfg`
--

CREATE TABLE `sys_cfg` (
  `id` int(11) NOT NULL,
  `k` varchar(100) NOT NULL,
  `l` int(11) NOT NULL,
  `s` int(11) NOT NULL,
  `h` varchar(64) NOT NULL,
  `t` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sys_cfg`
--

INSERT INTO `sys_cfg` (`id`, `k`, `l`, `s`, `h`, `t`) VALUES
(1, 'conf/header.php', 148, 8166, 'b97a9c4fd6ae27bb45dffd1e79ed2f03662a81079d2656e4d66c5ee1253efdce', '2026-09-07 18:07:45'),
(2, 'conf/footer.php', 128, 7487, '6b30e3941193e4af45396d21919c7087fbc27a3a493830ede15a3fa2f8a439be', '2026-09-07 18:07:45'),
(3, 'index.php', 1504, 69277, '30da8c8e377f23a14df87e34f0df0a59830db487dff6275b3075522858c4cead', '2026-09-07 18:07:45'),
(4, 'equipos.php', 2448, 104305, '2e6ed854e9e1fdd0c066d5795211f024aeb5607eba7d0406064f26d9fba9ec28', '2026-09-07 18:07:45'),
(5, 'robo_banco.php', 879, 47877, 'fe1b8900f1a9355763caefea526e47af8e069a56b1e9bb6c14ec23d5624df31f', '2026-09-07 18:07:45'),
(6, 'conf/functions.php', 1230, 38948, '83ac19c989312aa1016439e4a2cd49668030e668e2f479834f9e4ab44d306667', '2026-09-07 18:07:45');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `configuracion_hackathon`
--
ALTER TABLE `configuracion_hackathon`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `desafios_completados`
--
ALTER TABLE `desafios_completados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipo_id` (`equipo_id`);

--
-- Indices de la tabla `equipos`
--
ALTER TABLE `equipos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_equipo` (`nombre_equipo`),
  ADD UNIQUE KEY `codigo_equipo` (`codigo_equipo`);

--
-- Indices de la tabla `participantes`
--
ALTER TABLE `participantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD KEY `equipo_id` (`equipo_id`);

--
-- Indices de la tabla `sys_cfg`
--
ALTER TABLE `sys_cfg`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `k` (`k`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `configuracion_hackathon`
--
ALTER TABLE `configuracion_hackathon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `desafios_completados`
--
ALTER TABLE `desafios_completados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=273;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `participantes`
--
ALTER TABLE `participantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT de la tabla `sys_cfg`
--
ALTER TABLE `sys_cfg`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `desafios_completados`
--
ALTER TABLE `desafios_completados`
  ADD CONSTRAINT `desafios_completados_ibfk_1` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `participantes`
--
ALTER TABLE `participantes`
  ADD CONSTRAINT `participantes_ibfk_1` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
