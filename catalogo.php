<?php
// catalogo.php
session_start();
require_once 'db_config.php'; // Conexión a la base de datos

// Crear tabla de libros si no existe (SOLO PARA FACILITAR LA PRIMERA EJECUCIÓN)
// En un entorno de producción, esto se haría mediante migraciones.
$sql_create_libros_table = "
CREATE TABLE IF NOT EXISTS libros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    autor VARCHAR(255) NOT NULL,
    isbn VARCHAR(20) UNIQUE, 
    editorial VARCHAR(100),
    ano_publicacion YEAR,
    descripcion TEXT,
    ruta_portada_img VARCHAR(255), 
    categoria_id INT,
    cantidad_disponible INT DEFAULT 1,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    visualizaciones INT DEFAULT 0,
    descargas INT DEFAULT 0,
    subido_por_usuario_id INT,
    CONSTRAINT fk_categoria_libros FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    CONSTRAINT fk_usuario_libros FOREIGN KEY (subido_por_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);";
if (!$mysqli->query($sql_create_libros_table)) {
    // error_log("Error al crear tabla libros: " . $mysqli->error);
}


// --- Variables para búsqueda y filtrado ---
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoria_filter = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;

// --- Paginación ---
$libros_por_pagina = 12;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $libros_por_pagina;

// --- Construcción de la consulta SQL ---
$sql_base = "SELECT l.id, l.titulo, l.autor, l.descripcion, l.ruta_portada_img, c.nombre as nombre_categoria 
             FROM libros l 
             LEFT JOIN categorias c ON l.categoria_id = c.id";
$where_clauses = [];
$params = [];
$types = "";

if (!empty($search_query)) {
    $where_clauses[] = "(l.titulo LIKE ? OR l.autor LIKE ? OR l.editorial LIKE ?)";
    $search_param = "%" . $search_query . "%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= "sss";
}

if ($categoria_filter > 0) {
    $where_clauses[] = "l.categoria_id = ?";
    $params[] = $categoria_filter;
    $types .= "i";
}

$sql_libros = $sql_base;
if (!empty($where_clauses)) {
    $sql_libros .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql_libros .= " ORDER BY l.titulo ASC";

// --- Contar total de libros para paginación (con los mismos filtros) ---
$sql_count = "SELECT COUNT(*) as total FROM libros l";
if (!empty($where_clauses)) {
    $sql_count .= " WHERE " . implode(" AND ", $where_clauses);
}

if ($stmt_count = $mysqli->prepare($sql_count)) {
    if (!empty($params)) { 
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $resultado_count = $stmt_count->get_result();
    $total_libros = $resultado_count->fetch_assoc()['total'];
    $stmt_count->close();
} else {
    $total_libros = 0;
}
$total_paginas = ceil($total_libros / $libros_por_pagina);


// --- Añadir LIMIT y OFFSET para la consulta principal ---
$sql_libros_paginado = $sql_libros . " LIMIT ? OFFSET ?";
$params_paginado = $params; 
$params_paginado[] = $libros_por_pagina;
$params_paginado[] = $offset;
$types_paginado = $types . "ii";

// Obtener IDs de libros favoritos del estudiante actual (si está logueado y es estudiante)
$libros_favoritos_ids = [];
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["role"]) && strtolower($_SESSION["role"]) === 'estudiante' && isset($_SESSION['user_id'])) {
    $usuario_id_actual = $_SESSION['user_id'];
    $sql_get_favoritos = "SELECT libro_id FROM favoritos WHERE usuario_id = ?";
    if ($stmt_get_fav = $mysqli->prepare($sql_get_favoritos)) {
        $stmt_get_fav->bind_param("i", $usuario_id_actual);
        $stmt_get_fav->execute();
        $result_get_fav = $stmt_get_fav->get_result();
        while ($fav_row = $result_get_fav->fetch_assoc()) {
            $libros_favoritos_ids[] = $fav_row['libro_id'];
        }
        $stmt_get_fav->close();
    }
}


if ($stmt_libros = $mysqli->prepare($sql_libros_paginado)) {
    if (!empty($params_paginado)) {
        $stmt_libros->bind_param($types_paginado, ...$params_paginado);
    }
    $stmt_libros->execute();
    $resultado_libros = $stmt_libros->get_result();
} else {
    $resultado_libros = false;
}


// Obtener todas las categorías para el filtro
$sql_categorias = "SELECT id, nombre FROM categorias ORDER BY nombre ASC";
$resultado_categorias = $mysqli->query($sql_categorias);

