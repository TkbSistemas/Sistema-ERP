<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Compras']);

$role = $role ?? ($_SESSION['role'] ?? '');
$nombre = $nombre ?? ($_SESSION['nombre'] ?? '');
$detalles = is_array($orden['detalles'] ?? null) ? $orden['detalles'] : [];
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
$valorEnviado = static function (int $detalleId, string $campo, $predeterminado) {
    return $_POST['detalles'][$detalleId][$campo] ?? $predeterminado;
};
$partidasConfirmadas = count(array_filter($detalles, static function (array $detalle): bool {
    $cantidad = $detalle['cantidad_confirmada'] ?? $detalle['cantidad_solicitada'] ?? 0;
    return (float) $cantidad > 0;
}));
$processStylePath = __DIR__ . '/../../../public/assets/css/procesar-solicitud.css';
$processStyleVersion = is_file($processStylePath) ? (string) filemtime($processStylePath) : '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Procesar Compra | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/procesar-solicitud.css?v=<?= rawurlencode($processStyleVersion) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body class="module-inventory-warehouse">
<?php $seccion_activa = 'ordenes_compra'; ?>
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Mostrar u Ocultar Menú"><i class="fa-solid fa-bars"></i></button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>
        <main class="dashboard-main process-request-main">
            <div class="process-request-header">
                <div>
                    <h1 class="page-title-icon"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i> PROCESAR COMPRA</h1>
                    <p class="process-request-description">Confirma la Cantidad y el Precio Unitario Recibidos para Cada Material.</p>
                </div>
                <a class="process-back-button" href="<?= htmlspecialchars(Session::url('ordenes_compra'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-arrow-left"></i> Volver a Órdenes</a>
            </div>

            <section class="request-overview" aria-label="Resumen de la Orden">
                <div class="request-overview-heading">
                    <div><span class="request-eyebrow">Orden Seleccionada</span><h2><?= htmlspecialchars((string) ($orden['folio'] ?? 'Sin Folio'), ENT_QUOTES, 'UTF-8') ?></h2></div>
                    <span class="request-status"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars((string) ($orden['estatus'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="request-overview-grid">
                    <div class="request-data"><span>Proveedor</span><strong><?= htmlspecialchars((string) ($orden['proveedor']['nombre'] ?? 'Sin Proveedor'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div class="request-data"><span>Proyecto</span><strong><?= htmlspecialchars((string) ($orden['proyecto']['nombre'] ?? 'Sin Proyecto'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div class="request-data"><span>Fecha de Compra</span><strong><?= htmlspecialchars($formatearFecha($orden['fecha_compra'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div class="request-data"><span>Método de Entrega</span><strong><?= htmlspecialchars((string) ($orden['metodo_entrega'] ?? 'Por Confirmar'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                </div>
            </section>

            <form id="purchase-form" method="post" action="<?= htmlspecialchars(Session::url('procesar_compra'), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="orden_id" value="<?= (int) ($orden['id'] ?? 0) ?>">
                <div class="process-layout">
                    <section class="process-card process-materials-card">
                        <div class="process-card-heading">
                            <div><h2><i class="fa-solid fa-list-check"></i> Materiales de la Orden</h2><p>Una Cantidad Menor a la Solicitada Mantendrá la Orden con Estatus Parcial.</p></div>
                            <span class="materials-count"><?= count($detalles) ?> <?= count($detalles) === 1 ? 'Partida' : 'Partidas' ?></span>
                        </div>
                        <?php if ($detalles === []): ?>
                            <div class="process-empty"><i class="fa-solid fa-box-open"></i><p>Esta Orden no Tiene Materiales para Procesar.</p></div>
                        <?php else: ?>
                            <div class="process-table-wrapper">
                                <table class="process-materials-table purchase-materials-table">
                                    <thead><tr><th>Material</th><th>Marca / Modelo</th><th>Solicitado</th><th>Precio Estimado</th><th>Cantidad Confirmada</th><th>Precio Confirmado</th><th>Importe Confirmado</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($detalles as $detalle): ?>
                                        <?php
                                        $detalleId = (int) ($detalle['id'] ?? 0);
                                        $cantidadSolicitada = max(0, (float) ($detalle['cantidad_solicitada'] ?? 0));
                                        $cantidadPredeterminada = $detalle['cantidad_confirmada'] !== null ? (float) $detalle['cantidad_confirmada'] : $cantidadSolicitada;
                                        $precioPredeterminado = $detalle['precio_confirmado'] !== null ? (float) $detalle['precio_confirmado'] : (float) ($detalle['precio_unitario'] ?? 0);
                                        $cantidadConfirmada = $valorEnviado($detalleId, 'cantidad_confirmada', $cantidadPredeterminada);
                                        $precioConfirmado = $valorEnviado($detalleId, 'precio_confirmado', $precioPredeterminado);
                                        $unidad = trim((string) ($detalle['unidad'] ?? '')) ?: 'Pza';
                                        $marcaModelo = trim(implode(' / ', array_filter([trim((string) ($detalle['marca'] ?? '')), trim((string) ($detalle['modelo'] ?? ''))])));
                                        ?>
                                        <tr>
                                            <td><div class="material-identity"><span class="material-icon"><i class="fa-solid fa-cube"></i></span><div><strong><?= htmlspecialchars((string) ($detalle['producto_nombre'] ?? 'Material sin Nombre'), ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars((string) ($detalle['producto_nomenclatura'] ?: $detalle['producto_sku'] ?: 'Sin Nomenclatura'), ENT_QUOTES, 'UTF-8') ?></span></div></div></td>
                                            <td><?= htmlspecialchars($marcaModelo !== '' ? $marcaModelo : 'Sin Registro', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="requested-quantity"><strong><?= htmlspecialchars($formatearCantidad($cantidadSolicitada), ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars($unidad, ENT_QUOTES, 'UTF-8') ?></span></td>
                                            <td class="purchase-money">$<?= number_format((float) ($detalle['precio_unitario'] ?? 0), 2) ?></td>
                                            <td>
                                                <div class="quantity-control">
                                                    <button type="button" class="quantity-button quantity-decrease" aria-label="Disminuir Cantidad"><i class="fa-solid fa-minus"></i></button>
                                                    <input class="delivery-quantity" type="number" name="detalles[<?= $detalleId ?>][cantidad_confirmada]" value="<?= htmlspecialchars((string) $cantidadConfirmada, ENT_QUOTES, 'UTF-8') ?>" min="0" max="<?= htmlspecialchars((string) $cantidadSolicitada, ENT_QUOTES, 'UTF-8') ?>" step="0.01" data-requested="<?= htmlspecialchars((string) $cantidadSolicitada, ENT_QUOTES, 'UTF-8') ?>" required>
                                                    <button type="button" class="quantity-button quantity-increase" aria-label="Aumentar Cantidad"><i class="fa-solid fa-plus"></i></button>
                                                    <span class="quantity-unit"><?= htmlspecialchars($unidad, ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                            </td>
                                            <td><div class="price-control"><span>$</span><input class="unit-price" type="number" name="detalles[<?= $detalleId ?>][precio_confirmado]" value="<?= htmlspecialchars((string) $precioConfirmado, ENT_QUOTES, 'UTF-8') ?>" min="0.01" step="0.01" required></div></td>
                                            <td class="purchase-line-total">$0.00</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="process-card delivery-summary">
                        <h2><i class="fa-solid fa-clipboard-check"></i> Resumen de Compra</h2>
                        <div class="delivery-summary-content">
                            <div class="summary-metrics purchase-summary-metrics">
                                <div class="summary-metric"><span>Partidas Solicitadas</span><strong><?= count($detalles) ?></strong></div>
                                <div class="summary-metric summary-delivery-total"><span>Partidas Confirmadas</span><strong id="confirmed-lines"><?= $partidasConfirmadas ?></strong></div>
                                <div class="summary-metric"><span>Total Confirmado</span><strong id="confirmed-total">$0.00</strong></div>
                            </div>
                            <div class="delivery-note"><i class="fa-solid fa-circle-info"></i><p>La Orden será Completa sólo si Todas las Cantidades Confirmadas Cubren lo Solicitado; de lo Contrario quedará Parcial.</p></div>
                            <div class="delivery-summary-actions">
                                <button type="submit" class="mark-delivered-button" <?= $detalles === [] ? 'disabled' : '' ?>><i class="fa-solid fa-circle-check"></i> Confirmar Compra</button>
                                <a class="cancel-process-button" href="<?= htmlspecialchars(Session::url('ordenes_compra'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-arrow-left"></i> Regresar a la Lista de Órdenes</a>
                            </div>
                        </div>
                    </section>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('purchase-form');
    const toggleButton = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.main_sidebar');
    const content = document.querySelector('.content-area');
    const quantityInputs = [...document.querySelectorAll('.delivery-quantity')];
    const priceInputs = [...document.querySelectorAll('.unit-price')];
    const confirmedLines = document.getElementById('confirmed-lines');
    const confirmedTotal = document.getElementById('confirmed-total');
    let confirmedSubmit = false;

    const updateSummary = () => {
        let lines = 0;
        let total = 0;
        quantityInputs.forEach((input) => {
            const requested = Math.max(0, Number(input.dataset.requested) || 0);
            const current = Math.max(0, Math.min(requested, Number(input.value) || 0));
            const row = input.closest('tr');
            const price = Math.max(0, Number(row?.querySelector('.unit-price')?.value) || 0);
            const control = input.closest('.quantity-control');
            if (current > 0) lines += 1;
            total += current * price;
            control?.classList.toggle('is-zero', current === 0);
            control?.classList.toggle('is-partial', current > 0 && current < requested);
            const lineTotal = row?.querySelector('.purchase-line-total');
            if (lineTotal) lineTotal.textContent = current.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
        });
        if (confirmedLines) confirmedLines.textContent = String(lines);
        if (confirmedTotal) confirmedTotal.textContent = total.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
    };

    quantityInputs.forEach((input) => input.addEventListener('input', () => {
        const requested = Math.max(0, Number(input.dataset.requested) || 0);
        if ((Number(input.value) || 0) < 0) input.value = '0';
        if ((Number(input.value) || 0) > requested) input.value = String(requested);
        updateSummary();
    }));
    priceInputs.forEach((input) => input.addEventListener('input', updateSummary));
    document.querySelectorAll('.quantity-control').forEach((control) => {
        const input = control.querySelector('.delivery-quantity');
        control.querySelector('.quantity-decrease')?.addEventListener('click', () => { input.value = String(Math.max(0, (Number(input.value) || 0) - 1)); updateSummary(); });
        control.querySelector('.quantity-increase')?.addEventListener('click', () => { const requested = Math.max(0, Number(input.dataset.requested) || 0); input.value = String(Math.min(requested, (Number(input.value) || 0) + 1)); updateSummary(); });
    });
    form?.addEventListener('submit', async (event) => {
        if (confirmedSubmit || !form.checkValidity()) return;
        event.preventDefault();
        const result = await Swal.fire({ icon: 'question', title: '¿Confirmar la Compra?', text: 'Se Guardarán las Cantidades y los Precios Indicados para Cada Material.', showCancelButton: true, confirmButtonColor: '#16834a', cancelButtonColor: '#64748b', confirmButtonText: 'Sí, Confirmar', cancelButtonText: 'Revisar' });
        if (result.isConfirmed) { confirmedSubmit = true; form.submit(); }
    });
    updateSummary();
    toggleButton?.addEventListener('click', () => {
        sidebar?.classList.toggle('collapsed');
        content?.classList.toggle('collapsed');
        const icon = toggleButton.querySelector('i');
        if (icon) icon.className = sidebar?.classList.contains('collapsed') ? 'fa-solid fa-bars' : 'fa-solid fa-xmark';
    });
    <?php if ($error !== ''): ?>
    Swal.fire({ icon: 'error', title: 'No Fue Posible Procesar la Compra', text: <?= json_encode($error, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, confirmButtonColor: '#2563eb' });
    <?php endif; ?>
});
</script>
</body>
</html>
