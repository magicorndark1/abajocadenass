<?php
session_start();
require_once 'db_config.php';

$libro_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($libro_id > 0) {
    // Obtener la información del libro, especialmente la ruta del archivo
    $sql = "SELECT titulo, ruta_archivo_pdf FROM libros WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $libro_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $libro = $resultado->fetch_assoc();
            $stmt->close();

             // ruta_archivo_pdf es 'uploads/nombre_archivo.pdf', relativa a la raíz del proyecto.
            $ruta_completa_servidor = __DIR__ . '/' . $libro['ruta_archivo_pdf'];

            if (file_exists($ruta_completa_servidor) && is_readable($ruta_completa_servidor)) {
                // Incrementar contador de descargas
                $update_downloads_sql = "UPDATE libros SET descargas = descargas + 1 WHERE id = ?";
                if ($stmt_downloads = $mysqli->prepare($update_downloads_sql)) {
                    $stmt_downloads->bind_param("i", $libro_id);
                    $stmt_downloads->execute();
                    $stmt_downloads->close();
                }

                // Configurar cabeceras para la descarga
                header('Content-Description: File Transfer');
                header('Content-Type: application/pdf'); // O application/octet-stream para forzar descarga de cualquier tipo
                header('Content-Disposition: attachment; filename="' . basename($libro['titulo'] . '.pdf') . '"'); // Nombre del archivo para el usuario
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($ruta_completa_servidor));
                
                // Limpiar el buffer de salida antes de leer el archivo
                ob_clean();
                flush();
                
                readfile($ruta_completa_servidor);
                $mysqli->close();
                exit;
            } else {
                error_log("Archivo no encontrado o no legible: " . $ruta_completa_servidor);
                die("Error: El archivo del libro no se encuentra en el servidor o no se puede leer.");
            }
        } else {
            $stmt->close();
            die("Error: Libro no encontrado.");
        }
    } else {
        die("Error al preparar la consulta: " . $mysqli->error);
    }
} else {
    die("Error: ID de libro no válido.");
}

$mysqli->close();
?>