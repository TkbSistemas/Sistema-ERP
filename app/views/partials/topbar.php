<?php
require_once __DIR__ . '/../../helpers/Navigation.php';

$role = Navigation::normalizeRole($role ?? ($_SESSION['role'] ?? ''));
$nombre = $nombre ?? ($_SESSION['nombre'] ?? '');
$breadcrumbsExtra = $breadcrumbs ?? null;
$breadcrumbsOverride = $breadcrumbsOverride ?? null;

$breadcrumbsMarkup = '';
if (is_array($breadcrumbsOverride)) {
    $breadcrumbsMarkup = Navigation::renderBreadcrumbs($breadcrumbsOverride);
} else {
    $breadcrumbsMarkup = Navigation::renderBreadcrumbs(is_array($breadcrumbsExtra) ? $breadcrumbsExtra : null);
}
?>
<header class="top-header">
    <div class="top-header-left">
        <?= $breadcrumbsMarkup ?>
    </div>
    <div class="top-header-user">
        <span><?= htmlspecialchars($nombre ?: 'Usuario') ?> (<?= htmlspecialchars($role) ?>)</span>
        <i class="fa-solid fa-user-circle"></i>
        <a href="menu.php" class="logout-btn" title="Ir al menú"><i class="fa-solid fa-home"></i></a>
        <a href="logout.php" class="logout-btn" title="Cerrar sesión"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>    
    </div>
</header>
