<?php
session_start();
require_once('config/database.php');

$db = new DatabaseManager();

// Si el usuario ha iniciado sesión, redireccionar al dashboard según su rol
if (isset($_SESSION['user_id']) && isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] == 'usuario') {
        header("Location: dashboard/usuario.php");
        exit;
    } else if ($_SESSION['rol'] == 'ingeniero') {
        header("Location: dashboard/ingeniero.php");
        exit;
    }
}

// Buscar solicitudes si se ha realizado una búsqueda
$resultados = [];
$mensaje = '';

if (isset($_GET['query']) && !empty($_GET['query'])) {
    $query = trim($_GET['query']);
    if (strlen($query) >= 3) {
        $resultados = $db->searchSolicitudes($query);
        if (empty($resultados)) {
            $mensaje = "No se encontraron resultados para la búsqueda: " . htmlspecialchars($query);
        }
    } else {
        $mensaje = "La búsqueda debe contener al menos 3 caracteres.";
    }
}

// Obtener tópicos para el filtro de búsqueda avanzada
$topicos = $db->getTopicos();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Solicitudes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Sistema de Solicitudes</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="busqueda/avanzada.php">Búsqueda Avanzada</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="navbar-text me-3">
                            Hola, <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                        </span>
                        <a href="auth/logout.php" class="btn btn-outline-light">Cerrar sesión</a>
                    <?php else: ?>
                        <a href="auth/login.php" class="btn btn-outline-light me-2">Iniciar sesión</a>
                        <a href="auth/register.php" class="btn btn-light">Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <h1 class="mb-4">Sistema de Gestión de Solicitudes</h1>
                <p class="lead mb-5">
                    Plataforma para gestionar solicitudes de funcionalidades y reportes de errores en el desarrollo de software.
                </p>
                
                <!-- Formulario de búsqueda simple -->
                <form action="index.php" method="get" class="mb-5">
                    <div class="input-group input-group-lg">
                        <input type="text" name="query" class="form-control" placeholder="Buscar solicitudes..." 
                               value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>">
                        <button class="btn btn-primary" type="submit">Buscar</button>
                    </div>
                    <div class="form-text text-end">
                        <a href="busqueda/avanzada.php">Búsqueda avanzada</a>
                    </div>
                </form>
                
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">¿Ya tienes una cuenta?</h5>
                                    <p class="card-text">Inicia sesión para acceder a todas las funcionalidades del sistema.</p>
                                    <a href="auth/login.php" class="btn btn-primary">Iniciar sesión</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">¿Eres nuevo?</h5>
                                    <p class="card-text">Regístrate para comenzar a gestionar tus solicitudes.</p>
                                    <a href="auth/register.php" class="btn btn-success">Registrarse</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Resultados de búsqueda -->
        <?php if (!empty($mensaje) || !empty($resultados)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h4 class="mb-0">Resultados de búsqueda</h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($mensaje)): ?>
                                <p class="text-center"><?php echo $mensaje; ?></p>
                            <?php else: ?>
                                <p>Se encontraron <?php echo count($resultados); ?> resultado(s):</p>
                                <div class="list-group">
                                    <?php foreach ($resultados as $item): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5 class="mb-1">
                                                    <?php if ($item['tipo'] === 'funcionalidad'): ?>
                                                        <span class="badge bg-primary me-2">Funcionalidad</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger me-2">Error</span>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($item['titulo']); ?>
                                                </h5>
                                                <span class="badge bg-<?php echo getEstadoColor($item['estado']); ?> rounded-pill">
                                                    <?php echo htmlspecialchars($item['estado']); ?>
                                                </span>
                                            </div>
                                            <p class="mb-1">
                                                <?php 
                                                    if ($item['tipo'] === 'funcionalidad') {
                                                        echo htmlspecialchars($item['resumen']);
                                                    } else {
                                                        echo htmlspecialchars($item['descripcion']);
                                                    }
                                                ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <small class="text-muted">
                                                    Tópico: <?php echo htmlspecialchars($item['nombre_topico']); ?>
                                                </small>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y', strtotime($item['fecha_publicacion'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

<?php
function getEstadoColor($estado) {
    switch ($estado) {
        case 'Abierto':
            return 'secondary';
        case 'En Progreso':
            return 'info';
        case 'Resuelto':
            return 'success';
        case 'Cerrado':
            return 'dark';
        case 'Archivado':
            return 'light';
        default:
            return 'secondary';
    }
}
?>