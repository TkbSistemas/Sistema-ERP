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
    <title>Herramientas Pendientes de Devolución | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/prestamos-pendientes.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="main-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="login-logo"><img src="assets/images/icono_takab.png" alt="logo_TAKAB" width="90" height="55""></div>
            <div>
                <div class="sidebar-title">TAKAB</div>
                <div class="sidebar-desc">Dashboard</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="productos.php"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php"><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href='revisar_solicitudes.php'><i class="fa-solid fa-comment-medical"></i>Solicitudes de Material</a>
                <a href='prestamos_pendientes.php' class="active">Préstamos Pendientes</a>
                <a href='prestamos_historial.php'>Historial de Préstamos</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="ajustes.php"><i class="fa-solid fa-gear"></i> Configuración</a>

            <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>

    <div class="content-area">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>

        <main class="prestamos-main">
        <div class="prestamos-title">
            <i class="fa-solid fa-toolbox"></i>
            Herramientas Prestadas y Pendientes de Devolución
        </div>
        <div class="prestamos-tabs">
            <a href="prestamos_pendientes.php" class="prestamos-tab active">Pendientes</a>
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
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
