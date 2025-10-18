<?php
session_start();
require_once('../config/database.php');

// Verificar si el usuario ha iniciado sesión y es un ingeniero
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'ingeniero') {
    header("Location: ../auth/login.php");
    exit;
}

$db = new DatabaseManager();
$id_ingeniero = $_SESSION['user_id'];

// Obtener las asignaciones del ingeniero
$asignaciones = $db->getAsignacionesByIngeniero($id_ingeniero);

// Obtener todas las solicitudes de funcionalidad
$todas_funcionalidades = $db->getAllFuncionalidades();

// Obtener todas las solicitudes de error
$todos_errores = $db->getAllErrores();

// Obtener las especialidades del ingeniero
$especialidades = $db->getEspecialidadesIngeniero($id_ingeniero);

// Mensajes de respuesta
$mensaje = '';
$tipo_mensaje = '';

// Procesar mensajes de la URL
if (isset($_GET['mensaje']) && isset($_GET['tipo'])) {
    $mensaje = urldecode($_GET['mensaje']);
    $tipo_mensaje = $_GET['tipo'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Ingeniero </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- navbar -->
    <?php include_once('../includes/navbar.php'); ?>

    <div class="container mt-4">
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <h1 class="mb-4">Dashboard de Ingeniero</h1>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></h5>
                        <p class="card-text">Este es tu panel de control donde puedes gestionar las solicitudes asignadas a ti.</p>
                        <p><strong>Especialidades:</strong> 
                            <?php 
                            if (!empty($especialidades)) {
                                $nombres = array_column($especialidades, 'nombre');
                                echo htmlspecialchars(implode(", ", $nombres));
                            } else {
                                echo "No tiene especialidades asignadas";
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Resumen</h5>
                        <div class="row text-center">
                            <div class="col-6">
                                <h2 class="text-primary">
                                    <?php 
                                    $count_funcionalidades = array_reduce($asignaciones, function($carry, $item) {
                                        return $carry + ($item['tipo'] === 'funcionalidad' ? 1 : 0);
                                    }, 0);
                                    echo $count_funcionalidades;
                                    ?>
                                </h2>
                                <p>Funcionalidades Asignadas</p>
                            </div>
                            <div class="col-6">
                                <h2 class="text-danger">
                                    <?php 
                                    $count_errores = array_reduce($asignaciones, function($carry, $item) {
                                        return $carry + ($item['tipo'] === 'error' ? 1 : 0);
                                    }, 0);
                                    echo $count_errores;
                                    ?>
                                </h2>
                                <p>Errores Asignados</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pestañas para navegación entre secciones -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="asignaciones-tab" data-bs-toggle="tab" data-bs-target="#asignaciones" type="button" role="tab" aria-controls="asignaciones" aria-selected="true">
                    Mis Asignaciones
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="todas-funcionalidades-tab" data-bs-toggle="tab" data-bs-target="#todas-funcionalidades" type="button" role="tab" aria-controls="todas-funcionalidades" aria-selected="false">
                    Todas las Funcionalidades
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="todos-errores-tab" data-bs-toggle="tab" data-bs-target="#todos-errores" type="button" role="tab" aria-controls="todos-errores" aria-selected="false">
                    Todos los Errores
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="busqueda-tab" data-bs-toggle="tab" data-bs-target="#busqueda" type="button" role="tab" aria-controls="busqueda" aria-selected="false">
                    Búsqueda Avanzada
                </button>
            </li>
        </ul>
        
        <div class="tab-content pt-4" id="myTabContent">
            <!-- Tab: Mis Asignaciones -->
            <div class="tab-pane fade show active" id="asignaciones" role="tabpanel" aria-labelledby="asignaciones-tab">
                <?php if (empty($asignaciones)): ?>
                    <div class="alert alert-info">
                        No tienes solicitudes asignadas. Las solicitudes se asignarán automáticamente según tus especialidades.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Título</th>
                                    <th>Tópico</th>
                                    <th>Solicitante</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($asignaciones as $a): ?>
                                    <tr>
                                        <td>
                                            <?php if ($a['tipo'] === 'funcionalidad'): ?>
                                                <span class="badge bg-primary">Funcionalidad</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Error</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($a['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($a['nombre_topico']); ?></td>
                                        <td><?php echo htmlspecialchars($a['nombre_usuario']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getEstadoColor($a['estado']); ?>">
                                                <?php echo htmlspecialchars($a['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($a['fecha_publicacion'])); ?></td>
                                        <td>
                                            <?php if ($a['tipo'] === 'funcionalidad'): ?>
                                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalVerFuncionalidad<?php echo $a['id_funcionalidad']; ?>">
                                                    <i class="bi bi-eye"></i> Ver
                                                </button>
                                                <?php if ($a['estado'] === 'En Progreso'): ?>
                                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalResolverFuncionalidad<?php echo $a['id_funcionalidad']; ?>">
                                                        <i class="bi bi-check-lg"></i> Resolver
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalVerError<?php echo $a['id_error']; ?>">
                                                    <i class="bi bi-eye"></i> Ver
                                                </button>
                                                <?php if ($a['estado'] === 'En Progreso'): ?>
                                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalResolverError<?php echo $a['id_error']; ?>">
                                                        <i class="bi bi-check-lg"></i> Resolver
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modales para funcionalidades -->
                                    <?php if ($a['tipo'] === 'funcionalidad'): ?>
                                        <!-- Modal para ver detalles de funcionalidad -->
                                        <div class="modal fade" id="modalVerFuncionalidad<?php echo $a['id_funcionalidad']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Detalles de Solicitud de Funcionalidad</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <h5><?php echo htmlspecialchars($a['titulo']); ?></h5>
                                                            <span class="badge bg-<?php echo getEstadoColor($a['estado']); ?>">
                                                                <?php echo htmlspecialchars($a['estado']); ?>
                                                            </span>
                                                        </div>
                                                        
                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <p><strong>Ambiente:</strong> <?php echo htmlspecialchars($a['ambiente']); ?></p>
                                                                <p><strong>Tópico:</strong> <?php echo htmlspecialchars($a['nombre_topico']); ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>Solicitante:</strong> <?php echo htmlspecialchars($a['nombre_usuario']); ?></p>
                                                                <p><strong>Fecha de publicación:</strong> <?php echo date('d/m/Y', strtotime($a['fecha_publicacion'])); ?></p>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <h6>Resumen:</h6>
                                                            <p><?php echo htmlspecialchars($a['resumen']); ?></p>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <h6>Criterios de aceptación:</h6>
                                                            <ul>
                                                                <?php 
                                                                $criterios = json_decode($a['criterios'], true);
                                                                if (is_array($criterios)) {
                                                                    foreach ($criterios as $criterio) {
                                                                        echo "<li>" . htmlspecialchars($criterio) . "</li>";
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
                                        
                                        <!-- Modal para resolver funcionalidad -->
                                        <?php if ($a['estado'] === 'En Progreso'): ?>
                                            <div class="modal fade" id="modalResolverFuncionalidad<?php echo $a['id_funcionalidad']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Resolver Solicitud de Funcionalidad</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="../solicitudes/funcionalidad/resolver.php" method="post">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="id_funcionalidad" value="<?php echo $a['id_funcionalidad']; ?>">
                                                                <input type="hidden" name="id_ingeniero" value="<?php echo $id_ingeniero; ?>">
                                                                
                                                                <p>Estás a punto de marcar como resuelta la solicitud de funcionalidad "<strong><?php echo htmlspecialchars($a['titulo']); ?></strong>".</p>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="observaciones" class="form-label">Observaciones:</label>
                                                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="5" required></textarea>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" id="criterios_check" required>
                                                                        <label class="form-check-label" for="criterios_check">
                                                                            Confirmo que todos los criterios de aceptación han sido cumplidos.
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-success">Marcar como resuelta</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                    <!-- Modales para errores -->    
                                    <?php else: ?>
                                        <!-- Modal para ver detalles de error -->
                                        <div class="modal fade" id="modalVerError<?php echo $a['id_error']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Detalles de Reporte de Error</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <h5><?php echo htmlspecialchars($a['titulo']); ?></h5>
                                                            <span class="badge bg-<?php echo getEstadoColor($a['estado']); ?>">
                                                                <?php echo htmlspecialchars($a['estado']); ?>
                                                            </span>
                                                        </div>
                                                        
                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <p><strong>Tópico:</strong> <?php echo htmlspecialchars($a['nombre_topico']); ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>Solicitante:</strong> <?php echo htmlspecialchars($a['nombre_usuario']); ?></p>
                                                                <p><strong>Fecha de publicación:</strong> <?php echo date('d/m/Y', strtotime($a['fecha_publicacion'])); ?></p>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <h6>Descripción:</h6>
                                                            <p><?php echo htmlspecialchars($a['descripcion']); ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Modal para resolver error -->
                                        <?php if ($a['estado'] === 'En Progreso'): ?>
                                            <div class="modal fade" id="modalResolverError<?php echo $a['id_error']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Resolver Reporte de Error</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="../solicitudes/error/resolver.php" method="post">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="id_error" value="<?php echo $a['id_error']; ?>">
                                                                <input type="hidden" name="id_ingeniero" value="<?php echo $id_ingeniero; ?>">
                                                                
                                                                <p>Estás a punto de marcar como resuelto el reporte de error "<strong><?php echo htmlspecialchars($a['titulo']); ?></strong>".</p>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="observaciones" class="form-label">Descripción de la solución:</label>
                                                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="5" required></textarea>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" id="confirmacion" required>
                                                                        <label class="form-check-label" for="confirmacion">
                                                                            Confirmo que el error ha sido corregido y verificado.
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-success">Marcar como resuelto</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tab: Todas las Funcionalidades -->
            <div class="tab-pane fade" id="todas-funcionalidades" role="tabpanel" aria-labelledby="todas-funcionalidades-tab">
                <?php if (empty($todas_funcionalidades)): ?>
                    <div class="alert alert-info">
                        No hay solicitudes de funcionalidad registradas.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Ambiente</th>
                                    <th>Tópico</th>
                                    <th>Solicitante</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todas_funcionalidades as $f): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($f['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($f['ambiente']); ?></td>
                                        <td><?php echo htmlspecialchars($f['nombre_topico']); ?></td>
                                        <td><?php echo htmlspecialchars($f['nombre_usuario']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getEstadoColor($f['estado']); ?>">
                                                <?php echo htmlspecialchars($f['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($f['fecha_publicacion'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalVerTodasFunc<?php echo $f['id_funcionalidad']; ?>">
                                                <i class="bi bi-eye"></i> Ver
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal para ver detalles -->
                                    <div class="modal fade" id="modalVerTodasFunc<?php echo $f['id_funcionalidad']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detalles de Solicitud de Funcionalidad</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <h5><?php echo htmlspecialchars($f['titulo']); ?></h5>
                                                        <span class="badge bg-<?php echo getEstadoColor($f['estado']); ?>">
                                                            <?php echo htmlspecialchars($f['estado']); ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <p><strong>Ambiente:</strong> <?php echo htmlspecialchars($f['ambiente']); ?></p>
                                                            <p><strong>Tópico:</strong> <?php echo htmlspecialchars($f['nombre_topico']); ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p><strong>Solicitante:</strong> <?php echo htmlspecialchars($f['nombre_usuario']); ?></p>
                                                            <p><strong>Fecha de publicación:</strong> <?php echo date('d/m/Y', strtotime($f['fecha_publicacion'])); ?></p>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <h6>Resumen:</h6>
                                                        <p><?php echo htmlspecialchars($f['resumen']); ?></p>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <h6>Criterios de aceptación:</h6>
                                                        <ul>
                                                            <?php 
                                                            $criterios = json_decode($f['criterios'], true);
                                                            if (is_array($criterios)) {
                                                                foreach ($criterios as $criterio) {
                                                                    echo "<li>" . htmlspecialchars($criterio) . "</li>";
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
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tab: Todos los Errores -->
            <div class="tab-pane fade" id="todos-errores" role="tabpanel" aria-labelledby="todos-errores-tab">
                <?php if (empty($todos_errores)): ?>
                    <div class="alert alert-info">
                        No hay reportes de error registrados.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Tópico</th>
                                    <th>Solicitante</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todos_errores as $e): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($e['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($e['nombre_topico']); ?></td>
                                        <td><?php echo htmlspecialchars($e['nombre_usuario']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getEstadoColor($e['estado']); ?>">
                                                <?php echo htmlspecialchars($e['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($e['fecha_publicacion'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalVerTodosErr<?php echo $e['id_error']; ?>">
                                                <i class="bi bi-eye"></i> Ver
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal para ver detalles -->
                                    <div class="modal fade" id="modalVerTodosErr<?php echo $e['id_error']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detalles de Reporte de Error</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <h5><?php echo htmlspecialchars($e['titulo']); ?></h5>
                                                        <span class="badge bg-<?php echo getEstadoColor($e['estado']); ?>">
                                                            <?php echo htmlspecialchars($e['estado']); ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <p><strong>Tópico:</strong> <?php echo htmlspecialchars($e['nombre_topico']); ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p><strong>Solicitante:</strong> <?php echo htmlspecialchars($e['nombre_usuario']); ?></p>
                                                            <p><strong>Fecha de publicación:</strong> <?php echo date('d/m/Y', strtotime($e['fecha_publicacion'])); ?></p>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <h6>Descripción:</h6>
                                                        <p><?php echo htmlspecialchars($e['descripcion']); ?></p>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tab: Búsqueda Avanzada -->
            <div class="tab-pane fade" id="busqueda" role="tabpanel" aria-labelledby="busqueda-tab">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Búsqueda Avanzada de Solicitudes</h5>
                        
                        <form action="../busqueda/avanzada.php" method="get">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="fecha_desde" class="form-label">Fecha desde:</label>
                                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde">
                                </div>
                                <div class="col-md-6">
                                    <label for="fecha_hasta" class="form-label">Fecha hasta:</label>
                                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="topico" class="form-label">Tópico:</label>
                                    <select class="form-select" id="topico" name="topico">
                                        <option value="">Todos</option>
                                        <?php foreach ($db->getTopicos() as $topico): ?>
                                            <option value="<?php echo $topico['id_topico']; ?>"><?php echo htmlspecialchars($topico['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="ambiente" class="form-label">Ambiente:</label>
                                    <select class="form-select" id="ambiente" name="ambiente">
                                        <option value="">Todos</option>
                                        <option value="Web">Web</option>
                                        <option value="Movil">Móvil</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="estado" class="form-label">Estado:</label>
                                    <select class="form-select" id="estado" name="estado">
                                        <option value="">Todos</option>
                                        <option value="Abierto">Abierto</option>
                                        <option value="En Progreso">En Progreso</option>
                                        <option value="Resuelto">Resuelto</option>
                                        <option value="Cerrado">Cerrado</option>
                                        <option value="Archivado">Archivado</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Buscar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
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