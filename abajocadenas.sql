-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-05-2025 a las 18:37:43
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
-- Base de datos: `abajocadenas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `creada_por` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `creada_por`, `fecha_creacion`) VALUES
(1, 'Novela Histórica', 'Libros que combinan ficción con eventos históricos reales.', 1, '2025-05-10 03:11:55'),
(2, 'Ciencia Ficción', 'Relatos basados en avances científicos y tecnológicos futuros.', 1, '2025-05-10 03:11:55'),
(3, 'Programación', 'Libros técnicos sobre desarrollo de software y lenguajes de programación.', 1, '2025-05-10 03:11:55'),
(8, 'Matemática', '', NULL, '2025-05-10 06:22:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE `favoritos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `libro_id` int(11) NOT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `favoritos`
--

INSERT INTO `favoritos` (`id`, `usuario_id`, `libro_id`, `fecha_agregado`) VALUES
(2, 4, 16, '2025-05-11 13:55:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `libros`
--

CREATE TABLE `libros` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `autor` varchar(255) DEFAULT NULL,
  `editorial` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `ruta_archivo_pdf` varchar(255) NOT NULL,
  `ruta_portada_img` varchar(255) DEFAULT NULL,
  `subido_por_usuario_id` int(11) DEFAULT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `palabras_clave` text DEFAULT NULL,
  `ano_publicacion` int(4) DEFAULT NULL,
  `cantidad_disponible` int(11) DEFAULT 1,
  `visualizaciones` int(11) DEFAULT 0,
  `descargas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `libros`
--

INSERT INTO `libros` (`id`, `titulo`, `autor`, `editorial`, `descripcion`, `categoria_id`, `ruta_archivo_pdf`, `ruta_portada_img`, `subido_por_usuario_id`, `fecha_subida`, `palabras_clave`, `ano_publicacion`, `cantidad_disponible`, `visualizaciones`, `descargas`) VALUES
(1, 'Matemáticas Básic', 'Carlos Alberto Rojas Hincapie', NULL, NULL, 1, '', NULL, NULL, '2025-05-10 14:53:20', NULL, NULL, 1, 28, 0),
(10, 'aaaaaa', 'aaaaaaaaaa', 'aaaa', 'aaaa', 2, '0', NULL, NULL, '2025-05-10 16:51:37', NULL, 0, 1, 2, 0),
(11, 'Conceptos y Magnitudes en Física', 'Carlos Alberto Rojas Hincapie', NULL, '', 2, '0', NULL, NULL, '2025-05-10 17:13:15', NULL, 0, 1, 16, 0),
(12, 'Libro Prueba SQL', 'Autor Prueba SQL', 'Editorial SQL', 'Descripción desde SQL', 1, 'uploads/prueba_directa.pdf', NULL, NULL, '2025-05-10 18:12:04', NULL, 2025, 1, 13, 0),
(14, 'Matemáticashhhhh', 'Carlos Alberto Rojas Hincapie', NULL, NULL, 1, 'uploads/libro_681f9cc78be5d9.13784969.pdf', NULL, 1, '2025-05-10 18:36:55', NULL, NULL, 1, 11, 3),
(15, 'Métodos Matemáticos de la Físicasssss', 'Oscar Reula', NULL, NULL, 2, 'uploads/libro_681ffe98d90424.53757455.pdf', NULL, 1, '2025-05-11 01:34:16', NULL, NULL, 1, 7, 0),
(16, 'Matemáticas Básicas', 'L. Laroze, N. Porras y G. Fuster', NULL, NULL, 2, 'uploads/libro_68203084d31816.72488160.pdf', NULL, 3, '2025-05-11 05:07:16', NULL, NULL, 1, 1, 0),
(17, 'Matemáticas Básicaaaaa', 'Oscar Reulaa', NULL, NULL, 8, 'uploads/libro_68203b5ab3f960.75016579.pdf', NULL, 3, '2025-05-11 05:53:30', NULL, NULL, 1, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log_descargas_reportes`
--

CREATE TABLE `log_descargas_reportes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_reporte` varchar(100) NOT NULL,
  `parametros_reporte` text DEFAULT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `fecha_descarga` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `log_descargas_reportes`
--

INSERT INTO `log_descargas_reportes` (`id`, `usuario_id`, `tipo_reporte`, `parametros_reporte`, `nombre_archivo`, `fecha_descarga`) VALUES
(1, 1, 'busqueda_libros', '{\"search_libro_term\":\"mate\"}', 'reporte_libros_mate_20250511.csv', '2025-05-11 00:32:57'),
(2, 1, 'usuarios_por_rol', NULL, 'reporte_usuarios_por_rol_20250511.csv', '2025-05-11 00:33:44'),
(3, 1, 'top_visualizados', NULL, 'reporte_top_5_libros_visualizados_20250511.csv', '2025-05-11 00:33:55'),
(4, 1, 'busqueda_libros', '{\"search_libro_term\":\"mate\"}', 'reporte_libros_mate_20250511.csv', '2025-05-11 00:51:47'),
(5, 1, 'top_descargados', NULL, 'reporte_top_5_libros_descargados_20250511.csv', '2025-05-11 00:52:09'),
(6, 1, 'usuarios_por_rol', NULL, 'reporte_usuarios_por_rol_20250511.csv', '2025-05-11 00:52:16'),
(7, 1, 'busqueda_usuarios', '{\"search_user\":\"ad\"}', 'reporte_usuarios_ad_20250511.csv', '2025-05-11 00:52:44'),
(8, 1, 'busqueda_usuarios', '{\"search_user\":\"ad\"}', 'reporte_usuarios_ad_20250511.csv', '2025-05-11 00:55:32'),
(9, 1, 'busqueda_libros', '{\"search_libro_term\":\"mate\"}', 'reporte_libros_mate_20250511.csv', '2025-05-11 00:58:49'),
(10, 1, 'busqueda_libros', '{\"search_libro_term\":\"mate\"}', 'reporte_libros_mate_20250511.csv', '2025-05-11 01:06:22'),
(11, 1, 'busqueda_libros', '{\"search_libro_term\":\"mate\"}', 'reporte_libros_mate_20250511.csv', '2025-05-11 01:10:38'),
(12, 1, 'usuarios_por_rol', NULL, 'reporte_usuarios_por_rol_20250511.csv', '2025-05-11 01:12:55'),
(13, 1, 'libros_por_categoria_detalle', '{\"categoria_id\":2,\"categoria_nombre\":\"Ciencia Ficci\\u00f3n\"}', 'reporte_libros_categoria_CienciaFiccin_20250511.csv', '2025-05-11 01:26:48'),
(14, 1, 'usuarios_por_rol', NULL, 'reporte_usuarios_por_rol_20250511.csv', '2025-05-11 01:27:24'),
(15, 1, 'usuarios_por_rol', NULL, 'reporte_usuarios_por_rol_20250511.csv', '2025-05-11 01:31:01'),
(16, 1, 'usuarios_por_rol', NULL, 'reporte_lista_usuarios_por_rol_20250511.csv', '2025-05-11 01:32:54'),
(17, 1, 'usuarios_por_rol', NULL, 'reporte_lista_usuarios_por_rol_20250511.csv', '2025-05-11 05:58:50'),
(18, 1, 'libros_por_categoria_general', NULL, 'reporte_libros_por_categoria_20250511.csv', '2025-05-11 05:59:31'),
(19, 1, 'top_descargados', NULL, 'reporte_top_5_libros_descargados_20250511.csv', '2025-05-11 05:59:42'),
(20, 1, 'top_visualizados', NULL, 'reporte_top_5_libros_visualizados_20250511.csv', '2025-05-11 05:59:53'),
(21, 1, 'busqueda_usuarios', '{\"search_user\":\"es\"}', 'reporte_usuarios_es_20250511.csv', '2025-05-11 06:00:38'),
(22, 1, 'top_visualizados', NULL, 'reporte_top_5_libros_visualizados_20250511.csv', '2025-05-11 06:15:35'),
(23, 1, 'top_visualizados', NULL, 'reporte_top_5_libros_visualizados_20250511.csv', '2025-05-11 06:15:43'),
(24, 1, 'libros_por_categoria_general', NULL, 'reporte_libros_por_categoria_20250511.csv', '2025-05-11 06:15:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(1, 'docente@prueba.com', 'e28d268813ba92d58b01bbec048c2b6d0cc1fcdd112558fc4f8e1b0f1d59a8e4', '2025-05-11 16:33:21', '2025-05-11 13:33:21'),
(2, 'estudiante@prueba.com', 'e81f5a361fdc3e587783be0114b4d42753abb25539164f00ff20271168982d2e', '2025-05-11 16:33:49', '2025-05-11 13:33:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `cedula` varchar(15) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `direccion` text DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('administrador','docente','estudiante') NOT NULL,
  `estado_aprobacion` enum('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_completo`, `cedula`, `telefono`, `email`, `direccion`, `username`, `password`, `role`, `estado_aprobacion`, `fecha_registro`) VALUES
(1, 'Administrador Principal', '0', '0412-1234567', 'admin@biblioteca.com', 'Direccion Admin', 'admin', '$2y$10$1qLtROZbnXrKKCfSS.aCtuqCj.mT5ANC17k/qCg2MDj5dMRsDqqDS', 'administrador', 'aprobado', '2025-05-10 03:11:55'),
(2, 'estudiante3', 'V-30302302', '04124568754', 'estudiante@prueba.com', 'dssssssss', 'test', '$2y$10$JZXIIqNk2lpPvUPaDvWuEOF/A61ZFQH/3gdq1ItsRyDQIsvAKbjr6', 'estudiante', 'aprobado', '2025-05-10 05:18:42'),
(3, 'docente', 'v-30582569', '0412487858', 'docente@prueba.com', '', 'docente', '$2y$10$1p3bt7otLKt1xBnq1yT.s.5M0iZYdDHIRFGvVkvyyMfIE9xswOUZO', 'docente', 'aprobado', '2025-05-11 03:58:31'),
(4, 'Maria Rivas', 'V-30517536', '04121960587', 'mariarivaswork1@gmail.com', '', 'mari', '$2y$10$ks8zSB8ITE8OGF04FS2nxOUpDvLYCHr4lA1enoTd0VGniAcXMFAc2', 'estudiante', 'aprobado', '2025-05-11 13:39:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vistas_recientes`
--

CREATE TABLE `vistas_recientes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `libro_id` int(11) NOT NULL,
  `fecha_vista` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_ultima_vista` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `vistas_recientes`
--

INSERT INTO `vistas_recientes` (`id`, `usuario_id`, `libro_id`, `fecha_vista`, `fecha_ultima_vista`) VALUES
(1, 4, 11, '2025-05-11 13:55:11', '2025-05-11 13:55:11'),
(6, 4, 16, '2025-05-11 13:55:20', '2025-05-11 13:55:20');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD KEY `creada_por` (`creada_por`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`,`libro_id`),
  ADD KEY `libro_id` (`libro_id`);

--
-- Indices de la tabla `libros`
--
ALTER TABLE `libros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `subido_por_usuario_id` (`subido_por_usuario_id`);

--
-- Indices de la tabla `log_descargas_reportes`
--
ALTER TABLE `log_descargas_reportes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `email` (`email`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indices de la tabla `vistas_recientes`
--
ALTER TABLE `vistas_recientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`,`libro_id`),
  ADD UNIQUE KEY `idx_usuario_libro_vista` (`usuario_id`,`libro_id`),
  ADD KEY `libro_id` (`libro_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `libros`
--
ALTER TABLE `libros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `log_descargas_reportes`
--
ALTER TABLE `log_descargas_reportes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `vistas_recientes`
--
ALTER TABLE `vistas_recientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `categorias_ibfk_1` FOREIGN KEY (`creada_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`libro_id`) REFERENCES `libros` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `libros`
--
ALTER TABLE `libros`
  ADD CONSTRAINT `libros_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `libros_ibfk_2` FOREIGN KEY (`subido_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `log_descargas_reportes`
--
ALTER TABLE `log_descargas_reportes`
  ADD CONSTRAINT `log_descargas_reportes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `vistas_recientes`
--
ALTER TABLE `vistas_recientes`
  ADD CONSTRAINT `vistas_recientes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vistas_recientes_ibfk_2` FOREIGN KEY (`libro_id`) REFERENCES `libros` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
