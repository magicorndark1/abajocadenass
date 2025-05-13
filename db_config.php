<?php
// c:\xampp\htdocs\abajocadenas\db_config.php

/* Credenciales de la base de datos */
define('DB_SERVER', 'localhost'); // O la IP de tu servidor de base de datos
define('DB_USERNAME', 'root');    // Tu usuario de MySQL
define('DB_PASSWORD', '');        // Tu contraseña de MySQL (por defecto en XAMPP es vacía)
define('DB_NAME', 'abajocadenas'); // El nombre de tu base de datos

/* Intentar conectar a la base de datos MySQL */
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Establecer el charset a utf8mb4 para soportar una amplia gama de caracteres
if (!$mysqli->set_charset("utf8mb4")) {
    // printf("Error cargando el conjunto de caracteres utf8mb4: %s\n", $mysqli->error);
    // Considera cómo manejar este error, quizás loguearlo o mostrar un mensaje genérico
}

// Verificar la conexión
if($mysqli === false || $mysqli->connect_error){
    // No mostrar errores detallados de la base de datos en producción por seguridad
    die("ERROR: No se pudo conectar a la base de datos. " . $mysqli->connect_error);
}
?>