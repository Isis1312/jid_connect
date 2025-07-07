-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-07-2025 a las 00:27:07
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
-- Base de datos: `jid_bd`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cargo`
--

CREATE TABLE `cargo` (
  `id` int(11) NOT NULL,
  `cargo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cargo`
--

INSERT INTO `cargo` (`id`, `cargo`) VALUES
(1, 'admin'),
(2, 'gerente'),
(3, 'ejecutivo'),
(4, 'tecnico');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `rif` int(10) NOT NULL,
  `ubicacion` text NOT NULL,
  `telefono` int(11) NOT NULL,
  `n_equipos` int(11) NOT NULL DEFAULT 0,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo' COMMENT 'Estado del cliente: activo o inactivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `correo`, `rif`, `ubicacion`, `telefono`, `n_equipos`, `fecha_registro`, `estado`) VALUES
(4, 'Unicornios C.A', 'unicornios@gmail.com', 123456, 'Av. Venezuelass', 2147483647, 11, '2025-06-20 11:53:18', 'inactivo'),
(5, 'Eunice Bastidas', 'p@gmail.com', 123456, 'Carrera con calle 48', 412998136, 1, '2025-06-20 11:53:18', 'inactivo'),
(6, 'Studio SEC', 'h@gmail.com', 123456, 'Av. Venezuela', 412998136, 4, '2025-06-20 11:53:18', 'inactivo'),
(7, 'Isis', 'kk@gmail.com', 123456, 'Av. Venezuela', 412998136, 1, '2025-06-29 04:25:52', 'inactivo'),
(9, 'jjjj', 'pjj@gmail.com', 4444, 'Av libertador', 555555, 1, '2025-06-29 23:34:41', 'activo'),
(10, 'Eunice Bastidas', 'p@gmail.com', 1111, 'ggg', 2147483647, 1, '2025-06-29 23:45:41', 'activo'),
(11, 'Laurys CA', 'laurysrivero1@gmail.com', 1111, 'Av. Venezuela', 111, 1, '2025-06-30 08:07:43', 'inactivo'),
(12, 'Laurys CA', 'laurysrivero1@gmail.com', 1111, 'Av. Venezuela', 111, 1, '2025-06-30 08:24:42', 'activo'),
(13, 'Paola studio', 'paolastudios@gmail.com', 123456789, 'Carrera 27 con calle 49 y 48', 467502011, 1, '2025-07-06 22:41:01', 'activo'),
(14, 'l', 'k@gamil.com', 1444444444, 'Av. Venezuela', 2147483647, 1, '2025-07-06 22:51:54', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sistema`
--

