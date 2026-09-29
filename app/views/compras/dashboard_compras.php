<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Compras']);

$role = $datos['role'] ?? ($_SESSION['role'] ?? 'Compras');
$nombre = $datos['nombre'] ?? ($_SESSION['nombre'] ?? '');
$ultimasOrdenes = is_array($datos['ultimas_ordenes'] ?? null) ? $datos['ultimas_ordenes'] : [];
$seccion_activa = 'dashboard_compras';

$fechaLegible = static function (?string $fecha): string {
    if (!$fecha) {
        return 'Sin Fecha';
    }
    $timestamp = strtotime($fecha);
    return $timestamp === false ? $fecha : date('d/m/Y', $timestamp);
};

$estatusClase = static function (string $estatus): string {
    return preg_replace('/[^a-z]/', '', strtolower(trim($estatus))) ?: 'pendiente';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard de Compras | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/dashboard_custom.css?v=20260927-2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
</head>
<body>
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Abrir o Cerrar Menú" aria-expanded="true">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>

        <main class="dashboard-main compras-dashboard-main">
            <div class="dashboard-header-row">
                <div>
                    <h1 class="page-title-icon"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i> DASHBOARD DE COMPRAS</h1>
                    <span class="dashboard-desc">Consulta las Órdenes que Requieren Atención y los Movimientos Recientes.</span>
                </div>
                <div class="productos-header-actions">
                    <a class="btn-main" href="<?= htmlspecialchars(Session::url('orden_nueva'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid fa-plus"></i> Nueva Orden
                    </a>
                </div>
            </div>

            <section class="dashboard-cards-row compras-dashboard-cards" aria-label="Resumen de Órdenes de Compra">
                <a class="dashboard-card yellow dashboard-card-link" href="<?= htmlspecialchars(Session::url('ordenes_compra') . '?tab=pendientes', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="card-info">
                        <div class="card-label">Órdenes Pendientes</div>
                        <div class="card-value"><?= number_format((int) ($datos['ordenes_pendientes'] ?? 0)) ?></div>
                        <div class="card-sub">En Espera de Aprobación</div>
                    </div>
                    <div class="card-icon-container"><span class="mdi mdi-file-clock-outline"></span></div>
                </a>

                <a class="dashboard-card waiting dashboard-card-link" href="<?= htmlspecialchars(Session::url('ordenes_compra') . '?tab=pendientes', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="card-info">
                        <div class="card-label">Órdenes por Procesar</div>
                        <div class="card-value"><?= number_format((int) ($datos['ordenes_por_procesar'] ?? 0)) ?></div>
                        <div class="card-sub">Aprobadas o Parciales</div>
                    </div>
                    <div class="card-icon-container"><span class="mdi mdi-progress-clock"></span></div>
                </a>

                <a class="dashboard-card red dashboard-card-link" href="<?= htmlspecialchars(Session::url('facturas_compras') . '?factura=pendiente', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="card-info">
                        <div class="card-label">Órdenes sin Factura</div>
                        <div class="card-value"><?= number_format((int) ($datos['ordenes_sin_factura'] ?? 0)) ?></div>
                        <div class="card-sub">Procesadas o Recibidas</div>
                    </div>
                    <div class="card-icon-container"><span class="mdi mdi-file-alert-outline"></span></div>
                </a>
            </section>

            <section class="dashboard-widget compras-orders-widget">
                <div class="compras-widget-header">
                    <div>
                        <div class="widget-title blue"><i class="fa-solid fa-clock-rotate-left"></i> Últimas Órdenes de Compra</div>
                        <p>Actividad Reciente del Proceso de Compras.</p>
                    </div>
                    <a href="<?= htmlspecialchars(Session::url('ordenes_compra') . '?tab=historial', ENT_QUOTES, 'UTF-8') ?>" class="dashboard-view-all">
                        Ver Todas <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                <?php if ($ultimasOrdenes === []): ?>
                    <div class="dashboard-empty-state">
                        <i class="fa-solid fa-cart-flatbed"></i>
                        <strong>Sin Órdenes de Compra</strong>
                        <span>Las Órdenes Registradas Aparecerán en Esta Sección.</span>
                    </div>
                <?php else: ?>
                    <div class="dashboard-table-wrap">
                        <table class="dashboard-mini-table compras-dashboard-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Folio</th>
                                    <th>Proveedor</th>
                                    <th>Estatus</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($ultimasOrdenes as $orden): ?>
                                <tr>
                                    <td><?= htmlspecialchars($fechaLegible($orden['fecha_compra'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="mono dashboard-order-folio"><?= htmlspecialchars((string) ($orden['folio'] ?? 'Sin Folio'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($orden['proveedor']['nombre'] ?? 'Sin Proveedor'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="dashboard-status dashboard-status--<?= htmlspecialchars($estatusClase((string) ($orden['estatus'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars((string) ($orden['estatus'] ?? 'Pendiente'), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="dashboard-order-total">$<?= number_format((float) ($orden['total_mostrado'] ?? 0), 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../layouts/scripts.php'; ?>
</body>
</html>
