<?php
session_start();
require_once('../config/database.php');

$db = new DatabaseManager();
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Búsqueda - Sistema de Gestión de Solicitudes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Incluir navbar -->
    <?php include_once('../includes/navbar.php'); ?>

    <div class="container mt-4">
        <h1 class="mb-4">Búsqueda de Solicitudes</h1>
        
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="simple.php" method="get">
                    <div class="input-group input-group-lg">
                        <input type="text" name="query" class="form-control" placeholder="Buscar solicitudes..." 
                               value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>">
                        <button class="btn btn-primary" type="submit">Buscar</button>
                    </div>
                    <div class="form-text text-end">
                        <a href="avanzada.php">Búsqueda avanzada</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Resultados de búsqueda -->
        <?php if (!empty($mensaje) || !empty($resultados)): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Resultados de búsqueda</h5>
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
                                                echo htmlspecialchars(substr($item['resumen'], 0, 150)) . (strlen($item['resumen']) > 150 ? '...' : '');
                                            } else {
                                                echo htmlspecialchars(substr($item['descripcion'], 0, 150)) . (strlen($item['descripcion']) > 150 ? '...' : '');
                                            }
                                        ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted">
                                            <strong>Tópico:</strong> <?php echo htmlspecialchars($item['nombre_topico']); ?>
                                            &nbsp;|&nbsp;
                                            <strong>Solicitante:</strong> <?php echo htmlspecialchars($item['nombre_usuario']); ?>
                                        </small>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y', strtotime($item['fecha_publicacion'])); ?>
                                        </small>
                                    </div>
                                    <div class="mt-2">
                                        <?php if ($item['tipo'] === 'funcionalidad'): ?>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalVerF<?php echo $item['id_funcionalidad']; ?>">
                                                <i class="bi bi-eye"></i> Ver detalles
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalVerE<?php echo $item['id_error']; ?>">
                                                <i class="bi bi-eye"></i> Ver detalles
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Modales para ver detalles -->
                                <?php if ($item['tipo'] === 'funcionalidad'): ?>
                                    <div class="modal fade" id="modalVerF<?php echo $item['id_funcionalidad']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detalles de Solicitud de Funcionalidad</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <h5><?php echo htmlspecialchars($item['titulo']); ?></h5>
                                                        <span class="badge bg-<?php echo getEstadoColor($item['estado']); ?>">
                                                            <?php echo htmlspecialchars($item['estado']); ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <p><strong>Ambiente:</strong> <?php echo htmlspecialchars($item['ambiente']); ?></p>
                                                            <p><strong>Tópico:</strong> <?php echo htmlspecialchars($item['nombre_topico']); ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p><strong>Solicitante:</strong> <?php echo htmlspecialchars($item['nombre_usuario']); ?></p>
                                                            <p><strong>Fecha de publicación:</strong> <?php echo date('d/m/Y', strtotime($item['fecha_publicacion'])); ?></p>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <h6>Resumen:</h6>
                                                        <p><?php echo htmlspecialchars($item['resumen']); ?></p>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <h6>Criterios de aceptación:</h6>
                                                        <ul>
                                                            <?php 
                                                            if ($item['tipo'] === 'funcionalidad') {
                                                                $criterios = $db->getCriteriosFuncionalidad($item['id_funcionalidad']);
                                                                if (is_array($criterios)) {
                                                                    foreach ($criterios as $criterio) {
                                                                        echo "<li>" . htmlspecialchars($criterio) . "</li>";
                                                                    }
                                                                }
                                                            }
                                                            ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="modal fade" id="modalVerE<?php echo $item['id_error']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detalles de Reporte de Error</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <h5><?php echo htmlspecialchars($item['titulo']); ?></h5>
                                                        <span class="badge bg-<?php echo getEstadoColor($item['estado']); ?>">
                                                            <?php echo htmlspecialchars($item['estado']); ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <p><strong>Tópico:</strong> <?php echo htmlspecialchars($item['nombre_topico']); ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p><strong>Solicitante:</strong> <?php echo htmlspecialchars($item['nombre_usuario']); ?></p>
                                                            <p><strong>Fecha de publicación:</strong> <?php echo date('d/m/Y', strtotime($item['fecha_publicacion'])); ?></p>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <h6>Descripción:</h6>
                                                        <p><?php echo htmlspecialchars($item['descripcion']); ?></p>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <footer class="bg-light text-center py-4 mt-5">
        <div class="container">
            <p class="mb-0">Sistema de Gestión de Solicitudes &copy; <?php echo date('Y'); ?></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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