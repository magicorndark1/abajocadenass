<?php
session_start();

// 1. Verificar si el usuario está realmente logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php?error_unauthorized=true_from_reportes_opciones");
    exit;
}

// 2. Verificar si el usuario tiene el rol correcto para acceder a este panel
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'administrador') {
    session_unset();
    session_destroy();
    header("location: ../index.php?error_role=unauthorized_panel_access_reportes_opciones");
    exit;
}

require_once '../db_config.php'; // Conexión a la base de datos (opcional para esta página si solo tiene enlaces)

$admin_nombre = htmlspecialchars($_SESSION["nombre_completo"] ?? $_SESSION["username"]);
$pagina_actual = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opciones de Reportes - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { width: 260px; transition: transform 0.3s ease-in-out; }
        .sidebar-link { display: block; padding: 0.75rem 1.5rem; border-radius: 0.375rem; transition: background-color 0.2s ease, color 0.2s ease; }
        .sidebar-link:hover, .sidebar-link.active { background-color: #4F46E5; color: white; }
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
        <aside id="sidebar" class="sidebar bg-gray-800 text-gray-300 p-4 space-y-2 fixed md:static min-h-screen h-full z-40">
            <div class="text-center py-4"> <!-- Este div ya estaba, solo como referencia -->
                <a href="dashboard_admin.php" class="text-2xl font-bold text-white"><i class="fas fa-book-open"></i> Biblioteca</a>
            </div>
            <nav>
                <a href="dashboard_admin.php" class="sidebar-link <?php echo ($pagina_actual == 'dashboard_admin.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="aprobar_usuarios.php" class="sidebar-link <?php echo ($pagina_actual == 'aprobar_usuarios.php') ? 'active' : ''; ?>"><i class="fas fa-user-check"></i> Aprobar Usuarios</a>
                <a href="gestionar_usuarios.php" class="sidebar-link <?php echo ($pagina_actual == 'gestionar_usuarios.php') ? 'active' : ''; ?>"><i class="fas fa-users-cog"></i> Gestionar Usuarios</a>
                <a href="gestionar_categorias.php" class="sidebar-link <?php echo ($pagina_actual == 'gestionar_categorias.php') ? 'active' : ''; ?>"><i class="fas fa-tags"></i> Gestionar Categorías</a>
                <a href="upload_libro.php" class="sidebar-link <?php echo ($pagina_actual == 'upload_libro.php') ? 'active' : ''; ?>"><i class="fas fa-book-medical"></i> Subir Libro</a>
                <a href="gestionar_libros.php" class="sidebar-link <?php echo ($pagina_actual == 'gestionar_libros.php') ? 'active' : ''; ?>"><i class="fas fa-book-reader"></i> Gestionar Libros</a>
                <a href="reportes_generales.php" class="sidebar-link <?php echo ($pagina_actual == 'reportes_generales.php') ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> Reportes Generales</a>
                <a href="reportes_especificos.php" class="sidebar-link <?php echo ($pagina_actual == 'reportes_especificos.php') ? 'active' : ''; ?>"><i class="fas fa-search-plus"></i> Reportes Específicos</a>
                <a href="../catalogo.php" class="sidebar-link" target="_blank"><i class="fas fa-search"></i> Ver Catálogo</a>
            </nav>
            <div class="mt-auto pt-2"> <!-- Contenedor para el botón de cerrar sesión -->
                <a href="../logout.php" class="sidebar-link bg-red-500 hover:bg-red-600 text-white"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
        </aside>
        <div id="sidebarOverlay" class="sidebar-overlay md:hidden"></div>

        <main class="flex-1 p-6 md:p-10 overflow-y-auto">
            <header class="flex justify-between items-center mb-8">
                <button id="mobileMenuButton" class="md:hidden text-gray-600 focus:outline-none">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
                <h1 class="text-3xl font-bold text-gray-800">Seleccionar Tipo de Reporte</h1>
                <div class="text-right">
                    <p class="text-gray-600">Admin: <span class="font-semibold"><?php echo $admin_nombre; ?></span></p>
                </div>
            </header>

            <div class="bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold text-gray-700 mb-6">Opciones de Reportes</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <a href="reportes_generales.php" class="block bg-indigo-500 hover:bg-indigo-600 text-white p-6 rounded-lg shadow transition-shadow text-center">
                        <i class="fas fa-chart-bar fa-3x mb-3"></i>
                        <p class="text-xl font-semibold">Reportes Generales</p>
                        <p class="text-sm">Visión general del rendimiento, KPIs, fuentes de tráfico y perfil de la audiencia.</p>
                    </a>
                    <a href="reportes_especificos.php" class="block bg-green-500 hover:bg-green-600 text-white p-6 rounded-lg shadow transition-shadow text-center">
                        <i class="fas fa-search-plus fa-3x mb-3"></i>
                        <p class="text-xl font-semibold">Reportes Específicos</p>
                        <p class="text-sm">Análisis de páginas, comportamiento del usuario, campañas y optimización de la experiencia.</p>
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
            mobileMenuButton.addEventListener('click', () => { sidebar.classList.toggle('open'); });
            sidebarOverlay.addEventListener('click', () => { sidebar.classList.remove('open'); });
        }
    </script>
    <footer class="bg-gray-800 text-white py-8 mt-12"> <!-- Ajustado para que sea similar al de index -->
        <div class="container mx-auto px-6 text-center">
            <p>&copy; <?php echo date("Y"); ?> Biblioteca Virtual Abajo Cadenas. Todos los derechos reservados.</p>
            <p class="text-sm">Desarrollado con <i class="fas fa-heart text-red-500"></i> y PHP.</p>
        </div>
    </footer>
</body>
</html>