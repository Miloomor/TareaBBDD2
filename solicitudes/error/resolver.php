<?php
session_start();
require_once('../../config/database.php');

// Verificar si el usuario ha iniciado sesión y es un ingeniero
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'ingeniero') {
    header("Location: ../../auth/login.php");
    exit;
}

$db = new DatabaseManager();
$id_ingeniero = $_SESSION['user_id'];
$mensaje = '';
$tipo = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar datos
    $id_error = $_POST['id_error'];
    $observaciones = trim($_POST['observaciones']);
    
    if (empty($id_error) || empty($observaciones)) {
        $mensaje = 'Todos los campos son obligatorios.';
        $tipo = 'danger';
    } else {
        // Verificar si el error está asignado a este ingeniero
        $es_asignado = $db->verificarAsignacionError($id_error, $id_ingeniero);
        
        if (!$es_asignado) {
            $mensaje = 'No tiene permiso para resolver este reporte de error.';
            $tipo = 'danger';
        } else {
            // Intentar resolver el reporte de error
            $resultado = $db->resolverError($id_error, $id_ingeniero, $observaciones);
            
            if ($resultado) {
                $mensaje = 'Reporte de error marcado como resuelto correctamente.';
                $tipo = 'success';
            } else {
                $mensaje = 'Ha ocurrido un error al resolver el reporte o el error no está en progreso.';
                $tipo = 'danger';
            }
        }
    }
    
    // Redireccionar al dashboard con mensaje
    header("Location: ../../dashboard/ingeniero.php?mensaje=" . urlencode($mensaje) . "&tipo=" . $tipo);
    exit;
} else {
    // Si no es POST, redirigir al dashboard
    header("Location: ../../dashboard/ingeniero.php");
    exit;
}
?>