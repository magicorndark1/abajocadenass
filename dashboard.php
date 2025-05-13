<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php?error_unauthorized=true");
    exit;
}

// Redirigir según el rol del usuario
if (isset($_SESSION["role"])) {
    $role = $_SESSION["role"];
    if ($role === 'administrador') { // Asegurarse que coincida con el valor de la BD y login_handler
        header("location: admin/dashboard_admin.php");
        exit;
    } elseif ($role === 'docente') {
        // Asumiendo que tienes una carpeta 'docente' con 'dashboard_docente.php'
        header("location: docente/dashboard_docente.php"); 
        exit;
    } elseif ($role === 'estudiante') {
        // Asumiendo que tienes una carpeta 'estudiante' con 'dashboard_estudiante.php'
        header("location: estudiante/dashboard_estudiante.php");
        exit;
    } else {
        // Rol desconocido o no manejado.
        // Si el usuario está logueado pero tiene un rol inválido,
        // es mejor cerrar la sesión y redirigir a index.php con un error.
        // Esto previene el bucle de redirección.
        session_unset(); // Elimina todas las variables de sesión
        session_destroy(); // Destruye la sesión
        header("location: index.php?error_role=invalid_role_session_terminated");
        exit;
    }
} else {
    // No hay rol en la sesión, pero el usuario está logueado (verificado al inicio).
    // Esto indica un estado de sesión inconsistente.
    // Cerrar la sesión y redirigir.
    session_unset();
    session_destroy();
    header("location: index.php?error_session=missing_role_session_terminated");
    exit;
}
// El resto del archivo PHP no es necesario ya que siempre hay una redirección o exit.
?>