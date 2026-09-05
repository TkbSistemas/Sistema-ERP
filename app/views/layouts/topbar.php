<?php
$nombre = $nombre ?? ($_SESSION['nombre'] ?? '');
$role = $role ?? ($_SESSION['role'] ?? '');

$dashboard_slug = 'dashboard_empleado'; // Por defecto
$home_title = 'Ir al Dashboard';

switch ($role) {
    case 'Administrador':
        $dashboard_slug = 'menu_admin';
        $home_title = 'Ir al menú principal';
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
        <a href="<?= htmlspecialchars(Session::url($dashboard_slug), ENT_QUOTES, 'UTF-8') ?>" class="logout-btn" title="<?= htmlspecialchars($home_title, ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-home"></i></a>
        <a href="logout" class="logout-btn" title="Cerrar Sesión"><i class="fa-solid fa-arrow-right-from-bracket"></i></a> 
    </div>
</header>
