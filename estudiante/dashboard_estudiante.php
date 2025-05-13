<?php
session_start();

// 1. Verificar si el usuario está realmente logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php?error_unauthorized=true_from_estudiante_dash");
    exit;
}

// 2. Verificar si el usuario tiene el rol correcto para acceder a este panel
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'estudiante') {
    // Si no es estudiante, pero está logueado, podría ser admin o docente.
    // Redirigir al dashboard general que decidirá.
    // O, si se quiere ser estricto, cerrar sesión y redirigir a index.
    session_unset();
    session_destroy();
    header("location: ../index.php?error_role=unauthorized_panel_access_estudiante");
    exit;
}

require_once '../db_config.php'; // Conexión a la base de datos

$estudiante_nombre = htmlspecialchars($_SESSION["nombre_completo"] ?? $_SESSION["username"]);
$pagina_actual = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Estudiante - Biblioteca Virtual</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { width: 260px; transition: transform 0.3s ease-in-out; background-color: #1F2937; /* gray-800 */ }
        .sidebar-link { display: block; padding: 0.75rem 1.5rem; border-radius: 0.375rem; transition: background-color 0.2s ease, color 0.2s ease; color: #D1D5DB; /* gray-300 */ }
        .sidebar-link:hover, .sidebar-link.active { background-color: #374151; /* gray-700 */ color: white; }
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
                <a href="dashboard_estudiante.php" class="inline-block">
                    <img src="../logo.png" alt="Logo Biblioteca Abajo Cadenas" class="h-16 mx-auto mb-2">
                    <span class="text-xl font-bold text-white">U.E. Abajo Cadenas</span>
                </a>
            </div>
            <nav class="flex-grow space-y-2">
                <a href="dashboard_estudiante.php" class="sidebar-link <?php echo ($pagina_actual == 'dashboard_estudiante.php') ? 'active' : ''; ?>"><i class="fas fa-home"></i> Inicio</a>
                <a href="../catalogo.php" class="sidebar-link"><i class="fas fa-book-open"></i> Catálogo de Libros</a>
                <a href="favoritos.php" class="sidebar-link <?php echo ($pagina_actual == 'favoritos.php') ? 'active' : ''; ?>"><i class="fas fa-heart"></i> Mis Favoritos</a>
                <a href="ultimos_leidos.php" class="sidebar-link <?php echo ($pagina_actual == 'ultimos_leidos.php') ? 'active' : ''; ?>"><i class="fas fa-history"></i> Últimos Leídos</a>
                <!-- Podríamos añadir un enlace a "Mi Perfil" si se implementa -->
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
                <h1 class="text-3xl font-bold text-gray-800">Panel de Estudiante</h1>
                <div class="text-right">
                    <p class="text-gray-600">Bienvenido, <span class="font-semibold"><?php echo $estudiante_nombre; ?></span></p>
                    <p class="text-sm text-gray-500">Rol: Estudiante</p>
                </div>
            </header>

            <div class="bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold text-gray-700 mb-6">Bienvenido a tu Espacio</h2>
                <p class="text-gray-600 mb-4">
                    Desde aquí puedes acceder al catálogo de libros, ver tus libros favoritos y los últimos que has consultado.
                    ¡Disfruta de la lectura!
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <a href="../catalogo.php" class="block bg-indigo-500 hover:bg-indigo-600 text-white p-6 rounded-lg shadow transition-shadow text-center">
                        <i class="fas fa-book-open fa-3x mb-3"></i>
                        <p class="text-xl font-semibold">Ver Catálogo</p>
                        <p class="text-sm">Explora todos los libros disponibles.</p>
                    </a>
                    <a href="favoritos.php" class="block bg-pink-500 hover:bg-pink-600 text-white p-6 rounded-lg shadow transition-shadow text-center">
                        <i class="fas fa-heart fa-3x mb-3"></i>
                        <p class="text-xl font-semibold">Mis Libros Favoritos</p>
                        <p class="text-sm">Accede a tu colección personal.</p>
                    </a>
                    <a href="ultimos_leidos.php" class="block bg-cyan-500 hover:bg-cyan-600 text-white p-6 rounded-lg shadow transition-shadow text-center">
                        <i class="fas fa-history fa-3x mb-3"></i>
                        <p class="text-xl font-semibold">Últimos Libros Leídos</p>
                        <p class="text-sm">Retoma tus lecturas recientes.</p>
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