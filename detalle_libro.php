<?php
// detalle_libro.php
session_start();
require_once 'db_config.php'; // Conexión a la base de datos

$libro_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$libro = null;
$mensaje_error = '';
$mensaje_detalle = '';
$mensaje_detalle_tipo = '';

if (isset($_SESSION['mensaje_detalle'])) {
    $mensaje_detalle = $_SESSION['mensaje_detalle'];
    $mensaje_detalle_tipo = $_SESSION['mensaje_detalle_tipo'];
    unset($_SESSION['mensaje_detalle'], $_SESSION['mensaje_detalle_tipo']);
}

if ($libro_id > 0) {
    $sql = "SELECT l.*, c.nombre as nombre_categoria 
            FROM libros l 
            LEFT JOIN categorias c ON l.categoria_id = c.id 
            WHERE l.id = ?";
    
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $libro_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($resultado->num_rows === 1) {
            $libro = $resultado->fetch_assoc();
        } else {
            $mensaje_error = "Libro no encontrado.";
        }

        if ($libro) {
            // Incrementar el contador de visualizaciones
            $update_views_sql = "UPDATE libros SET visualizaciones = visualizaciones + 1 WHERE id = ?";
            if ($stmt_views = $mysqli->prepare($update_views_sql)) {
                $stmt_views->bind_param("i", $libro_id);
                $stmt_views->execute();
                $stmt_views->close();
            }

            // Registrar vista si es estudiante
            if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["role"]) && strtolower($_SESSION["role"]) === 'estudiante' && isset($_SESSION['user_id'])) {
                $usuario_id_actual_vista = $_SESSION['user_id'];
                // Inserta una nueva fila en vistas_recientes o actualiza la fecha_vista si ya existe.
                // Esto asume que la tabla 'vistas_recientes' tiene una UNIQUE KEY en (usuario_id, libro_id)
                // y una columna 'fecha_vista' de tipo TIMESTAMP o DATETIME.
                $sql_registrar_vista = "INSERT INTO vistas_recientes (usuario_id, libro_id, fecha_vista) VALUES (?, ?, NOW())
                                        ON DUPLICATE KEY UPDATE fecha_vista = NOW()";
                if ($stmt_vista = $mysqli->prepare($sql_registrar_vista)) {
                    $stmt_vista->bind_param("ii", $usuario_id_actual_vista, $libro_id);
                    if (!$stmt_vista->execute()) {
                        error_log("Error al registrar/actualizar vista reciente: " . $stmt_vista->error);
                    }
                    $stmt_vista->close();
                }else {
                    error_log("Error al preparar consulta para registrar vista: " . $mysqli->error);
                }    
            }
        }
        $stmt->close();
    } else {
        $mensaje_error = "Error al preparar la consulta: " . $mysqli->error;
    }
} else {
    $mensaje_error = "No se especificó un ID de libro válido.";
}

