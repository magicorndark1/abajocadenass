<?php
// registro_handler.php
session_start();
require_once 'db_config.php';

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger y sanear (básicamente) los datos del formulario
    $nombre_completo = trim($_POST['nombre_completo']);
    $cedula = trim($_POST['cedula']); // Validar formato V-12345678 o E-12345678, J, G, P
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $direccion = trim($_POST['direccion']);
    $username = trim($_POST['username']);
    $role = trim($_POST['role']); // Asegurarse que sea 'estudiante' o 'docente'
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // --- Validaciones del Lado del Servidor ---
    if (empty($nombre_completo) || empty($cedula) || empty($email) || empty($username) || empty($role) || empty($password)) {
        $error_message = "Por favor, completa todos los campos obligatorios.";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $nombre_completo)) {
        $error_message = "El nombre completo solo debe contener letras y espacios.";
    } elseif (!preg_match("/^[VEJPGvejpg]-\d{7,9}$/", $cedula)) { // Ajustado para 7-9 dígitos después del prefijo
        $error_message = "Formato de cédula inválido. Debe ser V, E, J, P o G seguido de '-' y 7-9 números (Ej: V-12345678).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Formato de correo electrónico inválido.";
    } elseif (strlen($username) < 4 || strlen($username) > 20 || !preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        $error_message = "El nombre de usuario debe tener entre 4 y 20 caracteres, y solo puede contener letras, números y guion bajo (_).";
    } elseif (!in_array($role, ['estudiante', 'docente'])) {
        $error_message = "Rol seleccionado no válido.";
    } elseif (strlen($password) < 8) {
        $error_message = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Las contraseñas no coinciden.";
    } else {
        // Verificar si el username o email o cédula ya existen
        $sql_check = "SELECT id FROM usuarios WHERE username = ? OR email = ? OR cedula = ?";
        if ($stmt_check = $mysqli->prepare($sql_check)) {
            $stmt_check->bind_param("sss", $username, $email, $cedula);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                // Podríamos ser más específicos sobre qué campo ya existe
                $stmt_check->bind_result($id_existente);
                $stmt_check->fetch();
                // Aquí podrías verificar cuál de los campos causó el conflicto si es necesario
                $error_message = "El nombre de usuario, correo electrónico o cédula ya están registrados.";
            }
            $stmt_check->close();
        } else {
            $error_message = "Error preparando la consulta de verificación: " . $mysqli->error;
        }

        // Si no hay errores de validación ni duplicados, proceder a insertar
        if (empty($error_message)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $estado_aprobacion = 'pendiente'; // Todos los registros nuevos quedan pendientes

            $sql_insert = "INSERT INTO usuarios (nombre_completo, cedula, telefono, email, direccion, username, password, role, estado_aprobacion)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            if ($stmt_insert = $mysqli->prepare($sql_insert)) {
                $stmt_insert->bind_param("sssssssss",
                    $nombre_completo,
                    $cedula,
                    $telefono,
                    $email,
                    $direccion,
                    $username,
                    $hashed_password,
                    $role,
                    $estado_aprobacion
                );

                if ($stmt_insert->execute()) {
                    $success_message = "¡Registro exitoso! Tu cuenta está pendiente de aprobación por un administrador. Serás notificado.";
                    // Redirigir a index.php con mensaje de éxito
                    header("location: index.php?success_registro=" . urlencode($success_message) . "#loginSection");
                    exit;
                } else {
                    $error_message = "Hubo un error al registrar tu cuenta. Inténtalo de nuevo más tarde. Error: " . $stmt_insert->error;
                }
                $stmt_insert->close();
            } else {
                $error_message = "Error preparando la consulta de inserción: " . $mysqli->error;
            }
        }
    }

    // Si hubo un error, redirigir de nuevo a registro.php con el mensaje
    if (!empty($error_message)) {
        header("location: registro.php?error_registro=" . urlencode($error_message));
        exit;
    }

    $mysqli->close();
} else {
    // Si no es POST, redirigir a la página de registro
    header("location: registro.php");
    exit;
}
?>
