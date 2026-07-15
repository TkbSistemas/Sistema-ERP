<?php
$nombre = $nombre ?? ($_SESSION['nombre'] ?? '');
$role = $role ?? ($_SESSION['role'] ?? '');

$dashboard_slug = 'dashboard_empleado'; // Por defecto

switch ($role) {
    case 'Administrador':
        $dashboard_slug = 'dashboard_admin';
        break;
    case 'Compras':
        $dashboard_slug = 'dashboard_compras';
        break;
    case 'Almacen':
        $dashboard_slug = 'dashboard_almacen';
        break;
}
?>

<header class="top-header">
    <div class="top-header-left">
    </div>
    <div class="top-header-user">
        <span><?= htmlspecialchars($nombre ?: 'Usuario') ?> (<?= htmlspecialchars($role) ?>)</span>
        <i class="fa-solid fa-user-circle"></i>
        <a href="<?= $dashboard_slug ?>" class="logout-btn" title="Ir al Dashboard"><i class="fa-solid fa-home"></i></a>
        <a href="logout" class="logout-btn" title="Cerrar Sesión"><i class="fa-solid fa-arrow-right-from-bracket"></i></a> 
    </div>
</header>