// Verificar si el libro actual es favorito para el usuario estudiante logueado
$es_favorito = false;
if ($libro && isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["role"]) && strtolower($_SESSION["role"]) === 'estudiante' && isset($_SESSION['user_id'])) {
    $usuario_id_actual = $_SESSION['user_id'];
    $sql_check_favorito = "SELECT id FROM favoritos WHERE usuario_id = ? AND libro_id = ?";
    if ($stmt_check_fav = $mysqli->prepare($sql_check_favorito)) {
        $stmt_check_fav->bind_param("ii", $usuario_id_actual, $libro_id);
        $stmt_check_fav->execute();
        $stmt_check_fav->store_result();
        if ($stmt_check_fav->num_rows > 0) {
            $es_favorito = true;
        }
        $stmt_check_fav->close();
    }
}

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
    <title><?php echo $libro ? htmlspecialchars($libro['titulo']) : 'Detalle del Libro'; ?> - Biblioteca Virtual</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .book-cover-large {
            max-width: 350px; 
            height: auto;
            border-radius: 0.5rem; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); 
        }
        .detail-section { margin-bottom: 0.75rem; }
        .detail-label { font-weight: 600; color: #4B5563; }
        .detail-value { color: #1F2937; margin-left: 0.5rem; }
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
<body class="bg-gray-100 text-gray-800">

    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a href="index.php" class="flex items-center text-xl font-bold text-indigo-600">
                <img src="logo.png" alt="Logo Biblioteca Abajo Cadenas" class="h-10 mr-3">
                <span>Biblioteca Virtual Abajo Cadenas</span>
            </a>
            <div class="hidden md:flex space-x-4 items-center">
                <a href="index.php" class="text-gray-600 hover:text-indigo-600">Inicio</a>
                <a href="catalogo.php" class="text-gray-600 hover:text-indigo-600">Catálogo</a>
                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["role"])): ?>
                    <a href="<?php echo $dashboard_link; ?>" class="text-gray-600 hover:text-indigo-600">Mi Panel</a>
                    <a href="logout.php" class="bg-red-500 text-white px-3 py-2 rounded-md hover:bg-red-600 text-sm">Cerrar Sesión</a>
                <?php else: ?>
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
            <a href="catalogo.php" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Catálogo</a>
             <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["role"])): ?>
                <a href="<?php echo $dashboard_link; ?>" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Mi Panel</a>
                <a href="logout.php" class="block px-6 py-3 text-red-500 hover:bg-red-100">Cerrar Sesión</a>
            <?php else: ?>
                <a href="registro.php" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Registrarse</a>
                <a href="index.php#loginSection" class="block px-6 py-3 text-white bg-indigo-600 hover:bg-indigo-700">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container mx-auto px-6 py-12">
        <?php if (!empty($mensaje_detalle)): ?>
            <div class="mb-6 message-box <?php echo $mensaje_detalle_tipo === 'success' ? 'success' : ($mensaje_detalle_tipo === 'info' ? 'info' : 'error'); ?>">
                <?php echo htmlspecialchars($mensaje_detalle); ?>
            </div>
        <?php endif; ?>

        <?php if ($libro): ?>
            <div class="bg-white p-6 sm:p-8 rounded-lg shadow-xl">
                <div class="flex flex-col md:flex-row gap-6 md:gap-8">
                    <div class="md:w-1/3 flex justify-center md:justify-start">
                        <img src="<?php echo !empty($libro['ruta_portada_img']) ? htmlspecialchars($libro['ruta_portada_img']) : 'cover.png'; ?>" 
                             alt="Portada de <?php echo htmlspecialchars($libro['titulo']); ?>" 
                             class="book-cover-large"
                             onerror="this.onerror=null; this.src='cover.png';">
                    </div>

                    <div class="md:w-2/3">
                        <h1 class="text-3xl sm:text-4xl font-bold text-indigo-700 mb-3"><?php echo htmlspecialchars($libro['titulo']); ?></h1>
                        <p class="text-xl text-gray-700 mb-1">Por: <?php echo htmlspecialchars($libro['autor']); ?></p>
                        
                        <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["role"]) && strtolower($_SESSION["role"]) === 'estudiante'): ?>
                            <form action="procesar_favorito.php" method="POST" class="my-4">
                                <input type="hidden" name="libro_id" value="<?php echo $libro_id; ?>">
                                <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                <?php if ($es_favorito): ?>
                                    <input type="hidden" name="action" value="remover">
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out">
                                        <i class="fas fa-heart-broken mr-2"></i>Quitar de Favoritos
                                    </button>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="agregar">
                                    <button type="submit" class="bg-pink-500 hover:bg-pink-600 text-white font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out">
                                        <i class="fas fa-heart mr-2"></i>Añadir a Favoritos
                                    </button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>

                        <div class="space-y-3 text-sm sm:text-base mt-4">
                            <?php if (!empty($libro['nombre_categoria'])): ?>
                            <div class="detail-section">
                                <span class="detail-label"><i class="fas fa-tag fa-fw mr-2 text-indigo-500"></i>Categoría:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($libro['nombre_categoria']); ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($libro['editorial'])): ?>
                            <div class="detail-section">
                                <span class="detail-label"><i class="fas fa-building fa-fw mr-2 text-indigo-500"></i>Editorial:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($libro['editorial']); ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($libro['ano_publicacion']) && $libro['ano_publicacion'] != '0000' && $libro['ano_publicacion'] != 0): ?>
                            <div class="detail-section">
                                <span class="detail-label"><i class="fas fa-calendar-alt fa-fw mr-2 text-indigo-500"></i>Año de Publicación:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($libro['ano_publicacion']); ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($libro['isbn'])): ?>
                            <div class="detail-section">
                                <span class="detail-label"><i class="fas fa-barcode fa-fw mr-2 text-indigo-500"></i>ISBN:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($libro['isbn']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="detail-section">
                                <span class="detail-label"><i class="fas fa-layer-group fa-fw mr-2 text-indigo-500"></i>Disponibles:</span>
                                <span class="detail-value font-bold <?php echo ($libro['cantidad_disponible'] > 0) ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo htmlspecialchars($libro['cantidad_disponible']); ?> unidad(es)
                                </span>
                            </div>
                             <div class="detail-section">
                                <span class="detail-label"><i class="fas fa-eye fa-fw mr-2 text-indigo-500"></i>Visto:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($libro['visualizaciones'] ?? 0); ?> veces</span>
                            </div>
                             <div class="detail-section">
                                <span class="detail-label"><i class="fas fa-download fa-fw mr-2 text-indigo-500"></i>Descargado:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($libro['descargas'] ?? 0); ?> veces</span>
                            </div>


                            <?php if (!empty($libro['descripcion'])): ?>
                            <div class="detail-section mt-6">
                                <h2 class="text-xl font-semibold text-gray-700 mb-2">Descripción</h2>
                                <p class="text-gray-600 leading-relaxed whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($libro['descripcion'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-8 flex flex-col sm:flex-row gap-3">
                            <a href="catalogo.php" class="w-full sm:w-auto bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-md text-center transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>Volver al Catálogo
                            </a>
                            <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                                <?php if (!empty($libro['ruta_archivo_pdf'])): ?>
                                    <a href="<?php echo htmlspecialchars($libro['ruta_archivo_pdf']); ?>" target="_blank" class="w-full sm:w-auto bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-md text-center transition-colors">
                                        <i class="fas fa-eye mr-2"></i>Leer Online
                                    </a>
                                    <a href="descargar_libro.php?id=<?php echo $libro['id']; ?>" class="w-full sm:w-auto bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-6 rounded-md text-center transition-colors">
                                        <i class="fas fa-download mr-2"></i>Descargar PDF
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-sm text-red-600 bg-red-100 p-3 rounded-md w-full sm:w-auto text-center">
                                    <i class="fas fa-lock mr-1"></i> Debes <a href="index.php#loginSection" class="font-semibold hover:underline">iniciar sesión</a> para leer o descargar.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-10 bg-white p-8 rounded-lg shadow-xl">
                <i class="fas fa-exclamation-triangle fa-4x text-red-500 mb-4"></i>
                <p class="text-2xl text-gray-700 font-semibold"><?php echo $mensaje_error ? htmlspecialchars($mensaje_error) : 'No se pudo cargar la información del libro.'; ?></p>
                <a href="catalogo.php" class="mt-6 inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-6 rounded-md transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Ir al Catálogo
                </a>
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
$mysqli->close();
?>
