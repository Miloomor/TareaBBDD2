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
    $titulo = trim($_POST['titulo']);
    $ambiente = $_POST['ambiente'];
    $resumen = trim($_POST['resumen']);
    $topico = $_POST['topico'];
    $criterios = isset($_POST['criterios']) ? $_POST['criterios'] : [];
    
    // Validaciones
    if (empty($id_funcionalidad) || empty($titulo) || empty($ambiente) || empty($resumen) || empty($topico) || empty($criterios)) {
        $mensaje = 'Todos los campos son obligatorios.';
        $tipo = 'danger';
    } elseif (strlen($titulo) < 20) {
        $mensaje = 'El título debe tener al menos 20 caracteres.';
        $tipo = 'danger';
    } elseif (count($criterios) < 3) {
        $mensaje = 'Debe especificar al menos 3 criterios de aceptación.';
        $tipo = 'danger';
    } else {
        // Verificar si la solicitud pertenece al usuario (si es usuario)
        if ($_SESSION['rol'] === 'usuario') {
            $es_propietario = $db->verificarPropietarioFuncionalidad($id_funcionalidad, $user_id);
            if (!$es_propietario) {
                $mensaje = 'No tiene permiso para modificar esta solicitud.';
                $tipo = 'danger';
                header("Location: ../../dashboard/usuario.php?mensaje=" . urlencode($mensaje) . "&tipo=" . $tipo);
                exit;
            }
        }
        
        // Intentar actualizar la solicitud
        $resultado = $db->updateFuncionalidad($id_funcionalidad, $titulo, $ambiente, $resumen, $criterios, $topico);
        
        if ($resultado) {
            $mensaje = 'Solicitud de funcionalidad actualizada correctamente.';
            $tipo = 'success';
        } else {
            $mensaje = 'Ha ocurrido un error al actualizar la solicitud o la solicitud está en progreso.';
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