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
$pagination = $pagination ?? [
    'pagina' => $pagina,
    'total_paginas' => $total_paginas,
    'por_pagina' => 8,
    'total' => 0,
    'desde' => 0,
    'hasta' => 0,
];
$buildPaginationQuery = function (int $targetPage): string {
    return '?' . http_build_query(array_merge($_GET, ['pagina' => $targetPage]));
};
$buildTabQuery = function (string $tab): string {
    return '?' . http_build_query(array_merge($_GET, ['tab' => $tab, 'pagina' => 1]));
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PRESTAMOS HERRAMIENTAS | TAKAB</title>
    <link rel="stylesheet" href="assets/css/prestamos-pendientes.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
</head>
<body class="module-inventory-warehouse">
<?php $seccion_activa = 'prestamos_herramientas'; ?>
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Toggle Menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>
        <main class="prestamos-main">
        <div class="prestamos-title">
            <i class="fa-solid fa-toolbox"></i>
            Prestamos de Herramientas
        </div>
        
            <section class="dashboard-cards-row">
                <div class="dashboard-card">
                    <div class="card-info">
                        <div class="card-label">Prestamos Activos</div>
                        <div class="card-value">10</div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-clock-check"></span>
                    </div>
                </div>
                <div class="dashboard-card warning">
                    <div class="card-info">
                        <div class="card-label">Prestamos Pendientes</div>
                        <div class="card-value"><//?= number_format($datos['solicitudesAlmacen'] ?? 0) ?></div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-clock-alert"></span>
                    </div>
                </div>
                <div class="dashboard-card caution">
                    <div class="card-info">
                        <div class="card-label">Prestamos Vencidos</div>
                        <div class="card-value"><//?= number_format($datos['stockBajo'] ?? 0) ?></div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-clock-remove"></span>
                    </div>
                </div>
            </section>
        <?php 
            $tab_activa = $_GET['tab'] ?? 'pendientes'; 
            ?>

            <div class="tab-container">
                <a href="<?= $buildTabQuery('activas') ?>" class="prestamos-tab <?= $tab_activa === 'activas' ? 'active' : '' ?>">Activas</a>
                <a href="<?= $buildTabQuery('pendientes') ?>" class="prestamos-tab <?= $tab_activa === 'pendientes' ? 'active' : '' ?>">Pendientes</a>
                <a href="<?= $buildTabQuery('vencidas') ?>" class="prestamos-tab <?= $tab_activa === 'vencidas' ? 'active' : '' ?>">Vencidas</a>
                <a href="<?= $buildTabQuery('historial') ?>" class="prestamos-tab <?= $tab_activa === 'historial' ? 'active' : '' ?>">Historial</a>
        </div>
        <div id="wrapper-pendientes" <?= $tab_activa !== 'pendientes' ? 'hidden' : '' ?>>
        <table class="takab-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Empleado</th>
                    <th>Herramienta</th>
                    <th>Fecha Préstamo</th>
                    <th>Fecha Devolución</th>
                    <th>Acción</th>
            </tr>
            </thead>
        <tbody>
            <?php foreach ($prestamos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['folio']) ?></td>
                    <td><?= htmlspecialchars($p['empleado']) ?></td>
                    <td><?= htmlspecialchars($p['herramienta']) ?></td>
                    <td><?= htmlspecialchars($p['fecha_prestamo']) ?></td>
                    <td><?= htmlspecialchars($p['fecha_devolucion']) ?></td>
                    <td>
                        <a class="btn-table" title="Ver" href="ver_solicitud?id=<?= $p['id'] ?>"><i class="fa fa-eye"></i></a>
                        <a class="btn-table" title="Aprobar" href="aprobar_prestamo?id=<?= $p['id'] ?>"><i class="fa fa-circle-check"></i></a>
                        <form method="post" action="rechazar_prestamo" class="inline-form form-eliminar" style="display:inline-block">
                            <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <input type="hidden" name="active" value="0">
                            <button type="submit" class="btn-table btn-danger" title="Aplazar Devolución"> <i class="fa fa-circle-xmark"></i> </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <div id="wrapper-historial" <?= $tab_activa !== 'historial' ? 'hidden' : '' ?>>
        <table class="takab-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Estatus</th>
                    <th>Empleado</th>
                    <th>Herramienta</th>
                    <th>Fecha Solicitud</th>
                    <th>Acción</th>
            </tr>
            </thead>
        <tbody>
            <?php foreach ($prestamos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['folio']) ?></td>
                    <td><?= htmlspecialchars($p['estatus']) ?></td>
                    <td><?= htmlspecialchars($p['empleado']) ?></td>
                    <td><?= htmlspecialchars($p['herramienta']) ?></td>
                    <td><?= htmlspecialchars($p['fecha_solicitud']) ?></td>
                    <td>
                        <a class="btn-table" title="Ver" href="ver_solicitud?id=<?= $p['id'] ?>"><i class="fa fa-eye"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <div id="wrapper-vencidas" <?= $tab_activa !== 'vencidas' ? 'hidden' : '' ?>>
        <table class="takab-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Empleado</th>
                    <th>Herramienta</th>
                    <th>Fecha Préstamo</th>
                    <th>Fecha Devolución</th>
                    <th>Acción</th>
            </tr>
            </thead>
        <tbody>
            <?php foreach ($prestamos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['folio']) ?></td>
                    <td><?= htmlspecialchars($p['estatus']) ?></td>
                    <td><?= htmlspecialchars($p['empleado']) ?></td>
                    <td><?= htmlspecialchars($p['herramienta']) ?></td>
                    <td><?= htmlspecialchars($p['fecha_solicitud']) ?></td>
                    <td><?= htmlspecialchars($p['fecha_devolucion']) ?></td>
                    <td>
                        <a class="btn-table" title="Ver" href="ver_solicitud?id=<?= $p['id'] ?>"><i class="fa fa-eye"></i></a>
                        <a class="btn-table" title="Marcar Devolución" href="aprobar_prestamo?id=<?= $p['id'] ?>"><i class="fa fa-circle-check"></i></a>
                        <form method="post" action="rechazar_prestamo" class="inline-form form-eliminar" style="display:inline-block">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <input type="hidden" name="active" value="0">
                            <button type="submit" class="btn-table btn-danger" title="Aplazar Devolución"> <i class="fa fa-circle-xmark"></i> </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <div id="wrapper-activas" <?= $tab_activa !== 'activas' ? 'hidden' : '' ?>>
        <table class="takab-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Empleado</th>
                    <th>Herramienta</th>
                    <th>Fecha Préstamo</th>
                    <th>Fecha Devolución</th>
                    <th>Acción</th>
            </tr>
            </thead>
        <tbody>
            <?php foreach ($prestamos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['folio']) ?></td>
                    <td><?= htmlspecialchars($p['estatus']) ?></td>
                    <td><?= htmlspecialchars($p['empleado']) ?></td>
                    <td><?= htmlspecialchars($p['herramienta']) ?></td>
                    <td><?= htmlspecialchars($p['fecha_solicitud']) ?></td>
                    <td><?= htmlspecialchars($p['fecha_devolucion']) ?></td>
                    <td>
                        <a class="btn-table" title="Ver" href="ver_solicitud?id=<?= $p['id'] ?>"><i class="fa fa-eye"></i></a>
                        <a class="btn-table" title="Marcar Devolución" href="aprobar_prestamo?id=<?= $p['id'] ?>"><i class="fa fa-circle-check"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        
        <nav class="module-pagination" aria-label="Paginación de préstamos">
            <span class="module-pagination-info">
                <?= $pagination['total'] > 0 ? "Mostrando {$pagination['desde']}–{$pagination['hasta']} de " . number_format($pagination['total']) : 'Sin préstamos para mostrar' ?>
            </span>
            <div class="module-pagination-controls">
                <?php if ($pagination['pagina'] > 1): ?>
                    <a href="<?= $buildPaginationQuery($pagination['pagina'] - 1) ?>" class="module-pagination-button"><i class="fa fa-chevron-left"></i><span>Anterior</span></a>
                <?php endif; ?>
                <span class="module-pagination-status">Página <?= $pagination['pagina'] ?> de <?= $pagination['total_paginas'] ?></span>
                <?php if ($pagination['pagina'] < $pagination['total_paginas']): ?>
                    <a href="<?= $buildPaginationQuery($pagination['pagina'] + 1) ?>" class="module-pagination-button"><span>Siguiente</span><i class="fa fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        </nav>
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
