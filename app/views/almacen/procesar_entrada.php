<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Almacen']);

$role = $_SESSION['role'] ?? 'Almacen';
$nombre = $_SESSION['nombre'] ?? '';
$detalles = is_array($orden['detalles'] ?? null) ? $orden['detalles'] : [];
$error = $error ?? '';
$formatearCantidad = static function ($valor): string {
    return rtrim(rtrim(number_format((float) $valor, 2, '.', ','), '0'), '.');
};
$valorEnviado = static function (int $detalleId, float $predeterminado) {
    return $_POST['cantidades'][$detalleId] ?? $predeterminado;
};
$seccion_activa = 'registrar_entrada';
$processStylePath = __DIR__ . '/../../../public/assets/css/procesar-solicitud.css';
$processStyleVersion = is_file($processStylePath) ? (string) filemtime($processStylePath) : '1';
$entryStylePath = __DIR__ . '/../../../public/assets/css/registrar-entrada.css';
$entryStyleVersion = is_file($entryStylePath) ? (string) filemtime($entryStylePath) : '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Procesar Recepción | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/procesar-solicitud.css?v=<?= rawurlencode($processStyleVersion) ?>">
    <link rel="stylesheet" href="assets/css/registrar-entrada.css?v=<?= rawurlencode($entryStyleVersion) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body class="module-inventory-warehouse">
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Mostrar u Ocultar Menú"><i class="fa-solid fa-bars"></i></button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>

        <main class="dashboard-main process-request-main">
            <div class="process-request-header">
                <div>
                    <h1 class="page-title-icon"><i class="fa-solid fa-truck-ramp-box" aria-hidden="true"></i> PROCESAR RECEPCIÓN</h1>
                    <p class="process-request-description">Confirma las Cantidades que Ingresarán al Almacén Destino.</p>
                </div>
                <a class="process-back-button" href="<?= htmlspecialchars(Session::url('registrar_entrada'), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa-solid fa-arrow-left"></i> Volver a Entradas
                </a>
            </div>

            <section class="request-overview" aria-label="Resumen de la Orden">
                <div class="request-overview-heading">
                    <div>
                        <span class="request-eyebrow">Orden Localizada</span>
                        <h2><?= htmlspecialchars((string) ($orden['folio'] ?? 'Sin Folio'), ENT_QUOTES, 'UTF-8') ?></h2>
                    </div>
                    <span class="request-status"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars((string) ($orden['estatus'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="request-overview-grid reception-overview-grid">
                    <div class="request-data"><span>Referencia</span><strong><?= htmlspecialchars((string) ($orden['referencia'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div class="request-data"><span>Proveedor</span><strong><?= htmlspecialchars((string) ($orden['proveedor_nombre'] ?? 'Sin Proveedor'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div class="request-data"><span>Almacén Destino</span><strong><?= htmlspecialchars((string) ($orden['almacen_nombre'] ?? 'Sin Almacén'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div class="request-data"><span>Forma de Entrega</span><strong><?= htmlspecialchars((string) ($orden['metodo_entrega'] ?? 'Por Confirmar'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                </div>
            </section>

            <form id="reception-form" method="post" action="<?= htmlspecialchars(Session::url('procesar_entrada'), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="orden_id" value="<?= (int) ($orden['id'] ?? 0) ?>">
                <div class="process-layout">
                    <section class="process-card process-materials-card">
                        <div class="process-card-heading">
                            <div>
                                <h2><i class="fa-solid fa-boxes-stacked"></i> Materiales Pendientes de Recepción</h2>
                                <p>Una Cantidad Menor a la Pendiente Generará una Recepción Parcial.</p>
                            </div>
                            <span class="materials-count"><?= count($detalles) ?> <?= count($detalles) === 1 ? 'Partida' : 'Partidas' ?></span>
                        </div>

                        <div class="process-table-wrapper">
                            <table class="process-materials-table reception-materials-table">
                                <thead>
                                    <tr>
                                        <th>Material</th>
                                        <th>Marca / Modelo</th>
                                        <th>Confirmado en Orden</th>
                                        <th>Recibido Previamente</th>
                                        <th>Pendiente</th>
                                        <th>Recibir Ahora</th>
                                        <th>Faltante Resultante</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($detalles as $detalle): ?>
                                    <?php
                                    $detalleId = (int) ($detalle['detalle_id'] ?? 0);
                                    $pendiente = (float) ($detalle['cantidad_pendiente'] ?? 0);
                                    $cantidadActual = $valorEnviado($detalleId, $pendiente);
                                    $unidad = trim((string) ($detalle['unidad'] ?? '')) ?: 'Pza';
                                    $marcaModelo = trim(implode(' / ', array_filter([
                                        trim((string) ($detalle['marca'] ?? '')),
                                        trim((string) ($detalle['modelo'] ?? '')),
                                    ])));
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="material-identity">
                                                <span class="material-icon"><i class="fa-solid fa-cube"></i></span>
                                                <div>
                                                    <strong><?= htmlspecialchars((string) ($detalle['producto_nombre'] ?? 'Material sin Nombre'), ENT_QUOTES, 'UTF-8') ?></strong>
                                                    <span><?= htmlspecialchars((string) ($detalle['nomenclatura'] ?: $detalle['sku'] ?: 'Sin Nomenclatura'), ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($marcaModelo !== '' ? $marcaModelo : 'Sin Registro', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="requested-quantity"><strong><?= htmlspecialchars($formatearCantidad($detalle['cantidad_confirmada'] ?? 0), ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars($unidad, ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="requested-quantity"><strong><?= htmlspecialchars($formatearCantidad($detalle['cantidad_recibida_previa'] ?? 0), ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars($unidad, ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="requested-quantity"><strong><?= htmlspecialchars($formatearCantidad($pendiente), ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars($unidad, ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td>
                                            <div class="quantity-control reception-quantity-control">
                                                <button type="button" class="quantity-button quantity-decrease" aria-label="Disminuir Cantidad"><i class="fa-solid fa-minus"></i></button>
                                                <input class="delivery-quantity reception-quantity" type="number"
                                                       name="cantidades[<?= $detalleId ?>]"
                                                       value="<?= htmlspecialchars((string) $cantidadActual, ENT_QUOTES, 'UTF-8') ?>"
                                                       min="0" max="<?= htmlspecialchars((string) $pendiente, ENT_QUOTES, 'UTF-8') ?>"
                                                       step="0.01" data-pending="<?= htmlspecialchars((string) $pendiente, ENT_QUOTES, 'UTF-8') ?>" required>
                                                <button type="button" class="quantity-button quantity-increase" aria-label="Aumentar Cantidad"><i class="fa-solid fa-plus"></i></button>
                                                <span class="quantity-unit"><?= htmlspecialchars($unidad, ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        </td>
                                        <td class="reception-remaining" data-unit="<?= htmlspecialchars($unidad, ENT_QUOTES, 'UTF-8') ?>">0 <?= htmlspecialchars($unidad, ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="process-card delivery-summary">
                        <h2><i class="fa-solid fa-clipboard-check"></i> Resumen de Recepción</h2>
                        <div class="delivery-summary-content reception-summary-content">
                            <div class="summary-metrics reception-summary-metrics">
                                <div class="summary-metric"><span>Partidas Pendientes</span><strong><?= count($detalles) ?></strong></div>
                                <div class="summary-metric summary-delivery-total"><span>Partidas a Recibir</span><strong id="received-lines">0</strong></div>
                                <div class="summary-metric"><span>Cantidad Total</span><strong id="received-total">0</strong></div>
                                <div class="summary-metric"><span>Partidas con Faltante</span><strong id="remaining-lines">0</strong></div>
                            </div>
                            <div class="delivery-note"><i class="fa-solid fa-circle-info"></i><p>El Stock se Actualizará en <strong><?= htmlspecialchars((string) ($orden['almacen_nombre'] ?? 'el Almacén Destino'), ENT_QUOTES, 'UTF-8') ?></strong>. Las cantidades faltantes podrán recibirse posteriormente con la misma referencia.</p></div>
                            <div class="delivery-summary-actions">
                                <button type="submit" class="mark-delivered-button"><i class="fa-solid fa-circle-check"></i> Confirmar Recepción</button>
                                <a class="cancel-process-button" href="<?= htmlspecialchars(Session::url('registrar_entrada'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-arrow-left"></i> Cancelar y Regresar</a>
                            </div>
                        </div>
                    </section>
                </div>
            </form>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../layouts/scripts.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('reception-form');
    const inputs = [...document.querySelectorAll('.reception-quantity')];
    const receivedLines = document.getElementById('received-lines');
    const receivedTotal = document.getElementById('received-total');
    const remainingLines = document.getElementById('remaining-lines');
    let confirmedSubmit = false;

    const numberText = (value) => Number(value).toLocaleString('es-MX', { maximumFractionDigits: 2 });
    const updateSummary = () => {
        let lines = 0;
        let total = 0;
        let linesWithRemaining = 0;
        inputs.forEach((input) => {
            const pending = Math.max(0, Number(input.dataset.pending) || 0);
            const current = Math.max(0, Math.min(pending, Number(input.value) || 0));
            const remaining = Math.max(0, pending - current);
            const control = input.closest('.quantity-control');
            const remainingCell = input.closest('tr')?.querySelector('.reception-remaining');
            if (current > 0) lines += 1;
            if (remaining > 0.00001) linesWithRemaining += 1;
            total += current;
            control?.classList.toggle('is-zero', current === 0);
            control?.classList.toggle('is-partial', current > 0 && current < pending);
            if (remainingCell) {
                remainingCell.textContent = `${numberText(remaining)} ${remainingCell.dataset.unit || ''}`.trim();
                remainingCell.classList.toggle('has-remaining', remaining > 0.00001);
            }
        });
        receivedLines.textContent = String(lines);
        receivedTotal.textContent = numberText(total);
        remainingLines.textContent = String(linesWithRemaining);
    };

    inputs.forEach((input) => input.addEventListener('input', () => {
        const pending = Math.max(0, Number(input.dataset.pending) || 0);
        if ((Number(input.value) || 0) < 0) input.value = '0';
        if ((Number(input.value) || 0) > pending) input.value = String(pending);
        updateSummary();
    }));
    document.querySelectorAll('.reception-quantity-control').forEach((control) => {
        const input = control.querySelector('.reception-quantity');
        control.querySelector('.quantity-decrease')?.addEventListener('click', () => {
            input.value = String(Math.max(0, (Number(input.value) || 0) - 1));
            updateSummary();
        });
        control.querySelector('.quantity-increase')?.addEventListener('click', () => {
            const pending = Math.max(0, Number(input.dataset.pending) || 0);
            input.value = String(Math.min(pending, (Number(input.value) || 0) + 1));
            updateSummary();
        });
    });

    form.addEventListener('submit', async (event) => {
        if (confirmedSubmit || !form.checkValidity()) return;
        event.preventDefault();
        const total = inputs.reduce((sum, input) => sum + Math.max(0, Number(input.value) || 0), 0);
        if (total <= 0) {
            Swal.fire({ icon: 'warning', title: 'Recepción Vacía', text: 'Captura una Cantidad Mayor a Cero en al Menos una Partida.', confirmButtonColor: '#2563eb' });
            return;
        }
        const result = await Swal.fire({
            icon: 'question',
            title: '¿Confirmar la Recepción?',
            text: 'Se Registrarán los Movimientos y se Actualizará el Stock del Almacén Destino.',
            showCancelButton: true,
            confirmButtonColor: '#16834a',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sí, Confirmar',
            cancelButtonText: 'Revisar'
        });
        if (result.isConfirmed) {
            confirmedSubmit = true;
            HTMLFormElement.prototype.submit.call(form);
        }
    });

    updateSummary();
    <?php if ($error !== ''): ?>
    Swal.fire({ icon: 'error', title: 'No Fue Posible Registrar la Recepción', text: <?= json_encode($error, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, confirmButtonColor: '#2563eb' });
    <?php endif; ?>
});
</script>
</body>
</html>
