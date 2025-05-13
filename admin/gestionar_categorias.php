<?php
// admin/gestionar_categorias.php
session_start();
require_once '../db_config.php'; // Ajustar la ruta si es necesario

// Verificar si el usuario es administrador y está logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php?error_unauthorized=true_from_gestionar_categorias");
    exit;
}
// Permitir acceso a administradores y docentes, y convertir el rol a minúsculas para la comparación
$user_role = isset($_SESSION["role"]) ? strtolower($_SESSION["role"]) : '';
if (!in_array($user_role, ['administrador', 'docente'])) {
    session_unset(); session_destroy(); // Cerrar sesión si el rol no es correcto
    header("location: ../index.php?error_role=unauthorized_panel_access_gestionar_categorias");
    exit;
}

$mensaje = '';
$error_crud = '';
$nombre_categoria = '';
$descripcion_categoria = '';
$edit_mode = false;
$categoria_id_edit = null;

// Crear tabla de categorías si no existe (SOLO PARA FACILITAR LA PRIMERA EJECUCIÓN)
// En un entorno de producción, esto se haría mediante migraciones.
$sql_create_table = "
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";
if (!$mysqli->query($sql_create_table)) {
    // Manejar el error si no se puede crear la tabla, aunque para UNIQUE puede fallar si ya existe
    // y no es un problema real si la tabla ya está como debe ser.
    // error_log("Error al crear tabla categorias: " . $mysqli->error);
}


// Procesar formulario para añadir o editar categoría
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_categoria_post = trim($_POST['nombre_categoria']);
    $descripcion_categoria_post = trim($_POST['descripcion_categoria']);
    $categoria_id_post = isset($_POST['categoria_id']) ? $_POST['categoria_id'] : null;

    if (empty($nombre_categoria_post)) {
        $error_crud = "El nombre de la categoría es obligatorio.";
    } else {
        if ($categoria_id_post) { // Editar categoría
            $sql = "UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?";
            if ($stmt = $mysqli->prepare($sql)) {
                try {
                    $stmt->bind_param("ssi", $nombre_categoria_post, $descripcion_categoria_post, $categoria_id_post);
                    if ($stmt->execute()) {
                        $mensaje = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative' role='alert'>Categoría actualizada exitosamente.</div>";
                    }
                    // Si execute() falla y lanza excepción, se captura abajo.
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() == 1062) { // Error de entrada duplicada (MySQL error code for duplicate entry)
                        $error_crud = "Error: Ya existe una categoría con ese nombre.";
                    } else {
                        $error_crud = "Error al actualizar la categoría: " . $e->getMessage();
                    }
                }
                $stmt->close();
            } else {
                $error_crud = "Error al preparar la actualización: " . $mysqli->error;
            }
        } else { // Añadir nueva categoría
            $sql = "INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)";
            if ($stmt = $mysqli->prepare($sql)) {
                try {
                    $stmt->bind_param("ss", $nombre_categoria_post, $descripcion_categoria_post);
                    if ($stmt->execute()) { // Esta es la línea 70
                        $mensaje = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative' role='alert'>Categoría añadida exitosamente.</div>";
                        $nombre_categoria = ''; // Limpiar campos después de añadir
                        $descripcion_categoria = '';
                    }
                    // Si execute() falla y lanza excepción, se captura abajo.
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() == 1062) { // Error de entrada duplicada
                        $error_crud = "Error: Ya existe una categoría con ese nombre.";
                         $nombre_categoria = $nombre_categoria_post; // Mantener valor para corrección
                         $descripcion_categoria = $descripcion_categoria_post;
                    } else {
                        $error_crud = "Error al añadir la categoría: " . $e->getMessage();
                    }
                }
                $stmt->close();
            } else {
                $error_crud = "Error al preparar la inserción: " . $mysqli->error;
            }
        }
    }
     if (!empty($error_crud) && $categoria_id_post) { // Si hay error en edición, mantener datos en form
        $nombre_categoria = $nombre_categoria_post;
        $descripcion_categoria = $descripcion_categoria_post;
        $edit_mode = true;
        $categoria_id_edit = $categoria_id_post;
    } elseif (!empty($error_crud) && !$categoria_id_post) { // Si hay error en creación, mantener datos
        $nombre_categoria = $nombre_categoria_post;
        $descripcion_categoria = $descripcion_categoria_post;
    }
}

// Lógica para eliminar categoría
if (isset($_GET['eliminar_id'])) {
    $id_eliminar = $_GET['eliminar_id'];
    // Antes de eliminar, verificar si hay libros asociados a esta categoría.
    // Esta es una validación IMPORTANTE para evitar datos huérfanos.
    // Por ahora, asumimos que se puede eliminar directamente o que la FK tiene ON DELETE SET NULL/CASCADE.
    // Ejemplo de verificación (necesitarías una tabla 'libros' con 'categoria_id'):
    /*
    $check_sql = "SELECT COUNT(*) as total_libros FROM libros WHERE categoria_id = ?";
    if ($stmt_check = $mysqli->prepare($check_sql)) {
        $stmt_check->bind_param("i", $id_eliminar);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row_check = $result_check->fetch_assoc();
        if ($row_check['total_libros'] > 0) {
            $mensaje = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>No se puede eliminar la categoría porque tiene libros asociados.</div>";
        } else {
            // Proceder a eliminar
        }
        $stmt_check->close();
    }
    */

    $sql_delete = "DELETE FROM categorias WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql_delete)) {
        $stmt->bind_param("i", $id_eliminar);
        if ($stmt->execute()) {
            $mensaje = "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative' role='alert'>Categoría eliminada exitosamente.</div>";
        } else {
            $mensaje = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Error al eliminar la categoría: " . $stmt->error . ". Es posible que tenga libros asociados.</div>";
        }
        $stmt->close();
    }
    header("Location: gestionar_categorias.php?mensaje_url=" . urlencode($mensaje)); // Recargar para ver cambios y mensaje
    exit;
}

