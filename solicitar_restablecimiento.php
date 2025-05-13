<?php
// solicitar_restablecimiento.php
session_start();
require_once 'db_config.php';

$mensaje_usuario = '';
$mensaje_tipo = ''; // 'success' o 'error'
$mostrar_enlace_simulado = false;
$enlace_restablecimiento_simulado = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje_usuario = "Por favor, ingresa un correo electrónico válido.";
        $mensaje_tipo = 'error';
    } else {
        // Verificar si el correo existe en la base de datos
        $sql_check_email = "SELECT id FROM usuarios WHERE email = ?";
        if ($stmt_check = $mysqli->prepare($sql_check_email)) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                // Generar token único
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token válido por 1 hora

                // Guardar token en la base de datos
                $sql_insert_token = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
                if ($stmt_insert = $mysqli->prepare($sql_insert_token)) {
                    $stmt_insert->bind_param("sss", $email, $token, $expires_at);
                    if ($stmt_insert->execute()) {
                        // SIMULACIÓN DE ENVÍO DE CORREO
                        // En un entorno real, aquí enviarías un correo electrónico.
                        // Por ahora, mostraremos el enlace en pantalla.
                        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
                        $directorio_actual = dirname($_SERVER['PHP_SELF']);
                        // Asegurarse de que el directorio actual no sea solo '/' si está en la raíz
                        $path_prefix = ($directorio_actual == '/' || $directorio_actual == '\\') ? '' : $directorio_actual;
                        $enlace_restablecimiento_simulado = $base_url . $path_prefix . "/restablecer_password.php?token=" . $token;

                        $mensaje_usuario = "Si tu correo está registrado, recibirás (simulado) un enlace para restablecer tu contraseña. Por favor, revisa (abajo).";
                        $mensaje_tipo = 'success';
                        $mostrar_enlace_simulado = true;

                    } else {
                        $mensaje_usuario = "Error al guardar el token de restablecimiento. Inténtalo más tarde.";
                        $mensaje_tipo = 'error';
                    }
                    $stmt_insert->close();
                } else {
                    $mensaje_usuario = "Error al preparar la solicitud de restablecimiento. Inténtalo más tarde.";
                    $mensaje_tipo = 'error';
                }
            } else {
                // Para no revelar si un email está registrado o no, se puede mostrar un mensaje genérico.
                $mensaje_usuario = "Si tu correo está registrado, recibirás (simulado) un enlace para restablecer tu contraseña.";
                $mensaje_tipo = 'info'; // O 'success' para consistencia
            }
            $stmt_check->close();
        } else {
            $mensaje_usuario = "Error al verificar el correo. Inténtalo más tarde.";
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
    <title>Restablecer Contraseña - Biblioteca Virtual</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- <link rel="stylesheet" href="css/styles.css"> --> <!-- Puedes mantener o quitar esta línea si los estilos de abajo son suficientes -->
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
                <i class="fas fa-key fa-3x text-indigo-600 mb-4"></i>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900">Restablecer Contraseña</h2>
                <p class="mt-2 text-sm text-gray-600">Ingresa tu correo electrónico para recibir un enlace de restablecimiento.</p>
            </div>

            <?php if (!empty($mensaje_usuario)): ?>
                <div class="mb-4 p-3 rounded-md text-sm <?php echo $mensaje_tipo === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : ($mensaje_tipo === 'info' ? 'bg-blue-100 text-blue-700 border border-blue-300' : 'bg-red-100 text-red-700 border border-red-300'); ?>">
                    <?php echo htmlspecialchars($mensaje_usuario); ?>
                </div>
            <?php endif; ?>

            <?php if ($mostrar_enlace_simulado && !empty($enlace_restablecimiento_simulado)): ?>
                <div class="mb-4 p-3 rounded-md bg-yellow-100 text-yellow-800 border border-yellow-300 text-sm">
                    <strong>Enlace de restablecimiento (simulado):</strong><br>
                    <a href="<?php echo htmlspecialchars($enlace_restablecimiento_simulado); ?>" class="text-indigo-600 hover:underline break-all"><?php echo htmlspecialchars($enlace_restablecimiento_simulado); ?></a>
                    <p class="mt-1 text-xs">En una aplicación real, este enlace se enviaría a tu correo.</p>
                </div>
            <?php endif; ?>

            <form action="solicitar_restablecimiento.php" method="POST" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required class="input-field mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none sm:text-sm" placeholder="tu.correo@ejemplo.com">
                </div>
                <div>
                    <button type="submit" class="btn-primary w-full flex justify-center py-2.5 px-4 border rounded-md shadow-sm text-sm font-medium text-white">Enviar Enlace de Restablecimiento</button>
                </div>
            </form>
            <p class="mt-6 text-sm text-center text-gray-600">
                ¿Recordaste tu contraseña? <a href="index.php#loginSection" class="font-medium text-indigo-600 hover:text-indigo-500">Inicia Sesión</a>
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