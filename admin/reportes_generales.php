<?php
session_start();
require_once '../db_config.php'; // Asegúrate que la ruta es correcta

// Verificar si el usuario es administrador y está logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php?error_unauthorized=true_from_reportes_generales");
    exit;
}
if ($_SESSION["role"] !== 'administrador') {
    session_unset(); session_destroy();
    header("location: ../index.php?error_role=unauthorized_panel_access_reportes_generales");
    exit;
}

// --- Lógica para obtener datos de reportes generales ---
// Total de usuarios
$sql_total_users = "SELECT COUNT(*) as total FROM usuarios";
$result_total_users = $mysqli->query($sql_total_users);
$total_users = $result_total_users ? $result_total_users->fetch_assoc()['total'] : 0;

// Usuarios por rol
$sql_users_by_role = "SELECT role, COUNT(*) as count FROM usuarios GROUP BY role";
$result_users_by_role = $mysqli->query($sql_users_by_role);
$users_by_role = [];
while($row = $result_users_by_role->fetch_assoc()){
    $users_by_role[$row['role']] = $row['count'];
}

// Total de libros
$sql_total_libros = "SELECT COUNT(*) as total FROM libros";
$result_total_libros = $mysqli->query($sql_total_libros);
$total_libros = $result_total_libros ? $result_total_libros->fetch_assoc()['total'] : 0;

// Libros por categoría
$sql_libros_por_categoria = "SELECT c.nombre, COUNT(l.id) as cantidad FROM libros l JOIN categorias c ON l.categoria_id = c.id GROUP BY c.id, c.nombre ORDER BY c.nombre";
$result_libros_por_categoria = $mysqli->query($sql_libros_por_categoria);
$libros_por_categoria = [];
if ($result_libros_por_categoria) {
    while($row = $result_libros_por_categoria->fetch_assoc()){
        $libros_por_categoria[] = $row;
    }
}

// Total de visualizaciones y descargas de libros
$sql_total_vistas_descargas = "SELECT SUM(visualizaciones) as total_visualizaciones, SUM(descargas) as total_descargas FROM libros";
$result_total_v_d = $mysqli->query($sql_total_vistas_descargas);
$stats_libros_global = $result_total_v_d ? $result_total_v_d->fetch_assoc() : ['total_visualizaciones' => 0, 'total_descargas' => 0];

// Libros más visualizados (Top 5)
$sql_top_visualizados = "SELECT titulo, autor, visualizaciones FROM libros WHERE visualizaciones > 0 ORDER BY visualizaciones DESC LIMIT 5";
$result_top_visualizados = $mysqli->query($sql_top_visualizados);
$libros_top_visualizados = [];
if ($result_top_visualizados) {
    while($row = $result_top_visualizados->fetch_assoc()){
        $libros_top_visualizados[] = $row;
    }
}

// Libros más descargados (Top 5)
$sql_top_descargados = "SELECT titulo, autor, descargas FROM libros WHERE descargas > 0 ORDER BY descargas DESC LIMIT 5";
$result_top_descargados = $mysqli->query($sql_top_descargados);
$libros_top_descargados = [];
if ($result_top_descargados) {
    while($row = $result_top_descargados->fetch_assoc()){
        $libros_top_descargados[] = $row;
    }
}

// Usuarios aprobados y tasa de aprobación
$sql_users_aprobados = "SELECT COUNT(*) as aprobados FROM usuarios WHERE estado_aprobacion = 'aprobado'";
$result_users_aprobados = $mysqli->query($sql_users_aprobados);
$usuarios_aprobados = $result_users_aprobados ? $result_users_aprobados->fetch_assoc()['aprobados'] : 0;
$tasa_aprobacion = ($total_users > 0) ? ($usuarios_aprobados / $total_users) * 100 : 0;

