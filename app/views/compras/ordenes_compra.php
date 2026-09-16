<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Compras']);

$role = $_SESSION['role'] ?? 'Empleado';
$nombre = $_SESSION['nombre'] ?? '';
$tabActiva = $tabActiva ?? 'pendientes';
$ordenesCompra = is_array($ordenesCompra ?? null) ? $ordenesCompra : [];
$pagination = $pagination ?? [
    'pagina' => 1,
    'total_paginas' => 1,
    'por_pagina' => 8,
    'total' => 0,
    'desde' => 0,
    'hasta' => 0,
];
$seccion_activa = 'ordenes_compra';
$alertaSesion = $_SESSION['alerta'] ?? null;
unset($_SESSION['alerta']);

$buildQuery = static function (string $tab, int $pagina = 1): string {
    return Session::url('ordenes_compra') . '?' . http_build_query([
        'tab' => $tab,
        'pagina' => $pagina,
    ]);
};

$estatusClase = static function (string $estatus): string {
    $clase = strtolower(trim($estatus));
    return preg_replace('/[^a-z]/', '', $clase) ?: 'pendiente';
};

$fechaLegible = static function (?string $fecha): string {
    if (!$fecha) {
        return 'Sin Fecha';
    }
    $timestamp = strtotime($fecha);
    return $timestamp === false ? $fecha : date('d/m/Y', $timestamp);
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Órdenes de Compra | TAKAB</title>
    <link rel="stylesheet" href="assets/css/prestamos-pendientes.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/inventory-warehouse-responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
    <script src="assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body class="module-inventory-warehouse">
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Abrir o Cerrar Menú" aria-expanded="true">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>

        <?php if (is_array($alertaSesion)): ?>
            <script>
                Swal.fire({
                    icon: <?= json_encode($alertaSesion['tipo'] ?? 'info', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                    title: <?= json_encode($alertaSesion['titulo'] ?? 'Aviso', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                    text: <?= json_encode($alertaSesion['mensaje'] ?? '', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                    confirmButtonColor: '#3085d6'
                });
            </script>
        <?php endif; ?>

        <main class="dashboard-main solicitudes-page-main">
            <div class="dashboard-header-row">
                <div>
                    <h1 class="page-title-icon"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i> ÓRDENES DE COMPRA</h1>
                    <span class="dashboard-desc">Consulta las Órdenes, sus Proveedores y los Materiales Solicitados.</span>
                </div>
                <div class="productos-header-actions">
                    <a class="btn-main" href="orden_nueva"><i class="fa fa-plus"></i> Nueva Órden</a>
                </div>
            </div>

            <section class="prestamos-main solicitudes-list-card">
                <section class="dashboard-cards-row" aria-label="Resumen de Órdenes de Compra">
                    <div class="dashboard-card warning">
                        <div class="card-info">
                            <div class="card-label">Órdenes Pendientes</div>
                            <div class="card-value"><?= number_format((int) ($datos['numOrdenesPendientes'] ?? 0)) ?></div>
                            <div class="card-sub">Por Procesar</div>
                        </div>
                        <div class="card-icon-container"><span class="mdi mdi-clock-alert-outline"></span></div>
                    </div>
                    <div class="dashboard-card waiting">
                        <div class="card-info">
                            <div class="card-label">Órdenes en Entrega</div>
                            <div class="card-value"><?= number_format((int) ($datos['numOrdenesEnEntrega'] ?? 0)) ?></div>
                            <div class="card-sub">Aprobadas o Parciales</div>
                        </div>
                        <div class="card-icon-container"><span class="mdi mdi-truck-delivery-outline"></span></div>
                    </div>
                    <div class="dashboard-card">
                        <div class="card-info">
                            <div class="card-label">Órdenes del Mes</div>
                            <div class="card-value"><?= number_format((int) ($datos['numOrdenesEsteMes'] ?? 0)) ?></div>
                            <div class="card-sub">Fecha de Compra</div>
                        </div>
                        <div class="card-icon-container"><span class="mdi mdi-calendar-month-outline"></span></div>
                    </div>
                </section>

                <nav class="prestamos-tabs" aria-label="Secciones de Órdenes de Compra">
                    <a href="<?= htmlspecialchars($buildQuery('pendientes'), ENT_QUOTES, 'UTF-8') ?>"
                       class="prestamos-tab <?= $tabActiva === 'pendientes' ? 'active' : '' ?>">Pendientes</a>
                    <a href="<?= htmlspecialchars($buildQuery('historial'), ENT_QUOTES, 'UTF-8') ?>"
                       class="prestamos-tab <?= $tabActiva === 'historial' ? 'active' : '' ?>">Historial</a>
                </nav>

                <div class="table-responsive">
                    <table class="takab-table">
                        <thead>
                            <tr>
                                <th>Estatus</th>
                                <th>Folio</th>
                                <th>Proyecto</th>
                                <th>Proveedor</th>
                                <th>Fecha Requerida</th>
                                <th>Total Estimado</th>
                                <th class="col-actions">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($ordenesCompra === []): ?>
                            <tr>
                                <td colspan="8" class="table-empty">
                                    <?= $tabActiva === 'pendientes' ? 'No Hay Órdenes Pendientes.' : 'No Hay Órdenes de Compra Registradas.' ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ordenesCompra as $orden): ?>
                                <tr>
                                    <td>
                                        <span class="solicitud-estatus solicitud-estatus--<?= htmlspecialchars($estatusClase((string) ($orden['estatus'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars((string) ($orden['estatus']), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="mono"><?= htmlspecialchars((string) ($orden['folio'] ?: 'Sin Folio'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?= htmlspecialchars((string) ($orden['proyecto']['nombre'] ?? 'Sin Proyecto'), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) ($orden['proveedor']['nombre'] ?? 'Sin Proveedor'), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td><?= htmlspecialchars($fechaLegible($orden['fecha_compra'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="orden-total-estimado">$<?= number_format((float) ($orden['total_estimado'] ?? 0), 2) ?></td>
                                    <td class="col-actions">
                                        <a class="btn-table"
                                           href="<?= htmlspecialchars(Session::url('ver_orden_compra') . '?id=' . (int) $orden['id'], ENT_QUOTES, 'UTF-8') ?>"
                                           target="_blank"
                                           rel="noopener"
                                           title="Ver e Imprimir Orden"
                                           aria-label="Ver e Imprimir la Orden <?= htmlspecialchars((string) ($orden['folio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <?php if (in_array($orden['estatus'], ['Aprobada', 'Parcial'], true)): ?>
                                            <a class="btn-table"
                                               href="<?= htmlspecialchars(Session::url('procesar_compra') . '?id=' . (int) $orden['id'], ENT_QUOTES, 'UTF-8') ?>"
                                               title="Procesar Compra"
                                               aria-label="Procesar la Orden <?= htmlspecialchars((string) ($orden['folio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                 <i class="fa-solid fa-boxes-stacked"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a class="btn-table"
                                           target="_blank"
                                           rel="noopener"
                                           title="Cancelar Orden"
                                           aria-label="Cancelar la Orden <?= htmlspecialchars((string) ($orden['folio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <nav class="module-pagination" aria-label="Paginación de Órdenes de Compra">
                    <span class="module-pagination-info">
                        <?= $pagination['total'] > 0
                            ? 'Mostrando ' . (int) $pagination['desde'] . '–' . (int) $pagination['hasta'] . ' de ' . number_format((int) $pagination['total'])
                            : 'Sin Órdenes para Mostrar' ?>
                    </span>
                    <div class="module-pagination-controls">
                        <?php if ($pagination['pagina'] > 1): ?>
                            <a href="<?= htmlspecialchars($buildQuery($tabActiva, $pagination['pagina'] - 1), ENT_QUOTES, 'UTF-8') ?>" class="module-pagination-button">
                                <i class="fa-solid fa-chevron-left"></i><span>Anterior</span>
                            </a>
                        <?php endif; ?>
                        <span class="module-pagination-status">Página <?= (int) $pagination['pagina'] ?> de <?= (int) $pagination['total_paginas'] ?></span>
                        <?php if ($pagination['pagina'] < $pagination['total_paginas']): ?>
                            <a href="<?= htmlspecialchars($buildQuery($tabActiva, $pagination['pagina'] + 1), ENT_QUOTES, 'UTF-8') ?>" class="module-pagination-button">
                                <span>Siguiente</span><i class="fa-solid fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </nav>
            </section>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../layouts/scripts.php'; ?>
</body>
</html>
