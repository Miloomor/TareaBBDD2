<?php
session_start();
require_once('../config/database.php');

$db = new DatabaseManager();

// Verificar si hay parámetros de búsqueda
$hay_busqueda = isset($_GET['fecha_desde']) || isset($_GET['fecha_hasta']) || 
                isset($_GET['topico']) || isset($_GET['ambiente']) || isset($_GET['estado']);

// Preparar filtros
$filtros = array();

if (!empty($_GET['fecha_desde'])) {
    $filtros['fecha_desde'] = $_GET['fecha_desde'];
}

if (!empty($_GET['fecha_hasta'])) {
    $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
}

if (!empty($_GET['topico'])) {
    $filtros['topico'] = $_GET['topico'];
}

if (!empty($_GET['ambiente'])) {
    $filtros['ambiente'] = $_GET['ambiente'];
}

if (!empty($_GET['estado'])) {
    $filtros['estado'] = $_GET['estado'];
}

// Realizar búsqueda si hay filtros
$resultados = array();
if ($hay_busqueda) {
    $resultados = $db->advancedSearch($filtros);
}

// Obtener la lista de tópicos para los filtros
$topicos = $db->getTopicos();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Búsqueda Avanzada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Incluir navbar -->
    <?php include_once('../includes/navbar.php'); ?>

    <div class="container mt-4">
        <h1 class="mb-4">Búsqueda Avanzada</h1>
        
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title mb-4">Filtros de búsqueda</h5>
                
                <form action="avanzada.php" method="get">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="fecha_desde" class="form-label">Fecha desde:</label>
                            <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?php echo isset($filtros['fecha_desde']) ? $filtros['fecha_desde'] : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_hasta" class="form-label">Fecha hasta:</label>
                            <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?php echo isset($filtros['fecha_hasta']) ? $filtros['fecha_hasta'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="topico" class="form-label">Tópico:</label>
                            <select class="form-select" id="topico" name="topico">
                                <option value="">Todos</option>
                                <?php foreach ($topicos as $topico): ?>
                                    <option value="<?php echo $topico['id_topico']; ?>" <?php if(isset($filtros['topico']) && $filtros['topico'] == $topico['id_topico']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($topico['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="ambiente" class="form-label">Ambiente:</label>
                            <select class="form-select" id="ambiente" name="ambiente">
                                <option value="">Todos</option>
                                <option value="Web" <?php if(isset($filtros['ambiente']) && $filtros['ambiente'] == 'Web') echo 'selected'; ?>>Web</option>
                                <option value="Movil" <?php if(isset($filtros['ambiente']) && $filtros['ambiente'] == 'Movil') echo 'selected'; ?>>Móvil</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="estado" class="form-label">Estado:</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="">Todos</option>
                                <option value="Abierto" <?php if(isset($filtros['estado']) && $filtros['estado'] == 'Abierto') echo 'selected'; ?>>Abierto</option>
                                <option value="En Progreso" <?php if(isset($filtros['estado']) && $filtros['estado'] == 'En Progreso') echo 'selected'; ?>>En Progreso</option>
                                <option value="Resuelto" <?php if(isset($filtros['estado']) && $filtros['estado'] == 'Resuelto') echo 'selected'; ?>>Resuelto</option>
                                <option value="Cerrado" <?php if(isset($filtros['estado']) && $filtros['estado'] == 'Cerrado') echo 'selected'; ?>>Cerrado</option>
                                <option value="Archivado" <?php if(isset($filtros['estado']) && $filtros['estado'] == 'Archivado') echo 'selected'; ?>>Archivado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Buscar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Resultados de búsqueda -->
        <?php if ($hay_busqueda): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Resultados de búsqueda</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($resultados)): ?>
                        <p class="text-center">No se encontraron resultados para los criterios seleccionados.</p>
                    <?php else: ?>
                        <p>Se encontraron <?php echo count($resultados); ?> resultado(s):</p>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Título</th>
                                        <th>Tópico</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Solicitante</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resultados as $item): ?>
                                        <tr>
                                            <td>
                                                <?php if ($item['tipo'] === 'funcionalidad'): ?>
                                                    <span class="badge bg-primary">Funcionalidad</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Error</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['titulo']); ?></td>
                                            <td><?php echo htmlspecialchars($item['nombre_topico']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getEstadoColor($item['estado']); ?>">
                                                    <?php echo htmlspecialchars($item['estado']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($item['fecha_publicacion'])); ?></td>
                                            <td><?php echo htmlspecialchars($item['nombre_usuario']); ?></td>
                                            <td>
                                                <?php if ($item['tipo'] === 'funcionalidad'): ?>
                                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalVerF<?php echo $item['id_funcionalidad']; ?>">
                                                        <i class="bi bi-eye"></i> Ver
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalVerE<?php echo $item['id_error']; ?>">
                                                        <i class="bi bi-eye"></i> Ver
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        
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
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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