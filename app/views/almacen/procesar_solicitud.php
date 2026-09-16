<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Almacen']);

$role = $role ?? ($_SESSION['role'] ?? '');
$nombre = $nombre ?? ($_SESSION['nombre'] ?? '');
$materiales = is_array($solicitud['items'] ?? null) ? $solicitud['items'] : [];
$formatearCantidad = static function ($valor): string {
    return rtrim(rtrim(number_format((float) $valor, 2, '.', ','), '0'), '.');
};
$formatearFecha = static function ($fecha): string {
    if (empty($fecha)) {
        return 'Sin Fecha';
    }
    $timestamp = strtotime((string) $fecha);
    return $timestamp === false ? (string) $fecha : date('d/m/Y', $timestamp);
};
$totalSolicitado = array_reduce($materiales, static function (float $total, array $material): float {
    return $total + (float) ($material['cantidad'] ?? 0);
}, 0.0);
$partidasAEntregar = count(array_filter($materiales, static function (array $material): bool {
    return (float) ($material['cantidad'] ?? 0) > 0;
}));
$processStylePath = __DIR__ . '/../../../public/assets/css/procesar-solicitud.css';
$processStyleVersion = is_file($processStylePath) ? (string) filemtime($processStylePath) : '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PROCESAR SOLICITUD | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/procesar-solicitud.css?v=<?= rawurlencode($processStyleVersion) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="./assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body class="module-inventory-warehouse">
<?php $seccion_activa = 'solicitudes_material'; ?>
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Mostrar u Ocultar Menú">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>

        <main class="dashboard-main process-request-main">
            <div class="process-request-header">
                <div>
                    <h1 class="page-title-icon"><i class="fa-solid fa-box-open" aria-hidden="true"></i> PROCESAR SOLICITUD</h1>
                    <p class="process-request-description">Verifica los Materiales y Ajusta la Cantidad que Será Entregada.</p>
                </div>
                <a class="process-back-button" href="solicitudes_material">
                    <i class="fa-solid fa-arrow-left"></i> Volver a Solicitudes
                </a>
            </div>

            <section class="request-overview" aria-label="Resumen de la Solicitud">
                <div class="request-overview-heading">
                    <div>
                        <span class="request-eyebrow">Solicitud Aprobada</span>
                        <h2><?= htmlspecialchars((string) ($solicitud['folio'] ?? 'Sin Folio'), ENT_QUOTES, 'UTF-8') ?></h2>
                    </div>
                    <span class="request-status"><i class="fa-solid fa-circle-check"></i> Aprobada</span>
                </div>
                <div class="request-overview-grid">
                    <div class="request-data">
                        <span>Solicitante</span>
                        <strong><?= htmlspecialchars((string) ($solicitud['solicitante'] ?? 'Sin Registro'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="request-data">
                        <span>Proyecto / Destino</span>
                        <strong><?= htmlspecialchars((string) ($solicitud['proyecto'] ?? 'Sin Proyecto'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="request-data">
                        <span>Fecha de Solicitud</span>
                        <strong><?= htmlspecialchars($formatearFecha($solicitud['fecha_solicitud'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="request-data">
                        <span>Fecha Requerida</span>
                        <strong><?= htmlspecialchars($formatearFecha($solicitud['fecha_entrega'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
            </section>

            <div class="process-layout">
                <section class="process-card process-materials-card">
                    <div class="process-card-heading">
                        <div>
                            <h2><i class="fa-solid fa-list-check"></i> Materiales de la Solicitud</h2>
                            <p>La Cantidad Puede Ajustarse desde Cero Antes de Confirmar la Entrega.</p>
                        </div>
                        <span class="materials-count"><?= count($materiales) ?> <?= count($materiales) === 1 ? 'Partida' : 'Partidas' ?></span>
                    </div>

                    <?php if ($materiales === []): ?>
                        <div class="process-empty">
                            <i class="fa-solid fa-box-open"></i>
                            <p>Esta Solicitud no Tiene Materiales para Procesar.</p>
                        </div>
                    <?php else: ?>
                        <form id="delivery-form" autocomplete="off">
                            <div class="process-table-wrapper">
                                <table class="process-materials-table">
                                    <thead>
                                        <tr>
                                            <th>Material</th>
                                            <th>Origen</th>
                                            <th>Stock Actual</th>
                                            <th>Solicitado</th>
                                            <th>Cantidad a Entregar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($materiales as $indice => $material): ?>
                                        <?php
                                        $cantidad = max(0, (float) ($material['cantidad'] ?? 0));
                                        $unidad = trim((string) ($material['unidad_medida'] ?? '')) ?: 'Pza';
                                        $fueraCatalogo = !empty($material['fuera_catalogo']);
                                        $stockActual = max(0, (float) ($material['stock_actual'] ?? 0));
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="material-identity">
                                                    <span class="material-icon <?= $fueraCatalogo ? 'external' : '' ?>">
                                                        <i class="fa-solid <?= $fueraCatalogo ? 'fa-cart-plus' : 'fa-cube' ?>"></i>
                                                    </span>
                                                    <div>
                                                        <strong><?= htmlspecialchars((string) ($material['nombre'] ?? 'Material sin Nombre'), ENT_QUOTES, 'UTF-8') ?></strong>
                                                        <span><?= htmlspecialchars((string) ($material['nomenclatura'] ?? 'Sin Nomenclatura'), ENT_QUOTES, 'UTF-8') ?></span>
                                                        <?php if (!empty($material['observaciones'])): ?>
                                                            <small><?= htmlspecialchars((string) $material['observaciones'], ENT_QUOTES, 'UTF-8') ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="material-origin <?= $fueraCatalogo ? 'external' : '' ?>">
                                                    <?= $fueraCatalogo ? 'Fuera del Catálogo' : 'Catálogo' ?>
                                                </span>
                                            </td>
                                            <td class="stock-quantity">
                                                <?php if ($fueraCatalogo): ?>
                                                    <span class="stock-not-applicable">No Aplica</span>
                                                <?php else: ?>
                                                    <strong><?= htmlspecialchars($formatearCantidad($stockActual), ENT_QUOTES, 'UTF-8') ?></strong>
                                                    <span><?= htmlspecialchars($unidad, ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="requested-quantity">
                                                <strong><?= htmlspecialchars($formatearCantidad($cantidad), ENT_QUOTES, 'UTF-8') ?></strong>
                                                <span><?= htmlspecialchars($unidad, ENT_QUOTES, 'UTF-8') ?></span>
                                            </td>
                                            <td>
                                                <div class="quantity-control">
                                                    <button type="button" class="quantity-button quantity-decrease" aria-label="Disminuir Cantidad">
                                                        <i class="fa-solid fa-minus"></i>
                                                    </button>
                                                    <input
                                                        class="delivery-quantity"
                                                        type="number"
                                                        name="materiales[<?= $indice ?>][cantidad]"
                                                        value="<?= htmlspecialchars((string) $cantidad, ENT_QUOTES, 'UTF-8') ?>"
                                                        min="0"
                                                        step="0.01"
                                                        data-requested="<?= htmlspecialchars((string) $cantidad, ENT_QUOTES, 'UTF-8') ?>"
                                                        aria-label="Cantidad a Entregar de <?= htmlspecialchars((string) ($material['nombre'] ?? 'Material'), ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                    <button type="button" class="quantity-button quantity-increase" aria-label="Aumentar Cantidad">
                                                        <i class="fa-solid fa-plus"></i>
                                                    </button>
                                                    <span class="quantity-unit"><?= htmlspecialchars($unidad, ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    <?php endif; ?>
                </section>

                <section class="process-card delivery-summary">
                    <h2><i class="fa-solid fa-clipboard-check"></i> Resumen de Entrega</h2>
                    <div class="delivery-summary-content">
                        <div class="summary-metrics">
                            <div class="summary-metric">
                                <span>Partidas Solicitadas</span>
                                <strong><?= count($materiales) ?></strong>
                            </div>
                            <div class="summary-metric summary-delivery-total">
                                <span>Partidas a Entregar</span>
                                <strong id="delivered-lines"><?= $partidasAEntregar ?></strong>
                            </div>
                        </div>
                        <div class="delivery-note">
                            <i class="fa-solid fa-circle-info"></i>
                            <p>Las Partidas con Cantidad Cero se Considerarán como no Entregadas.</p>
                        </div>
                        <div class="delivery-summary-actions">
                            <button type="button" class="mark-delivered-button" id="mark-delivered" <?= $materiales === [] ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-circle-check"></i>
                                Marcar como Entregada
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggleButton = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.main_sidebar');
    const content = document.querySelector('.content-area');
    const quantityInputs = [...document.querySelectorAll('.delivery-quantity')];
    const deliveredLines = document.getElementById('delivered-lines');

    const updateSummary = () => {
        const totalLines = quantityInputs.filter((input) => (Number(input.value) || 0) > 0).length;
        if (deliveredLines) deliveredLines.textContent = String(totalLines);

        quantityInputs.forEach((input) => {
            const requested = Math.max(0, Number(input.dataset.requested) || 0);
            const current = Math.max(0, Number(input.value) || 0);
            const control = input.closest('.quantity-control');
            control?.classList.toggle('is-zero', current === 0);
            control?.classList.toggle('is-partial', current > 0 && current < requested);
        });
    };

    quantityInputs.forEach((input) => {
        input.addEventListener('input', () => {
            if (Number(input.value) < 0) input.value = '0';
            updateSummary();
        });
    });

    document.querySelectorAll('.quantity-control').forEach((control) => {
        const input = control.querySelector('.delivery-quantity');
        control.querySelector('.quantity-decrease')?.addEventListener('click', () => {
            input.value = String(Math.max(0, (Number(input.value) || 0) - 1));
            updateSummary();
        });
        control.querySelector('.quantity-increase')?.addEventListener('click', () => {
            input.value = String((Number(input.value) || 0) + 1);
            updateSummary();
        });
    });

    updateSummary();

    document.getElementById('mark-delivered')?.addEventListener('click', () => {
        Swal.fire({
            icon: 'info',
            title: 'Interfaz Preparada',
            text: 'El Registro de la Entrega se Conectará en la Siguiente Etapa de Implementación.',
            confirmButtonColor: '#2563eb',
            confirmButtonText: 'Entendido'
        });
    });

    toggleButton?.addEventListener('click', () => {
        sidebar?.classList.toggle('collapsed');
        content?.classList.toggle('collapsed');
        const icon = toggleButton.querySelector('i');
        if (icon) icon.className = sidebar?.classList.contains('collapsed') ? 'fa-solid fa-bars' : 'fa-solid fa-xmark';
    });
});
</script>
</body>
</html>
