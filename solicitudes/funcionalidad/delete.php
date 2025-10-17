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
    $id_funcionalidad = $_POST['id_funcionalidad'];
    
    if (empty($id_funcionalidad)) {
        $mensaje = 'ID de funcionalidad no proporcionado.';
        $tipo = 'danger';
    } else {
        // Verificar si la solicitud pertenece al usuario (si es usuario)
        if ($_SESSION['rol'] === 'usuario') {
            $es_propietario = $db->verificarPropietarioFuncionalidad($id_funcionalidad, $user_id);
            if (!$es_propietario) {
                $mensaje = 'No tiene permiso para eliminar esta solicitud.';
                $tipo = 'danger';
                header("Location: ../../dashboard/usuario.php?mensaje=" . urlencode($mensaje) . "&tipo=" . $tipo);
                exit;
            }
        }
        
        // Intentar eliminar la solicitud
        $resultado = $db->deleteFuncionalidad($id_funcionalidad, $user_id);
        
        if ($resultado) {
            $mensaje = 'Solicitud de funcionalidad eliminada correctamente.';
            $tipo = 'success';
        } else {
            $mensaje = 'Ha ocurrido un error al eliminar la solicitud o la solicitud está en progreso.';
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