// Lógica para cargar datos de categoría para editar
if (isset($_GET['editar_id'])) {
    $categoria_id_edit = $_GET['editar_id'];
    $sql_edit = "SELECT id, nombre, descripcion FROM categorias WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql_edit)) {
        $stmt->bind_param("i", $categoria_id_edit);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($categoria_data = $result->fetch_assoc()) {
            $nombre_categoria = $categoria_data['nombre'];
            $descripcion_categoria = $categoria_data['descripcion'];
            $edit_mode = true;
        } else {
            $mensaje = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Categoría no encontrada para editar.</div>";
            $edit_mode = false; // Asegurar que no se quede en modo edición
        }
        $stmt->close();
    }
}

if(isset($_GET['mensaje_url'])) {
    $mensaje = urldecode($_GET['mensaje_url']);
}

$pagina_actual = basename($_SERVER['PHP_SELF']);
$clase_activa = 'bg-indigo-500 text-white'; 
$clase_hover = 'text-indigo-100 hover:bg-indigo-600 hover:text-white';


// Obtener todas las categorías para mostrar
$sql_categorias = "SELECT id, nombre, descripcion, fecha_creacion FROM categorias ORDER BY nombre ASC";
$resultado_categorias = $mysqli->query($sql_categorias);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Categorías - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
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
            <h1 class="text-3xl font-bold text-gray-800">Gestión de Categorías de Libros</h1>
            <p class="text-gray-600">Añade, edita o elimina las categorías para organizar los libros.</p>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mb-4"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_crud)): ?>
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <?php echo $error_crud; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-xl font-semibold mb-4"><?php echo $edit_mode ? 'Editar Categoría' : 'Añadir Nueva Categoría'; ?></h2>
            <form action="gestionar_categorias.php" method="POST" class="space-y-4">
                <?php if ($edit_mode && $categoria_id_edit): ?>
                    <input type="hidden" name="categoria_id" value="<?php echo htmlspecialchars($categoria_id_edit); ?>">
                <?php endif; ?>
                <div>
                    <label for="nombre_categoria" class="block text-sm font-medium text-gray-700">Nombre de la Categoría <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre_categoria" id="nombre_categoria" value="<?php echo htmlspecialchars($nombre_categoria); ?>" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="descripcion_categoria" class="block text-sm font-medium text-gray-700">Descripción (Opcional)</label>
                    <textarea name="descripcion_categoria" id="descripcion_categoria" rows="3"
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo htmlspecialchars($descripcion_categoria); ?></textarea>
                </div>
                <div class="flex items-center justify-end space-x-3">
                    <?php if ($edit_mode): ?>
                        <a href="gestionar_categorias.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out">Cancelar Edición</a>
                    <?php endif; ?>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out">
                        <i class="fas <?php echo $edit_mode ? 'fa-save' : 'fa-plus-circle'; ?> mr-2"></i>
                        <?php echo $edit_mode ? 'Guardar Cambios' : 'Añadir Categoría'; ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto bg-white shadow-md rounded-lg">
            <h2 class="text-xl font-semibold p-4 border-b">Categorías Existentes</h2>
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm">
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-left">Nombre</th>
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-left">Descripción</th>
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-left">Fecha Creación</th>
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    <?php if ($resultado_categorias && $resultado_categorias->num_rows > 0): ?>
                        <?php while($categoria = $resultado_categorias->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 border-b border-gray-200">
                            <td class="px-5 py-4 text-sm"><?php echo htmlspecialchars($categoria['nombre']); ?></td>
                            <td class="px-5 py-4 text-sm"><?php echo nl2br(htmlspecialchars($categoria['descripcion'] ? $categoria['descripcion'] : '-')); ?></td>
                            <td class="px-5 py-4 text-sm"><?php echo htmlspecialchars(date("d/m/Y", strtotime($categoria['fecha_creacion']))); ?></td>
                            <td class="px-5 py-4 text-sm text-center whitespace-nowrap">
                                <a href="gestionar_categorias.php?editar_id=<?php echo $categoria['id']; ?>" title="Editar Categoría" class="text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-edit fa-fw"></i> Editar
                                </a>
                                <a href="gestionar_categorias.php?eliminar_id=<?php echo $categoria['id']; ?>" title="Eliminar Categoría" class="text-red-600 hover:text-red-900 ml-3"
                                   onclick="return confirm('¿Estás seguro de que deseas eliminar la categoría \'<?php echo htmlspecialchars(addslashes($categoria['nombre'])); ?>\'? Esta acción no se puede deshacer y podría afectar a los libros asociados.');">
                                    <i class="fas fa-trash fa-fw"></i> Eliminar
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center text-gray-500">
                                <i class="fas fa-info-circle fa-lg mr-2"></i>No hay categorías registradas.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
