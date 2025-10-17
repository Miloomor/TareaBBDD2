<?php
session_start();
require_once('../config/database.php');

$db = new DatabaseManager();
$error = '';
$success = '';

// Verificar si ya hay una sesión activa
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['rol'] == 'usuario') {
        header("Location: ../dashboard/usuario.php");
        exit;
    } else if ($_SESSION['rol'] == 'ingeniero') {
        header("Location: ../dashboard/ingeniero.php");
        exit;
    }
}

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Por favor, complete todos los campos.";
    } else {
        $user = $db->authenticateUser($email, $password);
        
        if ($user) {
            // Establecer variables de sesión
            $_SESSION['user_id'] = $user['rol'] == 'usuario' ? $user['id_usuario'] : $user['id_ingeniero'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol'];
            
            // Redireccionar según el rol
            if ($user['rol'] == 'usuario') {
                header("Location: ../dashboard/usuario.php");
                exit;
            } else {
                header("Location: ../dashboard/ingeniero.php");
                exit;
            }
        } else {
            $error = "Email o contraseña incorrectos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Gestión de Solicitudes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Iniciar Sesión</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="login.php">
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p class="mb-0">¿No tiene una cuenta? <a href="register.php">Regístrese aquí</a></p>
                        <p class="mt-2"><a href="../index.php">Volver al inicio</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>