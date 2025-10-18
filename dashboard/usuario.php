<?php
session_start();
require_once('../config/database.php');

// Verificar si el usuario ha iniciado sesión y es un usuario (no ingeniero)
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'usuario') {
    header("Location: ../auth/login.php");
    exit;
}

$db = new DatabaseManager();
$user_id = $_SESSION['user_id'];

// Obtener las solicitudes de funcionalidad del usuario
$funcionalidades = $db->getFuncionalidadesByUser($user_id);

// Obtener las solicitudes de error del usuario
$errores = $db->getErroresByUser($user_id);

// Obtener la lista de tópicos para los formularios de creación
$topicos = $db->getTopicos();

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
    <title>Dashboard de Usuario</title>
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
        
        <h1 class="mb-4">Dashboard de Usuario</h1>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></h5>
                        <p class="card-text">Este es tu panel de control donde puedes gestionar tus solicitudes de funcionalidad y reportes de errores.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Resumen</h5>
                        <div class="row text-center">
                            <div class="col-6">
                                <h2 class="text-primary"><?php echo count($funcionalidades); ?></h2>
                                <p>Solicitudes de Funcionalidad</p>
                            </div>
                            <div class="col-6">
                                <h2 class="text-danger"><?php echo count($errores); ?></h2>
                                <p>Reportes de Error</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pestañas para navegación entre secciones -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="funcionalidades-tab" data-bs-toggle="tab" data-bs-target="#funcionalidades" type="button" role="tab" aria-controls="funcionalidades" aria-selected="true">
                    Solicitudes de Funcionalidad
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="errores-tab" data-bs-toggle="tab" data-bs-target="#errores" type="button" role="tab" aria-controls="errores" aria-selected="false">
                    Reportes de Error
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="nueva-funcionalidad-tab" data-bs-toggle="tab" data-bs-target="#nueva-funcionalidad" type="button" role="tab" aria-controls="nueva-funcionalidad" aria-selected="false">
                    Nueva Funcionalidad
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="nuevo-error-tab" data-bs-toggle="tab" data-bs-target="#nuevo-error" type="button" role="tab" aria-controls="nuevo-error" aria-selected="false">
                    Nuevo Error
                </button>
            </li>
        </ul>
        
        <div class="tab-content pt-4" id="myTabContent">
            <!-- Tab: Solicitudes de Funcionalidad -->
            <div class="tab-pane fade show active" id="funcionalidades" role="tabpanel" aria-labelledby="funcionalidades-tab">
                <?php if (empty($funcionalidades)): ?>
                    <div class="alert alert-info">
                        No tienes solicitudes de funcionalidad. Crea una nueva desde la pestaña "Nueva Funcionalidad".
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Ambiente</th>
                                    <th>Tópico</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($funcionalidades as $f): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($f['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($f['ambiente']); ?></td>
                                        <td><?php echo htmlspecialchars($f['nombre_topico']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getEstadoColor($f['estado']); ?>">
                                                <?php echo htmlspecialchars($f['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($f['fecha_publicacion'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalVerFuncionalidad<?php echo $f['id_funcionalidad']; ?>">
                                                <i class="bi bi-eye"></i> Ver
                                            </button>
                                            <?php if ($f['estado'] !== 'En Progreso' && $f['estado'] !== 'Resuelto' && $f['estado'] !== 'Cerrado' && $f['estado'] !== 'Archivado'): ?>
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalEditarFuncionalidad<?php echo $f['id_funcionalidad']; ?>">
                                                    <i class="bi bi-pencil"></i> Editar
                                                </button>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarFuncionalidad<?php echo $f['id_funcionalidad']; ?>">
                                                    <i class="bi bi-trash"></i> Eliminar
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal para ver detalles -->
                                    <div class="modal fade" id="modalVerFuncionalidad<?php echo $f['id_funcionalidad']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detalles de Solicitud</h5>
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
                                                            $criterios = $db->getCriteriosFuncionalidad($f['id_funcionalidad']);
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
                                    
                                    <!-- Modal para editar -->
                                    <?php if ($f['estado'] !== 'En Progreso' && $f['estado'] !== 'Resuelto' && $f['estado'] !== 'Cerrado' && $f['estado'] !== 'Archivado'): ?>
                                        <div class="modal fade" id="modalEditarFuncionalidad<?php echo $f['id_funcionalidad']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Editar Solicitud de Funcionalidad</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="../solicitudes/funcionalidad/update.php" method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id_funcionalidad" value="<?php echo $f['id_funcionalidad']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label for="titulo" class="form-label">Título</label>
                                                                <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($f['titulo']); ?>" required minlength="20">
                                                                <div class="form-text">Mínimo 20 caracteres</div>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="ambiente" class="form-label">Ambiente</label>
                                                                <select class="form-select" id="ambiente" name="ambiente" required>
                                                                    <option value="Web" <?php if($f['ambiente'] == 'Web') echo 'selected'; ?>>Web</option>
                                                                    <option value="Movil" <?php if($f['ambiente'] == 'Movil') echo 'selected'; ?>>Móvil</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="resumen" class="form-label">Resumen</label>
                                                                <textarea class="form-control" id="resumen" name="resumen" required rows="3"><?php echo htmlspecialchars($f['resumen']); ?></textarea>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="topico" class="form-label">Tópico</label>
                                                                <select class="form-select" id="topico" name="topico" required>
                                                                    <?php foreach ($topicos as $topico): ?>
                                                                        <option value="<?php echo $topico['id_topico']; ?>" <?php if($topico['id_topico'] == $f['id_topico']) echo 'selected'; ?>>
                                                                            <?php echo htmlspecialchars($topico['nombre']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Criterios de aceptación</label>
                                                                <div id="criterios-container-edit-<?php echo $f['id_funcionalidad']; ?>">
                                                                    <?php 
                                                                    $criterios = $db->getCriteriosFuncionalidad($f['id_funcionalidad']);
                                                                    if (is_array($criterios)) {
                                                                        foreach ($criterios as $index => $criterio) {
                                                                            ?>
                                                                            <div class="input-group mb-2">
                                                                                <input type="text" class="form-control" name="criterios[]" value="<?php echo htmlspecialchars($criterio); ?>" required>
                                                                                <?php if ($index >= 3): ?>
                                                                                    <button type="button" class="btn btn-danger" onclick="eliminarCriterio(this)">
                                                                                        <i class="bi bi-trash"></i>
                                                                                    </button>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                            <?php
                                                                        }
                                                                    }
                                                                    ?>
                                                                </div>
                                                                <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="agregarCriterio('criterios-container-edit-<?php echo $f['id_funcionalidad']; ?>')">
                                                                    <i class="bi bi-plus"></i> Agregar criterio
                                                                </button>
                                                                <div class="form-text">Mínimo 3 criterios de aceptación</div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Modal para eliminar -->
                                        <div class="modal fade" id="modalEliminarFuncionalidad<?php echo $f['id_funcionalidad']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Eliminar Solicitud</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>¿Está seguro que desea eliminar la solicitud de funcionalidad "<strong><?php echo htmlspecialchars($f['titulo']); ?></strong>"?</p>
                                                        <p class="text-danger">Esta acción no se puede deshacer.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <form action="../solicitudes/funcionalidad/delete.php" method="post">
                                                            <input type="hidden" name="id_funcionalidad" value="<?php echo $f['id_funcionalidad']; ?>">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-danger">Eliminar</button>
                                                        </form>
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
            
            <!-- Tab: Reportes de Error -->
            <div class="tab-pane fade" id="errores" role="tabpanel" aria-labelledby="errores-tab">
                <?php if (empty($errores)): ?>
                    <div class="alert alert-info">
                        No tienes reportes de error. Crea uno nuevo desde la pestaña "Nuevo Error".
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Tópico</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($errores as $e): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($e['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($e['nombre_topico']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getEstadoColor($e['estado']); ?>">
                                                <?php echo htmlspecialchars($e['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($e['fecha_publicacion'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalVerError<?php echo $e['id_error']; ?>">
                                                <i class="bi bi-eye"></i> Ver
                                            </button>
                                            <?php if ($e['estado'] !== 'En Progreso' && $e['estado'] !== 'Resuelto' && $e['estado'] !== 'Cerrado' && $e['estado'] !== 'Archivado'): ?>
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalEditarError<?php echo $e['id_error']; ?>">
                                                    <i class="bi bi-pencil"></i> Editar
                                                </button>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarError<?php echo $e['id_error']; ?>">
                                                    <i class="bi bi-trash"></i> Eliminar
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal para ver detalles -->
                                    <div class="modal fade" id="modalVerError<?php echo $e['id_error']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detalles de Error</h5>
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
                                    
                                    <!-- Modal para editar -->
                                    <?php if ($e['estado'] !== 'En Progreso' && $e['estado'] !== 'Resuelto' && $e['estado'] !== 'Cerrado' && $e['estado'] !== 'Archivado'): ?>
                                        <div class="modal fade" id="modalEditarError<?php echo $e['id_error']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Editar Reporte de Error</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="../solicitudes/error/update.php" method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id_error" value="<?php echo $e['id_error']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label for="titulo" class="form-label">Título</label>
                                                                <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($e['titulo']); ?>" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="descripcion" class="form-label">Descripción</label>
                                                                <textarea class="form-control" id="descripcion" name="descripcion" required rows="5"><?php echo htmlspecialchars($e['descripcion']); ?></textarea>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="topico" class="form-label">Tópico</label>
                                                                <select class="form-select" id="topico" name="topico" required>
                                                                    <?php foreach ($topicos as $topico): ?>
                                                                        <option value="<?php echo $topico['id_topico']; ?>" <?php if($topico['id_topico'] == $e['id_topico']) echo 'selected'; ?>>
                                                                            <?php echo htmlspecialchars($topico['nombre']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Modal para eliminar -->
                                        <div class="modal fade" id="modalEliminarError<?php echo $e['id_error']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Eliminar Reporte de Error</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>¿Está seguro que desea eliminar el reporte de error "<strong><?php echo htmlspecialchars($e['titulo']); ?></strong>"?</p>
                                                        <p class="text-danger">Esta acción no se puede deshacer.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <form action="../solicitudes/error/delete.php" method="post">
                                                            <input type="hidden" name="id_error" value="<?php echo $e['id_error']; ?>">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-danger">Eliminar</button>
                                                        </form>
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
            
            <!-- Tab: Nueva Funcionalidad -->
            <div class="tab-pane fade" id="nueva-funcionalidad" role="tabpanel" aria-labelledby="nueva-funcionalidad-tab">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Crear nueva solicitud de funcionalidad</h5>
                        
                        <form action="../solicitudes/funcionalidad/create.php" method="post" id="form-nueva-funcionalidad">
                            <div class="mb-3">
                                <label for="titulo_nueva" class="form-label">Título</label>
                                <input type="text" class="form-control" id="titulo_nueva" name="titulo" required minlength="20">
                                <div class="form-text">Mínimo 20 caracteres</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ambiente_nueva" class="form-label">Ambiente</label>
                                <select class="form-select" id="ambiente_nueva" name="ambiente" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Web">Web</option>
                                    <option value="Movil">Móvil</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="resumen_nueva" class="form-label">Resumen</label>
                                <textarea class="form-control" id="resumen_nueva" name="resumen" required rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="topico_nueva" class="form-label">Tópico</label>
                                <select class="form-select" id="topico_nueva" name="topico" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($topicos as $topico): ?>
                                        <option value="<?php echo $topico['id_topico']; ?>"><?php echo htmlspecialchars($topico['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Criterios de aceptación</label>
                                <div id="criterios-container">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="criterios[]" placeholder="Criterio 1" required>
                                    </div>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="criterios[]" placeholder="Criterio 2" required>
                                    </div>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="criterios[]" placeholder="Criterio 3" required>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="agregarCriterio('criterios-container')">
                                    <i class="bi bi-plus"></i> Agregar criterio
                                </button>
                                <div class="form-text">Mínimo 3 criterios de aceptación</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Crear Solicitud de Funcionalidad</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Tab: Nuevo Error -->
            <div class="tab-pane fade" id="nuevo-error" role="tabpanel" aria-labelledby="nuevo-error-tab">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Crear nuevo reporte de error</h5>
                        
                        <form action="../solicitudes/error/create.php" method="post">
                            <div class="mb-3">
                                <label for="titulo_error" class="form-label">Título</label>
                                <input type="text" class="form-control" id="titulo_error" name="titulo" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion_error" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion_error" name="descripcion" required rows="5"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="topico_error" class="form-label">Tópico</label>
                                <select class="form-select" id="topico_error" name="topico" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($topicos as $topico): ?>
                                        <option value="<?php echo $topico['id_topico']; ?>"><?php echo htmlspecialchars($topico['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-danger">Crear Reporte de Error</button>
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
    <script>
        // Función para agregar criterio de aceptación
        function agregarCriterio(containerId) {
            const container = document.getElementById(containerId);
            const div = document.createElement('div');
            div.className = 'input-group mb-2';
            
            div.innerHTML = `
                <input type="text" class="form-control" name="criterios[]" required>
                <button type="button" class="btn btn-danger" onclick="eliminarCriterio(this)">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            
            container.appendChild(div);
        }
        
        // Función para eliminar criterio de aceptación
        function eliminarCriterio(button) {
            const inputGroup = button.parentNode;
            const container = inputGroup.parentNode;
            
            // Asegurarse de que queden al menos 3 criterios
            if (container.childElementCount > 3) {
                container.removeChild(inputGroup);
            } else {
                alert('Debe haber al menos 3 criterios de aceptación.');
            }
        }
    </script>
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