<?php
session_start();
require_once '../db_config.php';
require_once 'admin_functions.php'; // Contiene la clase Administrador

// Verificar si el usuario es administrador y está logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php?error_unauthorized=true_from_upload_libro");
    exit;
}
$user_role = isset($_SESSION["role"]) ? strtolower($_SESSION["role"]) : '';
if (!in_array($user_role, ['administrador', 'docente'])) {
    session_unset(); session_destroy();
    header("location: ../index.php?error_role=unauthorized_panel_access_upload_libro");
    exit;
}

$administrador = new Administrador($mysqli); // Crear instancia de la clase

$mensaje = '';
$error_form = '';

// Obtener categorías para el dropdown
$sql_categorias = "SELECT id, nombre FROM categorias ORDER BY nombre ASC";
$result_categorias = $mysqli->query($sql_categorias);
$categorias_options = [];
if ($result_categorias) {
    while ($row = $result_categorias->fetch_assoc()) {
        $categorias_options[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = trim($_POST['titulo']);
    $autor = trim($_POST['autor']);
    $categoria_id = isset($_POST['categoria_id']) ? intval($_POST['categoria_id']) : 0;
    $editorial_input = trim($_POST['editorial']);
    $editorial = !empty($editorial_input) ? $editorial_input : null; // Usar null si está vacío
    
    $ano_publicacion_input = trim($_POST['ano_publicacion']);
    $ano_publicacion = !empty($ano_publicacion_input) ? $ano_publicacion_input : null;
    $descripcion_input = trim($_POST['descripcion']);
    $descripcion = !empty($descripcion_input) ? $descripcion_input : null;

    if (empty($titulo) || empty($autor) || $categoria_id == 0) {
        $error_form = "Título, Autor y Categoría son campos obligatorios.";
    } elseif (!isset($_FILES['archivo_pdf']) || $_FILES['archivo_pdf']['error'] == UPLOAD_ERR_NO_FILE) {
        $error_form = "Debe seleccionar un archivo PDF para el libro.";
    } else {
        // Crear el directorio 'uploads' si no existe en la raíz del proyecto
        $upload_dir = dirname(__DIR__) . '/uploads/'; // Ruta absoluta al directorio de subidas en la raíz
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $error_form = "Error: No se pudo crear el directorio de subidas. Verifique los permisos.";
            }
        }
        
        if (empty($error_form) && is_writable($upload_dir)) {
            // La función subirLibroPDF ahora manejará la ruta relativa a la raíz del proyecto
            $resultado_subida = $administrador->subirLibroPDF(
                $_FILES['archivo_pdf'],
                $titulo,
                $autor,
                $categoria_id,
                // $isbn, // Ya no se pasa ISBN
                $editorial,
                $ano_publicacion,
                $descripcion
            );

            if ($resultado_subida === true) {
                $mensaje = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative' role='alert'>Libro subido y registrado exitosamente.</div>";
                // Limpiar campos o redirigir
            } else {
                $error_form = "Error al subir el libro: " . htmlspecialchars($resultado_subida);
            }
        } elseif(!is_writable($upload_dir)) {
            $error_form = "Error: El directorio de subidas ('" . htmlspecialchars($upload_dir) . "') no tiene permisos de escritura.";
        }
    }
}

$pagina_actual = basename($_SERVER['PHP_SELF']);
$clase_activa = 'bg-indigo-500 text-white'; 
$clase_hover = 'text-indigo-100 hover:bg-indigo-600 hover:text-white';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Nuevo Libro - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100 text-gray-800">
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
        include '../docente/docente_nav.php';
    }
    ?>

    <div class="container mx-auto px-4 sm:px-8 py-8">
        <div class="py-4">
            <h1 class="text-3xl font-bold text-gray-800">Subir Nuevo Libro</h1>
            <p class="text-gray-600">Completa los detalles del libro y sube el archivo PDF.</p>
        </div>

        <?php if (!empty($mensaje)): echo $mensaje; endif; ?>
        <?php if (!empty($error_form)): ?>
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <?php echo $error_form; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <form action="upload_libro.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label for="titulo" class="block text-sm font-medium text-gray-700">Título <span class="text-red-500">*</span></label>
                    <input type="text" name="titulo" id="titulo" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="autor" class="block text-sm font-medium text-gray-700">Autor <span class="text-red-500">*</span></label>
                    <input type="text" name="autor" id="autor" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="categoria_id" class="block text-sm font-medium text-gray-700">Categoría <span class="text-red-500">*</span></label>
                    <select name="categoria_id" id="categoria_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Seleccione una categoría</option>
                        <?php foreach ($categorias_options as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <!-- El campo ISBN ha sido eliminado ya que la columna fue renombrada a editorial -->
                 <!-- <div>
                    <label for="isbn" class="block text-sm font-medium text-gray-700">ISBN (Opcional)</label>
                    <input type="text" name="isbn" id="isbn" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div> -->
                <div>
                    <label for="editorial" class="block text-sm font-medium text-gray-700">Editorial (Opcional)</label>
                    <input type="text" name="editorial" id="editorial" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="ano_publicacion" class="block text-sm font-medium text-gray-700">Año de Publicación (Opcional)</label>
                    <input type="text" name="ano_publicacion" id="ano_publicacion" pattern="\d{4}" title="Ingrese un año de 4 dígitos" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción (Opcional)</label>
                    <textarea name="descripcion" id="descripcion" rows="4" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
                <div>
                    <label for="archivo_pdf" class="block text-sm font-medium text-gray-700">Archivo del Libro (PDF) <span class="text-red-500">*</span></label>
                    <input type="file" name="archivo_pdf" id="archivo_pdf" required accept=".pdf" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>
                <div class="flex justify-end pt-4">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out">
                        <i class="fas fa-upload mr-2"></i>Subir Libro
                    </button>
                </div>
            </form>
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