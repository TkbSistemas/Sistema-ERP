<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Compras']);

$role = $_SESSION['role'] ?? 'Compras';
$nombre = $_SESSION['nombre'] ?? '';
$ordenesFacturables = is_array($ordenesFacturables ?? null) ? $ordenesFacturables : [];
$proyectos = is_array($proyectos ?? null) ? $proyectos : [];
$seccion_activa = 'facturas_compras';
$alertaSesion = $_SESSION['alerta'] ?? null;
unset($_SESSION['alerta']);
$filtroFacturaInicial = in_array(($_GET['factura'] ?? ''), ['asignada', 'pendiente'], true)
    ? (string) $_GET['factura']
    : '';

$facturadas = count(array_filter($ordenesFacturables, static fn(array $orden): bool => !empty($orden['factura_id'])));
$pendientes = count($ordenesFacturables) - $facturadas;
$montoFacturado = array_reduce(
    $ordenesFacturables,
    static fn(float $total, array $orden): float => $total + (float) ($orden['monto_total'] ?? 0),
    0.0
);

$fechaLegible = static function (?string $fecha): string {
    if (!$fecha) {
        return 'Sin Fecha';
    }
    $timestamp = strtotime($fecha);
    return $timestamp === false ? $fecha : date('d/m/Y', $timestamp);
};

