<?php
// c:\xampp\htdocs\abajocadenas\admin\admin_functions.php
require_once '../db_config.php'; // Ruta corregida para apuntar al directorio padre

class Administrador {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Gestiona los usuarios (aprobar, rechazar, eliminar).
     * @param int $user_id ID del usuario a gestionar.
     * @param string $action Acción a realizar: 'aprobar', 'rechazar' o 'eliminar'.
     * @return bool|string True en caso de éxito, mensaje de error en caso de fallo.
     */
    public function gestionarUsuario($user_id, $action) {
        try {
            if (!in_array($action, ['aprobar', 'rechazar', 'eliminar'])) {
                throw new InvalidArgumentException("Acción no válida: $action");
            }

            $this->conn->begin_transaction(); // Iniciar transacción para asegurar atomicidad

            switch ($action) {
                case 'aprobar':
                    $sql = "UPDATE usuarios SET estado_aprobacion = 'aprobado' WHERE id = ?";
                    break;
                case 'rechazar':
                    $sql = "UPDATE usuarios SET estado_aprobacion = 'rechazado' WHERE id = ?";
                    break;
                case 'eliminar':
                    // Antes de eliminar al usuario, eliminar sus registros relacionados en otras tablas
                    $this->eliminarRegistrosUsuario($user_id); // Método para eliminar registros relacionados
                    $sql = "DELETE FROM usuarios WHERE id = ?";
                    break;
            }

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $this->conn->error);
            }

            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }

            $stmt->close();
            $this->conn->commit(); // Confirmar la transacción
            return true;

        } catch (Exception $e) {
            $this->conn->rollback(); // Revertir la transacción en caso de error
            return "Error: " . $e->getMessage(); // Devolver el mensaje de error
        }
    }

    /**
     * Elimina los registros relacionados de un usuario en otras tablas (favoritos, vistas, etc.).
     * @param int $user_id ID del usuario.
     * @return void
     */
    private function eliminarRegistrosUsuario($user_id) {
        // Implementar lógica para eliminar registros en tablas como 'favoritos', 'ultimas_vistas', etc.
        // Esto es crucial para mantener la integridad de la base de datos.
        // Ejemplo:
        $tablas_a_limpiar = ['favoritos', 'ultimas_vistas']; // Agregar otras tablas según sea necesario
        foreach ($tablas_a_limpiar as $tabla) {
            // Verificar si la tabla existe antes de intentar eliminar
            $check_table_sql = "SHOW TABLES LIKE '$tabla'";
            $table_result = $this->conn->query($check_table_sql);
            if ($table_result && $table_result->num_rows > 0) {
                $sql_delete = "DELETE FROM $tabla WHERE usuario_id = ?";
                $stmt_delete = $this->conn->prepare($sql_delete);
                if ($stmt_delete) {
                    $stmt_delete->bind_param("i", $user_id);
                    $stmt_delete->execute(); 
                    $stmt_delete->close();
                }
            }
        }
    }


    /**
     * Sube un libro en formato PDF.
     * @param array $file_data Datos del archivo PDF ($_FILES['archivo']).
     * @param string $titulo Título del libro.
     * @param string $autor Autor del libro.
     * @param int $categoria_id ID de la categoría del libro.
     * @param string $editorial Editorial del libro.
     * @param int $ano_publicacion Año de publicación del libro.
     * @param string $descripcion Descripción del libro.
     * @return bool|string True en caso de éxito, mensaje de error en caso de fallo.
     */
    public function subirLibroPDF($file_data, $titulo, $autor, $categoria_id, $editorial, $ano_publicacion, $descripcion) {
        try {
            
            $sql_check = "SELECT id FROM libros WHERE titulo = ? AND autor = ?";
            if ($stmt_check = $this->conn->prepare($sql_check)) {
                $stmt_check->bind_param("ss", $titulo, $autor);
                $stmt_check->execute();
                $stmt_check->store_result();
                if ($stmt_check->num_rows > 0) {
                    throw new Exception("Error: Ya existe un libro con el mismo título y autor.");
                }
                $stmt_check->close();
            } else {
                error_log("Error al preparar la consulta para verificar el libro: " . $this->conn->error);
            }

            if ($file_data['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException("Error al subir el archivo: " . $this->getUploadErrorMessage($file_data['error']));
            }

            $allowed_types = ['application/pdf'];
            if (!in_array($file_data['type'], $allowed_types)) {
                throw new InvalidArgumentException("Tipo de archivo no válido. Se espera un archivo PDF.");
            }

            $max_size = 20 * 1024 * 1024; 
            if ($file_data['size'] > $max_size) {
                throw new InvalidArgumentException("El archivo excede el tamaño máximo permitido (20MB).");
            }

            $nombre_archivo_unico = uniqid('libro_', true) . '.pdf';
            $ruta_destino_servidor = '../uploads/' . $nombre_archivo_unico; 
            $ruta_para_db = 'uploads/' . $nombre_archivo_unico; 

            if (empty($ruta_para_db) || !is_string($ruta_para_db) || strlen(trim($ruta_para_db)) === 0 || strpos($ruta_para_db, 'uploads/') !== 0) {
                error_log("DEBUG SUBIRLIBRO: Error Crítico - \$ruta_para_db no es válida. Valor: '" . var_export($ruta_para_db, true) . "'");
                throw new Exception("Error interno: La ruta del archivo para la base de datos no se pudo construir correctamente.");
            }
            
            if (!move_uploaded_file($file_data['tmp_name'], $ruta_destino_servidor)) {
                throw new RuntimeException("Error al mover el archivo subido.");
            }

            $usuario_id_actual = $_SESSION['user_id'] ?? null; 

            // La columna se llama fecha_subida en la BD
            $sql = "INSERT INTO libros (titulo, autor, categoria_id, editorial, ano_publicacion, descripcion, ruta_archivo_pdf, subido_por_usuario_id, fecha_subida, cantidad_disponible) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)"; 
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $this->conn->error);
            }
            
            $stmt->bind_param("ssissssi", $titulo, $autor, $categoria_id, $editorial, $ano_publicacion, $descripcion, $ruta_para_db, $usuario_id_actual);

            if (!$stmt->execute()) {
                error_log("DEBUG SUBIRLIBRO: Error en stmt->execute(): " . $stmt->error . " (Error No: " . $stmt->errno . ")");
                throw new Exception("Error al insertar el libro en la base de datos: " . $stmt->error);
            }
            
            $stmt->close();
            return true;

        } catch (Exception $e) {
             if (isset($ruta_destino_servidor) && file_exists($ruta_destino_servidor)) {
                 unlink($ruta_destino_servidor);
             }
            return "Error: " . $e->getMessage();
        }
    }

    private function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return "El archivo excede el tamaño permitido por la directiva upload_max_filesize en php.ini.";
            case UPLOAD_ERR_FORM_SIZE:
                return "El archivo excede el tamaño permitido por la directiva MAX_FILE_SIZE en el formulario HTML.";
            case UPLOAD_ERR_PARTIAL:
                return "El archivo fue subido parcialmente.";
            case UPLOAD_ERR_NO_FILE:
                return "No se subió ningún archivo.";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Falta la carpeta temporal.";
            case UPLOAD_ERR_CANT_WRITE:
                return "No se pudo escribir el archivo en el disco.";
            case UPLOAD_ERR_EXTENSION:
                return "La subida del archivo fue detenida por una extensión.";
            default:
                return "Error desconocido al subir el archivo.";
        }
    }

    public function agregarCategoria($nombre_categoria, $descripcion) {
        try {
            if (empty($nombre_categoria)) {
                throw new InvalidArgumentException("El nombre de la categoría no puede estar vacío.");
            }
            $sql_check = "SELECT id FROM categorias WHERE nombre = ?";
            $stmt_check = $this->conn->prepare($sql_check);
            $stmt_check->bind_param("s", $nombre_categoria);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $stmt_check->close();
                throw new Exception("Ya existe una categoría con el nombre: " . $nombre_categoria);
            }
            $stmt_check->close();

            $sql = "INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $this->conn->error);
            }
            $stmt->bind_param("ss", $nombre_categoria, $descripcion);
            if (!$stmt->execute()) {
                throw new Exception("Error al agregar la categoría: " . $stmt->error);
            }
            $stmt->close();
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function modificarCategoria($categoria_id, $nuevo_nombre, $nueva_descripcion) {
        try {
            if (empty($nuevo_nombre)) {
                throw new InvalidArgumentException("El nombre de la categoría no puede estar vacío.");
            }
            $sql_check = "SELECT id FROM categorias WHERE id = ?";
            $stmt_check = $this->conn->prepare($sql_check);
            $stmt_check->bind_param("i", $categoria_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows == 0) {
                $stmt_check->close();
                throw new Exception("No existe la categoría con el ID: " . $categoria_id);
            }
            $stmt_check->close();

            $sql_check_nombre = "SELECT id FROM categorias WHERE nombre = ? AND id != ?";
            $stmt_check_nombre = $this->conn->prepare($sql_check_nombre);
            $stmt_check_nombre->bind_param("si", $nuevo_nombre, $categoria_id);
            $stmt_check_nombre->execute();
            $stmt_check_nombre->store_result();
            if ($stmt_check_nombre->num_rows > 0) {
                $stmt_check_nombre->close();
                throw new Exception("Ya existe una categoría con el nombre: " . $nuevo_nombre);
            }
            $stmt_check_nombre->close();

            $sql = "UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $this->conn->error);
            }
            $stmt->bind_param("ssi", $nuevo_nombre, $nueva_descripcion, $categoria_id);
            if (!$stmt->execute()) {
                throw new Exception("Error al modificar la categoría: " . $stmt->error);
            }
            $stmt->close();
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
    
    public function eliminarCategoria($categoria_id) {
         try {
            $sql_check = "SELECT id FROM categorias WHERE id = ?";
            $stmt_check = $this->conn->prepare($sql_check);
            $stmt_check->bind_param("i", $categoria_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows == 0) {
                $stmt_check->close();
                throw new Exception("No existe la categoría con el ID: " . $categoria_id);
            }
            $stmt_check->close();

            $sql_update_libros = "UPDATE libros SET categoria_id = NULL WHERE categoria_id = ?";
            $stmt_update_libros = $this->conn->prepare($sql_update_libros);
            if (!$stmt_update_libros) {
                throw new Exception("Error al preparar la consulta de actualización de libros: " . $this->conn->error);
            }
            $stmt_update_libros->bind_param("i", $categoria_id);
            if (!$stmt_update_libros->execute()) {
                throw new Exception("Error al actualizar los libros: " . $stmt_update_libros->error);
            }
            $stmt_update_libros->close();

            $sql = "DELETE FROM categorias WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $this->conn->error);
            }
            $stmt->bind_param("i", $categoria_id);
            if (!$stmt->execute()) {
                throw new Exception("Error al eliminar la categoría: " . $stmt->error);
            }
            $stmt->close();
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function agregarFavorito($usuario_id, $libro_id) {
        try {
            $sql_check = "SELECT id FROM favoritos WHERE usuario_id = ? AND libro_id = ?";
            $stmt_check = $this->conn->prepare($sql_check);
            if (!$stmt_check) throw new Exception("Error preparando check favorito: " . $this->conn->error);
            $stmt_check->bind_param("ii", $usuario_id, $libro_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $stmt_check->close();
                return "El libro ya está en tu lista de favoritos.";
            }
            $stmt_check->close();

            $sql = "INSERT INTO favoritos (usuario_id, libro_id) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $this->conn->error);
            }
            $stmt->bind_param("ii", $usuario_id, $libro_id);
            if (!$stmt->execute()) {
                throw new Exception("Error al agregar a favoritos: " . $stmt->error);
            }
            $stmt->close();
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function removerFavorito($usuario_id, $libro_id) {
        try {
            $sql = "DELETE FROM favoritos WHERE usuario_id = ? AND libro_id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $this->conn->error);
            }
            $stmt->bind_param("ii", $usuario_id, $libro_id);
            if (!$stmt->execute()) {
                throw new Exception("Error al eliminar de favoritos: " . $stmt->error);
            }
            $stmt->close();
            return true;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function getFavoritos($usuario_id) {
        try{
            $sql = "SELECT l.* FROM libros l
                    INNER JOIN favoritos f ON l.id = f.libro_id
                    WHERE f.usuario_id = ?";
            $stmt = $this->conn->prepare($sql);
             if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $this->conn->error);
            }
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $libros = array();
            while ($row = $result->fetch_assoc()) {
                $libros[] = $row;
            }
            $stmt->close();
            return $libros;

        } catch(Exception $e){
            return "Error: ".$e->getMessage();
        }
    }

    public function registrarUltimaVista($usuario_id, $libro_id) {
        try {
            // Usar INSERT ... ON DUPLICATE KEY UPDATE para manejar inserción o actualización
            $sql = "INSERT INTO ultimas_vistas (usuario_id, libro_id, fecha_vista) VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE fecha_vista = NOW()";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta para registrar vista: " . $this->conn->error);
            }
            $stmt->bind_param("ii", $usuario_id, $libro_id);
            if (!$stmt->execute()) {
                throw new Exception("Error al registrar la última vista: " . $stmt->error);
            }
            $stmt->close();
            return true;
        } catch (Exception $e) {
            error_log("Error en registrarUltimaVista: " . $e->getMessage());
            return "Error: " . $e->getMessage();
        }
    }

    public function getUltimasVistas($usuario_id, $limit = 10) {
        try {
            $sql = "SELECT l.id, l.titulo, l.autor, l.ruta_portada_img, l.descripcion, c.nombre as nombre_categoria, uv.fecha_vista 
                    FROM libros l
                    INNER JOIN ultimas_vistas uv ON l.id = uv.libro_id
                    LEFT JOIN categorias c ON l.categoria_id = c.id
                    WHERE uv.usuario_id = ?
                    ORDER BY uv.fecha_vista DESC
                    LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta de últimas vistas: " . $this->conn->error);
            }
            $stmt->bind_param("ii", $usuario_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $libros = array();
            while ($row = $result->fetch_assoc()) {
                $libros[] = $row;
            }
            $stmt->close();
            return $libros;
        } catch (Exception $e) {
            error_log("Error en getUltimasVistas: " . $e->getMessage());
            return []; // Devolver array vacío en caso de error
        }
    }

    public function generarReporteLibros() {
        try {
            $sql = "SELECT l.*, c.nombre as nombre_categoria
                    FROM libros l
                    LEFT JOIN categorias c ON l.categoria_id = c.id"; // Corregido: ON l.categoria_id = c.id
            $result = $this->conn->query($sql);
            if (!$result) {
                throw new Exception("Error al obtener el reporte de libros: " . $this->conn->error);
            }
            $libros = array();
            while ($row = $result->fetch_assoc()) {
                $libros[] = $row;
            }
            return $libros;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function generarReporteLibro($libro_id) {
        try {
            $sql = "SELECT l.*, c.nombre as nombre_categoria
                    FROM libros l
                    LEFT JOIN categorias c ON l.categoria_id = c.id  -- Corregido: ON l.categoria_id = c.id
                    WHERE l.id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $this->conn->error);
            }
            $stmt->bind_param("i", $libro_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 0) {
                return "Libro no encontrado.";
            }
            $libro = $result->fetch_assoc();
            $stmt->close();
            return $libro;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function generarReporteUsuarios() {
        try {
            $sql = "SELECT * FROM usuarios";
            $result = $this->conn->query($sql);
            if (!$result) {
                throw new Exception("Error al obtener el reporte de usuarios: " . $this->conn->error);
            }
            $usuarios = array();
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
            return $usuarios;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function generarReporteUsuario($usuario_id) {
        try {
            $sql = "SELECT * FROM usuarios WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $this->conn->error);
            }
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 0) {
                return "Usuario no encontrado.";
            }
            $usuario = $result->fetch_assoc();
            $stmt->close();
            return $usuario;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function getTotalUsuarios() {
        try {
            $sql = "SELECT COUNT(*) as total FROM usuarios";
            $result = $this->conn->query($sql);
            if (!$result) {
                throw new Exception("Error al obtener el total de usuarios: " . $this->conn->error);
            }
            $row = $result->fetch_assoc();
            return $row['total'];
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function getUsuariosPendientes() {
        try {
            $sql = "SELECT COUNT(*) as pendientes FROM usuarios WHERE estado_aprobacion = 'pendiente'";
            $result = $this->conn->query($sql);
            if (!$result) {
                throw new Exception("Error al obtener usuarios pendientes: " . $this->conn->error);
            }
            $row = $result->fetch_assoc();
            return $row['pendientes'];
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function getTotalLibros() {
        try {
            $sql = "SELECT COUNT(*) as total FROM libros";
            $result = $this->conn->query($sql);
            if (!$result) {
                throw new Exception("Error al obtener el total de libros: " . $this->conn->error);
            }
            $row = $result->fetch_assoc();
            return $row['total'];
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function getLibrosDisponibles() {
        try {
            $sql = "SELECT SUM(cantidad_disponible) as disponibles FROM libros";
            $result = $this->conn->query($sql);
            if (!$result) {
                throw new Exception("Error al obtener libros disponibles: " . $this->conn->error);
            }
            $row = $result->fetch_assoc();
            return $row['disponibles'] ?? 0; // Devolver 0 si es NULL (ningún libro)
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function getLibrosRecientes($limit = 5) {
        try {
            $sql = "SELECT * FROM libros ORDER BY fecha_subida DESC LIMIT ?"; // Usar fecha_subida
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar consulta de libros recientes: " . $this->conn->error);
            }
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $libros = [];
            while ($row = $result->fetch_assoc()) {
                $libros[] = $row;
            }
            $stmt->close();
            return $libros;
        } catch (Exception $e) {
            error_log("Error en getLibrosRecientes: " . $e->getMessage());
            return [];
        }
    }

    public function eliminarLibro($libro_id) {
        try {
            $this->conn->begin_transaction();

            $sql_select_rutas = "SELECT ruta_archivo_pdf, ruta_portada_img FROM libros WHERE id = ?";
            $stmt_select = $this->conn->prepare($sql_select_rutas);
            if (!$stmt_select) {
                throw new Exception("Error al preparar la consulta para obtener rutas: " . $this->conn->error);
            }
            $stmt_select->bind_param("i", $libro_id);
            $stmt_select->execute();
            $result_rutas = $stmt_select->get_result();
            $rutas = $result_rutas->fetch_assoc();
            $stmt_select->close();

            $sql_delete = "DELETE FROM libros WHERE id = ?";
            $stmt_delete = $this->conn->prepare($sql_delete);
            if (!$stmt_delete) {
                throw new Exception("Error al preparar la consulta de eliminación: " . $this->conn->error);
            }
            $stmt_delete->bind_param("i", $libro_id);
            if (!$stmt_delete->execute()) {
                throw new Exception("Error al eliminar el libro de la base de datos: " . $stmt_delete->error);
            }
            $stmt_delete->close();

            if ($rutas) {
                if (!empty($rutas['ruta_archivo_pdf']) && file_exists('../' . $rutas['ruta_archivo_pdf'])) { 
                    unlink('../' . $rutas['ruta_archivo_pdf']);
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return "Error al eliminar el libro: " . $e->getMessage();
        }
    }

    public function modificarLibro($libro_id, $titulo, $autor, $categoria_id, $editorial, $ano_publicacion, $descripcion, $portada_url, $nuevo_archivo_pdf = null) {
        try {
            if (empty($libro_id) || !is_numeric($libro_id) || $libro_id <= 0) {
                throw new InvalidArgumentException("ID de libro no válido.");
            }
            if (empty($titulo)) {
                throw new InvalidArgumentException("El título es obligatorio.");
            }
            if (empty($autor)) {
                throw new InvalidArgumentException("El autor es obligatorio.");
            }
            if (empty($categoria_id) || !is_numeric($categoria_id) || $categoria_id <= 0) {
                throw new InvalidArgumentException("La categoría no es válida.");
            }

            $this->conn->begin_transaction();

            $campos_sql_set = [];
            $params_valores = [];
            $params_tipos = "";

            $campos_sql_set[] = "titulo = ?"; $params_valores[] = $titulo; $params_tipos .= "s";
            $campos_sql_set[] = "autor = ?"; $params_valores[] = $autor; $params_tipos .= "s";
            $campos_sql_set[] = "categoria_id = ?"; $params_valores[] = $categoria_id; $params_tipos .= "i";
            $campos_sql_set[] = "editorial = ?"; $params_valores[] = $editorial; $params_tipos .= "s";
            $campos_sql_set[] = "ano_publicacion = ?"; $params_valores[] = $ano_publicacion; $params_tipos .= "s"; 
            $campos_sql_set[] = "descripcion = ?"; $params_valores[] = $descripcion; $params_tipos .= "s";
            $campos_sql_set[] = "ruta_portada_img = ?"; $params_valores[] = $portada_url; $params_tipos .= "s"; 

            $ruta_nuevo_pdf_servidor = null; 
            $ruta_pdf_antigua_servidor = null; 

            if (isset($nuevo_archivo_pdf) && $nuevo_archivo_pdf['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['application/pdf'];
                if (!in_array($nuevo_archivo_pdf['type'], $allowed_types)) {
                    throw new InvalidArgumentException("Tipo de archivo no válido para el nuevo PDF. Se espera un archivo PDF.");
                }
                $max_size = 20 * 1024 * 1024; 
                if ($nuevo_archivo_pdf['size'] > $max_size) {
                    throw new InvalidArgumentException("El nuevo archivo PDF excede el tamaño máximo permitido (20MB).");
                }

                $sql_old_pdf = "SELECT ruta_archivo_pdf FROM libros WHERE id = ?";
                $stmt_old_pdf = $this->conn->prepare($sql_old_pdf);
                if (!$stmt_old_pdf) throw new Exception("Error al preparar consulta para PDF antiguo: " . $this->conn->error);
                $stmt_old_pdf->bind_param("i", $libro_id);
                $stmt_old_pdf->execute();
                $result_old_pdf = $stmt_old_pdf->get_result();
                $old_pdf_data = $result_old_pdf->fetch_assoc();
                $stmt_old_pdf->close();
                if ($old_pdf_data && !empty($old_pdf_data['ruta_archivo_pdf'])) {
                     $ruta_pdf_antigua_servidor = '../' . $old_pdf_data['ruta_archivo_pdf']; 
                }

                $nombre_nuevo_pdf_unico = uniqid('libro_', true) . '.pdf';
                $ruta_nuevo_pdf_servidor = '../uploads/' . $nombre_nuevo_pdf_unico; 
                $ruta_pdf_para_db_nueva = 'uploads/' . $nombre_nuevo_pdf_unico;    

                if (!move_uploaded_file($nuevo_archivo_pdf['tmp_name'], $ruta_nuevo_pdf_servidor)) {
                    throw new RuntimeException("Error al mover el nuevo archivo PDF subido.");
                }
                error_log("DEBUG MODIFICARLIBRO: Nuevo PDF movido a: " . $ruta_nuevo_pdf_servidor);

                $campos_sql_set[] = "ruta_archivo_pdf = ?";
                $params_valores[] = $ruta_pdf_para_db_nueva;
                $params_tipos .= "s";

            } elseif (isset($nuevo_archivo_pdf) && $nuevo_archivo_pdf['error'] !== UPLOAD_ERR_NO_FILE) {
                throw new RuntimeException("Error al subir el nuevo archivo PDF: " . $this->getUploadErrorMessage($nuevo_archivo_pdf['error']));
            }

            $sql_update = "UPDATE libros SET " . implode(", ", $campos_sql_set) . " WHERE id = ?";
            $params_valores[] = $libro_id; 
            $params_tipos .= "i";

            error_log("DEBUG MODIFICARLIBRO: SQL Update: " . $sql_update);
            error_log("DEBUG MODIFICARLIBRO: Types: " . $params_tipos);
            error_log("DEBUG MODIFICARLIBRO: Params: " . print_r($params_valores, true));

            $stmt_update = $this->conn->prepare($sql_update);
            if (!$stmt_update) {
                throw new Exception("Error al preparar la consulta de actualización: " . $this->conn->error);
            }

            $stmt_update->bind_param($params_tipos, ...$params_valores);

            if ($stmt_update->execute()) {
                error_log("DEBUG MODIFICARLIBRO: Libro ID {$libro_id} actualizado. Filas afectadas: " . $stmt_update->affected_rows);
                if (isset($ruta_pdf_para_db_nueva) && $ruta_pdf_antigua_servidor && file_exists($ruta_pdf_antigua_servidor)) {
                    if (unlink($ruta_pdf_antigua_servidor)) {
                        error_log("DEBUG MODIFICARLIBRO: Antiguo PDF eliminado: " . $ruta_pdf_antigua_servidor);
                    } else {
                        error_log("DEBUG MODIFICARLIBRO: Error al eliminar antiguo PDF: " . $ruta_pdf_antigua_servidor);
                    }
                }
                $this->conn->commit();
                $stmt_update->close();
                return true;
            } else {
                $stmt_update->close(); 
                throw new Exception("Error al actualizar el libro en la base de datos: " . $this->conn->error);
            }

        } catch (Exception $e) {
            if ($this->conn->in_transaction) {
                $this->conn->rollback();
            }
            if (isset($ruta_nuevo_pdf_servidor) && file_exists($ruta_nuevo_pdf_servidor) && $e->getMessage() !== "Error al mover el nuevo archivo PDF subido.") {
                unlink($ruta_nuevo_pdf_servidor);
                error_log("DEBUG MODIFICARLIBRO: Nuevo PDF subido ({$ruta_nuevo_pdf_servidor}) eliminado debido a error en BD.");
            }
            error_log("DEBUG MODIFICARLIBRO: Excepción: " . $e->getMessage());
            return "Error al modificar el libro: " . $e->getMessage();
        }
    }
    
    public function getCategorias() {
        $sql = "SELECT id, nombre FROM categorias ORDER BY nombre ASC";
        $result = $this->conn->query($sql);
        $categorias = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categorias[] = $row;
            }
        }
        return $categorias;
    }
    
    public function getEstadisticasLibros() {
        $sql = "SELECT 
                    SUM(visualizaciones) as total_visualizaciones, 
                    SUM(descargas) as total_descargas 
                FROM libros";
        $result = $this->conn->query($sql);
        if ($result) {
            $data = $result->fetch_assoc();
            return [
                'total_visualizaciones' => $data['total_visualizaciones'] ?? 0,
                'total_descargas' => $data['total_descargas'] ?? 0,
            ];
        }
        return ['total_visualizaciones' => 0, 'total_descargas' => 0];
    }
}
?>
