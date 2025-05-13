<?php
session_start();
require_once '../db_config.php';

// Seguridad: Verificar sesión y rol de administrador
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["role"]) || $_SESSION["role"] !== 'administrador') {
    header("HTTP/1.1 403 Forbidden");
    echo "Acceso denegado.";
    exit;
}

// Asumimos que el ID del usuario está en la sesión. Ajusta 'user_id' si tu variable de sesión se llama diferente.
$usuario_id_actual = $_SESSION['user_id'] ?? 0; 
if ($usuario_id_actual === 0) {
    // Manejar caso donde el user_id no está en la sesión, aunque la seguridad de arriba debería cubrirlo.
}

$tipo_reporte = isset($_GET['tipo_reporte']) ? $_GET['tipo_reporte'] : '';
$filename = "reporte_desconocido_" . date('Ymd') . ".csv";

// Establecer cabeceras para la descarga del CSV
header('Content-Type: text/csv; charset=utf-8');
header('Pragma: no-cache');
header('Expires: 0');

// Abrir el flujo de salida de PHP para escribir el CSV
$output = fopen('php://output', 'w');

// Añadir BOM UTF-8 para compatibilidad con Excel y caracteres especiales
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
$parametros_para_log = []; // Para almacenar parámetros específicos del reporte

switch ($tipo_reporte) {
    case 'usuarios_por_rol':
        // Modificado para descargar una lista detallada de usuarios por rol
        $filename = "reporte_lista_usuarios_por_rol_" . date('Ymd') . ".csv";
        $sql = "SELECT id, username, nombre_completo, email, role, estado_aprobacion, fecha_registro 
                FROM usuarios 
                ORDER BY role ASC, nombre_completo ASC";
        $result = $mysqli->query($sql);

        fputcsv($output, ['ID', 'Username', 'Nombre Completo', 'Email', 'Rol', 'Estado Aprobación', 'Fecha Creación'], ';');
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $fecha_registro_formateada = $row['fecha_registro'] ? date("d/m/Y H:i", strtotime($row['fecha_registro'])) : 'N/A';
                fputcsv($output, [
                    $row['id'],
                    $row['username'],
                    $row['nombre_completo'],
                    $row['email'],
                    ucfirst($row['role']),
                    ucfirst($row['estado_aprobacion']),
                    $fecha_registro_formateada
                ], ';');
            }
        }
        break;

    case 'libros_por_categoria_general':
        $filename = "reporte_libros_por_categoria_" . date('Ymd') . ".csv";
        $sql = "SELECT c.nombre as categoria, COUNT(l.id) as cantidad FROM libros l JOIN categorias c ON l.categoria_id = c.id GROUP BY c.id, c.nombre ORDER BY c.nombre";
        $result = $mysqli->query($sql);
        fputcsv($output, ['Categoría', 'Cantidad de Libros'], ';');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [$row['categoria'], $row['cantidad']], ';');
            }
        }
        break;

    case 'top_visualizados':
        $filename = "reporte_top_5_libros_visualizados_" . date('Ymd') . ".csv";
        $sql = "SELECT titulo, autor, visualizaciones FROM libros WHERE visualizaciones > 0 ORDER BY visualizaciones DESC LIMIT 5";
        $result = $mysqli->query($sql);
        fputcsv($output, ['Título', 'Autor', 'Visualizaciones'], ';');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [$row['titulo'], $row['autor'], $row['visualizaciones']], ';');
            }
        }
        break;

    case 'top_descargados':
        $filename = "reporte_top_5_libros_descargados_" . date('Ymd') . ".csv";
        $sql = "SELECT titulo, autor, descargas FROM libros WHERE descargas > 0 ORDER BY descargas DESC LIMIT 5";
        $result = $mysqli->query($sql);
        fputcsv($output, ['Título', 'Autor', 'Descargas'], ';');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [$row['titulo'], $row['autor'], $row['descargas']], ';');
            }
        }
        break;

    case 'busqueda_usuarios':
        $search_user_term = isset($_GET['search_user']) ? trim($_GET['search_user']) : '';
        $filename_suffix = !empty($search_user_term) ? preg_replace('/[^a-z0-9_]/i', '', $search_user_term) : 'todos';
        $parametros_para_log['search_user'] = $search_user_term;
        $filename = "reporte_usuarios_" . $filename_suffix . "_" . date('Ymd') . ".csv";
        
        fputcsv($output, ['ID', 'Username', 'Nombre Completo', 'Email', 'Rol', 'Estado Aprobación', 'Fecha Registro'], ';');
        
        $sql_search_user = "SELECT id, username, nombre_completo, email, role, estado_aprobacion, fecha_registro FROM usuarios";
        $params = [];
        $types = "";
        if (!empty($search_user_term)) {
            $sql_search_user .= " WHERE username LIKE ? OR email LIKE ? OR nombre_completo LIKE ?";
            $param_user_term = "%" . $search_user_term . "%";
            $params = [$param_user_term, $param_user_term, $param_user_term];
            $types = "sss";
        }
        $sql_search_user .= " ORDER BY nombre_completo ASC";

        if($stmt_search_user = $mysqli->prepare($sql_search_user)){
            if (!empty($params)) {
                $stmt_search_user->bind_param($types, ...$params);
            }
            $stmt_search_user->execute();
            $result_users = $stmt_search_user->get_result();
            while($row = $result_users->fetch_assoc()){
                $fecha_registro_formateada = $row['fecha_registro'] ? date("d/m/Y H:i", strtotime($row['fecha_registro'])) : 'N/A';
                $row_csv = [$row['id'], $row['username'], $row['nombre_completo'], $row['email'], $row['role'], $row['estado_aprobacion'], $fecha_registro_formateada];
                fputcsv($output, $row_csv, ';');
            }
            $stmt_search_user->close();
        }
        break;

    case 'busqueda_libros':
        $search_libro_term = isset($_GET['search_libro_term']) ? trim($_GET['search_libro_term']) : '';
        $filename_suffix = !empty($search_libro_term) ? preg_replace('/[^a-z0-9_]/i', '', $search_libro_term) : 'todos';
        $parametros_para_log['search_libro_term'] = $search_libro_term;
        $filename = "reporte_libros_" . $filename_suffix . "_" . date('Ymd') . ".csv";
        
        fputcsv($output, ['ID', 'Título', 'Autor', 'Categoría', 'Editorial', 'Año Pub.', 'Vistas', 'Descargas', 'Subido Por', 'Fecha Subida'], ';');
        
        $sql_search_libro = "SELECT l.id, l.titulo, l.autor, c.nombre as nombre_categoria, l.editorial, l.ano_publicacion, l.visualizaciones, l.descargas, u.username as subido_por_username, l.fecha_subida
                             FROM libros l 
                             LEFT JOIN categorias c ON l.categoria_id = c.id 
                             LEFT JOIN usuarios u ON l.subido_por_usuario_id = u.id";
        $params_lib = [];
        $types_lib = "";
        if (!empty($search_libro_term)) {
            $sql_search_libro .= " WHERE l.titulo LIKE ? OR l.autor LIKE ?";
            $param_libro_term = "%" . $search_libro_term . "%";
            $params_lib = [$param_libro_term, $param_libro_term];
            $types_lib = "ss";
        }
        $sql_search_libro .= " ORDER BY l.titulo ASC";

        if($stmt_search_libro = $mysqli->prepare($sql_search_libro)){
            if(!empty($params_lib)){
                $stmt_search_libro->bind_param($types_lib, ...$params_lib);
            }
            $stmt_search_libro->execute();
            $result_libros_search = $stmt_search_libro->get_result();
            while($row = $result_libros_search->fetch_assoc()){
                $fecha_subida_formateada = (isset($row['fecha_subida']) && $row['fecha_subida']) ? date("d/m/Y H:i", strtotime($row['fecha_subida'])) : 'N/A';
                fputcsv($output, [$row['id'], $row['titulo'], $row['autor'], $row['nombre_categoria'], $row['editorial'], $row['ano_publicacion'], $row['visualizaciones'], $row['descargas'], $row['subido_por_username'], $fecha_subida_formateada], ';');
            }
            $stmt_search_libro->close();
        }
        break;

    case 'libros_por_categoria_detalle':
        $selected_categoria_id = isset($_GET['categoria_id']) ? intval($_GET['categoria_id']) : 0;
        $nombre_categoria_param = isset($_GET['categoria_nombre']) ? preg_replace('/[^a-z0-9_]/i', '', $_GET['categoria_nombre']) : 'desconocida';
        $parametros_para_log['categoria_id'] = $selected_categoria_id;
        $parametros_para_log['categoria_nombre'] = isset($_GET['categoria_nombre']) ? $_GET['categoria_nombre'] : '';
        $filename = "reporte_libros_categoria_" . $nombre_categoria_param . "_" . date('Ymd') . ".csv";
        
        fputcsv($output, ['ID Libro', 'Título', 'Autor', 'Visualizaciones', 'Descargas', 'Fecha Subida'], ';');

        if ($selected_categoria_id > 0) {
            $sql_libros_cat = "SELECT l.id, l.titulo, l.autor, l.visualizaciones, l.descargas, l.fecha_subida FROM libros l WHERE l.categoria_id = ? ORDER BY l.titulo ASC";
            if($stmt_libros_cat = $mysqli->prepare($sql_libros_cat)){
                $stmt_libros_cat->bind_param("i", $selected_categoria_id);
                $stmt_libros_cat->execute();
                $result_libros_cat = $stmt_libros_cat->get_result();
                while($row = $result_libros_cat->fetch_assoc()){
                    $fecha_subida_formateada_cat = (isset($row['fecha_subida']) && $row['fecha_subida']) ? date("d/m/Y H:i", strtotime($row['fecha_subida'])) : 'N/A';
                    $row_csv_cat = [$row['id'], $row['titulo'], $row['autor'], $row['visualizaciones'], $row['descargas'], $fecha_subida_formateada_cat];
                    fputcsv($output, $row_csv_cat, ';');
                }
                $stmt_libros_cat->close();
            }
        }
        break;

    default:
        fputcsv($output, ["Tipo de reporte no válido o no especificado."], ';');
        break;
}

// Registrar la descarga en la nueva tabla (solo si el tipo de reporte fue válido y no es el default)
if ($tipo_reporte !== '' && $filename !== "reporte_desconocido_" . date('Ymd') . ".csv") {
    $parametros_json = !empty($parametros_para_log) ? json_encode($parametros_para_log) : null;
    $sql_log = "INSERT INTO log_descargas_reportes (usuario_id, tipo_reporte, parametros_reporte, nombre_archivo) VALUES (?, ?, ?, ?)";
    if ($stmt_log = $mysqli->prepare($sql_log)) {
        $stmt_log->bind_param("isss", $usuario_id_actual, $tipo_reporte, $parametros_json, $filename);
        $stmt_log->execute();
        $stmt_log->close();
    }
}


header('Content-Disposition: attachment; filename="' . $filename . '"');

fclose($output);
$mysqli->close();
exit;
?>