$estatusClase = static function (string $estatus): string {
    $clase = strtolower(trim($estatus));
    return preg_replace('/[^a-z]/', '', $clase) ?: 'parcial';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FACTURAS DE COMPRA | TAKAB</title>
    <link rel="stylesheet" href="assets/css/prestamos-pendientes.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/inventory-warehouse-responsive.css">
    <link rel="stylesheet" href="assets/css/facturas-compras.css?v=20260927-1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

        <main class="dashboard-main facturas-page-main">
            <div class="dashboard-header-row">
                <div>
                    <h1 class="page-title-icon"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i> FACTURAS DE COMPRA</h1>
                    <span class="dashboard-desc">Asigna la Factura y el Proyecto a las Órdenes Procesadas o Recibidas.</span>
                </div>
                <a class="btn-main facturas-history-button" href="<?= htmlspecialchars(Session::url('factura_historica'), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Añadir Factura Histórica
                </a>
            </div>

            <section class="dashboard-cards-row facturas-summary" aria-label="Resumen de Facturación">
                <div class="dashboard-card">
                    <div class="card-info">
                        <div class="card-label">Órdenes Disponibles</div>
                        <div class="card-value"><?= number_format(count($ordenesFacturables)) ?></div>
                        <div class="card-sub">Desde Parcial Hasta Recibida</div>
                    </div>
                    <div class="card-icon-container"><i class="fa-solid fa-cart-flatbed"></i></div>
                </div>
                <div class="dashboard-card success">
                    <div class="card-info">
                        <div class="card-label">Con Factura</div>
                        <div class="card-value"><?= number_format($facturadas) ?></div>
                        <div class="card-sub">Facturas Asignadas</div>
                    </div>
                    <div class="card-icon-container"><i class="fa-solid fa-file-circle-check"></i></div>
                </div>
                <div class="dashboard-card warning">
                    <div class="card-info">
                        <div class="card-label">Sin Factura</div>
                        <div class="card-value"><?= number_format($pendientes) ?></div>
                        <div class="card-sub">Pendientes por Asignar</div>
                    </div>
                    <div class="card-icon-container"><i class="fa-solid fa-file-circle-exclamation"></i></div>
                </div>
                <?php if ($role === 'Administrador'): ?>
                    <div class="dashboard-card">
                        <div class="card-info">
                            <div class="card-label">Monto Facturado</div>
                            <div class="card-value card-value-money">$<?= number_format($montoFacturado, 2) ?></div>
                            <div class="card-sub">Suma de Facturas Registradas</div>
                        </div>
                        <div class="card-icon-container"><i class="fa-solid fa-coins"></i></div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="prestamos-main facturas-list-card">
                <div class="facturas-toolbar">
                    <label class="facturas-search">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <span class="sr-only">Buscar Órdenes</span>
                        <input id="buscarFactura" type="search" placeholder="Buscar por Folio, Proveedor, Proyecto o Factura" autocomplete="off">
                    </label>
                    <label class="facturas-status-filter">
                        <span>Estatus</span>
                        <select id="filtroEstatus">
                            <option value="">Todos</option>
                            <option value="Parcial">Parcial</option>
                            <option value="Completa">Completa</option>
                            <option value="Incompleta">Incompleta</option>
                            <option value="Recibida">Recibida</option>
                        </select>
                    </label>
                    <label class="facturas-status-filter">
                        <span>Factura</span>
                        <select id="filtroFactura">
                            <option value="">Todas</option>
                            <option value="asignada" <?= $filtroFacturaInicial === 'asignada' ? 'selected' : '' ?>>Asignada</option>
                            <option value="pendiente" <?= $filtroFacturaInicial === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        </select>
                    </label>
                </div>

                <div class="table-responsive">
                    <table class="takab-table facturas-table">
                        <thead>
                            <tr>
                                <th>Orden de Compra</th>
                                <th>Proveedor</th>
                                <th>Estatus</th>
                                <th>Proyecto</th>
                                <th>Factura</th>
                                <th>Monto</th>
                                <th>Emisión</th>
                                <th class="col-actions">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="facturasBody">
                        <?php if ($ordenesFacturables === []): ?>
                            <tr class="table-empty-row"><td colspan="8" class="table-empty">No Hay Órdenes Disponibles para Facturar.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ordenesFacturables as $orden): ?>
                                <?php
                                $tieneFactura = !empty($orden['factura_id']);
                                $textoBusqueda = implode(' ', [
                                    $orden['folio'] ?? '',
                                    $orden['proveedor_nombre'] ?? '',
                                    $orden['proyecto_nombre'] ?? '',
                                    $orden['proyecto_codigo'] ?? '',
                                    $orden['folio_fiscal'] ?? '',
                                ]);
                                ?>
                                <tr class="factura-row"
                                    data-search="<?= htmlspecialchars(mb_strtolower($textoBusqueda), ENT_QUOTES, 'UTF-8') ?>"
                                    data-status="<?= htmlspecialchars((string) ($orden['estatus'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-invoice="<?= $tieneFactura ? 'asignada' : 'pendiente' ?>">
                                    <td>
                                        <strong class="mono factura-order-folio"><?= htmlspecialchars((string) ($orden['folio'] ?? 'Sin Folio'), ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span class="factura-cell-note"><?= number_format((int) ($orden['numero_partidas'] ?? 0)) ?> Partida<?= (int) ($orden['numero_partidas'] ?? 0) === 1 ? '' : 's' ?></span>
                                    </td>
                                    <td><?= htmlspecialchars((string) ($orden['proveedor_nombre'] ?? 'Sin Proveedor'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="solicitud-estatus solicitud-estatus--<?= htmlspecialchars($estatusClase((string) ($orden['estatus'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars((string) ($orden['estatus'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) ($orden['proyecto_nombre'] ?? 'Sin Proyecto Asignado'), ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (!empty($orden['proyecto_codigo'])): ?>
                                            <span class="factura-cell-note"><?= htmlspecialchars((string) $orden['proyecto_codigo'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($tieneFactura): ?>
                                            <span class="invoice-state invoice-state--assigned"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars((string) $orden['folio_fiscal'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php else: ?>
                                            <span class="invoice-state invoice-state--pending"><i class="fa-regular fa-clock"></i> Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $tieneFactura ? '$' . number_format((float) $orden['monto_total'], 2) : '—' ?></td>
                                    <td><?= $tieneFactura ? htmlspecialchars($fechaLegible($orden['fecha_emision'] ?? null), ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                    <td class="col-actions">
                                        <button type="button"
                                                class="btn-table btn-invoice"
                                                title="<?= $tieneFactura ? 'Editar Factura' : 'Asignar Factura' ?>"
                                                aria-label="<?= $tieneFactura ? 'Editar' : 'Asignar' ?> Factura de <?= htmlspecialchars((string) ($orden['folio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-order-id="<?= (int) $orden['id'] ?>">
                                            <i class="fa-solid <?= $tieneFactura ? 'fa-pen-to-square' : 'fa-file-circle-plus' ?>"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="sinResultados" hidden><td colspan="8" class="table-empty">No Hay Resultados para los Filtros Aplicados.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<form id="facturaPostForm" method="post" action="<?= htmlspecialchars(Session::url('facturas_compras'), ENT_QUOTES, 'UTF-8') ?>" hidden>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="orden_id">
    <input type="hidden" name="proyecto_id">
    <input type="hidden" name="folio_fiscal">
    <input type="hidden" name="monto_total">
    <input type="hidden" name="fecha_emision">
</form>

<?php include __DIR__ . '/../layouts/scripts.php'; ?>
<script>
const proyectosFactura = <?= json_encode(array_map(static fn(array $proyecto): array => [
    'id' => (int) ($proyecto['id'] ?? 0),
    'codigo' => (string) ($proyecto['codigo'] ?? ''),
    'nombre' => (string) ($proyecto['nombre'] ?? ''),
], $proyectos), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

const ordenesFactura = <?= json_encode(array_column(array_map(static function (array $orden): array {
    $tieneFactura = !empty($orden['factura_id']);
    return [
        'id' => (int) $orden['id'],
        'folio' => (string) ($orden['folio'] ?? ''),
        'proveedor' => (string) ($orden['proveedor_nombre'] ?? 'Sin Proveedor'),
        'proyecto_id' => $orden['proyecto_id'] !== null ? (int) $orden['proyecto_id'] : null,
        'folio_fiscal' => (string) ($orden['folio_fiscal'] ?? ''),
        'monto_total' => $tieneFactura
            ? number_format((float) $orden['monto_total'], 2, '.', '')
            : number_format((float) ($orden['total_orden'] ?? 0), 2, '.', ''),
        'fecha_emision' => (string) ($orden['fecha_emision'] ?? date('Y-m-d')),
        'tiene_factura' => $tieneFactura,
    ];
}, $ordenesFacturables), null, 'id'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

const escaparHtml = (valor) => String(valor ?? '').replace(/[&<>'"]/g, (caracter) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
}[caracter]));

function formularioFacturaHtml(orden) {
    const opciones = proyectosFactura.map((proyecto) => {
        const etiqueta = proyecto.codigo ? `${proyecto.codigo} — ${proyecto.nombre}` : proyecto.nombre;
        return `<option value="${proyecto.id}" ${Number(orden.proyecto_id) === proyecto.id ? 'selected' : ''}>${escaparHtml(etiqueta)}</option>`;
    }).join('');

    return `<div class="invoice-modal-summary">
                <span><strong>Orden:</strong> ${escaparHtml(orden.folio)}</span>
                <span><strong>Proveedor:</strong> ${escaparHtml(orden.proveedor)}</span>
            </div>
            <div class="invoice-modal-grid">
                <label class="invoice-modal-field invoice-modal-field--wide">
                    <span>Proyecto <b>*</b></span>
                    <select id="swalProyecto" class="swal2-select invoice-swal-control">
                        <option value="">Selecciona un Proyecto</option>${opciones}
                    </select>
                </label>
                <label class="invoice-modal-field invoice-modal-field--wide">
                    <span>Folio Fiscal <b>*</b></span>
                    <input id="swalFolio" class="swal2-input invoice-swal-control" maxlength="100" value="${escaparHtml(orden.folio_fiscal)}" placeholder="UUID o Folio de la Factura">
                </label>
                <label class="invoice-modal-field">
                    <span>Monto Total <b>*</b></span>
                    <input id="swalMonto" class="swal2-input invoice-swal-control" type="number" min="0.01" max="999999999999.99" step="0.01" value="${escaparHtml(orden.monto_total)}">
                </label>
                <label class="invoice-modal-field">
                    <span>Fecha de Emisión <b>*</b></span>
                    <input id="swalFecha" class="swal2-input invoice-swal-control" type="date" value="${escaparHtml(orden.fecha_emision)}">
                </label>
            </div>`;
}

document.querySelectorAll('.btn-invoice').forEach((boton) => {
    boton.addEventListener('click', async () => {
        const orden = ordenesFactura[boton.dataset.orderId];
        if (!orden) {
            Swal.fire({
                icon: 'error',
                title: 'Orden no Disponible',
                text: 'No Fue Posible Cargar los Datos de la Orden Seleccionada.',
                confirmButtonColor: '#2563eb'
            });
            return;
        }
        const resultado = await Swal.fire({
            icon: orden.tiene_factura ? 'info' : 'question',
            title: orden.tiene_factura ? 'Editar Factura de Compra' : 'Asignar Factura de Compra',
            html: formularioFacturaHtml(orden),
            width: 680,
            showCancelButton: true,
            confirmButtonText: orden.tiene_factura ? 'Guardar Cambios' : 'Asignar Factura',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            focusConfirm: false,
            customClass: { popup: 'invoice-swal-popup' },
            preConfirm: () => {
                const proyectoId = document.getElementById('swalProyecto').value;
                const folio = document.getElementById('swalFolio').value.trim().toUpperCase();
                const monto = document.getElementById('swalMonto').value;
                const fecha = document.getElementById('swalFecha').value;
                if (!proyectoId || !folio || !fecha || !monto || Number(monto) <= 0) {
                    Swal.showValidationMessage('Completa el Proyecto, Folio Fiscal, Monto y Fecha de Emisión.');
                    return false;
                }
                return { proyectoId, folio, monto, fecha };
            }
        });

        if (!resultado.isConfirmed) return;
        const form = document.getElementById('facturaPostForm');
        form.elements.orden_id.value = orden.id;
        form.elements.proyecto_id.value = resultado.value.proyectoId;
        form.elements.folio_fiscal.value = resultado.value.folio;
        form.elements.monto_total.value = resultado.value.monto;
        form.elements.fecha_emision.value = resultado.value.fecha;
        form.submit();
    });
});

const buscador = document.getElementById('buscarFactura');
const filtroEstatus = document.getElementById('filtroEstatus');
const filtroFactura = document.getElementById('filtroFactura');
const filas = [...document.querySelectorAll('.factura-row')];
const sinResultados = document.getElementById('sinResultados');

function aplicarFiltros() {
    const termino = buscador.value.trim().toLocaleLowerCase('es');
    let visibles = 0;
    filas.forEach((fila) => {
        const coincide = (!termino || fila.dataset.search.includes(termino))
            && (!filtroEstatus.value || fila.dataset.status === filtroEstatus.value)
            && (!filtroFactura.value || fila.dataset.invoice === filtroFactura.value);
        fila.hidden = !coincide;
        if (coincide) visibles += 1;
    });
    if (sinResultados) sinResultados.hidden = visibles !== 0;
}

[buscador, filtroEstatus, filtroFactura].forEach((control) => control?.addEventListener('input', aplicarFiltros));
aplicarFiltros();

<?php if (is_array($alertaSesion)): ?>
Swal.fire({
    icon: <?= json_encode($alertaSesion['tipo'] ?? 'info', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    title: <?= json_encode($alertaSesion['titulo'] ?? 'Aviso', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    text: <?= json_encode($alertaSesion['mensaje'] ?? '', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    confirmButtonColor: '#2563eb'
});
<?php endif; ?>
</script>
</body>
</html>
