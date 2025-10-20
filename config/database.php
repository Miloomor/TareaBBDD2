<?php
/**
 * Gestión de base de datos - Sistema de Solicitudes
 * 
 * Este archivo contiene las funciones principales para la conexión y gestión
 * de la base de datos del sistema, así como consultas comunes utilizadas en todo el proyecto.
 */

class DatabaseManager {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "bdzeropressure";
    private $conn;
    
    // Constructor - establece la conexión
    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=$this->host;dbname=$this->database;charset=utf8mb4",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    // Método para obtener la conexión actual
    public function getConnection() {
        return $this->conn;
    }
    
    // Cerrar la conexión
    public function closeConnection() {
        $this->conn = null;
    }
    
    // ===== USUARIOS Y AUTENTICACIÓN =====
    
    /**
     * Verifica las credenciales de un usuario
     * 
     * @param string $email Email del usuario
     * @param string $password Contraseña del usuario
     * @return array|bool Datos del usuario si la autenticación es exitosa, false en caso contrario
     */
    public function authenticateUser($email, $password) {
        try {
            // Primero intentamos autenticar como usuario
            $stmt = $this->conn->prepare("SELECT id_usuario, nombre, email, password, 'usuario' as rol 
                                         FROM Usuario WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                return $user;
            }
            
            // Si no es usuario, intentamos como ingeniero
            $stmt = $this->conn->prepare("SELECT id_ingeniero, nombre, email, password, 'ingeniero' as rol 
                                         FROM Ingeniero WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $engineer = $stmt->fetch();
            
            if ($engineer && password_verify($password, $engineer['password'])) {
                return $engineer;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error en autenticación: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra un nuevo usuario en el sistema
     * 
     * @param string $rut RUT del usuario
     * @param string $nombre Nombre completo del usuario
     * @param string $email Email del usuario
     * @param string $password Contraseña del usuario
     * @param string $rol Rol del usuario ('usuario' o 'ingeniero')
     * @return bool True si el registro fue exitoso, false en caso contrario
     */
    public function registerUser($rut, $nombre, $email, $password, $rol) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            if ($rol === 'usuario') {
                $stmt = $this->conn->prepare("INSERT INTO Usuario (rut, nombre, email, password) 
                                             VALUES (:rut, :nombre, :email, :password)");
            } else {
                $stmt = $this->conn->prepare("INSERT INTO Ingeniero (rut, nombre, email, password) 
                                             VALUES (:rut, :nombre, :email, :password)");
            }
            
            $stmt->bindParam(':rut', $rut);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            
            $result = $stmt->execute();
            
            if ($result) {
                // Si la inserción fue exitosa, devolvemos el ID insertado
                return $this->conn->lastInsertId();
            } else {
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error en registro: " . $e->getMessage());
            return false;
        }
    }
    
    // ===== SOLICITUDES DE FUNCIONALIDAD =====
    
    /**
     * Obtiene todas las solicitudes de funcionalidad
     * 
     * @return array Lista de solicitudes de funcionalidad
     */
    public function getAllFuncionalidades() {
        try {
            $stmt = $this->conn->prepare("
                SELECT f.*, u.nombre as nombre_usuario, t.nombre as nombre_topico 
                FROM Solicitud_Funcionalidad f
                JOIN Usuario u ON f.id_usuario = u.id_usuario
                JOIN Topico t ON f.id_topico = t.id_topico
                ORDER BY f.fecha_publicacion DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo funcionalidades: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene las solicitudes de funcionalidad de un usuario específico
     * 
     * @param int $id_usuario ID del usuario
     * @return array Lista de solicitudes de funcionalidad del usuario
     */
    public function getFuncionalidadesByUser($id_usuario) {
        try {
            $stmt = $this->conn->prepare("
                SELECT f.*, t.nombre as nombre_topico 
                FROM Solicitud_Funcionalidad f
                JOIN Topico t ON f.id_topico = t.id_topico
                WHERE f.id_usuario = :id_usuario
                ORDER BY f.fecha_publicacion DESC
            ");
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo funcionalidades del usuario: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene los criterios de una funcionalidad específica
     * 
     * @param int $id_funcionalidad ID de la funcionalidad
     * @return array Lista de criterios ordenados
     */
    public function getCriteriosFuncionalidad($id_funcionalidad) {
        try {
            $stmt = $this->conn->prepare("
                SELECT descripcion 
                FROM Criterios_Funcionalidad 
                WHERE id_funcionalidad = :id_funcionalidad
                ORDER BY orden ASC
            ");
            $stmt->bindParam(':id_funcionalidad', $id_funcionalidad);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $result;
        } catch (PDOException $e) {
            error_log("Error obteniendo criterios: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verifica si un usuario es propietario de una funcionalidad
     * 
     * @param int $id_funcionalidad ID de la funcionalidad
     * @param int $id_usuario ID del usuario
     * @return bool True si el usuario es propietario, false en caso contrario
     */
    public function verificarPropietarioFuncionalidad($id_funcionalidad, $id_usuario) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) 
                FROM Solicitud_Funcionalidad 
                WHERE id_funcionalidad = :id_funcionalidad AND id_usuario = :id_usuario
            ");
            $stmt->bindParam(':id_funcionalidad', $id_funcionalidad);
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error verificando propietario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea una nueva solicitud de funcionalidad
     * 
     * @param string $titulo Título de la funcionalidad
     * @param string $ambiente Ambiente (Web/Movil)
     * @param string $resumen Resumen de la funcionalidad
     * @param array $criterios Criterios de aceptación (como array)
     * @param int $id_usuario ID del usuario que crea la solicitud
     * @param int $id_topico ID del tópico relacionado
     * @return bool True si la creación fue exitosa, false en caso contrario
     */
    public function createFuncionalidad($titulo, $ambiente, $resumen, $criterios, $id_usuario, $id_topico) {
        try {
            // Validar que hay al menos 3 criterios
            if (count($criterios) < 3) {
                error_log("Error: Se requieren al menos 3 criterios de aceptación.");
                return false;
            }
            
            // Validar que los criterios no estén vacíos
            foreach ($criterios as $criterio) {
                if (empty(trim($criterio))) {
                    error_log("Error: Los criterios de aceptación no pueden estar vacíos.");
                    return false;
                }
            }
            
            // Iniciamos una transacción
            $this->conn->beginTransaction();
            
            $stmt = $this->conn->prepare("
                INSERT INTO Solicitud_Funcionalidad 
                (titulo, ambiente, resumen, id_usuario, id_topico, fecha_publicacion, estado)
                VALUES (:titulo, :ambiente, :resumen, :id_usuario, :id_topico, CURRENT_DATE, 'Abierto')
            ");
            
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':ambiente', $ambiente);
            $stmt->bindParam(':resumen', $resumen);
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->bindParam(':id_topico', $id_topico);
            
            $result = $stmt->execute();
            
            if ($result) {
                $id_funcionalidad = $this->conn->lastInsertId();
                
                // Insertamos los criterios en la tabla normalizada
                $stmt_criterios = $this->conn->prepare("
                    INSERT INTO Criterios_Funcionalidad (id_funcionalidad, descripcion, orden)
                    VALUES (:id_funcionalidad, :descripcion, :orden)
                ");
                
                $criterio_error = false;
                
                foreach ($criterios as $index => $criterio) {
                    if (!empty(trim($criterio))) {
                        $orden = $index + 1;
                        $criterio_trim = trim($criterio);
                        $stmt_criterios->bindParam(':id_funcionalidad', $id_funcionalidad);
                        $stmt_criterios->bindParam(':descripcion', $criterio_trim);
                        $stmt_criterios->bindParam(':orden', $orden);
                        
                        if (!$stmt_criterios->execute()) {
                            $criterio_error = true;
                            error_log("Error al insertar criterio: " . $criterio);
                            break;
                        }
                    } else {
                        $criterio_error = true;
                        error_log("Error: Criterio vacío encontrado");
                        break;
                    }
                }
                
                if ($criterio_error) {
                    $this->conn->rollBack();
                    return false;
                }
                
                // Confirmamos la transacción
                $this->conn->commit();
                
                // Ejecutamos el procedimiento almacenado para asignación automática
                try {
                    $this->conn->exec("CALL asignar_ing_funcionalidad(" . $id_funcionalidad . ")");
                } catch (PDOException $e) {
                    error_log("Advertencia: Error en asignación automática: " . $e->getMessage());
                    // Continuamos aunque haya error en la asignación automática
                }
                
                return true;
            } else {
                $this->conn->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error creando funcionalidad: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza una solicitud de funcionalidad existente
     * 
     * @param int $id_funcionalidad ID de la funcionalidad
     * @param string $titulo Título actualizado
     * @param string $ambiente Ambiente actualizado
     * @param string $resumen Resumen actualizado
     * @param array $criterios Criterios actualizados
     * @param int $id_topico ID del tópico actualizado
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function updateFuncionalidad($id_funcionalidad, $titulo, $ambiente, $resumen, $criterios, $id_topico) {
        try {
            // Primero verificamos que la funcionalidad no esté "En Progreso"
            $check = $this->conn->prepare("SELECT estado FROM Solicitud_Funcionalidad WHERE id_funcionalidad = :id");
            $check->bindParam(':id', $id_funcionalidad);
            $check->execute();
            $estado = $check->fetchColumn();
            
            if ($estado === 'En Progreso') {
                return false; // No se puede modificar si está en progreso
            }
            
            // Iniciamos una transacción
            $this->conn->beginTransaction();
            
            $stmt = $this->conn->prepare("
                UPDATE Solicitud_Funcionalidad 
                SET titulo = :titulo, ambiente = :ambiente, resumen = :resumen, 
                    id_topico = :id_topico
                WHERE id_funcionalidad = :id_funcionalidad
            ");
            
            $stmt->bindParam(':id_funcionalidad', $id_funcionalidad);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':ambiente', $ambiente);
            $stmt->bindParam(':resumen', $resumen);
            $stmt->bindParam(':id_topico', $id_topico);
            
            $result = $stmt->execute();
            
            if ($result) {
                // Eliminamos los criterios antiguos
                $delete_stmt = $this->conn->prepare("
                    DELETE FROM Criterios_Funcionalidad 
                    WHERE id_funcionalidad = :id_funcionalidad
                ");
                $delete_stmt->bindParam(':id_funcionalidad', $id_funcionalidad);
                $delete_stmt->execute();
                
                // Insertamos los nuevos criterios
                $stmt_criterios = $this->conn->prepare("
                    INSERT INTO Criterios_Funcionalidad (id_funcionalidad, descripcion, orden)
                    VALUES (:id_funcionalidad, :descripcion, :orden)
                ");
                
                foreach ($criterios as $index => $criterio) {
                    $orden = $index + 1;
                    $stmt_criterios->bindParam(':id_funcionalidad', $id_funcionalidad);
                    $stmt_criterios->bindParam(':descripcion', $criterio);
                    $stmt_criterios->bindParam(':orden', $orden);
                    $stmt_criterios->execute();
                }
                
                // Confirmamos la transacción
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error actualizando funcionalidad: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina una solicitud de funcionalidad
     * 
     * @param int $id_funcionalidad ID de la funcionalidad a eliminar
     * @param int $id_usuario ID del usuario que intenta eliminar (para verificar propiedad)
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function deleteFuncionalidad($id_funcionalidad, $id_usuario) {
        try {
            // Verificamos propiedad y estado
            $check = $this->conn->prepare("
                SELECT id_usuario, estado 
                FROM Solicitud_Funcionalidad 
                WHERE id_funcionalidad = :id
            ");
            $check->bindParam(':id', $id_funcionalidad);
            $check->execute();
            $result = $check->fetch();
            
            if (!$result || $result['id_usuario'] != $id_usuario || $result['estado'] === 'En Progreso') {
                return false; // No autorizado o está en progreso
            }
            
            $stmt = $this->conn->prepare("DELETE FROM Solicitud_Funcionalidad WHERE id_funcionalidad = :id");
            $stmt->bindParam(':id', $id_funcionalidad);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error eliminando funcionalidad: " . $e->getMessage());
            return false;
        }
    }
    
    // ===== SOLICITUDES DE ERROR =====
    
    /**
     * Obtiene todas las solicitudes de error
     * 
     * @return array Lista de solicitudes de error
     */
    public function getAllErrores() {
        try {
            $stmt = $this->conn->prepare("
                SELECT e.*, u.nombre as nombre_usuario, t.nombre as nombre_topico 
                FROM Solicitud_Error e
                JOIN Usuario u ON e.id_usuario = u.id_usuario
                JOIN Topico t ON e.id_topico = t.id_topico
                ORDER BY e.fecha_publicacion DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo errores: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene las solicitudes de error de un usuario específico
     * 
     * @param int $id_usuario ID del usuario
     * @return array Lista de solicitudes de error del usuario
     */
    public function getErroresByUser($id_usuario) {
        try {
            $stmt = $this->conn->prepare("
                SELECT e.*, t.nombre as nombre_topico 
                FROM Solicitud_Error e
                JOIN Topico t ON e.id_topico = t.id_topico
                WHERE e.id_usuario = :id_usuario
                ORDER BY e.fecha_publicacion DESC
            ");
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo errores del usuario: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Crea una nueva solicitud de error
     * 
     * @param string $titulo Título del error
     * @param string $descripcion Descripción del error
     * @param int $id_usuario ID del usuario que crea la solicitud
     * @param int $id_topico ID del tópico relacionado
     * @return bool True si la creación fue exitosa, false en caso contrario
     */
    public function createError($titulo, $descripcion, $id_usuario, $id_topico) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO Solicitud_Error 
                (titulo, descripcion, id_usuario, id_topico, fecha_publicacion, estado)
                VALUES (:titulo, :descripcion, :id_usuario, :id_topico, CURRENT_DATE, 'Abierto')
            ");
            
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->bindParam(':id_topico', $id_topico);
            
            $result = $stmt->execute();
            
            // Si la inserción fue exitosa, ejecutamos el procedimiento almacenado para asignación automática
            if ($result) {
                $this->conn->exec("CALL asignar_ing_error(" . $this->conn->lastInsertId() . ")");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error creando solicitud de error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza una solicitud de error existente
     * 
     * @param int $id_error ID del error
     * @param string $titulo Título actualizado
     * @param string $descripcion Descripción actualizada
     * @param int $id_topico ID del tópico actualizado
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function updateError($id_error, $titulo, $descripcion, $id_topico) {
        try {
            // Primero verificamos que el error no esté "En Progreso"
            $check = $this->conn->prepare("SELECT estado FROM Solicitud_Error WHERE id_error = :id");
            $check->bindParam(':id', $id_error);
            $check->execute();
            $estado = $check->fetchColumn();
            
            if ($estado === 'En Progreso') {
                return false; // No se puede modificar si está en progreso
            }
            
            $stmt = $this->conn->prepare("
                UPDATE Solicitud_Error 
                SET titulo = :titulo, descripcion = :descripcion, id_topico = :id_topico
                WHERE id_error = :id_error
            ");
            
            $stmt->bindParam(':id_error', $id_error);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':id_topico', $id_topico);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error actualizando solicitud de error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina una solicitud de error
     * 
     * @param int $id_error ID del error a eliminar
     * @param int $id_usuario ID del usuario que intenta eliminar (para verificar propiedad)
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public function deleteError($id_error, $id_usuario) {
        try {
            // Verificamos propiedad y estado
            $check = $this->conn->prepare("
                SELECT id_usuario, estado 
                FROM Solicitud_Error 
                WHERE id_error = :id
            ");
            $check->bindParam(':id', $id_error);
            $check->execute();
            $result = $check->fetch();
            
            if (!$result || $result['id_usuario'] != $id_usuario || $result['estado'] === 'En Progreso') {
                return false; // No autorizado o está en progreso
            }
            
            $stmt = $this->conn->prepare("DELETE FROM Solicitud_Error WHERE id_error = :id");
            $stmt->bindParam(':id', $id_error);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error eliminando solicitud de error: " . $e->getMessage());
            return false;
        }
    }
    
    // ===== ASIGNACIONES Y MANEJO DE INGENIEROS =====
    
    /**
     * Obtiene las solicitudes asignadas a un ingeniero específico
     * 
     * @param int $id_ingeniero ID del ingeniero
     * @return array Lista de solicitudes asignadas
     */
    public function getAsignacionesByIngeniero($id_ingeniero) {
        try {
            $solicitudes = [];
            
            // Obtenemos funcionalidades asignadas
            $stmt1 = $this->conn->prepare("
                SELECT f.*, t.nombre as nombre_topico, u.nombre as nombre_usuario, 'funcionalidad' as tipo
                FROM Asignacion_Funcionalidad af
                JOIN Solicitud_Funcionalidad f ON af.id_funcionalidad = f.id_funcionalidad
                JOIN Topico t ON f.id_topico = t.id_topico
                JOIN Usuario u ON f.id_usuario = u.id_usuario
                WHERE af.id_ingeniero = :id_ingeniero
                ORDER BY f.fecha_publicacion DESC
            ");
            $stmt1->bindParam(':id_ingeniero', $id_ingeniero);
            $stmt1->execute();
            $funcionalidades = $stmt1->fetchAll();
            
            // Obtenemos errores asignados
            $stmt2 = $this->conn->prepare("
                SELECT e.*, t.nombre as nombre_topico, u.nombre as nombre_usuario, 'error' as tipo
                FROM Asignacion_Error ae
                JOIN Solicitud_Error e ON ae.id_error = e.id_error
                JOIN Topico t ON e.id_topico = t.id_topico
                JOIN Usuario u ON e.id_usuario = u.id_usuario
                WHERE ae.id_ingeniero = :id_ingeniero
                ORDER BY e.fecha_publicacion DESC
            ");
            $stmt2->bindParam(':id_ingeniero', $id_ingeniero);
            $stmt2->execute();
            $errores = $stmt2->fetchAll();
            
            // Combinamos ambos resultados
            $solicitudes = array_merge($funcionalidades, $errores);
            
            // Ordenamos por fecha de publicación (más reciente primero)
            usort($solicitudes, function($a, $b) {
                return strtotime($b['fecha_publicacion']) - strtotime($a['fecha_publicacion']);
            });
            
            return $solicitudes;
        } catch (PDOException $e) {
            error_log("Error obteniendo asignaciones: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene las especialidades (tópicos) de un ingeniero
     * 
     * @param int $id_ingeniero ID del ingeniero
     * @return array Lista de tópicos/especialidades
     */
    public function getEspecialidadesIngeniero($id_ingeniero) {
        try {
            $stmt = $this->conn->prepare("
                SELECT t.* 
                FROM Ingeniero_Topico it
                JOIN Topico t ON it.id_topico = t.id_topico
                WHERE it.id_ingeniero = :id_ingeniero
            ");
            $stmt->bindParam(':id_ingeniero', $id_ingeniero);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo especialidades: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene el ID del último registro insertado
     * 
     * @return string El ID del último registro insertado
     */
    public function getLastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    /**
     * Agrega una especialidad (tópico) a un ingeniero
     * 
     * @param int $id_ingeniero ID del ingeniero
     * @param int $id_topico ID del tópico/especialidad
     * @return bool True si la operación fue exitosa, false en caso contrario
     */
    public function addEspecialidadIngeniero($id_ingeniero, $id_topico) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO Ingeniero_Topico (id_ingeniero, id_topico)
                VALUES (:id_ingeniero, :id_topico)
            ");
            $stmt->bindParam(':id_ingeniero', $id_ingeniero);
            $stmt->bindParam(':id_topico', $id_topico);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error agregando especialidad: " . $e->getMessage());
            return false;
        }
    }
    
    // ===== BÚSQUEDAS =====
    
    /**
     * Realiza una búsqueda simple por texto en solicitudes
     * 
     * @param string $query Texto a buscar
     * @return array Resultados de la búsqueda
     */
    public function searchSolicitudes($query) {
        try {
            $resultados = [];
            $search = "%$query%";
            
            // Búsqueda en funcionalidades
            $stmt1 = $this->conn->prepare("
                SELECT f.*, t.nombre as nombre_topico, u.nombre as nombre_usuario, 'funcionalidad' as tipo
                FROM Solicitud_Funcionalidad f
                JOIN Topico t ON f.id_topico = t.id_topico
                JOIN Usuario u ON f.id_usuario = u.id_usuario
                WHERE f.titulo LIKE :query OR f.resumen LIKE :query
                ORDER BY f.fecha_publicacion DESC
            ");
            $stmt1->bindParam(':query', $search);
            $stmt1->execute();
            $funcionalidades = $stmt1->fetchAll();
            
            // Búsqueda en errores
            $stmt2 = $this->conn->prepare("
                SELECT e.*, t.nombre as nombre_topico, u.nombre as nombre_usuario, 'error' as tipo
                FROM Solicitud_Error e
                JOIN Topico t ON e.id_topico = t.id_topico
                JOIN Usuario u ON e.id_usuario = u.id_usuario
                WHERE e.titulo LIKE :query OR e.descripcion LIKE :query
                ORDER BY e.fecha_publicacion DESC
            ");
            $stmt2->bindParam(':query', $search);
            $stmt2->execute();
            $errores = $stmt2->fetchAll();
            
            // Combinamos resultados
            $resultados = array_merge($funcionalidades, $errores);
            
            // Ordenamos por relevancia (si el título contiene la búsqueda, es más relevante)
            usort($resultados, function($a, $b) use ($query) {
                $a_title_match = stripos($a['titulo'], $query) !== false;
                $b_title_match = stripos($b['titulo'], $query) !== false;
                
                if ($a_title_match && !$b_title_match) return -1;
                if (!$a_title_match && $b_title_match) return 1;
                
                // Si ambos o ninguno coinciden en el título, ordenamos por fecha
                return strtotime($b['fecha_publicacion']) - strtotime($a['fecha_publicacion']);
            });
            
            return $resultados;
        } catch (PDOException $e) {
            error_log("Error en búsqueda: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Realiza una búsqueda avanzada con filtros
     * 
     * @param array $filtros Filtros a aplicar (fecha, tópico, ambiente, estado)
     * @return array Resultados de la búsqueda
     */
    public function advancedSearch($filtros) {
        try {
            $resultados = [];
            $where_funcionalidad = [];
            $where_error = [];
            $params = [];
            
            // Construimos las cláusulas WHERE según los filtros
            if (!empty($filtros['fecha_desde'])) {
                $where_funcionalidad[] = "f.fecha_publicacion >= :fecha_desde";
                $where_error[] = "e.fecha_publicacion >= :fecha_desde";
                $params[':fecha_desde'] = $filtros['fecha_desde'];
            }
            
            if (!empty($filtros['fecha_hasta'])) {
                $where_funcionalidad[] = "f.fecha_publicacion <= :fecha_hasta";
                $where_error[] = "e.fecha_publicacion <= :fecha_hasta";
                $params[':fecha_hasta'] = $filtros['fecha_hasta'];
            }
            
            if (!empty($filtros['topico'])) {
                $where_funcionalidad[] = "f.id_topico = :topico";
                $where_error[] = "e.id_topico = :topico";
                $params[':topico'] = $filtros['topico'];
            }
            
            if (!empty($filtros['ambiente'])) {
                $where_funcionalidad[] = "f.ambiente = :ambiente";
                // No aplicamos este filtro a errores porque no tienen campo ambiente
                $params[':ambiente'] = $filtros['ambiente'];
            }
            
            if (!empty($filtros['estado'])) {
                $where_funcionalidad[] = "f.estado = :estado";
                $where_error[] = "e.estado = :estado";
                $params[':estado'] = $filtros['estado'];
            }
            
            // Construimos las consultas
            $where_f_clause = !empty($where_funcionalidad) ? "WHERE " . implode(" AND ", $where_funcionalidad) : "";
            $where_e_clause = !empty($where_error) ? "WHERE " . implode(" AND ", $where_error) : "";
            
            // Búsqueda en funcionalidades
            $sql_f = "
                SELECT f.*, t.nombre as nombre_topico, u.nombre as nombre_usuario, 'funcionalidad' as tipo
                FROM Solicitud_Funcionalidad f
                JOIN Topico t ON f.id_topico = t.id_topico
                JOIN Usuario u ON f.id_usuario = u.id_usuario
                $where_f_clause
                ORDER BY f.fecha_publicacion DESC
            ";
            
            $stmt1 = $this->conn->prepare($sql_f);
            foreach ($params as $key => $value) {
                $stmt1->bindValue($key, $value);
            }
            $stmt1->execute();
            $funcionalidades = $stmt1->fetchAll();
            
            // Búsqueda en errores (solo si no hay filtro de ambiente)
            if (empty($filtros['ambiente'])) {
                $sql_e = "
                    SELECT e.*, t.nombre as nombre_topico, u.nombre as nombre_usuario, 'error' as tipo
                    FROM Solicitud_Error e
                    JOIN Topico t ON e.id_topico = t.id_topico
                    JOIN Usuario u ON e.id_usuario = u.id_usuario
                    $where_e_clause
                    ORDER BY e.fecha_publicacion DESC
                ";
                
                $stmt2 = $this->conn->prepare($sql_e);
                foreach ($params as $key => $value) {
                    if ($key !== ':ambiente') {
                        $stmt2->bindValue($key, $value);
                    }
                }
                $stmt2->execute();
                $errores = $stmt2->fetchAll();
            } else {
                $errores = [];
            }
            
            // Combinamos resultados
            $resultados = array_merge($funcionalidades, $errores);
            
            // Ordenamos por fecha de publicación (más reciente primero)
            usort($resultados, function($a, $b) {
                return strtotime($b['fecha_publicacion']) - strtotime($a['fecha_publicacion']);
            });
            
            return $resultados;
        } catch (PDOException $e) {
            error_log("Error en búsqueda avanzada: " . $e->getMessage());
            return [];
        }
    }
    
    // ===== UTILITARIOS =====
    
    /**
     * Obtiene la lista de tópicos disponibles
     * 
     * @return array Lista de tópicos
     */
    public function getTopicos() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM Topico ORDER BY nombre");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo tópicos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ejecuta la asignación automática para todas las solicitudes pendientes
     * 
     * @return bool True si la ejecucion fue exitosa
     */
    public function ejecutarAsignacionAutomatica() {
        try {
            $this->conn->exec("CALL ejecutar_asignacion_automatica()");
            return true;
        } catch (PDOException $e) {
            error_log("Error ejecutando asignación automática: " . $e->getMessage());
            return false;
        }
    }
}
?>