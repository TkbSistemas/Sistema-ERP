<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Almacen']);
$breadcrumbs = [["label" => 'Préstamos pendientes']];
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';

// Variables necesarias:
// $prestamos           - array de registros a mostrar
// $pagina              - página actual (entero, desde 1)
// $total_paginas       - total de páginas (entero)

if (!isset($pagina)) $pagina = 1;
if (!isset($total_paginas)) $total_paginas = 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>PRESTAMOS HERRAMINETAS | TAKAB</title>
    <link rel="stylesheet" href="assets/css/prestamos-pendientes.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
</head>
<body>
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Toggle Menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <aside class="main_sidebar">
        <div class="sidebar-header">
            <div class="login-logo"><img src="assets/images/icono_takab.png" alt="logo_TAKAB" width="90" height="55"></div>
            <div>
                <div class="sidebar-title">TAKAB</div>
                <div class="sidebar-desc">ERP Takab</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i> Solicitudes de Material</a>
            <a href="gestion_productos"><i class="fa-solid fa-boxes-stacked"></i> Entrada de Productos</a>
            <a href="inventario_actual" ><i class="fa-solid fa-list-check"></i> Baja de Productos</a>
            <a href="prestamos" ><i class="fa-solid fa-screwdriver-wrench"></i> Prestamos de Herramientas</a>
             <?php if ($role === 'Administrador'): ?> <a href="inventario_actual" ><i class="fa-solid fa-list-check"></i> Reportes de Inventario</a> <?php endif; ?>
            <a href="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar Sesión</a>
        </nav>
    </aside>

    <div class="content-area">
        <?php 
            require_once __DIR__ . '/../../helpers/Navigation.php';

            $role = Navigation::normalizeRole($role ?? ($_SESSION['role'] ?? ''));
            ?>
            <header class="top-header">
                <div class="top-header-left">
                </div>
                <div class="top-header-user">
                    <span><?= htmlspecialchars($nombre ?: 'Usuario') ?> (<?= htmlspecialchars($role) ?>)</span>
                    <i class="fa-solid fa-user-circle"></i>
                    <a href="dashboard_almacen" class="logout-btn" title="Ir al Dashboard"><i class="fa-solid fa-home"></i></a>
                    <a href="logout" class="logout-btn" title="Cerrar Sesión"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>    
                </div>
            </header>

        <main class="prestamos-main">
        <div class="prestamos-title">
            <i class="fa-solid fa-toolbox"></i>
            Prestamos de Herramientas
        </div>
        
            <section class="dashboard-cards-row">
                <div class="dashboard-card blue">
                    <div class="card-info">
                        <div class="card-label">Prestamos Activos</div>
                        <div class="card-value">10</div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-clock-check"></span>
                    </div>
                </div>
                <div class="dashboard-card yellow">
                    <div class="card-info">
                        <div class="card-label">Prestamos Pendientes</div>
                        <div class="card-value"><//?= number_format($datos['solicitudesAlmacen'] ?? 0) ?></div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-clock-alert"></span>
                    </div>
                </div>
                <div class="dashboard-card red">
                    <div class="card-info">
                        <div class="card-label">Prestamos Vencidos</div>
                        <div class="card-value"><//?= number_format($datos['stockBajo'] ?? 0) ?></div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-clock-remove"></span>
                    </div>
                </div>
            </section>
        <div class="prestamos-tabs">
            <a href="prestamos_historial.php" class="prestamos-tab">Activos</a>
            <a href="prestamos_pendientes.php" class="prestamos-tab active">Pendientes</a>
            <a href="prestamos_historial.php" class="prestamos-tab">Vencidos</a>
            <a href="prestamos_historial.php" class="prestamos-tab">Historial</a>
        </div>
        <table class="takab-table">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Código</th>

                    <th>Herramienta</th>
                <th>Fecha Préstamo</th>

                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($prestamos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['empleado']) ?></td>
                    <td><?= htmlspecialchars($p['codigo_producto']) ?></td>
                    <td><?= htmlspecialchars($p['producto']) ?></td>
                    <td><?= htmlspecialchars($p['fecha_prestamo']) ?></td>

                    <td>
                        <a href="prestamo_devolver.php?id=<?= $p['id'] ?>" class="btn-devolver">
                            <i class="fa fa-undo"></i> Registrar devolución
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- PAGINACIÓN -->
        <?php if ($total_paginas > 1): ?>
            <div class="takab-pagination">
                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?= $pagina - 1 ?>" class="pagination-btn">&laquo; Anterior</a>
                <?php endif; ?>
                <?php
                $inicio = max(1, $pagina - 3);
                $fin    = min($total_paginas, $pagina + 3);
                for ($i = $inicio; $i <= $fin; $i++):
                    if ($i == $pagina): ?>
                        <span class="pagination-current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?pagina=<?= $i ?>" class="pagination-btn"><?= $i ?></a>
                    <?php endif;
                endfor;
                if ($pagina < $total_paginas): ?>
                    <a href="?pagina=<?= $pagina + 1 ?>" class="pagination-btn">Siguiente &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        </main>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById('toggleSidebar');
   const sidebar = document.querySelector('.main_sidebar'); 
    const mainContent = document.querySelector('.content-area');

    if (toggleBtn && sidebar && mainContent) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            
            mainContent.classList.toggle('collapsed');
            
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                if (sidebar.classList.contains('collapsed')) {
                    icon.className = 'fa-solid fa-bars'; 
                } else {
                    icon.className = 'fa-solid fa-xmark'; 
                }
            }
        });
    }
});
</script>
</body>
</html>