// Para la barra de navegación, determinar el dashboard link
$dashboard_link = "index.php"; 
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["role"])) {
    if (strtolower($_SESSION["role"]) == 'administrador') $dashboard_link = "admin/dashboard_admin.php";
    elseif (strtolower($_SESSION["role"]) == 'docente') $dashboard_link = "docente/dashboard_docente.php";
    elseif (strtolower($_SESSION["role"]) == 'estudiante') $dashboard_link = "estudiante/dashboard_estudiante.php";
    else $dashboard_link = "dashboard.php"; 
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Libros - Biblioteca Virtual</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .book-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%; 
            display: flex;
            flex-direction: column;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .book-cover, .book-cover-catalog { 
            width: 100%;
            height: 280px; 
            object-fit: cover; 
            border-bottom: 1px solid #e5e7eb; 
        }
        .book-card-content {
            flex-grow: 1; 
            display: flex;
            flex-direction: column;
            justify-content: space-between; 
        }
         .message-box {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        .message-box.error {
            background-color: #FEE2E2; /* red-100 */
            color: #B91C1C; /* red-700 */
            border: 1px solid #FCA5A5; /* red-300 */
        }
         .message-box.success {
            background-color: #D1FAE5; /* green-100 */
            color: #065F46; /* green-700 */
            border: 1px solid #6EE7B7; /* green-300 */
        }
        .message-box.info {
            background-color: #DBEAFE; /* blue-100 */
            color: #1E40AF; /* blue-700 */
            border: 1px solid #93C5FD; /* blue-300 */
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a href="index.php" class="flex items-center text-xl font-bold text-indigo-600">
                <img src="logo.png" alt="Logo Biblioteca Abajo Cadenas" class="h-10 mr-3">
                <span>Biblioteca Virtual Abajo Cadenas</span>
            </a>
            <div class="hidden md:flex space-x-4 items-center">
                <a href="index.php" class="text-gray-600 hover:text-indigo-600">Inicio</a>
                <a href="catalogo.php" class="text-indigo-600 font-semibold border-b-2 border-indigo-500">Catálogo</a>
                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["role"])): ?>
                    <a href="<?php echo $dashboard_link; ?>" class="text-gray-600 hover:text-indigo-600">Mi Panel</a>
                    <a href="logout.php" class="bg-red-500 text-white px-3 py-2 rounded-md hover:bg-red-600 text-sm">Cerrar Sesión</a>
                <?php else: ?>
                    <a href="index.php#acerca" class="text-gray-600 hover:text-indigo-600">Acerca de</a>
                    <a href="index.php#contacto" class="text-gray-600 hover:text-indigo-600">Contáctanos</a>
                    <a href="registro.php" class="text-gray-600 hover:text-indigo-600">Registrarse</a>
                    <a href="index.php#loginSection" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Iniciar Sesión</a>
                <?php endif; ?>
            </div>
            <div class="md:hidden">
                <button id="mobileMenuButton" class="text-gray-600 focus:outline-none">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
            </div>
        </div>
        <div id="mobileMenu" class="md:hidden hidden bg-white shadow-lg">
            <a href="index.php" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Inicio</a>
            <a href="catalogo.php" class="block px-6 py-3 text-indigo-600 bg-indigo-50 font-semibold">Catálogo</a>
             <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["role"])): ?>
                <a href="<?php echo $dashboard_link; ?>" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Mi Panel</a>
                <a href="logout.php" class="block px-6 py-3 text-red-500 hover:bg-red-100">Cerrar Sesión</a>
            <?php else: ?>
                <a href="index.php#acerca" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Acerca de</a>
                <a href="index.php#contacto" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Contáctanos</a>
                <a href="registro.php" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Registrarse</a>
                <a href="index.php#loginSection" class="block px-6 py-3 text-white bg-indigo-600 hover:bg-indigo-700">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </nav>

    <?php if (isset($_SESSION['mensaje_detalle'])): ?>
        <div class="container mx-auto px-6 mt-4">
            <div class="message-box <?php echo $_SESSION['mensaje_detalle_tipo'] === 'success' ? 'success' : ($_SESSION['mensaje_detalle_tipo'] === 'info' ? 'info' : 'error'); ?>">
                <?php echo htmlspecialchars($_SESSION['mensaje_detalle']); ?>
            </div>
        </div>
        <?php unset($_SESSION['mensaje_detalle'], $_SESSION['mensaje_detalle_tipo']); ?>
    <?php endif; ?>

    <header class="bg-indigo-600 text-white py-12">
        <div class="container mx-auto px-6 text-center">
            <h1 class="text-4xl font-bold mb-4">Nuestro Catálogo de Libros</h1>
            <p class="text-lg mb-6">Explora nuestra colección y encuentra tu próxima lectura.</p>
            <form action="catalogo.php" method="GET" class="max-w-2xl mx-auto bg-white p-4 rounded-lg shadow-xl flex flex-col sm:flex-row gap-3">
                <input type="search" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Buscar por título, autor, editorial..." class="w-full sm:flex-grow px-4 py-2 text-gray-700 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <select name="categoria_id" class="w-full sm:w-auto px-4 py-2 text-gray-700 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="0">Todas las Categorías</option>
                    <?php if ($resultado_categorias && $resultado_categorias->num_rows > 0): ?>
                        <?php while($cat = $resultado_categorias->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($categoria_filter == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                         <?php $resultado_categorias->data_seek(0); ?>
                    <?php endif; ?>
                </select>
                <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white px-6 py-2 rounded-md transition-colors w-full sm:w-auto">
                    <i class="fas fa-search mr-1"></i> Buscar
                </button>
            </form>
        </div>
    </header>

    <main class="container mx-auto px-6 py-12">
        <?php if (!empty($search_query) || $categoria_filter > 0): ?>
            <div class="mb-6 text-center">
                <p class="text-gray-700">
                    Mostrando resultados para:
                    <?php if (!empty($search_query)): ?>
                        <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong>
                    <?php endif; ?>
                    <?php if ($categoria_filter > 0 && $resultado_categorias && $resultado_categorias->num_rows > 0): ?>
                        <?php
                        $nombre_cat_filtrada = "Categoría Desconocida";
                        $temp_categorias = [];
                        if ($resultado_categorias) $resultado_categorias->data_seek(0); 
                        while($cat_temp = $resultado_categorias->fetch_assoc()){ $temp_categorias[$cat_temp['id']] = $cat_temp['nombre']; }
                        if(isset($temp_categorias[$categoria_filter])) $nombre_cat_filtrada = htmlspecialchars($temp_categorias[$categoria_filter]);
                        if ($resultado_categorias) $resultado_categorias->data_seek(0); 
                        ?>
                        en la categoría <strong><?php echo $nombre_cat_filtrada; ?></strong>
                    <?php endif; ?>
                    <a href="catalogo.php" class="ml-2 text-sm text-indigo-600 hover:underline">(Limpiar filtros)</a>
                </p>
                <p class="text-sm text-gray-500"><?php echo $total_libros; ?> libro(s) encontrado(s).</p>
            </div>
        <?php endif; ?>

        <?php if ($resultado_libros && $resultado_libros->num_rows > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                <?php while($libro = $resultado_libros->fetch_assoc()): ?>
                <div class="book-card bg-white rounded-lg shadow-lg overflow-hidden">
                    <a href="detalle_libro.php?id=<?php echo $libro['id']; ?>">
                        <img src="<?php echo !empty($libro['ruta_portada_img']) ? htmlspecialchars($libro['ruta_portada_img']) : 'cover.png'; ?>" 
                             alt="Portada de <?php echo htmlspecialchars($libro['titulo']); ?>" class="book-cover-catalog rounded-t-lg"
                             onerror="this.onerror=null; this.src='cover.png';">
                    </a>
                    <div class="p-5 book-card-content">
                        <div>
                            <h3 class="text-lg font-semibold text-indigo-700 mb-1 truncate" title="<?php echo htmlspecialchars($libro['titulo']); ?>">
                                <a href="detalle_libro.php?id=<?php echo $libro['id']; ?>" class="hover:underline">
                                    <?php echo htmlspecialchars($libro['titulo']); ?>
                                </a>
                            </h3>
                            <p class="text-sm text-gray-600 mb-1">Por: <?php echo htmlspecialchars($libro['autor']); ?></p>
                            <?php if(!empty($libro['nombre_categoria'])): ?>
                            <p class="text-xs text-gray-500 mb-2">
                                <i class="fas fa-tag mr-1"></i><?php echo htmlspecialchars($libro['nombre_categoria']); ?>
                            </p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500 mb-3 truncate" title="<?php echo htmlspecialchars($libro['descripcion']); ?>">
                                <?php echo htmlspecialchars(substr($libro['descripcion'] ?? '', 0, 70)) . (strlen($libro['descripcion'] ?? '') > 70 ? '...' : ''); ?>
                            </p>
                        </div>
                        <div class="mt-auto">
                            <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["role"]) && strtolower($_SESSION["role"]) === 'estudiante'): ?>
                                <?php $es_favorito_actual = in_array($libro['id'], $libros_favoritos_ids); ?>
                                <form action="procesar_favorito.php" method="POST" class="mb-2">
                                    <input type="hidden" name="libro_id" value="<?php echo $libro['id']; ?>">
                                    <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                    <?php if ($es_favorito_actual): ?>
                                        <input type="hidden" name="action" value="remover">
                                        <button type="submit" class="w-full text-xs bg-red-100 hover:bg-red-200 text-red-700 font-semibold py-1 px-2 rounded-md transition duration-150 ease-in-out">
                                            <i class="fas fa-heart-broken mr-1"></i>Quitar Favorito
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="agregar">
                                        <button type="submit" class="w-full text-xs bg-pink-100 hover:bg-pink-200 text-pink-700 font-semibold py-1 px-2 rounded-md transition duration-150 ease-in-out">
                                            <i class="fas fa-heart mr-1"></i>Añadir Favorito
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                            <a href="detalle_libro.php?id=<?php echo $libro['id']; ?>" 
                               class="block w-full text-center bg-indigo-500 hover:bg-indigo-600 text-white font-medium py-2 px-4 rounded-md text-sm transition-colors">
                               Ver Detalles
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <?php if ($total_paginas > 1): ?>
            <nav class="mt-12 flex justify-center" aria-label="Paginación">
                <ul class="inline-flex items-center -space-x-px">
                    <?php // Botón Anterior ?>
                    <li>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])); ?>"
                           class="py-2 px-3 ml-0 leading-tight text-gray-500 bg-white rounded-l-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 <?php if($pagina_actual <= 1){ echo ' pointer-events-none opacity-50'; } ?>">
                            <span class="sr-only">Anterior</span>
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>

                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <?php
                        $mostrar_pagina = false;
                        if ($total_paginas <= 7) { 
                            $mostrar_pagina = true;
                        } else {
                            if ($i == 1 || $i == $total_paginas || ($i >= $pagina_actual - 1 && $i <= $pagina_actual + 1)) {
                                $mostrar_pagina = true;
                            } elseif (($pagina_actual <= 3 && $i <= 4) || ($pagina_actual >= $total_paginas - 2 && $i >= $total_paginas - 3)) {
                                 $mostrar_pagina = true; 
                            } elseif (($i == $pagina_actual - 2 && $pagina_actual - 3 > 1) || ($i == $pagina_actual + 2 && $pagina_actual + 3 < $total_paginas)) {
                                echo '<li><span class="py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
                            }
                        }
                    ?>
                    <?php if ($mostrar_pagina): ?>
                    <li>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>"
                           class="py-2 px-3 leading-tight border border-gray-300 <?php echo ($i == $pagina_actual) ? 'text-indigo-600 bg-indigo-50 hover:bg-indigo-100 hover:text-indigo-700 z-10' : 'text-gray-500 bg-white hover:bg-gray-100 hover:text-gray-700'; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php // Botón Siguiente ?>
                    <li>
                         <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])); ?>"
                           class="py-2 px-3 leading-tight text-gray-500 bg-white rounded-r-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 <?php if($pagina_actual >= $total_paginas){ echo ' pointer-events-none opacity-50'; } ?>">
                            <span class="sr-only">Siguiente</span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-10">
                <i class="fas fa-book-reader fa-4x text-gray-400 mb-4"></i>
                <p class="text-xl text-gray-600">No se encontraron libros que coincidan con tu búsqueda.</p>
                <p class="text-gray-500 mt-2">Intenta con otros términos o revisa las categorías.</p>
                <?php if (isset($_SESSION["role"]) && strtolower($_SESSION["role"]) == 'administrador'): ?>
                    <p class="mt-4">
                        <a href="admin/upload_libro.php" class="text-indigo-600 hover:underline">
                            <i class="fas fa-plus-circle mr-1"></i>Añadir nuevos libros al catálogo
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; <?php echo date("Y"); ?> Biblioteca Virtual Abajo Cadenas. Todos los derechos reservados.</p>
            <p class="text-sm">Desarrollado con <i class="fas fa-heart text-red-500"></i> y PHP.</p>
        </div>
    </footer>

    <script>
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const mobileMenu = document.getElementById('mobileMenu');

        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }
    </script>

</body>
</html>
<?php
if ($resultado_libros && $stmt_libros) @$stmt_libros->close(); 
if ($resultado_categorias) @$resultado_categorias->data_seek(0); 
$mysqli->close();
?>
