<?php
session_start();

// 1. Verificar si el usuario está realmente logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php?error_unauthorized=true_from_docente_ver_estudiantes");
    exit;
}

// 2. Verificar si el usuario tiene el rol correcto para acceder a este panel
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'docente') {
    session_unset();
    session_destroy();
    header("location: ../index.php?error_role=unauthorized_panel_access_docente_ver_estudiantes");
    exit;
}

require_once '../db_config.php'; // Conexión a la base de datos

$docente_nombre = htmlspecialchars($_SESSION["nombre_completo"] ?? $_SESSION["username"]);
$pagina_actual = basename($_SERVER['PHP_SELF']);
// Definir clases para los enlaces de navegación para consistencia
$clase_activa = 'bg-indigo-500 text-white'; // Estilo para el enlace activo, consistente con docente_nav.php
$clase_hover = 'text-indigo-100 hover:bg-indigo-600 hover:text-white'; // Estilo actual para el enlace hover/inactivo


$estudiantes = [];
$sql_estudiantes = "SELECT id, nombre_completo, username, email, fecha_registro 
                    FROM usuarios 
                    WHERE role = 'estudiante' 
                    ORDER BY nombre_completo ASC";

if ($result = $mysqli->query($sql_estudiantes)) {
    while ($row = $result->fetch_assoc()) {
        $estudiantes[] = $row;
    }
    $result->free();
} else {
    // Manejar error de consulta si es necesario
    error_log("Error al obtener estudiantes: " . $mysqli->error);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Estudiantes - Panel de Docente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">
    <nav class="bg-indigo-700 text-white shadow-lg">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard_docente.php" class="flex-shrink-0 flex items-center text-white">
                        <img src="../logo.png" alt="Logo Biblioteca Abajo Cadenas" class="h-8 w-auto mr-2">
                        <span class="font-semibold text-xl">Panel Docente</span>
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="dashboard_docente.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'dashboard_docente.php') ? $clase_activa : $clase_hover; ?>">Dashboard</a>                    
                    <a href="../admin/gestionar_categorias.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'gestionar_categorias.php') ? $clase_activa : $clase_hover; ?>">Gestionar Categorías</a>
                    <a href="../admin/upload_libro.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'upload_libro.php') ? $clase_activa : $clase_hover; ?>">Subir Libro</a>
                    <a href="ver_estudiantes.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'ver_estudiantes.php') ? $clase_activa : $clase_hover; ?>">Ver Estudiantes</a>
                    <a href="../admin/gestionar_libros.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'gestionar_libros.php') ? $clase_activa : $clase_hover; ?>">Gestionar Libros</a>
                    <a href="../catalogo.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo $clase_hover; ?>" target="_blank">Ver Catálogo</a>
                    <a href="../logout.php" class="px-3 py-2 rounded-md text-sm font-medium bg-red-500 hover:bg-red-600 text-white">Cerrar Sesión <i class="fas fa-sign-out-alt ml-1"></i></a>
                </div>
                <div class="md:hidden flex items-center">
                    <button id="topMobileMenuButton" class="text-indigo-100 hover:text-white focus:outline-none">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                
            </div>
        </div>
        <!-- Mobile menu -->
        <div id="topMobileMenu" class="md:hidden hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="dashboard_docente.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo ($pagina_actual == 'dashboard_docente.php') ? $clase_activa : $clase_hover; ?>">Dashboard</a>                
                <a href="../admin/gestionar_categorias.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo ($pagina_actual == 'gestionar_categorias.php') ? $clase_activa : $clase_hover; ?>">Categorías</a>
                <a href="../admin/upload_libro.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo ($pagina_actual == 'upload_libro.php') ? $clase_activa : $clase_hover; ?>">Subir Libro</a>
                <a href="ver_estudiantes.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo ($pagina_actual == 'ver_estudiantes.php') ? $clase_activa : $clase_hover; ?>">Estudiantes</a>
                <a href="../admin/gestionar_libros.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo ($pagina_actual == 'gestionar_libros.php') ? $clase_activa : $clase_hover; ?>">Gestionar Libros</a>
                <a href="../catalogo.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $clase_hover; ?>" target="_blank">Ver Catálogo</a>
                <a href="../logout.php" class="block px-3 py-2 rounded-md text-base font-medium bg-red-500 hover:bg-red-600 text-white">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 sm:px-8 py-8 flex-grow">
        <header class="flex justify-between items-center mb-8">
            <div> <!-- Contenedor para el título para que no interfiera con el lado derecho si se usa justify-between en el header principal -->
                <h1 class="text-3xl font-bold text-gray-800">Lista de Estudiantes</h1>
            </div>
            <div class="text-right">
                <p class="text-gray-600">Docente: <span class="font-semibold"><?php echo $docente_nombre; ?></span></p>
            </div>
        </header>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <?php if (count($estudiantes) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre Completo</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha de Registro</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($estudiantes as $estudiante): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($estudiante['nombre_completo']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($estudiante['username']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($estudiante['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($estudiante['fecha_registro']))); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-600 text-center py-10">No hay estudiantes registrados en el sistema.</p>
            <?php endif; ?>
        </div>
    </main>
    <script>
        const topMobileMenuButton = document.getElementById('topMobileMenuButton');
        const topMobileMenu = document.getElementById('topMobileMenu');

        if (topMobileMenuButton && topMobileMenu) {
            topMobileMenuButton.addEventListener('click', () => {
                topMobileMenu.classList.toggle('hidden');
            });
        }
    </script>
    <footer class="bg-gray-800 text-white py-6 mt-auto">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; <?php echo date("Y"); ?> Biblioteca Virtual Abajo Cadenas. Todos los derechos reservados.</p>
            <p class="text-sm">Desarrollado con <i class="fas fa-heart text-red-500"></i> y PHP.</p>
        </div>
    </footer>
</body>
</html>
<?php $mysqli->close(); ?>