CREATE TABLE `configuracion_sistema` (
  `id` int(11) NOT NULL,
  `smtp_host` varchar(100) NOT NULL DEFAULT 'smtp.gmail.com',
  `smtp_usuario` varchar(100) NOT NULL,
  `smtp_clave` varchar(255) NOT NULL,
  `smtp_puerto` int(11) NOT NULL DEFAULT 587,
  `smtp_seguridad` varchar(10) NOT NULL DEFAULT 'tls',
  `correo_sistema` varchar(100) NOT NULL,
  `nombre_sistema` varchar(100) NOT NULL DEFAULT 'JID Connect',
  `smtp_timeout` int(11) DEFAULT 30,
  `smtp_debug` tinyint(1) DEFAULT 0,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion_sistema`
--

INSERT INTO `configuracion_sistema` (`id`, `smtp_host`, `smtp_usuario`, `smtp_clave`, `smtp_puerto`, `smtp_seguridad`, `correo_sistema`, `nombre_sistema`, `smtp_timeout`, `smtp_debug`, `creado_en`, `actualizado_en`) VALUES
(1, 'smtp.gmail.com', 'tucorreo@gmail.com', 'tucontraseñadeapp', 587, 'tls', 'notificaciones@jidconnect.com', 'JID Connect', 30, 0, '2025-06-25 06:02:54', '2025-06-25 06:02:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entrevistas`
--

CREATE TABLE `entrevistas` (
  `id_entrevista` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `marca_equipo` varchar(100) NOT NULL,
  `descripcion_problema` text NOT NULL,
  `tiene_garantia` enum('Si','No') NOT NULL,
  `numero_garantia` varchar(50) DEFAULT NULL,
  `necesita_repuesto` enum('Si','No') NOT NULL,
  `detalles_repuesto` text DEFAULT NULL,
  `fecha_entrevista` datetime NOT NULL,
  `ejecutivo` varchar(100) NOT NULL,
  `codigo_entrevista` varchar(20) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `entrevistas`
--

INSERT INTO `entrevistas` (`id_entrevista`, `id_cliente`, `marca_equipo`, `descripcion_problema`, `tiene_garantia`, `numero_garantia`, `necesita_repuesto`, `detalles_repuesto`, `fecha_entrevista`, `ejecutivo`, `codigo_entrevista`, `fecha_registro`) VALUES
(9, 6, 'lll', 'a', 'No', NULL, 'No', NULL, '2025-07-02 00:00:00', '4444', 'EN00001', '2025-07-01 20:44:00'),
(10, 10, 'fff', 'l', 'No', NULL, 'No', NULL, '2025-07-22 00:00:00', '5555', 'EN00002', '2025-07-07 03:02:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `informes`
--

CREATE TABLE `informes` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `servicio_id` int(11) DEFAULT NULL,
  `numero_informe` varchar(20) NOT NULL,
  `tecnico_nombre` varchar(100) NOT NULL,
  `problema_reportado` text NOT NULL,
  `detalles_servicio` text NOT NULL,
  `estado_resolucion` enum('Reparado','No reparado','Solucion temporal') NOT NULL,
  `recomendaciones` text DEFAULT NULL,
  `fecha_visita` date NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `informes`
--

INSERT INTO `informes` (`id`, `cliente_id`, `servicio_id`, `numero_informe`, `tecnico_nombre`, `problema_reportado`, `detalles_servicio`, `estado_resolucion`, `recomendaciones`, `fecha_visita`, `fecha_creacion`) VALUES
(2, 5, NULL, 'IN00001', 'lll', 'fd', 'ddd', 'Solucion temporal', 'gg g f', '2025-06-29', '2025-06-29 18:38:16'),
(6, 12, NULL, 'IN00003', '000', 'j', 'j', 'Reparado', 'j', '2025-07-07', '2025-07-06 23:00:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_correos`
--

CREATE TABLE `logs_correos` (
  `id` int(11) NOT NULL,
  `destinatario` varchar(100) NOT NULL,
  `asunto` varchar(255) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `estado` enum('enviado','fallido') NOT NULL,
  `error` text DEFAULT NULL,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL,
  `servicio_id` int(11) DEFAULT NULL,
  `tipo_correo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos`
--

CREATE TABLE `modulos` (
  `id_modulo` int(11) NOT NULL,
  `nombre_modulo` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modulos`
--

INSERT INTO `modulos` (`id_modulo`, `nombre_modulo`, `descripcion`) VALUES
(1, 'clientes', 'Gestión de clientes'),
(2, 'agenda', 'Gestión de citas/agenda'),
(3, 'entrevistas', 'Gestión de entrevistas'),
(4, 'informes', 'Gestión de informes'),
(5, 'configuracion', 'Configuración del sistema');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `id_cargo` int(11) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `puede_ver` tinyint(1) NOT NULL DEFAULT 0,
  `puede_crear` tinyint(1) NOT NULL DEFAULT 0,
  `puede_editar` tinyint(1) NOT NULL DEFAULT 0,
  `puede_eliminar` tinyint(1) NOT NULL DEFAULT 0,
  `ver_todo` tinyint(1) DEFAULT 0,
  `generar_pdf` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id`, `id_cargo`, `modulo`, `puede_ver`, `puede_crear`, `puede_editar`, `puede_eliminar`, `ver_todo`, `generar_pdf`) VALUES
(1, 1, 'clientes', 1, 1, 1, 1, 1, 1),
(2, 1, 'agenda', 1, 1, 1, 1, 1, 1),
(3, 1, 'entrevistas', 1, 1, 1, 1, 1, 1),
(4, 1, 'informe', 1, 1, 1, 1, 1, 1),
(5, 2, 'clientes', 1, 1, 1, 1, 1, 1),
(6, 2, 'agenda', 1, 1, 1, 1, 1, 1),
(7, 2, 'entrevistas', 1, 0, 1, 1, 1, 1),
(8, 2, 'informe', 1, 1, 1, 1, 1, 1),
(9, 3, 'clientes', 1, 0, 0, 0, 1, 1),
(10, 3, 'entrevistas', 1, 1, 1, 1, 1, 1),
(12, 4, 'informe', 1, 1, 0, 0, 1, 1),
(13, 4, 'clientes', 1, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `descripcion` text NOT NULL,
  `estado` enum('pendiente','en_progreso','completado','cancelado') DEFAULT 'pendiente',
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`id`, `cliente_id`, `usuario_id`, `fecha`, `hora`, `descripcion`, `estado`, `fecha_creacion`) VALUES
(14, 11, 1, '2025-06-30', '09:08:00', 'hola', 'pendiente', '2025-06-30 08:08:32'),
(15, 5, 2, '2025-06-30', '08:28:00', 'jj', 'pendiente', '2025-06-30 08:26:59'),
(16, 10, 2, '2025-06-30', '08:28:00', 'lll', 'pendiente', '2025-06-30 08:27:33'),
(17, 10, 1, '2025-07-08', '11:00:00', 'y', 'pendiente', '2025-07-06 22:53:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `id_cargo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `usuario`, `contraseña`, `id_cargo`) VALUES
(1, 'admin01', '1234', 1),
(2, 'gerente01', '1234', 2),
(3, 'ejecutivo01', '1234', 3),
(7, 'tecnico01', '1234', 4),
(8, 'admin02', '1234', 1),
(9, 'gerente02', '1234', 2),
(10, 'ejecutivo02', '1234', 3),
(11, 'tecnico02', '1234', 4);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cargo`
--
ALTER TABLE `cargo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `entrevistas`
--
ALTER TABLE `entrevistas`
  ADD PRIMARY KEY (`id_entrevista`),
  ADD UNIQUE KEY `codigo_entrevista` (`codigo_entrevista`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `informes`
--
ALTER TABLE `informes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_informe` (`numero_informe`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `servicio_id` (`servicio_id`);

--
-- Indices de la tabla `modulos`
--
ALTER TABLE `modulos`
  ADD PRIMARY KEY (`id_modulo`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cargo` (`id_cargo`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `id_cargo` (`id_cargo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cargo`
--
ALTER TABLE `cargo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `entrevistas`
--
ALTER TABLE `entrevistas`
  MODIFY `id_entrevista` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `informes`
--
ALTER TABLE `informes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `modulos`
--
ALTER TABLE `modulos`
  MODIFY `id_modulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `entrevistas`
--
ALTER TABLE `entrevistas`
  ADD CONSTRAINT `entrevistas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`);

--
-- Filtros para la tabla `informes`
--
ALTER TABLE `informes`
  ADD CONSTRAINT `informes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Filtros para la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD CONSTRAINT `permisos_ibfk_1` FOREIGN KEY (`id_cargo`) REFERENCES `cargo` (`id`);

--
-- Filtros para la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD CONSTRAINT `servicios_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `servicios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_cargo`) REFERENCES `cargo` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
