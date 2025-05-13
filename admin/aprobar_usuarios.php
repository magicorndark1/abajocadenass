<?php
// admin/aprobar_usuarios.php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php?error_unauthorized=true_from_aprobar_usuarios");
    exit;
}
// Verificar si el usuario es administrador
if ($_SESSION["role"] !== 'administrador') {
    session_unset(); session_destroy(); // Cerrar sesión si el rol no es correcto
    header("location: ../index.php?error_role=unauthorized_panel_access_aprobar_usuarios");
    exit;
}

require_once '../db_config.php';

$message = '';
$message_type = ''; // 'success' or 'error'

// Procesar acciones de aprobación/rechazo
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['user_id']) && isset($_POST['action'])) {
        $user_id = intval($_POST['user_id']);
        $action = $_POST['action']; // 'aprobar' o 'rechazar'

        if ($user_id > 0) {
            $new_status = '';
            if ($action === 'aprobar') {
                $new_status = 'aprobado';
            } elseif ($action === 'rechazar') {
                $new_status = 'rechazado';
            }

            if (!empty($new_status)) {
                $sql_update = "UPDATE usuarios SET estado_aprobacion = ? WHERE id = ? AND estado_aprobacion = 'pendiente'";
                if ($stmt_update = $mysqli->prepare($sql_update)) {
                    $stmt_update->bind_param("si", $new_status, $user_id);
                    if ($stmt_update->execute()) {
                        if ($stmt_update->affected_rows > 0) {
                            $message = "El estado del usuario ha sido actualizado a '" . htmlspecialchars($new_status) . "'.";
                            $message_type = 'success';
                        } else {
                            $message = "No se pudo actualizar el usuario o ya no estaba pendiente.";
                            $message_type = 'error';
                        }
                    } else {
                        $message = "Error al ejecutar la actualización: " . $stmt_update->error;
                        $message_type = 'error';
                    }
                    $stmt_update->close();
                } else {
                    $message = "Error al preparar la consulta de actualización: " . $mysqli->error;
                    $message_type = 'error';
                }
            } else {
                $message = "Acción no válida.";
                $message_type = 'error';
            }
        } else {
            $message = "ID de usuario no válido.";
            $message_type = 'error';
        }
    }
    // Redirigir para evitar reenvío de formulario (PRG pattern)
    // Pasamos el mensaje por la sesión para mostrarlo después de la redirección
    $_SESSION['form_message'] = $message;
    $_SESSION['form_message_type'] = $message_type;
    header("Location: aprobar_usuarios.php");
    exit;
}

// Recuperar y limpiar mensajes de la sesión
if (isset($_SESSION['form_message'])) {
    $message = $_SESSION['form_message'];
    $message_type = $_SESSION['form_message_type'];
    unset($_SESSION['form_message']);
    unset($_SESSION['form_message_type']);
}


// Obtener lista de usuarios pendientes
$sql_pending = "SELECT id, nombre_completo, cedula, email, username, role, fecha_registro FROM usuarios WHERE estado_aprobacion = 'pendiente' ORDER BY fecha_registro ASC";
$result_pending = $mysqli->query($sql_pending);
$pending_users = [];
if ($result_pending) {
    while ($row = $result_pending->fetch_assoc()) {
        $pending_users[] = $row;
    }
    $result_pending->free();
} else {
    // Manejar error si la consulta falla, aunque $pending_users seguirá siendo un array vacío
    $message = "Error al obtener la lista de usuarios pendientes: " . $mysqli->error;
    $message_type = 'error';
}

$admin_nombre = htmlspecialchars($_SESSION["nombre_completo"] ?? $_SESSION["username"]);

$pagina_actual = basename($_SERVER['PHP_SELF']);
$clase_activa = 'bg-indigo-500';
$clase_hover = 'hover:bg-indigo-600';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprobar Usuarios - Panel de Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { width: 260px; transition: transform 0.3s ease-in-out; }
        .sidebar-link { display: block; padding: 0.75rem 1.5rem; border-radius: 0.375rem; transition: background-color 0.2s ease, color 0.2s ease; }
        .sidebar-link:hover, .sidebar-link.active { background-color: #4F46E5; color: white; }
        .sidebar-link i { margin-right: 0.75rem; }
        .message-box { padding: 1rem; margin-bottom: 1.5rem; border-radius: 0.375rem; font-size: 0.875rem; }
        .message-box.success { background-color: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
        .message-box.error { background-color: #FEE2E2; color: #B91C1C; border: 1px solid #FCA5A5; }
        /* Mobile sidebar */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); position: fixed; height: 100%; z-index: 40; }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.5); z-index: 30; display: none; }
            .sidebar.open + .sidebar-overlay { display: block; }
        }
    </style>
</head>
<body class="bg-gray-100">
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

        <main class="flex-1 p-6 md:p-10 overflow-y-auto">
            <header class="flex justify-between items-center mb-8">
                 <button id="mobileMenuButton" class="md:hidden text-gray-600 focus:outline-none">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
                <h1 class="text-3xl font-bold text-gray-800">Aprobar Usuarios Pendientes</h1>
                <div class="text-right">
                    <p class="text-gray-600">Admin: <span class="font-semibold"><?php echo $admin_nombre; ?></span></p>
                </div>
            </header>

            <?php if (!empty($message)): ?>
                <div class="message-box <?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white p-6 md:p-8 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold text-gray-700 mb-6">Lista de Usuarios Pendientes de Aprobación</h2>
                <?php if (count($pending_users) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre Completo</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cédula</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol Solicitado</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Registro</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($pending_users as $user): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($user['nombre_completo']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['cedula']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $user['fecha_registro'] ? htmlspecialchars(date("d/m/Y H:i", strtotime($user['fecha_registro']))) : 'N/A'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <form action="aprobar_usuarios.php" method="POST" class="inline-block">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="aprobar">
                                                <button type="submit" class="text-indigo-600 hover:text-indigo-900 hover:bg-indigo-100 px-2 py-1 rounded-md transition-colors" title="Aprobar Usuario">
                                                    <i class="fas fa-check-circle mr-1"></i> Aprobar
                                                </button>
                                            </form>
                                            <form action="aprobar_usuarios.php" method="POST" class="inline-block">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="rechazar">
                                                <button type="submit" class="text-red-600 hover:text-red-900 hover:bg-red-100 px-2 py-1 rounded-md transition-colors" title="Rechazar Usuario">
                                                    <i class="fas fa-times-circle mr-1"></i> Rechazar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600 text-center py-4">No hay usuarios pendientes de aprobación en este momento.</p>
                <?php endif; ?>
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
    <footer class="bg-gray-800 text-white py-6 mt-auto">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; <?php echo date("Y"); ?> Biblioteca Virtual Abajo Cadenas. Todos los derechos reservados.</p>
            <p class="text-sm">Desarrollado con <i class="fas fa-heart text-red-500"></i> y PHP.</p>
        </div>
    </footer>
</body>
</html>
<?php $mysqli->close(); ?>
