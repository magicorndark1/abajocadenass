<?php
// restablecer_password.php
session_start();
require_once 'db_config.php';

$token_valido = false;
$mensaje_usuario = '';
$mensaje_tipo = ''; // 'success' o 'error'
$token_from_url = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!empty($token_from_url)) {
    // Verificar el token
    $sql_check_token = "SELECT email, expires_at FROM password_resets WHERE token = ? AND expires_at > NOW()";
    if ($stmt_check = $mysqli->prepare($sql_check_token)) {
        $stmt_check->bind_param("s", $token_from_url);
        $stmt_check->execute();
        $result_token = $stmt_check->get_result();

        if ($result_token->num_rows > 0) {
            $token_data = $result_token->fetch_assoc();
            $email_asociado = $token_data['email'];
            $token_valido = true;
        } else {
            $mensaje_usuario = "El enlace de restablecimiento no es válido o ha expirado. Por favor, solicita uno nuevo.";
            $mensaje_tipo = 'error';
        }
        $stmt_check->close();
    } else {
        $mensaje_usuario = "Error al verificar el token. Inténtalo más tarde.";
        $mensaje_tipo = 'error';
    }
} else {
    $mensaje_usuario = "No se proporcionó un token de restablecimiento.";
    $mensaje_tipo = 'error';
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valido) {
    $nueva_password = $_POST['nueva_password'];
    $confirmar_password = $_POST['confirmar_password'];

    if (empty($nueva_password) || empty($confirmar_password)) {
        $mensaje_usuario = "Por favor, completa ambos campos de contraseña.";
        $mensaje_tipo = 'error';
    } elseif (strlen($nueva_password) < 8) {
        $mensaje_usuario = "La nueva contraseña debe tener al menos 8 caracteres.";
        $mensaje_tipo = 'error';
    } elseif ($nueva_password !== $confirmar_password) {
        $mensaje_usuario = "Las contraseñas no coinciden.";
        $mensaje_tipo = 'error';
    } else {
        // Actualizar contraseña en la tabla usuarios
        $hashed_password = password_hash($nueva_password, PASSWORD_DEFAULT);
        $sql_update_pass = "UPDATE usuarios SET password = ? WHERE email = ?";
        if ($stmt_update = $mysqli->prepare($sql_update_pass)) {
            $stmt_update->bind_param("ss", $hashed_password, $email_asociado);
            if ($stmt_update->execute()) {
                // Invalidar el token (eliminarlo o marcarlo como usado)
                $sql_delete_token = "DELETE FROM password_resets WHERE token = ?";
                if ($stmt_delete = $mysqli->prepare($sql_delete_token)) {
                    $stmt_delete->bind_param("s", $token_from_url);
                    $stmt_delete->execute();
                    $stmt_delete->close();
                }

                $mensaje_usuario = "Tu contraseña ha sido actualizada exitosamente. Ahora puedes iniciar sesión.";
                $mensaje_tipo = 'success';
                $token_valido = false; // Para ocultar el formulario después del éxito
            } else {
                $mensaje_usuario = "Error al actualizar la contraseña. Inténtalo más tarde.";
                $mensaje_tipo = 'error';
            }
            $stmt_update->close();
        } else {
            $mensaje_usuario = "Error al preparar la actualización de contraseña. Inténtalo más tarde.";
            $mensaje_tipo = 'error';
        }
    }
    $mysqli->close();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Establecer Nueva Contraseña - Biblioteca Virtual</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- <link rel="stylesheet" href="css/styles.css"> --> <!-- Puedes mantener o quitar esta línea -->
    <style>
        body { font-family: 'Inter', sans-serif; }
        .input-field {
            border-color: #D1D5DB; /* Gray-300 */
        }
        .input-field:focus {
            border-color: #4F46E5; /* Indigo-600 */
            box-shadow: 0 0 0 2px #A5B4FC; /* Indigo-200 */
        }
        .btn-primary {
            background-color: #4F46E5; /* Indigo-600 */
        }
        .btn-primary:hover {
            background-color: #4338CA; /* Indigo-700 */
        }
    </style>
</head>
<body class="bg-gray-100">

    <nav class="bg-white shadow-md fixed w-full z-10 top-0">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a href="index.php" class="flex items-center text-xl font-bold text-indigo-600">
                <img src="logo.png" alt="Logo Biblioteca Abajo Cadenas" class="h-10 mr-3">
                <span>Biblioteca Virtual Abajo Cadenas</span>
            </a>
            <div class="hidden md:flex space-x-4 items-center">
                <a href="index.php" class="text-gray-600 hover:text-indigo-600">Inicio</a>
                <a href="catalogo.php" class="text-gray-600 hover:text-indigo-600">Catálogo</a>
                <a href="index.php#acerca" class="text-gray-600 hover:text-indigo-600">Acerca de</a>
                <a href="index.php#contacto" class="text-gray-600 hover:text-indigo-600">Contáctanos</a>
                <a href="registro.php" class="text-gray-600 hover:text-indigo-600 px-3 py-2">Registrarse</a>
                <a href="index.php#loginSection" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Iniciar Sesión</a>
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
            <a href="index.php#acerca" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Acerca de</a>
            <a href="index.php#contacto" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Contáctanos</a>
            <a href="registro.php" class="block px-6 py-3 text-gray-600 hover:bg-indigo-50">Registrarse</a>
            <a href="index.php#loginSection" class="block px-6 py-3 text-white bg-indigo-600 hover:bg-indigo-700">Iniciar Sesión</a>
        </div>
    </nav>

    <div class="min-h-screen flex flex-col items-center justify-center pt-24 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md">
            <div class="text-center mb-8">
                <i class="fas fa-shield-alt fa-3x text-indigo-600 mb-4"></i>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900">Establecer Nueva Contraseña</h2>
            </div>

            <?php if (!empty($mensaje_usuario)): ?>
                <div class="mb-4 p-3 rounded-md text-sm <?php echo $mensaje_tipo === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300'; ?>">
                    <?php echo htmlspecialchars($mensaje_usuario); ?>
                </div>
            <?php endif; ?>

            <?php if ($token_valido): ?>
                <form action="restablecer_password.php?token=<?php echo htmlspecialchars($token_from_url); ?>" method="POST" class="space-y-6">
                    <div>
                        <label for="nueva_password" class="block text-sm font-medium text-gray-700">Nueva Contraseña</label>
                        <input type="password" id="nueva_password" name="nueva_password" required minlength="8" class="input-field mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none sm:text-sm" placeholder="Mínimo 8 caracteres">
                    </div>
                    <div>
                        <label for="confirmar_password" class="block text-sm font-medium text-gray-700">Confirmar Nueva Contraseña</label>
                        <input type="password" id="confirmar_password" name="confirmar_password" required class="input-field mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none sm:text-sm" placeholder="Repite tu nueva contraseña">
                    </div>
                    <div>
                        <button type="submit" class="btn-primary w-full flex justify-center py-2.5 px-4 border rounded-md shadow-sm text-sm font-medium text-white">Actualizar Contraseña</button>
                    </div>
                </form>
            <?php endif; ?>

            <p class="mt-6 text-sm text-center text-gray-600">
                <a href="index.php#loginSection" class="font-medium text-indigo-600 hover:text-indigo-500">Volver a Iniciar Sesión</a>
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Menú móvil
            const mobileMenuButton = document.getElementById('mobileMenuButton');
            const mobileMenu = document.getElementById('mobileMenu');

            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }
        });
    </script>
</body>
</html>