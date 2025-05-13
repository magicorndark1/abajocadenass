<?php
session_start();
require_once 'db_config.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index.php?error=not_logged_in"); // O a la página de login
    exit;
}

// Verificar que el usuario sea un estudiante
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'estudiante') {
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'catalogo.php';
    header("Location: " . $redirect_url . "?error_favorito=role_not_allowed");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['libro_id']) && isset($_POST['action'])) {
    $libro_id = intval($_POST['libro_id']);
    $usuario_id = $_SESSION['user_id'];
    $action = $_POST['action']; // 'agregar' o 'remover'
    $return_url = isset($_POST['return_url']) ? $_POST['return_url'] : 'detalle_libro.php?id=' . $libro_id; // URL por defecto

    if ($libro_id <= 0) {
        // Si la URL de retorno es el catálogo, mantenla, sino, detalle_libro.
        $fallback_url = (strpos($return_url, 'catalogo.php') !== false) ? $return_url : 'detalle_libro.php?id=' . $libro_id;
        header("Location: " . $fallback_url . "&error_favorito=invalid_book");
        exit;
    }

    if ($action === 'agregar') {
        $sql = "INSERT IGNORE INTO favoritos (usuario_id, libro_id) VALUES (?, ?)";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ii", $usuario_id, $libro_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['mensaje_detalle'] = "Libro añadido a favoritos.";
                    $_SESSION['mensaje_detalle_tipo'] = "success";
                } else {
                    $_SESSION['mensaje_detalle'] = "El libro ya estaba en tus favoritos.";
                    $_SESSION['mensaje_detalle_tipo'] = "info"; // O 'success' si prefieres
                }
            } else {
                $_SESSION['mensaje_detalle'] = "Error al añadir a favoritos: " . $stmt->error;
                $_SESSION['mensaje_detalle_tipo'] = "error";
            }
            $stmt->close();
        } else {
            $_SESSION['mensaje_detalle'] = "Error al preparar la consulta: " . $mysqli->error;
            $_SESSION['mensaje_detalle_tipo'] = "error";
        }
    } elseif ($action === 'remover') {
        $sql = "DELETE FROM favoritos WHERE usuario_id = ? AND libro_id = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ii", $usuario_id, $libro_id);
            if ($stmt->execute()) {
                $_SESSION['mensaje_detalle'] = "Libro removido de favoritos.";
                $_SESSION['mensaje_detalle_tipo'] = "success";
            } else {
                $_SESSION['mensaje_detalle'] = "Error al remover de favoritos: " . $stmt->error;
                $_SESSION['mensaje_detalle_tipo'] = "error";
            }
            $stmt->close();
        } else {
            $_SESSION['mensaje_detalle'] = "Error al preparar la consulta: " . $mysqli->error;
            $_SESSION['mensaje_detalle_tipo'] = "error";
        }
    } else {
        $_SESSION['mensaje_detalle'] = "Acción no válida.";
        $_SESSION['mensaje_detalle_tipo'] = "error";
    }

    // Validar y usar return_url
    // Simple validación para evitar redirecciones abiertas, asegúrate que la URL es relativa a tu sitio.
    if (strpos($return_url, 'http://') === 0 || strpos($return_url, 'https://') === 0) {
        // Si es una URL absoluta, verifica que sea de tu propio host
        if (parse_url($return_url, PHP_URL_HOST) !== $_SERVER['HTTP_HOST']) {
            $return_url = 'catalogo.php'; // Fallback seguro
        }
    } elseif (strpos($return_url, '/') !== 0 && strpos($return_url, '../') !== 0) {
         // Si no es absoluta y no empieza con / o ../, asumimos que es relativa al directorio actual.
         // Esto podría necesitar más lógica dependiendo de tu estructura.
    }

    header("Location: " . $return_url);
    exit;

} else {
    header("Location: catalogo.php");
    exit;
}
?>
