<?php
// admin/gestionar_usuarios.php
session_start();
require_once '../db_config.php'; // Ajustar la ruta si es necesario

// Verificar si el usuario es administrador y está logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php?error_unauthorized=true_from_gestionar_usuarios");
    exit;
}
if ($_SESSION["role"] !== 'administrador') {
    session_unset(); session_destroy(); // Cerrar sesión si el rol no es correcto
    header("location: ../index.php?error_role=unauthorized_panel_access_gestionar_usuarios");
    exit;
}

// Lógica para obtener y mostrar usuarios
$sql_usuarios = "SELECT id, username, nombre_completo, email, role, estado_aprobacion, fecha_registro FROM usuarios ORDER BY fecha_registro DESC";
$resultado_usuarios = $mysqli->query($sql_usuarios);

// Lógica para cambiar estado de aprobación, rol o eliminar (se manejaría con POST requests aquí o en un handler separado)
$mensaje = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['aprobar_usuario_id'])) {
        $usuario_id = $_POST['aprobar_usuario_id'];
        $update_sql = "UPDATE usuarios SET estado_aprobacion = 'aprobado' WHERE id = ?";
        if ($stmt = $mysqli->prepare($update_sql)) {
            $stmt->bind_param("i", $usuario_id);
            if ($stmt->execute()) {
                $mensaje = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative' role='alert'>Usuario aprobado exitosamente.</div>";
            } else {
                $mensaje = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Error al aprobar usuario: " . $stmt->error . "</div>";
            }
            $stmt->close();
            header("Location: gestionar_usuarios.php?mensaje=" . urlencode($mensaje)); // Recargar para ver cambios y mensaje
            exit;
        }
    } elseif (isset($_POST['rechazar_usuario_id'])) {
        $usuario_id = $_POST['rechazar_usuario_id'];
        $update_sql = "UPDATE usuarios SET estado_aprobacion = 'rechazado' WHERE id = ?";
        if ($stmt = $mysqli->prepare($update_sql)) {
            $stmt->bind_param("i", $usuario_id);
            if ($stmt->execute()) {
                $mensaje = "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative' role='alert'>Usuario rechazado.</div>";
            } else {
                $mensaje = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Error al rechazar usuario: " . $stmt->error . "</div>";
            }
            $stmt->close();
            header("Location: gestionar_usuarios.php?mensaje=" . urlencode($mensaje));
            exit;
        }
    } elseif (isset($_POST['eliminar_usuario_id'])) {
        // Es recomendable implementar una confirmación antes de eliminar
        $usuario_id = $_POST['eliminar_usuario_id'];

        // No permitir que el admin se elimine a sí mismo
        if($usuario_id == $_SESSION['user_id']){
            $mensaje = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>No puedes eliminar tu propia cuenta de administrador.</div>";
        } else {
            $delete_sql = "DELETE FROM usuarios WHERE id = ?";
            if ($stmt = $mysqli->prepare($delete_sql)) {
                $stmt->bind_param("i", $usuario_id);
                if ($stmt->execute()) {
                    $mensaje = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Usuario eliminado exitosamente.</div>";
                } else {
                    $mensaje = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Error al eliminar usuario: " . $stmt->error . "</div>";
                }
                $stmt->close();
                header("Location: gestionar_usuarios.php?mensaje=" . urlencode($mensaje));
                exit;
            }
        }
    }
    // Refrescar la lista de usuarios después de una acción
    $resultado_usuarios = $mysqli->query($sql_usuarios);
}

if(isset($_GET['mensaje_exito'])) {
    $mensaje = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative' role='alert'>" . htmlspecialchars(urldecode($_GET['mensaje_exito'])) . "</div>";
} elseif (isset($_GET['mensaje_error'])) {
     $mensaje = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>" . htmlspecialchars(urldecode($_GET['mensaje_error'])) . "</div>";
} elseif(isset($_GET['mensaje'])) { // Para compatibilidad con mensajes anteriores
    $mensaje = urldecode($_GET['mensaje']);
}

