<?php
// admin/modificar_usuario.php
session_start();
require_once '../db_config.php';

// Verificar si el usuario es administrador y está logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php?error_unauthorized=true_from_modificar_usuario");
    exit;
}
if ($_SESSION["role"] !== 'administrador') {
    session_unset(); session_destroy();
    header("location: ../index.php?error_role=unauthorized_panel_access_modificar_usuario");
    exit;
}

$user_id_edit = 0;
$username_display = ''; // Para mostrar, no editar
$nombre_completo = '';
$email = '';
$current_role = '';
$current_estado_aprobacion = '';

$mensaje_error = '';
$mensaje_exito = '';
$show_form = false;

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
    $user_id_edit = intval($_GET['id']);
    if ($user_id_edit > 0) {
        $sql_load = "SELECT username, nombre_completo, email, role, estado_aprobacion FROM usuarios WHERE id = ?";
        if ($stmt_load = $mysqli->prepare($sql_load)) {
            $stmt_load->bind_param("i", $user_id_edit);
            $stmt_load->execute();
            $result_load = $stmt_load->get_result();
            if ($user_data = $result_load->fetch_assoc()) {
                $username_display = $user_data['username'];
                $nombre_completo = $user_data['nombre_completo'];
                $email = $user_data['email'];
                $current_role = $user_data['role'];
                $current_estado_aprobacion = $user_data['estado_aprobacion'];
                $show_form = true;
            } else {
                $mensaje_error = "Usuario no encontrado.";
            }
            $stmt_load->close();
        } else {
            $mensaje_error = "Error al preparar la consulta para cargar datos del usuario: " . $mysqli->error;
        }
    } else {
        $mensaje_error = "ID de usuario no válido.";
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id_edit = intval($_POST['user_id_edit']);
    $nombre_completo = trim($_POST['nombre_completo']);
    $email = trim($_POST['email']);
    $new_role = trim($_POST['role']);
    $new_estado_aprobacion = trim($_POST['estado_aprobacion']);
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];
    $username_display = $_POST['username_display']; // Recuperar para mostrar en caso de error

    $show_form = true; // Siempre mostrar formulario en POST para rellenar con errores

    if (empty($nombre_completo) || empty($email) || empty($new_role) || empty($new_estado_aprobacion)) {
        $mensaje_error = "Los campos Nombre, Email, Rol y Estado son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "Formato de correo electrónico inválido.";
    } elseif (!in_array($new_role, ['administrador', 'docente', 'estudiante'])) {
        $mensaje_error = "Rol seleccionado no válido.";
    } elseif (!in_array($new_estado_aprobacion, ['pendiente', 'aprobado', 'rechazado'])) {
        $mensaje_error = "Estado de aprobación no válido.";
    } else {
        $sql_check_email = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
        if ($stmt_check_email = $mysqli->prepare($sql_check_email)) {
            $stmt_check_email->bind_param("si", $email, $user_id_edit);
            $stmt_check_email->execute();
            $stmt_check_email->store_result();
            if ($stmt_check_email->num_rows > 0) {
                $mensaje_error = "El correo electrónico ingresado ya está en uso por otro usuario.";
            }
            $stmt_check_email->close();
        } else {
            $mensaje_error = "Error al verificar el correo electrónico: " . $mysqli->error;
        }
    }

    $update_password_sql_part = "";
    $params_for_password = [];
    $types_for_password = "";

    if (!empty($new_password)) {
        if (strlen($new_password) < 8) {
            $mensaje_error = "La nueva contraseña debe tener al menos 8 caracteres.";
        } elseif ($new_password !== $confirm_new_password) {
            $mensaje_error = "Las nuevas contraseñas no coinciden.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password_sql_part = ", password = ?";
            $params_for_password[] = $hashed_password;
            $types_for_password .= "s";
        }
    }

    if ($user_id_edit == $_SESSION['user_id'] && $new_role !== 'administrador') {
        $sql_count_admins = "SELECT COUNT(*) as total_admins FROM usuarios WHERE role = 'administrador'";
        $result_count_admins = $mysqli->query($sql_count_admins);
        $row_count_admins = $result_count_admins->fetch_assoc();
        
        $current_user_role_sql = "SELECT role FROM usuarios WHERE id = ?";
        if($stmt_curr_role = $mysqli->prepare($current_user_role_sql)){
            $stmt_curr_role->bind_param("i", $user_id_edit);
            $stmt_curr_role->execute();
            $res_curr_role = $stmt_curr_role->get_result();
            $data_curr_role = $res_curr_role->fetch_assoc();
            if ($data_curr_role['role'] === 'administrador' && $row_count_admins['total_admins'] == 1) {
                $mensaje_error = "No puedes cambiar tu propio rol porque eres el único administrador. Esto bloquearía el acceso de administración.";
            }
            $stmt_curr_role->close();
        }
    }

    if (empty($mensaje_error) && $user_id_edit > 0) {
        $sql_update = "UPDATE usuarios SET nombre_completo = ?, email = ?, role = ?, estado_aprobacion = ? $update_password_sql_part WHERE id = ?";
        $types = "ssss" . $types_for_password . "i";
        $params = [$nombre_completo, $email, $new_role, $new_estado_aprobacion];
        $params = array_merge($params, $params_for_password);
        $params[] = $user_id_edit;

        if ($stmt_update = $mysqli->prepare($sql_update)) {
            $stmt_update->bind_param($types, ...$params);
            if ($stmt_update->execute()) {
                if ($user_id_edit == $_SESSION['user_id'] && $_SESSION['role'] != $new_role) {
                    $_SESSION['role'] = $new_role; // Actualizar rol en sesión si el admin se modifica a sí mismo
                }
                header("Location: gestionar_usuarios.php?mensaje_exito=" . urlencode("Usuario actualizado exitosamente."));
                exit;
            } else {
                $mensaje_error = "Error al actualizar el usuario: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $mensaje_error = "Error al preparar la actualización: " . $mysqli->error;
        }
    }
    // Para que el formulario mantenga los valores en caso de error POST
    $current_role = $new_role;
    $current_estado_aprobacion = $new_estado_aprobacion;

} else if ($_SERVER["REQUEST_METHOD"] != "GET") {
     $mensaje_error = "Método no permitido o ID de usuario no especificado.";
}

