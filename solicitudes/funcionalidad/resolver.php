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
    $id_funcionalidad = $_POST['id_funcionalidad'];
    $observaciones = trim($_POST['observaciones']);
    
    if (empty($id_funcionalidad) || empty($observaciones)) {
        $mensaje = 'Todos los campos son obligatorios.';
        $tipo = 'danger';
    } else {
        // Verificar si la funcionalidad está asignada a este ingeniero
        $es_asignado = $db->verificarAsignacionFuncionalidad($id_funcionalidad, $id_ingeniero);
        
        if (!$es_asignado) {
            $mensaje = 'No tiene permiso para resolver esta solicitud.';
            $tipo = 'danger';
        } else {
            // Intentar resolver la solicitud
            $resultado = $db->resolverFuncionalidad($id_funcionalidad, $id_ingeniero, $observaciones);
            
            if ($resultado) {
                $mensaje = 'Solicitud de funcionalidad marcada como resuelta correctamente.';
                $tipo = 'success';
            } else {
                $mensaje = 'Ha ocurrido un error al resolver la solicitud o la solicitud no está en progreso.';
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