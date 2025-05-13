<?php
session_start();
require_once '../db_config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["role"]) || strtolower($_SESSION["role"]) !== 'estudiante') {
    header("location: ../index.php?error_unauthorized=true_from_favoritos");
    exit;
}

$estudiante_id = $_SESSION['user_id'];
$estudiante_nombre = htmlspecialchars($_SESSION["nombre_completo"] ?? $_SESSION["username"]);
$pagina_actual = basename($_SERVER['PHP_SELF']);

$favoritos = [];
$sql_favoritos = "SELECT l.id, l.titulo, l.autor, l.ruta_portada_img, l.descripcion, c.nombre as nombre_categoria, f.fecha_agregado
                  FROM libros l
                  JOIN favoritos f ON l.id = f.libro_id
                  LEFT JOIN categorias c ON l.categoria_id = c.id
                  WHERE f.usuario_id = ?
                  ORDER BY f.fecha_agregado DESC";

if ($stmt = $mysqli->prepare($sql_favoritos)) {
    $stmt->bind_param("i", $estudiante_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $favoritos[] = $row;
    }
    $stmt->close();
} else {
    error_log("Error al preparar consulta de favoritos: " . $mysqli->error);
}

$mensaje_pagina = '';
$mensaje_pagina_tipo = '';
if (isset($_SESSION['mensaje_detalle'])) {
    $mensaje_pagina = $_SESSION['mensaje_detalle'];
    $mensaje_pagina_tipo = $_SESSION['mensaje_detalle_tipo'];
    unset($_SESSION['mensaje_detalle'], $_SESSION['mensaje_detalle_tipo']);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Libros Favoritos - Biblioteca Virtual</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { width: 260px; transition: transform 0.3s ease-in-out; background-color: #1F2937; }
        .sidebar-link { display: block; padding: 0.75rem 1.5rem; border-radius: 0.375rem; transition: background-color 0.2s ease, color 0.2s ease; color: #D1D5DB; }
        .sidebar-link:hover, .sidebar-link.active { background-color: #374151; color: white; }
        .sidebar-link i { margin-right: 0.75rem; }
        .book-card { display: flex; flex-direction: column; height: 100%; }
        .book-card-content { flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .book-cover-catalog { width: 100%; height: 280px; object-fit: cover; border-radius: 0.25rem; }
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
                <h1 class="text-3xl font-bold text-gray-800">Mis Libros Favoritos</h1>
                 <div class="text-right">
                    <p class="text-gray-600">Estudiante: <span class="font-semibold"><?php echo $estudiante_nombre; ?></span></p>
                </div>
            </header>

            <?php if (!empty($mensaje_pagina)): ?>
            <div class="mb-4 p-4 rounded-md <?php echo $mensaje_pagina_tipo === 'success' ? 'bg-green-100 text-green-700' : ($mensaje_pagina_tipo === 'info' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'); ?>">
                <?php echo htmlspecialchars($mensaje_pagina); ?>
            </div>
            <?php endif; ?>

            <div class="bg-white p-8 rounded-lg shadow-md">
                <?php if (empty($favoritos)): ?>
                    <p class="text-gray-600 text-center py-10">Aún no has añadido ningún libro a tus favoritos. <a href="../catalogo.php" class="text-indigo-600 hover:underline">Explora el catálogo</a>.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        <?php foreach ($favoritos as $libro): ?>
                            <div class="book-card bg-white rounded-lg shadow-lg overflow-hidden">
                                <a href="../detalle_libro.php?id=<?php echo $libro['id']; ?>">
                                    <img src="<?php echo !empty($libro['ruta_portada_img']) ? htmlspecialchars($libro['ruta_portada_img']) : '../cover.png'; ?>" 
                                         alt="Portada de <?php echo htmlspecialchars($libro['titulo']); ?>" class="book-cover-catalog rounded-t-lg"
                                         onerror="this.onerror=null; this.src='../cover.png';">
                                </a>
                                <div class="p-4 book-card-content">
                                    <div>
                                        <h3 class="text-md font-semibold text-indigo-700 mb-1 truncate" title="<?php echo htmlspecialchars($libro['titulo']); ?>"><?php echo htmlspecialchars($libro['titulo']); ?></h3>
                                        <p class="text-xs text-gray-600 mb-1 truncate">Por: <?php echo htmlspecialchars($libro['autor']); ?></p>
                                        <p class="text-xs text-gray-500">Agregado: <?php echo htmlspecialchars(date("d/m/Y", strtotime($libro['fecha_agregado']))); ?></p>
                                    </div>
                                    <div class="mt-3 space-y-2">
                                        <form action="../procesar_favorito.php" method="POST">
                                            <input type="hidden" name="libro_id" value="<?php echo $libro['id']; ?>">
                                            <input type="hidden" name="action" value="remover">
                                            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                            <button type="submit" class="w-full text-xs bg-red-100 hover:bg-red-200 text-red-700 font-semibold py-1 px-2 rounded-md transition duration-150 ease-in-out"><i class="fas fa-heart-broken mr-1"></i>Quitar Favorito</button>
                                        </form>
                                        <a href="../detalle_libro.php?id=<?php echo $libro['id']; ?>" class="block w-full text-center bg-indigo-500 hover:bg-indigo-600 text-white font-medium py-1.5 px-3 rounded-md text-xs transition-colors">Ver Detalles</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; <?php echo date("Y"); ?> Biblioteca Virtual Abajo Cadenas. Todos los derechos reservados.</p>
            <p class="text-sm">Desarrollado con <i class="fas fa-heart text-red-500"></i> y PHP.</p>
        </div>
    </footer>
</body>
</html>
<?php $mysqli->close(); ?>
