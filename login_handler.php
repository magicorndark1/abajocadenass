<?php
// c:\xampp\htdocs\abajocadenas\login_handler.php

// Habilitar la visualización de errores de PHP (SOLO PARA DESARROLLO)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_config.php'; // Asegúrate que la ruta sea correcta

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CORREGIDO: El campo en index.php se llama 'identifier'.
    // Ajustamos para que coincida con el name="identifier" del formulario.
    $usernameOrEmail = trim($_POST['identifier']); // Antes era $_POST['username']
    $password = $_POST['password'];
    // El rol enviado desde el formulario de login en index.php no se está usando actualmente
    // para la consulta SQL de autenticación, lo cual es generalmente correcto.
    // Se usa el rol de la BD para la redirección.
    // $role_from_form = isset($_POST['role']) ? trim($_POST['role']) : null;

    if (empty($usernameOrEmail) || empty($password)) {
        $error_message = "Por favor, ingresa tu nombre de usuario/email y contraseña.";
    } else {
        // Buscar usuario por username o email
        // Es importante seleccionar el hash de la contraseña y el estado de aprobación
        $sql = "SELECT id, username, password, role, estado_aprobacion, nombre_completo FROM usuarios WHERE username = ? OR email = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $db_username, $db_hashed_password, $db_role, $db_estado_aprobacion, $db_nombre_completo);
                $stmt->fetch();

                // Verificar la contraseña usando password_verify()
                if (password_verify($password, $db_hashed_password)) {
                    // Contraseña correcta, verificar estado de aprobación
                    if ($db_estado_aprobacion == 'aprobado') {
                        // Iniciar sesión
                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_id'] = $id;
                        $_SESSION['username'] = $db_username; // Guardamos el username de la BD
                        $_SESSION['role'] = $db_role;
                        $_SESSION['nombre_completo'] = $db_nombre_completo;

                        // Redirigir según el rol
                        if ($db_role == 'administrador') { // Comparar con 'administrador' si así está en la BD
                            header("location: admin/dashboard_admin.php");
                            exit;
                        } elseif ($db_role == 'docente') {
                            header("location: docente/dashboard_docente.php");
                            exit;
                        } elseif ($db_role == 'estudiante') {
                            header("location: estudiante/dashboard_estudiante.php");
                            exit;
                        } else {
                            // Rol desconocido o no especificado desde la BD.
                            // Aunque el login (usuario/pass) fue exitoso y la cuenta está aprobada,
                            // el rol no es manejable por los dashboards actuales.
                            // En lugar de redirigir a index.php manteniendo la sesión activa (lo que causaría un bucle),
                            // se considera un error de configuración del usuario o del sistema.
                            // Limpiamos las variables de sesión que se pudieron haber establecido y mostramos error.
                            unset($_SESSION['loggedin']);
                            unset($_SESSION['user_id']);
                            unset($_SESSION['username']);
                            unset($_SESSION['role']);
                            unset($_SESSION['nombre_completo']);
                            $error_message = "Rol de usuario ('" . htmlspecialchars($db_role) . "') no reconocido. Contacte al administrador.";
                        }
                    } elseif ($db_estado_aprobacion == 'pendiente') {
                        $error_message = "Tu cuenta aún está pendiente de aprobación por un administrador.";
                    } elseif ($db_estado_aprobacion == 'rechazado') {
                        $error_message = "Tu cuenta ha sido rechazada. Contacta al administrador para más información.";
                    } else {
                         $error_message = "El estado de tu cuenta es desconocido (".htmlspecialchars($db_estado_aprobacion)."). Contacta al administrador.";
                    }
                } else {
                    // Contraseña incorrecta
                    $error_message = "Nombre de usuario/email o contraseña incorrectos.";
                }
            } else {
                // Usuario no encontrado
                $error_message = "Nombre de usuario/email o contraseña incorrectos.";
            }
            $stmt->close();
        } else {
            $error_message = "Error al preparar la consulta: " . $mysqli->error;
            // Para depuración avanzada, podrías loguear $mysqli->error aquí
        }
    }

    // Si hubo un error, redirigir de nuevo a la página de login con el mensaje
    if (!empty($error_message)) {
        header("location: index.php?error_login=" . urlencode($error_message) . "#loginSection");
        exit;
    }

    $mysqli->close();
} else {
    // Si no es POST, redirigir a la página de inicio o login
    header("location: index.php");
    exit;
}
?>
