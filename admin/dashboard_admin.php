<?php
session_start();

// 1. Verificar si el usuario está realmente logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logueado, redirigir a la página de inicio (index.php)
    header("location: ../index.php?error_unauthorized=true_from_admin_dash");
    exit;
}

// 2. Verificar si el usuario tiene el rol correcto para acceder a este panel
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'administrador') {
    // Si está logueado pero no es 'administrador':
    // Opción A: Redirigir al dashboard.php general, que decidirá qué hacer (podría ser a otro panel o cerrar sesión).
    // header("location: ../dashboard.php");
    // exit;

    // Opción B (más segura si se considera un intento de acceso no autorizado a este panel específico):
    // Cerrar la sesión y redirigir a index.php con un mensaje.
    session_unset();
    session_destroy();
    header("location: ../index.php?error_role=unauthorized_panel_access_admin");
    exit;
}

require_once '../db_config.php'; // Conexión a la base de datos

$admin_nombre = htmlspecialchars($_SESSION["nombre_completo"] ?? $_SESSION["username"]);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Biblioteca Virtual</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar {
            width: 260px;
            transition: transform 0.3s ease-in-out;
            height: 100%;
            background-color: #4F46E5; /* indigo-600 */
            transition: transform 0.3s ease-in-out;
        }
        .sidebar.active {
            transform: translateX(0);
        }
        .sidebar-content {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .sidebar-logo {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: white;
            text-align: center;
        }
        .sidebar-link {
            display: block;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .sidebar-link:hover, .sidebar-link.active {
            background-color: #4F46E5; /* indigo-600 */
            color: white;
        }
        .sidebar-link i {
            margin-right: 0.75rem;
        }
        /* Mobile sidebar */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                height: 100%;
                z-index: 40; /* Ensure sidebar is above content but below overlay */
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0,0,0,0.5);
                z-index: 30; /* Below sidebar */
                display: none; /* Hidden by default */
            }
            .sidebar.open + .sidebar-overlay {
                display: block; /* Show overlay when sidebar is open */
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <aside id="sidebar" class="sidebar bg-gray-800 text-gray-300 p-4 flex flex-col fixed md:static h-full z-40">
            <div class="text-center py-4">
                <a href="dashboard_admin.php" class="inline-block">
                    <img src="../logo.png" alt="Logo Biblioteca Abajo Cadenas" class="h-16 mx-auto mb-2">
                    <span class="text-xl font-bold text-white">U.E. Abajo Cadenas</span>
                </a>
            </div>
            <nav class="flex-grow space-y-2">
                <a href="dashboard_admin.php" class="sidebar-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="aprobar_usuarios.php" class="sidebar-link"><i class="fas fa-user-check"></i> Aprobar Usuarios</a>
                <a href="gestionar_usuarios.php" class="sidebar-link"><i class="fas fa-users-cog"></i> Gestionar Usuarios</a>
                <a href="gestionar_categorias.php" class="sidebar-link"><i class="fas fa-tags"></i> Gestionar Categorías</a>
                <a href="upload_libro.php" class="sidebar-link"><i class="fas fa-book-medical"></i> Subir Libro</a>
                <a href="gestionar_libros.php" class="sidebar-link"><i class="fas fa-book-reader"></i> Gestionar Libros</a>
                <a href="reportes_opciones.php" class="sidebar-link"><i class="fas fa-file-alt"></i> Reportes</a>
                <a href="../catalogo.php" class="sidebar-link text-gray-300" target="_blank"><i class="fas fa-search"></i> Ver Catálogo</a>
                <a href="../logout.php" class="sidebar-link bg-red-500 hover:bg-red-600 text-white"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </nav>
            <!--<div class="mt-auto pt-2"> <!-- pt-2 para un pequeño espacio arriba del botón si es necesario 
                
            </div>/-->
        </aside>
        <div id="sidebarOverlay" class="sidebar-overlay md:hidden"></div>


        <main class="flex-1 p-6 md:p-10 overflow-y-auto">
            <header class="flex justify-between items-center mb-8">
                <button id="mobileMenuButton" class="md:hidden text-gray-600 focus:outline-none">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
                <h1 class="text-3xl font-bold text-gray-800">Panel de Administración</h1>
                <div class="text-right">
                    <p class="text-gray-600">Bienvenido, <span class="font-semibold"><?php echo $admin_nombre; ?></span></p>
                    <p class="text-sm text-gray-500">Rol: Administrador</p>
                </div>
            </header>

            <div class="bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold text-gray-700 mb-6">Resumen General</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-indigo-500 text-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
                        <div class="flex items-center">
                            <i class="fas fa-users fa-3x mr-4"></i>
                            <div>
                                <p class="text-4xl font-bold">
                                    <?php
                                    // Contar usuarios totales (ejemplo)
                                    $sql_total_users = "SELECT COUNT(id) as total FROM usuarios";
                                    $result_total = $mysqli->query($sql_total_users);
                                    $total_users = $result_total->fetch_assoc()['total'] ?? 0;
                                    echo $total_users;
                                    ?>
                                </p>
                                <p>Usuarios Registrados</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-green-500 text-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
                        <div class="flex items-center">
                            <i class="fas fa-user-clock fa-3x mr-4"></i>
                            <div>
                                <p class="text-4xl font-bold">
                                     <?php
                                    // Contar usuarios pendientes (ejemplo)
                                    $sql_pending_users = "SELECT COUNT(id) as total_pending FROM usuarios WHERE estado_aprobacion = 'pendiente'";
                                    $result_pending = $mysqli->query($sql_pending_users);
                                    $pending_users = $result_pending->fetch_assoc()['total_pending'] ?? 0;
                                    echo $pending_users;
                                    ?>
                                </p>
                                <p>Usuarios Pendientes</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-yellow-500 text-white p-6 rounded-lg shadow hover:shadow-lg transition-shadow">
                        <div class="flex items-center">
                            <i class="fas fa-book fa-3x mr-4"></i>
                            <div>
                                <p class="text-4xl font-bold">
                                    <?php
                                    // Contar libros totales
                                    $sql_total_libros = "SELECT COUNT(id) as total_libros FROM libros";
                                    $result_total_libros = $mysqli->query($sql_total_libros);
                                    $total_libros = $result_total_libros->fetch_assoc()['total_libros'] ?? 0;
                                    echo $total_libros;
                                    ?>
                                </p> <p>Libros en Catálogo</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">Accesos Rápidos</h3>
                    <div class="space-y-3">
                        <a href="aprobar_usuarios.php" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors mr-2">
                            <i class="fas fa-user-check mr-2"></i>Aprobar Usuarios
                        </a>
                        <a href="upload_libro.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                            <i class="fas fa-plus-circle mr-2"></i>Añadir Nuevo Libro
                        </a>
                        </div>
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
            sidebarOverlay.addEventListener('click', () => { // Close sidebar when overlay is clicked
                sidebar.classList.remove('open');
            });
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
