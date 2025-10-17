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
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $topico = $_POST['topico'];
    
    // Validaciones
    if (empty($titulo) || empty($descripcion) || empty($topico)) {
        $mensaje = 'Todos los campos son obligatorios.';
        $tipo = 'danger';
    } else {
        // Intentar crear el reporte de error
        $resultado = $db->createError($titulo, $descripcion, $user_id, $topico);
        
        if ($resultado) {
            $mensaje = 'Reporte de error creado correctamente.';
            $tipo = 'success';
        } else {
            $mensaje = 'Ha ocurrido un error al crear el reporte. Por favor, inténtelo de nuevo.';
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