// Nuevos usuarios (registrados en los últimos 30 días)
$sql_nuevos_usuarios = "SELECT COUNT(*) as nuevos_ultimos_30_dias FROM usuarios WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$result_nuevos_usuarios = $mysqli->query($sql_nuevos_usuarios);
$nuevos_usuarios_30_dias = $result_nuevos_usuarios ? $result_nuevos_usuarios->fetch_assoc()['nuevos_ultimos_30_dias'] : 0;

// Usuarios por estado de aprobación
$sql_users_by_status = "SELECT estado_aprobacion, COUNT(*) as count FROM usuarios GROUP BY estado_aprobacion";
$result_users_by_status = $mysqli->query($sql_users_by_status);
$users_by_status = [
    'pendiente' => 0,
    'aprobado' => 0,
    'rechazado' => 0
];
if ($result_users_by_status) {
    while($row = $result_users_by_status->fetch_assoc()){
        if (array_key_exists($row['estado_aprobacion'], $users_by_status)) {
            $users_by_status[$row['estado_aprobacion']] = $row['count'];
        }
    }
}
$pagina_actual = basename($_SERVER['PHP_SELF']);
$clase_activa = 'bg-indigo-500';
$clase_hover = 'hover:bg-indigo-600';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Generales - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100 text-gray-800">
    <nav class="bg-indigo-700 text-white shadow-lg">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a href="dashboard_admin.php" class="text-xl font-bold"><i class="fas fa-user-shield mr-2"></i>Panel de Administrador</a>
            <div class="flex space-x-2">
                <a href="gestionar_usuarios.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'gestionar_usuarios.php') ? $clase_activa : $clase_hover; ?>">Gestionar Usuarios</a>
                <a href="aprobar_usuarios.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'aprobar_usuarios.php') ? $clase_activa : $clase_hover; ?>">Aprobar Registros</a>
                <a href="gestionar_categorias.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'gestionar_categorias.php') ? $clase_activa : $clase_hover; ?>">Gestionar Categorías</a>
                <a href="upload_libro.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'upload_libro.php') ? $clase_activa : $clase_hover; ?>">Subir Libro</a>
                <a href="gestionar_libros.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'gestionar_libros.php') ? $clase_activa : $clase_hover; ?>">Gestionar Libros</a>
                <a href="reportes_generales.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'reportes_generales.php') ? $clase_activa : $clase_hover; ?>">Reportes Generales</a>
                <a href="reportes_especificos.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'reportes_especificos.php') ? $clase_activa : $clase_hover; ?>">Reportes Específicos</a>
                <a href="../catalogo.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-indigo-600" target="_blank">Ver Catálogo</a>
                <a href="../logout.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-red-600 bg-red-500">Cerrar Sesión <i class="fas fa-sign-out-alt ml-1"></i></a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 sm:px-8 py-8">
        <div class="py-4">
            <h1 class="text-3xl font-bold text-gray-800">Reportes Generales</h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md space-y-3">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-xl font-semibold text-indigo-600">Usuarios</h2>
                    <a href="descargar_reporte.php?tipo_reporte=usuarios_por_rol" class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold py-1 px-3 rounded-md transition duration-150 ease-in-out"><i class="fas fa-download mr-1"></i> Descargar Roles</a>
                </div>
                <ul class="list-disc list-inside space-y-1">
                    <li>Total de Usuarios Registrados: <strong class="font-medium"><?php echo $total_users; ?></strong></li>
                    <li>Nuevos Usuarios (últimos 30 días): <strong class="font-medium"><?php echo $nuevos_usuarios_30_dias; ?></strong></li>
                    <li>Usuarios por Rol:
                        <ul class="list-disc list-inside ml-6">
                            <li>Administradores: <strong class="font-medium"><?php echo $users_by_role['administrador'] ?? 0; ?></strong></li>
                            <li>Docentes: <strong class="font-medium"><?php echo $users_by_role['docente'] ?? 0; ?></strong></li>
                            <li>Estudiantes: <strong class="font-medium"><?php echo $users_by_role['estudiante'] ?? 0; ?></strong></li>
                        </ul>
                    </li>
                    <li>Estado de Aprobación de Usuarios:
                        <ul class="list-disc list-inside ml-6">
                            <li>Aprobados: <strong class="font-medium"><?php echo $users_by_status['aprobado']; ?></strong> (<?php echo number_format($tasa_aprobacion, 2); ?>% del total)</li>
                            <li>Pendientes: <strong class="font-medium"><?php echo $users_by_status['pendiente']; ?></strong></li>
                            <li>Rechazados: <strong class="font-medium"><?php echo $users_by_status['rechazado']; ?></strong></li>
                        </ul>
                    </li>
                </ul>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md space-y-3">
                 <div class="flex justify-between items-center mb-3">
                    <h2 class="text-xl font-semibold text-indigo-600">Libros</h2>
                    <a href="descargar_reporte.php?tipo_reporte=libros_por_categoria_general" class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold py-1 px-3 rounded-md transition duration-150 ease-in-out"><i class="fas fa-download mr-1"></i> Descargar Categorías</a>
                </div>
                <ul class="list-disc list-inside space-y-1">
                    <li>Total de Libros en el Catálogo: <strong class="font-medium"><?php echo $total_libros; ?></strong></li>
                    <?php if (!empty($libros_por_categoria)): ?>
                    <li>Libros por Categoría:
                        <ul class="list-disc list-inside ml-6">
                            <?php foreach ($libros_por_categoria as $cat_data): ?>
                                <li><?php echo htmlspecialchars($cat_data['nombre']); ?>: <strong class="font-medium"><?php echo $cat_data['cantidad']; ?></strong></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <li>Total de Visualizaciones de Libros: <strong class="font-medium"><?php echo number_format($stats_libros_global['total_visualizaciones'] ?? 0); ?></strong></li>
                    <li>Total de Descargas de Libros: <strong class="font-medium"><?php echo number_format($stats_libros_global['total_descargas'] ?? 0); ?></strong></li>
                </ul>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md md:col-span-1">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-xl font-semibold text-indigo-600">Top 5 Libros Más Visualizados</h2>
                    <a href="descargar_reporte.php?tipo_reporte=top_visualizados" class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold py-1 px-3 rounded-md transition duration-150 ease-in-out"><i class="fas fa-download mr-1"></i> Descargar</a>
                </div>
                <ul class="list-decimal list-inside space-y-1">
                    <?php if (!empty($libros_top_visualizados)): ?>
                        <?php foreach ($libros_top_visualizados as $libro): ?>
                            <li><?php echo htmlspecialchars($libro['titulo']); ?> (<?php echo htmlspecialchars($libro['autor']); ?>) - <strong class="font-medium"><?php echo number_format($libro['visualizaciones']); ?></strong> vistas</li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="text-gray-500">No hay datos de visualizaciones.</li>
                    <?php endif; ?>
                </ul>
            </div>
             <div class="bg-white p-6 rounded-lg shadow-md md:col-span-1">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-xl font-semibold text-indigo-600">Top 5 Libros Más Descargados</h2>
                    <a href="descargar_reporte.php?tipo_reporte=top_descargados" class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold py-1 px-3 rounded-md transition duration-150 ease-in-out"><i class="fas fa-download mr-1"></i> Descargar</a>
                </div>
                <ul class="list-decimal list-inside space-y-1">
                    <?php if (!empty($libros_top_descargados)): ?>
                        <?php foreach ($libros_top_descargados as $libro): ?>
                            <li><?php echo htmlspecialchars($libro['titulo']); ?> (<?php echo htmlspecialchars($libro['autor']); ?>) - <strong class="font-medium"><?php echo number_format($libro['descargas']); ?></strong> descargas</li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="text-gray-500">No hay datos de descargas.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <footer class="bg-gray-800 text-white py-6 mt-12">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; <?php echo date("Y"); ?> Biblioteca Virtual Abajo Cadenas. Todos los derechos reservados.</p>
            <p class="text-sm">Desarrollado con <i class="fas fa-heart text-red-500"></i> y PHP.</p>
        </div>
    </footer>
</body>
</html>
<?php $mysqli->close(); ?>