$pagina_actual = basename($_SERVER['PHP_SELF']); // Aunque no hay menú completo, es buena práctica
$clase_activa = 'bg-indigo-500';
$clase_hover = 'hover:bg-indigo-600';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Usuario - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100 text-gray-800">
    <nav class="bg-indigo-700 text-white shadow-lg">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a href="dashboard_admin.php" class="text-xl font-bold"><i class="fas fa-user-shield mr-2"></i>Panel de Administrador</a>
            <div class="flex space-x-2"> <!-- Menú simplificado para página de modificación -->
                <a href="gestionar_usuarios.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo $clase_hover; ?>">Gestionar Usuarios</a>
                <!-- Puedes añadir más enlaces si son relevantes para esta página específica -->
                <a href="../catalogo.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-indigo-600" target="_blank">Ver Catálogo</a>
                <a href="../logout.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-red-600 bg-red-500">Cerrar Sesión <i class="fas fa-sign-out-alt ml-1"></i></a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 sm:px-8 py-8">
        <div class="py-4">
            <h1 class="text-3xl font-bold text-gray-800">Modificar Usuario</h1>
        </div>

        <?php if (!empty($mensaje_exito)): ?>
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert"><?php echo htmlspecialchars($mensaje_exito); ?></div>
        <?php endif; ?>
        <?php if (!empty($mensaje_error)): ?>
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert"><?php echo htmlspecialchars($mensaje_error); ?></div>
        <?php endif; ?>

        <?php if ($show_form && $user_id_edit > 0): ?>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <form action="modificar_usuario.php" method="POST" class="space-y-4">
                <input type="hidden" name="user_id_edit" value="<?php echo $user_id_edit; ?>">
                <input type="hidden" name="username_display" value="<?php echo htmlspecialchars($username_display); ?>">

                <div>
                    <p class="block text-sm font-medium text-gray-700">Usuario (no editable): <strong class="text-gray-900"><?php echo htmlspecialchars($username_display); ?></strong></p>
                </div>

                <div>
                    <label for="nombre_completo" class="block text-sm font-medium text-gray-700">Nombre Completo <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre_completo" id="nombre_completo" value="<?php echo htmlspecialchars($nombre_completo); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Rol <span class="text-red-500">*</span></label>
                    <select name="role" id="role" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="estudiante" <?php echo ($current_role == 'estudiante') ? 'selected' : ''; ?>>Estudiante</option>
                        <option value="docente" <?php echo ($current_role == 'docente') ? 'selected' : ''; ?>>Docente</option>
                        <option value="administrador" <?php echo ($current_role == 'administrador') ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                </div>
                <div>
                    <label for="estado_aprobacion" class="block text-sm font-medium text-gray-700">Estado de Aprobación <span class="text-red-500">*</span></label>
                    <select name="estado_aprobacion" id="estado_aprobacion" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="pendiente" <?php echo ($current_estado_aprobacion == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="aprobado" <?php echo ($current_estado_aprobacion == 'aprobado') ? 'selected' : ''; ?>>Aprobado</option>
                        <option value="rechazado" <?php echo ($current_estado_aprobacion == 'rechazado') ? 'selected' : ''; ?>>Rechazado</option>
                    </select>
                </div>
                <hr class="my-6">
                <p class="text-sm text-gray-600">Dejar los campos de contraseña en blanco para no cambiar la contraseña actual.</p>
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700">Nueva Contraseña</label>
                    <input type="password" name="new_password" id="new_password" minlength="8" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="confirm_new_password" class="block text-sm font-medium text-gray-700">Confirmar Nueva Contraseña</label>
                    <input type="password" name="confirm_new_password" id="confirm_new_password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4">
                    <a href="gestionar_usuarios.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out">Cancelar</a>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out">
                        <i class="fas fa-save mr-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
        <?php elseif (!$show_form && empty($mensaje_error)): // Caso inicial sin ID o error GET no crítico ?>
             <p class="text-gray-600">Por favor, selecciona un usuario de la <a href="gestionar_usuarios.php" class="text-indigo-600 hover:underline">lista de usuarios</a> para modificar.</p>
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