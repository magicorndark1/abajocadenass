<?php
session_start();
require_once '../db_config.php';
require_once 'admin_functions.php'; // Para usar la clase Administrador

// Verificar si el usuario está logueado y tiene un rol permitido
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php?error_unauthorized=true_from_gestionar_libros");
    exit;
}
// Permitir acceso a administradores y docentes, y convertir el rol a minúsculas para la comparación
$user_role = isset($_SESSION["role"]) ? strtolower($_SESSION["role"]) : '';
if (!in_array($user_role, ['administrador', 'docente'])) {
    session_unset(); 
    session_destroy();
    header("location: ../index.php?error_role=unauthorized_panel_access_gestionar_libros");
    exit;
}

$administrador = new Administrador($mysqli);
$mensaje = '';
$mensaje_tipo = ''; // 'success' o 'error'

// Procesar eliminación de libro
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_libro_id'])) {
    $libro_id_eliminar = intval($_POST['eliminar_libro_id']);
    // Solo los administradores pueden eliminar libros
    if ($user_role === 'administrador') {
        $resultado_eliminacion = $administrador->eliminarLibro($libro_id_eliminar);
        if ($resultado_eliminacion === true) {
            $_SESSION['mensaje_gestion_libros'] = "Libro eliminado exitosamente.";
            $_SESSION['mensaje_tipo_gestion_libros'] = "success";
        } else {
            $_SESSION['mensaje_gestion_libros'] = $resultado_eliminacion; // Contiene el mensaje de error
            $_SESSION['mensaje_tipo_gestion_libros'] = "error";
        }
    } else {
        $_SESSION['mensaje_gestion_libros'] = "No tienes permiso para eliminar libros.";
        $_SESSION['mensaje_tipo_gestion_libros'] = "error";
    }
    header("Location: gestionar_libros.php"); // Redirigir para evitar reenvío de formulario
    exit;
}

// Recuperar mensajes de la sesión
if (isset($_SESSION['mensaje_gestion_libros'])) {
    $mensaje = $_SESSION['mensaje_gestion_libros'];
    $mensaje_tipo = $_SESSION['mensaje_tipo_gestion_libros'];
    unset($_SESSION['mensaje_gestion_libros']);
    unset($_SESSION['mensaje_tipo_gestion_libros']);
}

// Obtener todos los libros para mostrar
$sql_libros = "SELECT l.id, l.titulo, l.autor, l.ruta_portada_img, c.nombre as nombre_categoria 
               FROM libros l 
               LEFT JOIN categorias c ON l.categoria_id = c.id 
               ORDER BY l.titulo ASC";
$resultado_libros = $mysqli->query($sql_libros);

$pagina_actual = basename($_SERVER['PHP_SELF']);
// Estas clases se usarán en ambas barras de navegación (admin y docente)
$clase_activa = 'bg-indigo-500 text-white'; 
$clase_hover = 'text-indigo-100 hover:bg-indigo-600 hover:text-white';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Libros - <?php echo ucfirst($user_role); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .message-box { padding: 1rem; margin-bottom: 1.5rem; border-radius: 0.375rem; font-size: 0.875rem; }
        .message-box.success { background-color: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
        .message-box.error { background-color: #FEE2E2; color: #B91C1C; border: 1px solid #FCA5A5; }
        .book-thumbnail { width: 50px; height: 75px; object-fit: cover; border-radius: 0.25rem; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col text-gray-800">

    <?php
    if ($user_role === 'administrador') {
    ?>
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
    <?php
    } elseif ($user_role === 'docente') {
        // Incluimos la barra de navegación específica para docentes
        // Las variables $pagina_actual, $clase_activa, $clase_hover ya están definidas
        include '../docente/docente_nav.php';
    }
    ?>

    <div class="container mx-auto px-4 sm:px-8 py-8">
        <div class="py-4 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Gestión de Libros</h1>
                <p class="text-gray-600">Administra los libros del catálogo.</p>
            </div>
            <a href="upload_libro.php" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out">
                <i class="fas fa-plus-circle mr-2"></i>Añadir Nuevo Libro
            </a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="message-box <?php echo $mensaje_tipo; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto bg-white shadow-md rounded-lg">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm">
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-left">Portada</th>
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-left">Título</th>
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-left">Autor</th>
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-left">Categoría</th>
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    <?php if ($resultado_libros && $resultado_libros->num_rows > 0): ?>
                        <?php while($libro = $resultado_libros->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 border-b border-gray-200">
                            <td class="px-5 py-4 text-sm">
                                <img src="<?php echo !empty($libro['ruta_portada_img']) ? htmlspecialchars($libro['ruta_portada_img']) : '../cover.png'; ?>" 
                                     alt="Portada" class="book-thumbnail"
                                     onerror="this.onerror=null; this.src='../cover.png';">
                            </td>
                            <td class="px-5 py-4 text-sm"><?php echo htmlspecialchars($libro['titulo']); ?></td>
                            <td class="px-5 py-4 text-sm"><?php echo htmlspecialchars($libro['autor']); ?></td>
                            <td class="px-5 py-4 text-sm"><?php echo htmlspecialchars($libro['nombre_categoria'] ?? 'N/A'); ?></td>
                            <td class="px-5 py-4 text-sm text-center whitespace-nowrap">
                                <a href="modificar_libro.php?id=<?php echo $libro['id']; ?>" title="Modificar Libro" class="text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-edit fa-fw"></i> Modificar
                                </a>
                                <?php if ($user_role === 'administrador'): // Solo los administradores pueden eliminar ?>
                                <form action="gestionar_libros.php" method="POST" class="inline-block ml-3" onsubmit="return confirm('¿Estás seguro de que deseas eliminar el libro \'<?php echo htmlspecialchars(addslashes($libro['titulo'])); ?>\'? Esta acción también eliminará su archivo PDF asociado y no se puede deshacer.');">
                                    <input type="hidden" name="eliminar_libro_id" value="<?php echo $libro['id']; ?>">
                                    <button type="submit" title="Eliminar Libro" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash fa-fw"></i> Eliminar
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center text-gray-500">
                                <i class="fas fa-info-circle fa-lg mr-2"></i>No hay libros registrados en el catálogo.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="bg-gray-800 text-white py-6 mt-auto">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; <?php echo date("Y"); ?> Biblioteca Virtual Abajo Cadenas. Todos los derechos reservados.</p>
            <p class="text-sm">Desarrollado con <i class="fas fa-heart text-red-500"></i> y PHP.</p>
        </div>
    </footer>
</body>
</html>
<?php $mysqli->close(); ?>
