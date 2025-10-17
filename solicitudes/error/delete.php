<?php
session_start();
require_once('../../config/database.php');

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$db = new DatabaseManager();
$user_id = $_SESSION['user_id'];
$mensaje = '';
$tipo = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar datos
    $id_error = $_POST['id_error'];
    
    if (empty($id_error)) {
        $mensaje = 'ID de error no proporcionado.';
        $tipo = 'danger';
    } else {
        // Verificar si el error pertenece al usuario (si es usuario)
        if ($_SESSION['rol'] === 'usuario') {
            $es_propietario = $db->verificarPropietarioError($id_error, $user_id);
            if (!$es_propietario) {
                $mensaje = 'No tiene permiso para eliminar este reporte de error.';
                $tipo = 'danger';
                header("Location: ../../dashboard/usuario.php?mensaje=" . urlencode($mensaje) . "&tipo=" . $tipo);
                exit;
            }
        }
        
        // Intentar eliminar el reporte de error
        $resultado = $db->deleteError($id_error, $user_id);
        
        if ($resultado) {
            $mensaje = 'Reporte de error eliminado correctamente.';
            $tipo = 'success';
        } else {
            $mensaje = 'Ha ocurrido un error al eliminar el reporte o el error está en progreso.';
            $tipo = 'danger';
        }
    }
    
    // Redireccionar al dashboard con mensaje
    if ($_SESSION['rol'] == 'usuario') {
        header("Location: ../../dashboard/usuario.php?mensaje=" . urlencode($mensaje) . "&tipo=" . $tipo);
    } else {
        header("Location: ../../dashboard/ingeniero.php?mensaje=" . urlencode($mensaje) . "&tipo=" . $tipo);
    }
    exit;
} else {
    // Si no es POST, redirigir al dashboard
    header("Location: ../../index.php");
    exit;
}
?>