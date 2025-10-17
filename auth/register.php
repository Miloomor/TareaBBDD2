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

// Obtener la lista de tópicos para los ingenieros
$topicos = $db->getTopicos();

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rut = trim($_POST['rut']);
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $rol = $_POST['rol'];
    
    // Validaciones
    if (empty($rut) || empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Por favor, complete todos los campos.";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato de correo electrónico inválido.";
    } elseif (!validarRut($rut)) {
        $error = "El formato del RUT es incorrecto (debe ser: 12345678-9).";
    } else {
        // Verificar especialidades si es ingeniero
        if ($rol === 'ingeniero') {
            if (!isset($_POST['especialidades']) || count($_POST['especialidades']) < 1 || count($_POST['especialidades']) > 2) {
                $error = "Un ingeniero debe tener 1 o 2 especialidades.";
            }
        }
        
        // Si no hay errores, intentar registrar al usuario
        if (empty($error)) {
            $resultado = $db->registerUser($rut, $nombre, $email, $password, $rol);
            
            if ($resultado) {
                // Si es ingeniero, registrar sus especialidades
                if ($rol === 'ingeniero' && isset($_POST['especialidades'])) {
                    $id_ingeniero = $db->getLastInsertId();
                    foreach ($_POST['especialidades'] as $topico_id) {
                        $db->addEspecialidadIngeniero($id_ingeniero, $topico_id);
                    }
                }
                
                $success = "Registro exitoso. Ahora puede iniciar sesión.";
            } else {
                $error = "Ha ocurrido un error al registrar. Posiblemente el correo o RUT ya existen.";
            }
        }
    }
}

// Función para validar formato de RUT chileno
function validarRut($rut) {
    return preg_match('/^\d{7,8}-[0-9kK]$/', $rut);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Gestión de Solicitudes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-4 mb-4">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Registro de Usuario</h4>
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
                                <p class="mb-0 mt-2">
                                    <a href="login.php" class="alert-link">Iniciar sesión ahora</a>
                                </p>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="register.php">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de cuenta</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="rol" id="rol_usuario" value="usuario" checked>
                                            <label class="form-check-label" for="rol_usuario">Usuario</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="rol" id="rol_ingeniero" value="ingeniero">
                                            <label class="form-check-label" for="rol_ingeniero">Ingeniero</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="rut" class="form-label">RUT (Formato: 12345678-9)</label>
                                        <input type="text" class="form-control" id="rut" name="rut" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre" class="form-label">Nombre completo</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Correo electrónico</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Contraseña</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <div class="form-text">Mínimo 6 caracteres</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                
                                <!-- Sección de especialidades para ingenieros -->
                                <div id="especialidades_section" class="mb-3 d-none">
                                    <label class="form-label">Especialidades (seleccione 1 o 2)</label>
                                    <div class="row">
                                        <?php foreach ($topicos as $topico): ?>
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="especialidades[]" value="<?php echo $topico['id_topico']; ?>" id="topico_<?php echo $topico['id_topico']; ?>">
                                                    <label class="form-check-label" for="topico_<?php echo $topico['id_topico']; ?>">
                                                        <?php echo htmlspecialchars($topico['nombre']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Registrarse</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <p class="mb-0">¿Ya tiene una cuenta? <a href="login.php">Inicie sesión aquí</a></p>
                        <p class="mt-2"><a href="../index.php">Volver al inicio</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/ocultar sección de especialidades según el rol seleccionado
        document.addEventListener('DOMContentLoaded', function() {
            const rolUsuario = document.getElementById('rol_usuario');
            const rolIngeniero = document.getElementById('rol_ingeniero');
            const especialidadesSection = document.getElementById('especialidades_section');
            
            function toggleEspecialidades() {
                if (rolIngeniero.checked) {
                    especialidadesSection.classList.remove('d-none');
                } else {
                    especialidadesSection.classList.add('d-none');
                    
                    // Desmarcar todos los checkboxes
                    const checkboxes = especialidadesSection.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach(checkbox => checkbox.checked = false);
                }
            }
            
            rolUsuario.addEventListener('change', toggleEspecialidades);
            rolIngeniero.addEventListener('change', toggleEspecialidades);
            
            // Inicializar
            toggleEspecialidades();
            
            // Limitar la selección a máximo 2 especialidades
            const checkboxes = document.querySelectorAll('input[name="especialidades[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const checked = document.querySelectorAll('input[name="especialidades[]"]:checked');
                    if (checked.length > 2) {
                        this.checked = false;
                        alert('Un ingeniero solo puede tener hasta 2 especialidades');
                    }
                });
            });
        });
    </script>
</body>
</html>