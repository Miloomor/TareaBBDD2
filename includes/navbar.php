<?php
// Determinar la ruta base
$base_path = '';
$current_path = $_SERVER['SCRIPT_NAME'];
$depth = substr_count($current_path, '/');
if ($depth > 1) {
    $parts = explode('/', $current_path);
    array_pop($parts); // Eliminar el nombre del archivo
    $depth = count($parts) - 1; // -1 para excluir la parte vacía inicial
    $base_path = str_repeat('../', $depth - 1);
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $base_path; ?>index.php">Sistema de Solicitudes</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_path; ?>index.php">Inicio</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['rol'] === 'usuario'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>dashboard/usuario.php">Dashboard</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>dashboard/ingeniero.php">Dashboard</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_path; ?>busqueda/avanzada.php">Búsqueda Avanzada</a>
                </li>
            </ul>
            <div class="d-flex">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="navbar-text me-3 text-white">
                        <i class="bi bi-person-fill"></i> 
                        <?php echo htmlspecialchars($_SESSION['nombre']); ?> 
                        (<?php echo $_SESSION['rol'] === 'usuario' ? 'Usuario' : 'Ingeniero'; ?>)
                    </span>
                    <a href="<?php echo $base_path; ?>auth/logout.php" class="btn btn-outline-light">
                        <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                    </a>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>auth/login.php" class="btn btn-outline-light me-2">
                        <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
                    </a>
                    <a href="<?php echo $base_path; ?>auth/register.php" class="btn btn-light">
                        <i class="bi bi-person-plus"></i> Registrarse
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>