$pagina_actual = basename($_SERVER['PHP_SELF']);
$clase_activa = 'bg-indigo-500';
$clase_hover = 'hover:bg-indigo-600';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .table-fixed-layout { table-layout: fixed; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">
     <nav class="bg-indigo-700 text-white shadow-lg">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a href="dashboard_admin.php" class="text-xl font-bold"><i class="fas fa-user-shield mr-2"></i>Panel de Administrador</a>
            <div class="flex space-x-2">
                <a href="gestionar_usuarios.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'gestionar_usuarios.php') ? $clase_activa : $clase_hover; ?>">Gestionar Usuarios</a>
                <a href="aprobar_usuarios.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'aprobar_usuarios.php') ? $clase_activa : $clase_hover; ?>">Aprobar Registros</a>
                <a href="gestionar_categorias.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'gestionar_categorias.php') ? $clase_activa : $clase_hover; ?>">Gestionar Categorías</a>
                <a href="upload_libro.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'upload_libro.php') ? $clase_activa : $clase_hover; ?>">Subir Libro</a>
                <a href="gestionar_libros.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'gestionar_libros.php') ? $clase_activa : $clase_hover; ?>">Gestionar Libros</a>
                <a href="reportes_generales.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'reportes_generales.php') ? $clase_activa : $clase_hover; ?>">Reportes Generales</a>
                <a href="reportes_especificos.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo ($pagina_actual == 'reportes_especificos.php') ? $clase_activa : $clase_hover; ?>">Reportes Específicos</a>
                <a href="../catalogo.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-indigo-600" target="_blank">Ver Catálogo</a>
                <a href="../logout.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-red-600 bg-red-500">Cerrar Sesión <i class="fas fa-sign-out-alt ml-1"></i></a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 sm:px-8 py-8">
        <div class="py-4">
            <h1 class="text-3xl font-bold text-gray-800">Gestión de Usuarios</h1>
            <p class="text-gray-600">Administra las cuentas de los usuarios registrados en el sistema.</p>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mb-4">
                <?php echo $mensaje; // El mensaje ya viene con formato HTML y clases de Tailwind ?>
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto bg-white shadow-md rounded-lg">
            <table class="min-w-full table-fixed-layout leading-normal">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm">
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-left w-1/12">ID</th>
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-left w-2/12">Usuario</th>
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-left w-3/12">Nombre Completo</th>
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-left w-3/12">Email</th>
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-left w-1/12">Rol</th>
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-left w-2/12">Estado</th>
                        <th class="px-5 py-3 border-b-2 border-gray-300 text-center w-2/12">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    <?php if ($resultado_usuarios && $resultado_usuarios->num_rows > 0): ?>
                        <?php while($usuario = $resultado_usuarios->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 border-b border-gray-200">
                            <td class="px-5 py-4 text-sm break-words"><?php echo htmlspecialchars($usuario['id']); ?></td>
                            <td class="px-5 py-4 text-sm break-words"><?php echo htmlspecialchars($usuario['username']); ?></td>
                            <td class="px-5 py-4 text-sm break-words"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                            <td class="px-5 py-4 text-sm break-words"><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td class="px-5 py-4 text-sm break-words">
                                <span class="px-2 py-1 font-semibold leading-tight text-xs rounded-full
                                    <?php if ($usuario['role'] == 'admin') echo 'bg-red-100 text-red-700';
                                          elseif ($usuario['role'] == 'docente') echo 'bg-blue-100 text-blue-700';
                                          else echo 'bg-green-100 text-green-700'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($usuario['role'])); ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-sm break-words">
                                <span class="px-2 py-1 font-semibold leading-tight text-xs rounded-full
                                    <?php if ($usuario['estado_aprobacion'] == 'aprobado') echo 'bg-green-100 text-green-700';
                                          elseif ($usuario['estado_aprobacion'] == 'pendiente') echo 'bg-yellow-100 text-yellow-700';
                                          else echo 'bg-red-100 text-red-700'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($usuario['estado_aprobacion'])); ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-sm text-center">
                                <a href="modificar_usuario.php?id=<?php echo $usuario['id']; ?>" title="Modificar Usuario" class="text-blue-600 hover:text-blue-900 mr-2">
                                    <i class="fas fa-edit fa-fw"></i>
                                </a>
                                <form method="POST" action="gestionar_usuarios.php" class="inline-block align-middle" onsubmit="return confirmAction(this, 'aprobar');">
                                    <input type="hidden" name="aprobar_usuario_id" value="<?php echo $usuario['id']; ?>">
                                    <button type="submit" title="Aprobar Usuario" class="text-green-600 hover:text-green-900 disabled:opacity-50"
                                        <?php echo ($usuario['estado_aprobacion'] == 'aprobado' || $usuario['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-check-circle fa-fw"></i>
                                    </button>
                                </form>
                                <form method="POST" action="gestionar_usuarios.php" class="inline-block align-middle" onsubmit="return confirmAction(this, 'rechazar');">
                                    <input type="hidden" name="rechazar_usuario_id" value="<?php echo $usuario['id']; ?>">
                                    <button type="submit" title="Rechazar Usuario" class="text-yellow-600 hover:text-yellow-900 ml-2 disabled:opacity-50"
                                        <?php echo ($usuario['estado_aprobacion'] == 'rechazado' || $usuario['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-times-circle fa-fw"></i>
                                    </button>
                                </form>
                                <form method="POST" action="gestionar_usuarios.php" class="inline-block align-middle" onsubmit="return confirmAction(this, 'eliminar');">
                                    <input type="hidden" name="eliminar_usuario_id" value="<?php echo $usuario['id']; ?>">
                                    <button type="submit" title="Eliminar Usuario" class="text-red-600 hover:text-red-900 ml-2 disabled:opacity-50"
                                        <?php echo ($usuario['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-trash fa-fw"></i>
                                    </button>
                                </form>
                                </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
                                No hay usuarios registrados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
         <div class="py-4 text-xs text-gray-600">
            Nota: Los administradores no pueden cambiar su propio estado ni eliminarse a sí mismos desde esta interfaz.
        </div>
    </div>

    <footer class="bg-gray-800 text-white py-6 mt-auto">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; <?php echo date("Y"); ?> Biblioteca Virtual Abajo Cadenas. Todos los derechos reservados.</p>
            <p class="text-sm">Desarrollado con <i class="fas fa-heart text-red-500"></i> y PHP.</p>
        </div>
    </footer>
    <script>
        function confirmAction(form, actionType) {
            let userName = form.closest('tr').cells[1].textContent;
            let message = '';
            if (actionType === 'aprobar') {
                message = `¿Estás seguro de que deseas APROBAR al usuario "${userName}"?`;
            } else if (actionType === 'rechazar') {
                message = `¿Estás seguro de que deseas RECHAZAR al usuario "${userName}"? Esta acción puede ser revertida.`;
            } else if (actionType === 'eliminar') {
                message = `¡ATENCIÓN! ¿Estás seguro de que deseas ELIMINAR PERMANENTEMENTE al usuario "${userName}"? Esta acción no se puede deshacer.`;
            } else {
                return true; // No confirmation for unknown action
            }
            return confirm(message);
        }
    </script>
</body>
</html>
<?php $mysqli->close(); ?>
