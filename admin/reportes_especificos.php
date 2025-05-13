<?php
session_start();
require_once '../db_config.php'; // Conexión a la base de datos

// Verificar si el usuario es administrador y está logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php?error_unauthorized=true_from_reportes_especificos");
    exit;
}
if ($_SESSION["role"] !== 'administrador') {
    session_unset(); session_destroy();
    header("location: ../index.php?error_role=unauthorized_panel_access_reportes_especificos");
    exit;
}

$search_user_term = isset($_GET['search_user']) ? trim($_GET['search_user']) : '';
$search_libro_term = isset($_GET['search_libro_term']) ? trim($_GET['search_libro_term']) : '';
$selected_categoria_id = isset($_GET['categoria_id']) ? intval($_GET['categoria_id']) : 0;

$user_results = [];
$libro_search_results = [];
$libros_por_categoria_detalle = [];

$nombre_categoria_seleccionada = '';
$categorias_disponibles = [];

// Lógica para buscar usuarios
if (!empty($search_user_term)) {
    $sql_search_user = "SELECT id, username, nombre_completo, email, role, estado_aprobacion, fecha_registro FROM usuarios WHERE username LIKE ? OR email LIKE ? OR nombre_completo LIKE ?";
    if($stmt_search_user = $mysqli->prepare($sql_search_user)){
        $param_user_term = "%" . $search_user_term . "%";
        $stmt_search_user->bind_param("sss", $param_user_term, $param_user_term, $param_user_term);
        $stmt_search_user->execute();
        $result_users = $stmt_search_user->get_result();
        while($row = $result_users->fetch_assoc()){
            $user_results[] = $row;
        }
        $stmt_search_user->close();
    }
}

// Lógica para buscar libros
if (!empty($search_libro_term)) {
    $sql_search_libro = "SELECT l.id, l.titulo, l.autor, l.editorial, l.ano_publicacion, l.visualizaciones, l.descargas, l.fecha_subida, c.nombre as nombre_categoria, u.username as subido_por_username 
                         FROM libros l 
                         LEFT JOIN categorias c ON l.categoria_id = c.id 
                         LEFT JOIN usuarios u ON l.subido_por_usuario_id = u.id 
                         WHERE l.titulo LIKE ? OR l.autor LIKE ?";
    if($stmt_search_libro = $mysqli->prepare($sql_search_libro)){
        $param_libro_term = "%" . $search_libro_term . "%";
        $stmt_search_libro->bind_param("ss", $param_libro_term, $param_libro_term);
        $stmt_search_libro->execute();
        $result_libros_search = $stmt_search_libro->get_result();
        while($row = $result_libros_search->fetch_assoc()){
            $libro_search_results[] = $row;
        }
        $stmt_search_libro->close();
    }
}

// Obtener todas las categorías para el dropdown
$sql_categorias = "SELECT id, nombre FROM categorias ORDER BY nombre ASC";
$result_categorias_query = $mysqli->query($sql_categorias);
if ($result_categorias_query) {
    while($row = $result_categorias_query->fetch_assoc()){
        $categorias_disponibles[] = $row;
    }
}

// Si se seleccionó una categoría, obtener los libros y sus estadísticas
if ($selected_categoria_id > 0) {
    $sql_libros_cat = "SELECT l.id, l.titulo, l.autor, l.visualizaciones, l.descargas, l.fecha_subida, c.nombre AS nombre_categoria_seleccionada
                       FROM libros l
                       JOIN categorias c ON l.categoria_id = c.id
                       WHERE l.categoria_id = ? 
                       ORDER BY l.titulo ASC";
    if($stmt_libros_cat = $mysqli->prepare($sql_libros_cat)){
        $stmt_libros_cat->bind_param("i", $selected_categoria_id);
        $stmt_libros_cat->execute();
        $result_libros_cat = $stmt_libros_cat->get_result();
        while($row = $result_libros_cat->fetch_assoc()){
            $libros_por_categoria_detalle[] = $row;
            if(empty($nombre_categoria_seleccionada)) $nombre_categoria_seleccionada = $row['nombre_categoria_seleccionada'];
        }
        $stmt_libros_cat->close();
    }
}

