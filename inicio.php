<?php
session_start();
require_once('config/database.php');
include 'includes/navbar.php';

$db = new DatabaseManager();

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
    <title>ZeroPressure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <h1 class="mb-4">ZeroPressure</h1>
                <p class="lead mb-5">
                    Gestión de solicitudes de funcionalidades y solicitudes de error.
                </p>
                
                <!-- Formulario de búsqueda simple -->
                <form action="inicio.php" method="get" class="mb-5">
                    <div class="input-group input-group-lg">
                        <input type="text" name="query" class="form-control" placeholder="Buscar solicitudes..." 
                               value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>">
                        <button class="btn btn-primary" type="submit">Buscar</button>
                    </div>
                    <div class="form-text text-end">
                        <a href="busqueda/avanzada.php">Búsqueda avanzada</a>
                    </div>
                </form>
                
                <!-- 🔹 CAMBIO 2: Solo mostrar tarjetas si NO hay sesión -->
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
                <!-- 🔹 FIN CAMBIO 2 -->
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
    <footer class="bg-light text-center py-4 mt-5">
        <div class="container">
            <p class="mb-0">ZeroPressure &copy; <?php echo date('Y'); ?></p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

<?php
function getEstadoColor($estado) {
    switch ($estado) {
        case 'Abierto': return 'secondary';
        case 'En Progreso': return 'info';
        case 'Resuelto': return 'success';
        case 'Cerrado': return 'dark';
        case 'Archivado': return 'light';
        default: return 'secondary';
    }
}
?>
