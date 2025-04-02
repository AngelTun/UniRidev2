-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-04-2025 a las 02:28:44
-- Versión del servidor: 10.4.24-MariaDB
-- Versión de PHP: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `uniride`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividad_usuario`
--

CREATE TABLE `actividad_usuario` (
  `correo` varchar(255) NOT NULL,
  `ultimo_contacto` varchar(255) DEFAULT NULL,
  `ultima_actividad` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `actividad_usuario`
--

INSERT INTO `actividad_usuario` (`correo`, `ultimo_contacto`, `ultima_actividad`) VALUES
('agonzaleztun@gmail.com', 'therapers100@gmail.com', '2025-03-29 09:40:19'),
('therapers100@gmail.com', 'agonzaleztun@gmail.com', '2025-03-29 10:13:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

CREATE TABLE `mensajes` (
  `id` int(11) NOT NULL,
  `remitente` varchar(255) NOT NULL,
  `destinatario` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha` datetime NOT NULL,
  `leido` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `mensajes`
--

INSERT INTO `mensajes` (`id`, `remitente`, `destinatario`, `mensaje`, `fecha`, `leido`) VALUES
(1, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hola', '2025-03-28 16:27:14', 1),
(2, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'Que hay?', '2025-03-28 16:27:37', 1),
(3, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Nada brother', '2025-03-28 16:37:47', 1),
(4, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'oh ya', '2025-03-28 16:38:04', 1),
(5, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'Que haces?', '2025-03-28 18:09:50', 1),
(6, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Nada y tu?', '2025-03-28 18:10:44', 1),
(7, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'Tarea brother', '2025-03-28 18:11:02', 1),
(8, 'agonzaleztun@gmail.com', 'clau_tun18@hotmail.com', 'Hola', '2025-03-28 22:11:31', 0),
(9, 'agonzaleztun@gmail.com', 'clau_tun18@hotmail.com', 'que haces', '2025-03-28 22:11:54', 0),
(10, 'clau_tun18@hotmail.com', 'agonzaleztun@gmail.com', 'Nana', '2025-03-28 22:12:31', 1),
(11, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hola', '2025-03-28 23:42:09', 1),
(12, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'Que hongos?', '2025-03-28 23:45:53', 1),
(13, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hey', '2025-03-28 23:46:05', 1),
(14, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Contesta', '2025-03-28 23:58:13', 1),
(15, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hey', '2025-03-28 23:58:22', 1),
(16, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hola', '2025-03-29 00:38:22', 1),
(17, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'que hay?', '2025-03-29 00:38:49', 1),
(18, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'hola', '2025-03-29 00:39:45', 1),
(19, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hh', '2025-03-29 00:39:59', 1),
(20, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'Hola', '2025-03-29 02:00:44', 1),
(21, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'Que pex', '2025-03-29 02:01:12', 1),
(22, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'hey', '2025-03-29 02:09:25', 1),
(23, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hola', '2025-03-29 02:12:46', 1),
(24, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'hey', '2025-03-29 02:13:02', 1),
(25, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'bro', '2025-03-29 02:19:28', 1),
(26, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hola', '2025-03-29 02:33:04', 1),
(27, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hola', '2025-03-29 02:40:42', 1),
(28, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Heyyy', '2025-03-29 02:41:04', 1),
(29, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hola', '2025-03-29 02:42:50', 1),
(30, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Que hongos', '2025-03-29 02:51:31', 1),
(31, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Heyyy', '2025-03-29 02:52:00', 1),
(32, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Heyyy', '2025-03-29 02:52:04', 1),
(33, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Heyyy', '2025-03-29 02:52:10', 1),
(34, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'eyy', '2025-03-29 02:52:21', 1),
(35, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'bye', '2025-03-29 02:56:25', 1),
(36, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'bye', '2025-03-29 02:56:38', 1),
(37, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'uiuyyuio', '2025-03-29 02:56:43', 1),
(38, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'contesta', '2025-03-29 02:56:49', 1),
(39, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Que onda', '2025-03-29 03:05:36', 1),
(40, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'Que hongos', '2025-03-29 03:06:53', 1),
(41, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hey', '2025-03-29 03:07:03', 1),
(42, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Yuy', '2025-03-29 03:07:19', 1),
(43, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Henry', '2025-03-29 03:07:26', 1),
(44, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'hey', '2025-03-29 03:07:33', 1),
(45, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hey', '2025-03-29 03:07:38', 1),
(46, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'Hola', '2025-03-29 03:12:08', 1),
(47, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hola', '2025-03-29 03:39:24', 1),
(48, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hey', '2025-03-29 03:39:46', 1),
(49, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Yyyy', '2025-03-29 03:39:57', 1),
(50, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Heyy', '2025-03-29 03:40:01', 1),
(51, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Jajaja', '2025-03-29 03:40:09', 1),
(52, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'Holaa', '2025-03-29 03:40:19', 1),
(53, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hola', '2025-03-29 03:48:48', 1),
(54, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hey', '2025-03-29 04:12:41', 1),
(55, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Que pex?', '2025-03-29 04:13:14', 1),
(56, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'hey', '2025-03-29 04:14:36', 1),
(57, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hey', '2025-03-29 04:23:14', 1),
(58, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hey', '2025-03-29 04:23:17', 1),
(59, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hh', '2025-03-29 04:23:24', 1),
(60, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hh', '2025-03-29 04:23:37', 1),
(61, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Bhhh', '2025-03-29 04:24:48', 1),
(62, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'hghjhjk', '2025-03-29 04:27:43', 1),
(63, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'hghkjjhgfgh', '2025-03-29 04:27:45', 1),
(64, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'hgffgj', '2025-03-29 04:27:48', 1),
(65, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hfjdhdhd', '2025-03-29 04:28:11', 1),
(66, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Bbdbdbd', '2025-03-29 04:28:14', 1),
(67, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hshdjsjs', '2025-03-29 04:28:19', 1),
(68, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hey', '2025-03-31 15:02:48', 1),
(69, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hhegegd', '2025-03-31 15:03:00', 1),
(70, 'agonzaleztun@gmail.com', 'therapers100@gmail.com', 'ggfjfgf', '2025-03-31 15:03:14', 1),
(71, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hey', '2025-03-31 15:08:55', 1),
(72, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hola', '2025-03-31 15:11:50', 1),
(73, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Hey', '2025-03-31 16:43:50', 0),
(74, 'therapers100@gmail.com', 'agonzaleztun@gmail.com', 'Contestame', '2025-03-31 16:43:53', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `matricula` varchar(50) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombres`, `apellidos`, `matricula`, `correo`, `password`, `created_at`, `session_id`) VALUES
(5, 'Jose', 'Echeverria', 'E21080429', 'terra.joseandez@gmail.com', '$2y$10$3u37.lCgtvNyUmzmOGNrOuOU3bmclZM3sGiQuoMdzp9Nu939SaOc6', '2025-02-20 00:47:05', NULL),
(8, 'Fernanda', 'Suarez', 'E21080503', 'fersuarez0705@gmail.com', '$2y$10$.1I7CG1P8XoHO3BjLy.4neXz4ExpGXcoy.Bk3mZiWnWFxuWQ7Z4VO', '2025-02-24 21:55:07', NULL),
(11, 'luis', 'ramirez', 'E12345678', 'luis', '$2y$10$6I10FukvmyrpHDvSZq70Vu3ni6ie8F5In5llEZ4K73Jid//8PjEci', '2025-03-06 17:39:39', NULL),
(21, 'Angel', 'Tun', 'E21080456', 'agonzaleztun@gmail.com', '$2y$10$39q5heaBVd9ZC64/cPiHX.RRgFCGPqjIgxHe7qOo8A0RflYlVMzKq', '2025-03-28 21:08:40', 'q3sja23joorme7mgd7msqe5nvd'),
(22, 'Angel Ernesto', 'González Tun', 'E21080455', 'therapers100@gmail.com', '$2y$10$etMzZYKDVWsH0alR6agKVeZEgJbI1IlqcBld9k5wYYkxH/GB9.Hb.', '2025-03-28 22:26:14', 'c9vqckqg316520h41tk59k3k4s'),
(23, 'Claudia Beatriz', 'Tun', '12', 'clau_tun18@hotmail.com', '$2y$10$g4S545JE4o08.LGHcQU8MudwF53MPxFMNMhFChU9eOqIpYkk91G1q', '2025-03-29 04:09:51', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividad_usuario`
--
ALTER TABLE `actividad_usuario`
  ADD PRIMARY KEY (`correo`),
  ADD KEY `ultima_actividad` (`ultima_actividad`);

--
-- Indices de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_remitente` (`remitente`),
  ADD KEY `idx_destinatario` (`destinatario`),
  ADD KEY `idx_mensajes_destinatario` (`destinatario`,`leido`),
  ADD KEY `idx_mensajes_remitente` (`remitente`),
  ADD KEY `idx_mensajes_fecha` (`fecha`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD CONSTRAINT `mensajes_ibfk_1` FOREIGN KEY (`remitente`) REFERENCES `usuarios` (`correo`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensajes_ibfk_2` FOREIGN KEY (`destinatario`) REFERENCES `usuarios` (`correo`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