$pagina_actual = basename($_SERVER['PHP_SELF']);
$clase_activa = 'bg-indigo-500 text-white';
$clase_hover = 'text-indigo-100 hover:bg-indigo-600 hover:text-white';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Específicos - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100 text-gray-800">
    <nav class="bg-indigo-700 text-white shadow-lg">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard_admin.php" class="flex-shrink-0 flex items-center text-white">
                        <img src="../logo.png" alt="Logo" class="h-8 w-auto mr-2">
                        <span class="font-semibold text-xl">Panel Administrador</span>
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-1">
                    <a href="dashboard_admin.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'dashboard_admin.php') ? $clase_activa : $clase_hover; ?>">Dashboard</a>
                    <a href="aprobar_usuarios.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'aprobar_usuarios.php') ? $clase_activa : $clase_hover; ?>">Aprobar Registros</a>
                    <a href="gestionar_categorias.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'gestionar_categorias.php') ? $clase_activa : $clase_hover; ?>">Gestionar Categorías</a>
                    <a href="upload_libro.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'upload_libro.php') ? $clase_activa : $clase_hover; ?>">Subir Libro</a>
                    <a href="gestionar_libros.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'gestionar_libros.php') ? $clase_activa : $clase_hover; ?>">Gestionar Libros</a>
                    <a href="reportes_generales.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'reportes_generales.php') ? $clase_activa : $clase_hover; ?>">Reportes Generales</a>
                    <a href="reportes_especificos.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'reportes_especificos.php') ? $clase_activa : $clase_hover; ?>">Reportes Específicos</a>
                    <a href="../catalogo.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo $clase_hover; ?>" target="_blank">Ver Catálogo</a>
                    <a href="../logout.php" class="px-3 py-2 rounded-md text-sm font-medium bg-red-500 hover:bg-red-600 text-white">Cerrar Sesión <i class="fas fa-sign-out-alt ml-1"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 sm:px-8 py-8">
        <div class="py-4">
            <h1 class="text-3xl font-bold text-gray-800">Reportes Específicos</h1>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md space-y-8">
            <div>
                <h2 class="text-xl font-semibold mb-3 text-indigo-600">Buscar Usuario Específico</h2>
                <form action="reportes_especificos.php" method="GET" class="flex items-center space-x-2">
                    <input type="text" id="search_user" name="search_user" value="<?php echo htmlspecialchars($search_user_term); ?>" placeholder="Nombre, Username o Email del usuario" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md"><i class="fas fa-search mr-2"></i>Buscar Usuario</button>
                </form>
                <?php if (!empty($search_user_term) && empty($user_results)): ?>
                    <p class="mt-4 text-gray-600">No se encontraron usuarios que coincidan con "<?php echo htmlspecialchars($search_user_term); ?>".</p>
                <?php elseif (!empty($user_results)): ?>
                    <div class="mt-4 mb-2 text-right">
                        <a href="descargar_reporte.php?tipo_reporte=busqueda_usuarios&search_user=<?php echo urlencode($search_user_term); ?>" class="bg-green-500 hover:bg-green-600 text-white text-sm font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out"><i class="fas fa-download mr-2"></i>Descargar Resultados Usuarios</a>
                    </div>
                    <div class="mt-4">
                        <p class="mb-2">Resultados para "<?php echo htmlspecialchars($search_user_term); ?>": <?php echo count($user_results); ?> encontrado(s).</p>
                        <div class="overflow-x-auto bg-white rounded-lg shadow">
                            <table class="min-w-full leading-normal">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Username</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nombre Completo</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Rol</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha Registro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_results as $user): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm"><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm"><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm"><?php echo htmlspecialchars($user['nombre_completo']); ?></td>
                                        <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                        <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm"><?php echo htmlspecialchars(ucfirst($user['estado_aprobacion'])); ?></td>
                                        <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm">
                                            <?php echo $user['fecha_registro'] ? htmlspecialchars(date("d/m/Y H:i", strtotime($user['fecha_registro']))) : 'N/A'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <hr class="my-8">

            <div>
                <h2 class="text-xl font-semibold mb-3 text-indigo-600">Buscar Libro Específico</h2>
                <form action="reportes_especificos.php" method="GET" class="flex items-center space-x-2">
                    <input type="text" id="search_libro_term" name="search_libro_term" value="<?php echo htmlspecialchars($search_libro_term); ?>" placeholder="Título o Autor del libro" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md"><i class="fas fa-search mr-2"></i>Buscar Libro</button>
                </form>
                <?php if (!empty($search_libro_term) && empty($libro_search_results)): ?>
                    <p class="mt-4 text-gray-600">No se encontraron libros que coincidan con "<?php echo htmlspecialchars($search_libro_term); ?>".</p>
                <?php elseif (!empty($libro_search_results)): ?>
                    <div class="mt-4 mb-2 text-right">
                        <a href="descargar_reporte.php?tipo_reporte=busqueda_libros&search_libro_term=<?php echo urlencode($search_libro_term); ?>" class="bg-green-500 hover:bg-green-600 text-white text-sm font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out"><i class="fas fa-download mr-2"></i>Descargar Resultados Libros</a>
                    </div>
                    <div class="mt-6">
                        <p class="mb-2">Resultados para "<?php echo htmlspecialchars($search_libro_term); ?>": <?php echo count($libro_search_results); ?> libro(s) encontrado(s).</p>
                        <div class="overflow-x-auto bg-white rounded-lg shadow">
                            <table class="min-w-full leading-normal">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Título</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Autor</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Categoría</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Vistas</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Descargas</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Subido por</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha Subida</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($libro_search_results as $libro): ?>
                                        <tr>
                                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($libro['titulo']); ?></td>
                                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($libro['autor']); ?></td>
                                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($libro['nombre_categoria'] ?? 'N/A'); ?></td>
                                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo htmlspecialchars($libro['visualizaciones'] ?? 0); ?></td>
                                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo htmlspecialchars($libro['descargas'] ?? 0); ?></td>
                                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($libro['subido_por_username'] ?? 'Sistema'); ?></td>
                                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo (isset($libro['fecha_subida']) && $libro['fecha_subida']) ? htmlspecialchars(date("d/m/Y H:i", strtotime($libro['fecha_subida']))) : 'N/A'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <hr class="my-8">


            <div>
                <h2 class="text-xl font-semibold mb-3 text-indigo-600">Reporte de Libros por Categoría</h2>
                <form action="reportes_especificos.php" method="GET" class="flex items-center space-x-2 mb-6">
                    <select name="categoria_id" id="categoria_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="0">Seleccione una categoría...</option>
                        <?php foreach ($categorias_disponibles as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>" <?php echo ($selected_categoria_id == $categoria['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md">Generar Reporte</button>
                </form>

                <?php if ($selected_categoria_id > 0 && !empty($nombre_categoria_seleccionada)): ?>
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-lg font-medium text-gray-700">Libros en: "<?php echo htmlspecialchars($nombre_categoria_seleccionada); ?>"</h3>
                        <a href="descargar_reporte.php?tipo_reporte=libros_por_categoria_detalle&categoria_id=<?php echo $selected_categoria_id; ?>&categoria_nombre=<?php echo urlencode($nombre_categoria_seleccionada); ?>" class="bg-green-500 hover:bg-green-600 text-white text-sm font-semibold py-2 px-3 rounded-md transition duration-150 ease-in-out"><i class="fas fa-download mr-1"></i>Descargar Lista</a>
                    </div>
                    <?php if (!empty($libros_por_categoria_detalle)): ?>
                        <div class="overflow-x-auto bg-white rounded-lg shadow">
                            <table class="min-w-full leading-normal">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID Libro</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Título</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Autor</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Visualizaciones</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Descargas</th>
                                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha Subida</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($libros_por_categoria_detalle as $libro_detalle): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($libro_detalle['id']); ?></p>
                                        </td>
                                        <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($libro_detalle['titulo']); ?></p>
                                        </td>
                                        <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($libro_detalle['autor']); ?></p>
                                        </td>
                                        <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm text-center">
                                            <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($libro_detalle['visualizaciones'] ?? 0); ?></p>
                                        </td>
                                        <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm text-center">
                                            <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($libro_detalle['descargas'] ?? 0); ?></p>
                                        </td>
                                        <td class="px-5 py-4 border-b border-gray-200 bg-white text-sm">
                                            <?php echo (isset($libro_detalle['fecha_subida']) && $libro_detalle['fecha_subida']) ? htmlspecialchars(date("d/m/Y H:i", strtotime($libro_detalle['fecha_subida']))) : 'N/A'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="mt-4 text-gray-600">No se encontraron libros para esta categoría.</p>
                    <?php endif; ?>
                <?php elseif ($selected_categoria_id > 0): ?>
                     <p class="mt-4 text-gray-600">No se encontraron libros para la categoría seleccionada o la categoría no existe.</p>
                <?php endif; ?>
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
