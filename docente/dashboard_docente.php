<?php
session_start();

// 1. Verificar si el usuario está realmente logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php?error_unauthorized=true_from_docente_dash");
    exit;
}

// 2. Verificar si el usuario tiene el rol correcto para acceder a este panel
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'docente') {
    session_unset();
    session_destroy();
    header("location: ../index.php?error_role=unauthorized_panel_access_docente");
    exit;
}

require_once '../db_config.php'; // Conexión a la base de datos

$docente_nombre = htmlspecialchars($_SESSION["nombre_completo"] ?? $_SESSION["username"]);
$pagina_actual = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Docente - Biblioteca Virtual</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { width: 260px; transition: transform 0.3s ease-in-out; background-color: #374151; /* gray-700 */ }
        .sidebar-link { display: block; padding: 0.75rem 1.5rem; border-radius: 0.375rem; transition: background-color 0.2s ease, color 0.2s ease; color: #D1D5DB; /* gray-300 */ }
        .sidebar-link:hover, .sidebar-link.active { background-color: #4B5563; /* gray-600 */ color: white; }
        .sidebar-link i { margin-right: 0.75rem; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); position: fixed; height: 100%; z-index: 40; }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.5); z-index: 30; display: none; }
            .sidebar.open + .sidebar-overlay { display: block; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <aside id="sidebar" class="sidebar text-gray-300 p-4 flex flex-col fixed md:static h-full z-40">
            <div class="text-center py-4">
                <a href="dashboard_docente.php" class="inline-block">
                    <img src="../logo.png" alt="Logo Biblioteca Abajo Cadenas" class="h-16 mx-auto mb-2">
                    <span class="text-xl font-bold text-white">U.E. Abajo Cadenas</span>
                </a>
            </div>
            <nav class="flex-grow space-y-2">
                <a href="dashboard_docente.php" class="sidebar-link <?php echo ($pagina_actual == 'dashboard_docente.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="ver_estudiantes.php" class="sidebar-link <?php echo ($pagina_actual == 'ver_estudiantes.php') ? 'active' : ''; ?>"><i class="fas fa-user-graduate"></i> Ver Estudiantes</a>
                <a href="../admin/gestionar_categorias.php" class="sidebar-link <?php echo ($pagina_actual == 'gestionar_categorias.php') ? 'active' : ''; ?>"><i class="fas fa-tags"></i> Gestionar Categorías</a>
                <a href="../admin/upload_libro.php" class="sidebar-link <?php echo ($pagina_actual == 'upload_libro.php') ? 'active' : ''; ?>"><i class="fas fa-book-medical"></i> Subir Libro</a>
                <a href="../admin/gestionar_libros.php" class="sidebar-link <?php echo ($pagina_actual == 'gestionar_libros.php') ? 'active' : ''; ?>"><i class="fas fa-book-reader"></i> Gestionar Libros</a>
                <a href="../catalogo.php" class="sidebar-link" target="_blank"><i class="fas fa-search"></i> Ver Catálogo</a>
            </nav>
            <div class="mt-auto pt-2">
                <a href="../logout.php" class="sidebar-link bg-red-500 hover:bg-red-600 text-white"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
        </aside>
        <div id="sidebarOverlay" class="sidebar-overlay md:hidden"></div>

        <main class="flex-1 p-6 md:p-10 overflow-y-auto">
            <header class="flex justify-between items-center mb-8">
                <button id="mobileMenuButton" class="md:hidden text-gray-600 focus:outline-none">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
                <h1 class="text-3xl font-bold text-gray-800">Panel de Docente</h1>
                <div class="text-right">
                    <p class="text-gray-600">Bienvenido, <span class="font-semibold"><?php echo $docente_nombre; ?></span></p>
                    <p class="text-sm text-gray-500">Rol: Docente</p>
                </div>
            </header>

            <div class="bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold text-gray-700 mb-6">Resumen y Acciones</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <a href="../admin/gestionar_libros.php" class="block bg-blue-500 hover:bg-blue-600 text-white p-6 rounded-lg shadow transition-shadow text-center">
                        <i class="fas fa-book-reader fa-3x mb-3"></i>
                        <p class="text-xl font-semibold">Gestionar Libros</p>
                        <p class="text-sm">Ver, editar y eliminar libros del catálogo.</p>
                    </a>

                    <a href="../admin/upload_libro.php" class="block bg-green-500 hover:bg-green-600 text-white p-6 rounded-lg shadow transition-shadow text-center">
                        <i class="fas fa-book-medical fa-3x mb-3"></i>
                        <p class="text-xl font-semibold">Subir Nuevo Libro</p>
                        <p class="text-sm">Añadir nuevos materiales a la biblioteca.</p>
                    </a>

                    <a href="../admin/gestionar_categorias.php" class="block bg-purple-500 hover:bg-purple-600 text-white p-6 rounded-lg shadow transition-shadow text-center">
                        <i class="fas fa-tags fa-3x mb-3"></i>
                        <p class="text-xl font-semibold">Gestionar Categorías</p>
                        <p class="text-sm">Organizar los libros en categorías.</p>
                    </a>

                    <a href="../catalogo.php" target="_blank" class="block bg-teal-500 hover:bg-teal-600 text-white p-6 rounded-lg shadow transition-shadow text-center">
                        <i class="fas fa-search fa-3x mb-3"></i>
                        <p class="text-xl font-semibold">Explorar Catálogo</p>
                        <p class="text-sm">Ver todos los libros disponibles.</p>
                    </a>

                    <a href="ver_estudiantes.php" class="block bg-orange-500 hover:bg-orange-600 text-white p-6 rounded-lg shadow transition-shadow text-center">
                        <i class="fas fa-user-graduate fa-3x mb-3"></i>
                        <p class="text-xl font-semibold">Ver Estudiantes</p>
                        <p class="text-sm">Consultar la lista de estudiantes.</p>
                    </a>

                </div>
            </div>
        </main>
    </div>
    <script>
        const sidebar = document.getElementById('sidebar');
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (mobileMenuButton && sidebar && sidebarOverlay) {
            mobileMenuButton.addEventListener('click', () => {
                sidebar.classList.toggle('open');
            });
            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
            });
        }
    </script>
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; <?php echo date("Y"); ?> Biblioteca Virtual Abajo Cadenas. Todos los derechos reservados.</p>
            <p class="text-sm">Desarrollado con <i class="fas fa-heart text-red-500"></i> y PHP.</p>
        </div>
    </footer>
</body>
</html>