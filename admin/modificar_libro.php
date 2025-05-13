<?php
session_start();
require_once '../db_config.php';
require_once 'admin_functions.php'; // Para usar la clase Administrador

// Verificar si el usuario es administrador y está logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php?error_unauthorized=true_from_modificar_libro");
    exit;
}
$user_role = isset($_SESSION["role"]) ? strtolower($_SESSION["role"]) : '';
if (!in_array($user_role, ['administrador', 'docente'])) {
    session_unset(); session_destroy();
    header("location: ../index.php?error_role=unauthorized_panel_access_modificar_libro");
    exit;
}

$administrador = new Administrador($mysqli);
$mensaje = '';
$mensaje_tipo = ''; // 'success' o 'error'
$libro_data = null;
$libro_id_edit = 0;

// Obtener categorías para el dropdown
$sql_categorias = "SELECT id, nombre FROM categorias ORDER BY nombre ASC";
$result_categorias = $mysqli->query($sql_categorias);
$categorias_options = [];
if ($result_categorias) {
    while ($row = $result_categorias->fetch_assoc()) {
        $categorias_options[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
    $libro_id_edit = intval($_GET['id']);
    if ($libro_id_edit > 0) {
        $sql_load = "SELECT * FROM libros WHERE id = ?";
        if ($stmt_load = $mysqli->prepare($sql_load)) {
            $stmt_load->bind_param("i", $libro_id_edit);
            $stmt_load->execute();
            $result_load = $stmt_load->get_result();
            if ($data = $result_load->fetch_assoc()) {
                $libro_data = $data;
            } else {
                $mensaje = "Libro no encontrado.";
                $mensaje_tipo = "error";
            }
            $stmt_load->close();
        } else {
            $mensaje = "Error al preparar la consulta para cargar datos del libro: " . $mysqli->error;
            $mensaje_tipo = "error";
        }
    } else {
        $mensaje = "ID de libro no válido.";
        $mensaje_tipo = "error";
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lógica para procesar el formulario de modificación
    $libro_id_edit = intval($_POST['libro_id']);
    $titulo = trim($_POST['titulo']);
    $autor = trim($_POST['autor']);
    $categoria_id = isset($_POST['categoria_id']) ? intval($_POST['categoria_id']) : 0;
    $editorial = !empty(trim($_POST['editorial'])) ? trim($_POST['editorial']) : null;
    $ano_publicacion_input = trim($_POST['ano_publicacion']);
    $ano_publicacion = !empty($ano_publicacion_input) ? $ano_publicacion_input : null;
    $descripcion_input = trim($_POST['descripcion']);
    $descripcion = !empty($descripcion_input) ? $descripcion_input : null;
    $portada_url_input = trim($_POST['portada_url']);
    $portada_url = !empty($portada_url_input) ? $portada_url_input : null;

    $nuevo_archivo_pdf_data = null;
    // Verificar si se subió un archivo y no es un error de "no se subió archivo"
    if (isset($_FILES['nuevo_archivo_pdf']) && $_FILES['nuevo_archivo_pdf']['error'] != UPLOAD_ERR_NO_FILE) {
        $nuevo_archivo_pdf_data = $_FILES['nuevo_archivo_pdf'];
    }

    // Validaciones básicas del formulario
    if (empty($titulo) || empty($autor) || $categoria_id == 0) {
        $mensaje = "Título, Autor y Categoría son campos obligatorios.";
        $mensaje_tipo = "error";
        $libro_data = $_POST; // Mantener los datos en el formulario
        $libro_data['id'] = $libro_id_edit;
    } else {
        $resultado_modificacion = $administrador->modificarLibro(
            $libro_id_edit,
            $titulo,
            $autor,
            $categoria_id,
            $editorial,
            $ano_publicacion,
            $descripcion,
            $portada_url,
            $nuevo_archivo_pdf_data
        );

        if ($resultado_modificacion === true) {
            $_SESSION['mensaje_gestion_libros'] = "Libro modificado exitosamente.";
            $_SESSION['mensaje_tipo_gestion_libros'] = "success";
            header("Location: gestionar_libros.php");
            exit;
        } else {
            $mensaje = $resultado_modificacion; // Contiene el mensaje de error
            $mensaje_tipo = "error";
            $libro_data = $_POST; // Mantener los datos en el formulario en caso de error
            $libro_data['id'] = $libro_id_edit;
        }
    }

} else {
    // Si no es GET con ID ni POST, redirigir o mostrar error
    if (!isset($_GET['id'])) { // Solo redirigir si no se está intentando cargar un libro
        header("Location: gestionar_libros.php");
        exit;
    }
}

$pagina_actual = basename($_SERVER['PHP_SELF']); // Aunque no hay menú completo, es buena práctica
$clase_activa = 'bg-indigo-500 text-white'; 
$clase_hover = 'text-indigo-100 hover:bg-indigo-600 hover:text-white';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Libro - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .message-box { padding: 1rem; margin-bottom: 1.5rem; border-radius: 0.375rem; font-size: 0.875rem; }
        .message-box.success { background-color: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
        .message-box.error { background-color: #FEE2E2; color: #B91C1C; border: 1px solid #FCA5A5; }
        .message-box.info { background-color: #DBEAFE; color: #1E40AF; border: 1px solid #93C5FD; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">
    <?php
    if ($user_role === 'administrador') {
    ?>
        <nav class="bg-indigo-700 text-white shadow-lg"> <!-- Nav simplificada para admin en esta página -->
            <div class="container mx-auto px-6 py-3 flex justify-between items-center">
                <a href="dashboard_admin.php" class="text-xl font-semibold flex items-center">
                    <img src="../logo.png" alt="Logo" class="h-8 w-auto mr-2"> Panel Administrador
                </a>
                <div>
                    <a href="gestionar_libros.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo $clase_hover; ?>">Gestionar Libros</a>
                    <a href="../logout.php" class="px-3 py-2 rounded-md text-sm font-medium bg-red-500 hover:bg-red-600 text-white">Cerrar Sesión <i class="fas fa-sign-out-alt ml-1"></i></a>
                </div>
            </div>
        </nav>
    <?php
    } elseif ($user_role === 'docente') {
        // Para el docente, usamos la nav completa de docente.
        // Las variables $pagina_actual, $clase_activa, $clase_hover ya están definidas
        include '../docente/docente_nav.php';
    }
    ?>

    <div class="container mx-auto px-4 sm:px-8 py-8">
        <div class="py-4">
            <h1 class="text-3xl font-bold text-gray-800">Modificar Libro</h1>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="message-box <?php echo $mensaje_tipo; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <?php if ($libro_data): ?>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <form action="modificar_libro.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="libro_id" value="<?php echo htmlspecialchars($libro_data['id']); ?>">

                <div>
                    <label for="titulo" class="block text-sm font-medium text-gray-700">Título <span class="text-red-500">*</span></label>
                    <input type="text" name="titulo" id="titulo" value="<?php echo htmlspecialchars($libro_data['titulo'] ?? ''); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="autor" class="block text-sm font-medium text-gray-700">Autor <span class="text-red-500">*</span></label>
                    <input type="text" name="autor" id="autor" value="<?php echo htmlspecialchars($libro_data['autor'] ?? ''); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="categoria_id" class="block text-sm font-medium text-gray-700">Categoría <span class="text-red-500">*</span></label>
                    <select name="categoria_id" id="categoria_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Seleccione una categoría</option>
                        <?php foreach ($categorias_options as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($libro_data['categoria_id']) && $libro_data['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="editorial" class="block text-sm font-medium text-gray-700">Editorial (Opcional)</label>
                    <input type="text" name="editorial" id="editorial" value="<?php echo htmlspecialchars($libro_data['editorial'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="ano_publicacion" class="block text-sm font-medium text-gray-700">Año de Publicación (Opcional)</label>
                    <input type="text" name="ano_publicacion" id="ano_publicacion" value="<?php echo htmlspecialchars($libro_data['ano_publicacion'] ?? ''); ?>" pattern="\d{4}?" title="Ingrese un año de 4 dígitos" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción (Opcional)</label>
                    <textarea name="descripcion" id="descripcion" rows="4" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($libro_data['descripcion'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label for="portada_url" class="block text-sm font-medium text-gray-700">URL de Portada (Opcional)</label>
                    <input type="url" name="portada_url" id="portada_url" value="<?php echo htmlspecialchars($libro_data['ruta_portada_img'] ?? ''); ?>" placeholder="https://ejemplo.com/portada.jpg" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    
                    <img src="<?php echo !empty($libro_data['ruta_portada_img']) ? htmlspecialchars($libro_data['ruta_portada_img']) : '../cover.png'; ?>" 
                         alt="Portada actual" class="mt-2 h-40 object-contain border"
                         onerror="this.onerror=null; this.src='../cover.png';">

                </div>
                <p class="text-sm text-gray-600">Para cambiar el archivo PDF, súbelo nuevamente. Si no seleccionas un nuevo archivo PDF, el existente se conservará.</p>
                <div>
                    <label for="nuevo_archivo_pdf" class="block text-sm font-medium text-gray-700">Nuevo Archivo PDF (Opcional)</label>
                    <input type="file" name="nuevo_archivo_pdf" id="nuevo_archivo_pdf" accept=".pdf" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <?php if (!empty($libro_data['ruta_archivo_pdf'])): ?>
                        <p class="text-xs text-gray-500 mt-1">Archivo actual: <?php echo htmlspecialchars(basename($libro_data['ruta_archivo_pdf'])); ?> 
                        (<a href="../<?php echo htmlspecialchars($libro_data['ruta_archivo_pdf']); ?>" target="_blank" class="text-indigo-600 hover:underline">Ver</a>)
                        </p>
                    <?php endif; ?>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4">
                    <a href="gestionar_libros.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out">Cancelar</a>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out">
                        <i class="fas fa-save mr-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
        <?php elseif (empty($mensaje)): // Si no hay libro_data y no hay mensaje de error previo (ej. ID no válido) ?>
            <p class="text-gray-600">No se ha especificado un libro para modificar o el libro no existe.</p>
        <?php endif; ?>
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