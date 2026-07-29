<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$tab_activa = $_GET['tab'] ?? 'pendientes';
$limite = 5;

$totalPaginasPendientes = ceil($totalPendientes / $limite); //ceil redondea hacia arriba
$desdePendientes = $totalPendientes > 0 ? (($page - 1) * $limite) + 1 : 0;
$hastaPendientes = min($page * $limite, $totalPendientes);

$totalPaginasHistorial = ceil($totalHistorial / $limite);
$desdeHistorial = $totalHistorial > 0 ? (($page - 1) * $limite) + 1 : 0;
$hastaHistorial = min($page * $limite, $totalHistorial);


$role = $_SESSION['role'] ?? 'Administrador';
$nombre = $_SESSION['nombre'] ?? '';
$selectedProducto = $_POST['producto_id'] ?? '';
$selectedAlmacen = $_POST['almacen_id'] ?? '';
$cantidadIngresada = $_POST['cantidad'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';
$salidaItems = $salidaItems ?? [];
$error = $error ?? '';
$categoriasFiltro = [];
foreach ($productos as $producto) {
    if (!empty($producto['categoria'])) {
        $categoriasFiltro[$producto['categoria']] = true;
    }
}
ksort($categoriasFiltro);
$breadcrumbs = [
    ['label' => 'Registrar salida'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SALIDA RÁPIDA | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/productos.css">
    <link rel="stylesheet" href="assets/css/inventario_form.css">
    <link rel="stylesheet" href="assets/css/prestamos-pendientes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="./assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body>
<?php $seccion_activa = 'registrar_salida'; ?>

                
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Toggle Menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
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

        <?php if (isset($_SESSION['alerta'])): ?>
            <script>
                Swal.fire({
                    icon: '<?php echo $_SESSION['alerta']['tipo']; ?>',
                    title: '<?php echo $_SESSION['alerta']['titulo']; ?>',
                    text: '<?php echo $_SESSION['alerta']['mensaje']; ?>',
                    confirmButtonColor: '#3085d6'
                });
            </script>
        <?php unset($_SESSION['alerta']); ?>
        <?php endif; ?>

        <main class="dashboard-main inventario-form-main">
            <div class="inventario-form-header">
                <div>
                    <h1><i class="fa fa-arrow-up"></i>REGISTRAR BAJAS DE INVENTARIO</h1>
                    <p class="form-desc">Salida de Productos por Desecho.</p>
                </div>
            </div>

            <?php if (!empty($msg)): ?>
                <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <section class="inventario-form-card inventario-solicitudes">
                <div class="recents-header">
                    <h2><i class="fa fa-file-lines"></i> Solicitudes de Salida de Material</h2>
                    <span class="recents-sub">Revisa las Solicitudes Pendientes y el Historial de Salidas.</span>
                </div>

            <?php $tab_activa = $_GET['tab'] ?? 'pendientes'; ?>

            <div class="tab-container" style="margin-top: 20px;">
                <a href="?tab=pendientes" class="prestamos-tab <?= $tab_activa === 'pendientes' ? 'active' : '' ?>">Pendientes</a>
                <a href="?tab=historial" class="prestamos-tab <?= $tab_activa === 'historial' ? 'active' : '' ?>">Historial</a>
            </div>
            <div id="wrapper-pendientes" <?= $tab_activa !== 'pendientes' ? 'hidden' : '' ?>>
            <table class="takab-table">
                <thead>
                    <tr>
                        <th>Estatus</th>
                        <th>Folio</th>
                        <th>Empleado</th>
                        <th>Fecha Solicitud</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($solicitudesPendientes)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:20px;">
                                <p>No Hay Solicitudes Pendientes.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($solicitudesPendientes as $solicitud): ?>
                            <tr>
                                <td><?= htmlspecialchars($solicitud['estatus'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($solicitud['folio'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($solicitud['solicitante'] ?? '-') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($solicitud['created_at'] ?? '')) ?></td>
                                <td>
                                    <a class="btn-table" title="Ver" href="ver_solicitud_baja?id=<?= $solicitud['id'] ?>"><i class="fa fa-eye"></i></a>
                                    <a class="btn-table btn-confirmar" 
                                    title="Aprobar" 
                                    href="javascript:void(0)" 
                                    data-id="<?= $solicitud['id'] ?>" 
                                    data-accion="aprobar" 
                                    data-folio="<?= htmlspecialchars($solicitud['folio'] ?? '') ?>">
                                        <i class="fa fa-circle-check"></i>
                                    </a>
                                    <a class="btn-table btn-confirmar" 
                                    title="Rechazar" 
                                    href="javascript:void(0)" 
                                    data-id="<?= $solicitud['id'] ?>" 
                                    data-accion="rechazar" 
                                    data-folio="<?= htmlspecialchars($solicitud['folio'] ?? '') ?>">
                                        <i class="fa fa-circle-xmark"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="inventario-pagination">
                <div class="inventario-pagination-info">
                    <?= $totalPendientes > 0
                        ? "Mostrando $desdePendientes - $hastaPendientes de " . number_format($totalPendientes) . " registros"
                        : "Sin registros disponibles" ?>
                </div>
                <div class="inventario-pagination-controls">
                    <?php if ($page > 1): ?>
                        <a class="btn-ghost" href="<?= $buildQuery(['page' => $page - 1]) ?>"><i class="fa fa-chevron-left"></i> Anterior</a>
                    <?php endif; ?>
                    <span class="inventario-pagination-page">Página <?= number_format($page) ?> de <?= number_format($totalPaginasPendientes ?: 1) ?></span>
                    <?php if ($page < $totalPaginasPendientes): ?>
                        <a class="btn-ghost" href="<?= $buildQuery(['page' => $page + 1]) ?>">Siguiente <i class="fa fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            </div>

            <div id="wrapper-historial" <?= $tab_activa !== 'historial' ? 'hidden' : '' ?>>
            <table class="takab-table">
                <thead>
                    <tr>
                        <th>Estatus</th>
                        <th>Folio</th>
                        <th>Empleado</th>
                        <th>Fecha Solicitud</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($solicitudesHistorial)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:20px;">
                                <p>No Hay Solicitudes en el Historial.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($solicitudesHistorial as $solicitud): ?>
                            <tr>
                                <td><?= htmlspecialchars($solicitud['estatus'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($solicitud['folio'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($solicitud['solicitante'] ?? '-') ?></td>  
                                <td><?= date('d/m/Y H:i', strtotime($solicitud['created_at'] ?? '')) ?></td>
                                <td><a href="ver_solicitud_baja?id=<?= $solicitud['id'] ?>" class="btn-table" title="Ver"><i class="fa fa-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="inventario-pagination">
                <div class="inventario-pagination-info">
                    <?= $totalHistorial > 0
                        ? "Mostrando $desdeHistorial - $hastaHistorial de " . number_format($totalHistorial) . " registros"
                        : "Sin registros disponibles" ?>
                </div>
                <div class="inventario-pagination-controls">
                    <?php if ($page > 1): ?>
                        <a class="btn-ghost" href="<?= $buildQuery(['page' => $page - 1]) ?>"><i class="fa fa-chevron-left"></i> Anterior</a>
                    <?php endif; ?>
                    <span class="inventario-pagination-page">Página <?= number_format($page) ?> de <?= number_format($totalPaginasHistorial ?: 1) ?></span>
                    <?php if ($page < $totalPaginasHistorial): ?>
                        <a class="btn-ghost" href="<?= $buildQuery(['page' => $page + 1]) ?>">Siguiente <i class="fa fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </section>

            <div class="inventario-form-grid" style="margin-top: 30px;">
                <section class="inventario-form-card">
                    <h2><i class="fa fa-clipboard"></i> Detalles de la Salida</h2>
                    <form  action="registrar_baja_inventario" method="post" enctype="application/x-www-form-urlencoded" autocomplete="off" class="inventario-entry-form" id="inventario-entry-form">
                        <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">

                        <div class="form-field" style="margin-bottom:14px;">
                            <label>Buscador Avanzado</label>
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:10px;">
                                <input type="text" id="filtro_texto" placeholder="Buscar por nombre, código o barras">
                                <select id="filtro_tipo">
                                    <option value="">Tipo</option>
                                    <option value="Consumible">Consumible</option>
                                    <option value="Herramienta">Herramienta</option>
                                    <option value="Equipo">Equipo</option>
                                </select>
                                <select id="filtro_categoria">
                                    <option value="">Categoría</option>
                                    <?php foreach (array_keys($categoriasFiltro) as $categoriaNombre): ?>
                                        <option value="<?= htmlspecialchars($categoriaNombre) ?>"><?= htmlspecialchars($categoriaNombre) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select id="filtro_almacen">
                                    <option value="">Almacén</option>
                                    <?php foreach ($almacenes as $almacen): ?>
                                        <option value="<?= (int) $almacen['id'] ?>"><?= htmlspecialchars($almacen['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label style="display:flex; align-items:center; gap:8px; font-size:0.95rem;">
                                    <input type="checkbox" id="filtro_stock" style="width:auto; margin:0;"> Solo con Stock
                                </label>
                            </div>
                            <div style="margin-top:8px; font-size:0.9rem; color:#5c6a96;">
                                Productos visibles: <span id="filtro_resultados">0</span>
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="producto_id">Producto *</label>
                            <select id="producto_id" name="producto_id">
                                <option value="">Selecciona un Producto...</option>
                                <?php foreach ($productos as $producto): ?>
                                    <option value="<?= $producto['id'] ?>"
                                        data-stock="<?= (float) ($producto['stock_actual'] ?? 0) ?>"
                                        data-min="<?= (float) ($producto['stock_minimo'] ?? 0) ?>"
                                        data-unidad="<?= htmlspecialchars($producto['unidad_medida_nombre'] ?? '') ?>"
                                        data-almacen="<?= (int) ($producto['almacen_id'] ?? 0) ?>"
                                        data-tipo="<?= htmlspecialchars($producto['tipo'] ?? '') ?>"
                                        data-categoria="<?= htmlspecialchars($producto['categoria'] ?? '') ?>"
                                        data-codigo="<?= htmlspecialchars($producto['codigo_fabricante'] ?? '') ?>"
                                        data-barras="<?= htmlspecialchars($producto['codigos_barras'] ?? '') ?>"
                                        <?= $selectedProducto == $producto['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($producto['nombre']) ?> (<?= htmlspecialchars($producto['codigo_fabricante']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="almacen_id">Almacén *</label>
                            <select id="almacen_id" name="almacen_id">
                                <option value="">Selecciona un Almacén...</option>
                                <?php foreach ($almacenes as $almacen): ?>
                                    <option value="<?= $almacen['id'] ?>" <?= $selectedAlmacen == $almacen['id'] ? 'selected' : '' ?>><?= htmlspecialchars($almacen['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="cantidad">Cantidad *</label>
                            <input type="text" id="cantidad" name="cantidad" min="0" step="0.01" placeholder="Ej. 25" value="<?= htmlspecialchars($cantidadIngresada) ?>">
                        </div>

                        <div class="form-field">
                            <label for="observaciones">Observaciones</label>
                            <textarea id="observaciones" name="observaciones" placeholder="Comentarios Adicionales..." rows="3"><?= htmlspecialchars($observaciones) ?></textarea>
                        </div>

                        <div class="entry-batch-actions">
                            <button type="button" class="btn-secondary" id="agregar-producto"><i class="fa fa-plus"></i> Agregar Producto</button>
                            <button type="button" class="btn-ghost" id="limpiar-captura"><i class="fa fa-eraser"></i> Limpiar Captura</button>
                            <button type="submit" class="btn-main" id="registrar-salida"><i class="fa fa-file-export"></i> Generar Salida</button>
                        </div>

                        <section class="entry-items-panel">
                            <div class="entry-items-header">
                                <h3><i class="fa fa-list-check"></i> Productos Seleccionados</h3>
                                <span id="salida-items-count">0 Productos en la Captura</span>
                            </div>
                            <div class="inventario-empty entry-items-empty" id="salida-items-empty">
                                <i class="fa fa-box-open"></i>
                                <p>Agrega Productos a la Captura para Dar Salida a la Vez.</p>
                            </div>
                            <div class="entry-items-table-wrapper" id="salida-items-wrapper" hidden>
                                <table class="entry-items-table">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Almacén</th>
                                            <th>Cantidad</th>
                                            <th>Observaciones</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="salida-items-body"></tbody>
                                </table>
                            </div>
                        </section>

                        <div id="salida-items-inputs"></div>
                    </form>
                </section>

                <section class="inventario-form-card form-summary">
                    <h2><i class="fa fa-circle-info"></i> Resumen del Producto</h2>
                    <div class="summary-placeholder" id="summary-placeholder">
                        <i class="fa fa-box"></i>
                        <p>Selecciona un Producto para Consultar su Stock Actual y Ubicación.</p>
                    </div>
                    <div class="summary-content" id="summary-content" hidden>
                        <div class="summary-item">
                            <span class="label">Producto</span>
                            <span class="value" id="summary-nombre">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Stock Actual</span>
                            <span class="value" id="summary-stock">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Stock mínimo</span>
                            <span class="value" id="summary-min">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Categoría</span>
                            <span class="value" id="summary-categoria">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Almacén</span>
                            <span class="value" id="summary-almacen">-</span>
                        </div>
                    </div>
                </section>
            </div>

            <section class="inventario-form-card inventario-recents">
                <div class="recents-header">
                    <h2><i class="fa fa-clock"></i> Últimos Movimientos Registrados</h2>
                    <span class="recents-sub">Ayuda a Verificar Duplicados o Confirmar Capturas Recientes</span>
                </div>
                <?php if (empty($movimientosRecientes)): ?>
                    <div class="inventario-empty">
                        <i class="fa fa-inbox"></i>
                        <p>Aún no se Registran Movimientos de Inventario.</p>
                    </div>
                <?php else: ?>
                    <div class="recents-table-wrapper">
                        <table class="recents-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Almacén</th>
                                    <th>Registro</th>
                                    <th>Notas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimientosRecientes as $mov): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($mov['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($mov['producto'] ?? '-') ?> <span class="mono">(<?= htmlspecialchars($mov['codigo_producto'] ?? '-') ?>)</span></td>
                                        <td><?= rtrim(rtrim(number_format((float) ($mov['cantidad'] ?? 0), 2), '0'), '.') ?></td>
                                        <td><?= htmlspecialchars($mov['almacen_destino'] ?? $mov['almacen_origen'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($mov['usuario'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($mov['observaciones'] ?? '-') ?></td>
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


<script>
const salidaForm = document.getElementById('inventario-entry-form');
const productosSelect = document.getElementById('producto_id');
const almacenSelect = document.getElementById('almacen_id');
const cantidadInput = document.getElementById('cantidad');
const observacionesInput = document.getElementById('observaciones');
const agregarProductoBtn = document.getElementById('agregar-producto');
const limpiarCapturaBtn = document.getElementById('limpiar-captura');
const salidaItemsInputs = document.getElementById('salida-items-inputs');
const salidaItemsBody = document.getElementById('salida-items-body');
const salidaItemsWrapper = document.getElementById('salida-items-wrapper');
const salidaItemsEmpty = document.getElementById('salida-items-empty');
const salidaItemsCount = document.getElementById('salida-items-count');
const summaryPlaceholder = document.getElementById('summary-placeholder');
const summaryContent = document.getElementById('summary-content');
const summaryNombre = document.getElementById('summary-nombre');
const summaryStock = document.getElementById('summary-stock');
const summaryMin = document.getElementById('summary-min');
const summaryCategoria = document.getElementById('summary-categoria');
const summaryAlmacen = document.getElementById('summary-almacen');
const filtroTexto = document.getElementById('filtro_texto');
const filtroTipo = document.getElementById('filtro_tipo');
const filtroCategoria = document.getElementById('filtro_categoria');
const filtroAlmacen = document.getElementById('filtro_almacen');
const filtroStock = document.getElementById('filtro_stock');
const filtroResultados = document.getElementById('filtro_resultados');
const lineasIniciales = <?= json_encode(array_values($salidaItems), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const almacenesMap = new Map([
    <?php foreach ($almacenes as $almacen): ?>
    [<?= (int) $almacen['id'] ?>, "<?= addslashes($almacen['nombre']) ?>"],
    <?php endforeach; ?>
]);
const salidaItems = Array.isArray(lineasIniciales) ? lineasIniciales.map((item) => ({
    producto_id: String(item.producto_id || ''),
    almacen_id: String(item.almacen_id || ''),
    cantidad: String(item.cantidad || ''),
    observaciones: String(item.observaciones || '')
})) : [];

function obtenerOpcionProducto(productoId) {
    return Array.from(productosSelect.options).find((option) => option.value === String(productoId)) || null;
}

function formatearCantidad(value) {
    const num = parseFloat(value || '0');
    if (Number.isNaN(num)) {
        return '-';
    }
    return num.toLocaleString(undefined, { maximumFractionDigits: 2 });
}

function construirBorradorSalida() {
    const productoId = String(productosSelect.value || '').trim();
    const almacenId = String(almacenSelect.value || '').trim();
    const cantidad = String(cantidadInput.value || '').trim();
    const observaciones = String(observacionesInput.value || '').trim();
    const tieneDatos = productoId !== '' || cantidad !== '' || observaciones !== ''; //

    if (!tieneDatos) {
        return { empty: true, valid: false, item: null, message: '' };
    }

    if (!productoId || !almacenId || !cantidad || Number(cantidad) <= 0) {
        return {
            empty: false,
            valid: false,
            item: null,
            message: 'Selecciona Producto, Almacén y una Cantidad Mayor a Cero Antes de Continuar.'
        };
    }

    return {
        empty: false,
        valid: true,
        item: {
            producto_id: productoId,
            almacen_id: almacenId,
            cantidad: cantidad,
            observaciones: observaciones
        },
        message: ''
    };
}

function limpiarBorradorSalida() {
    productosSelect.value = '';
    cantidadInput.value = '';
    observacionesInput.value = '';
    actualizarResumen();
}

function renderSalidaItems() {
    salidaItemsBody.innerHTML = '';
    salidaItemsInputs.innerHTML = '';
    salidaItemsCount.textContent = `${salidaItems.length} producto${salidaItems.length === 1 ? '' : 's'} en la captura`;

    if (!salidaItems.length) {
        salidaItemsEmpty.hidden = false;
        salidaItemsWrapper.hidden = true;
        return;
    }

    salidaItemsEmpty.hidden = true;
    salidaItemsWrapper.hidden = false;

    salidaItems.forEach((item, index) => {
        const productoOption = obtenerOpcionProducto(item.producto_id);
        const productoNombre = productoOption ? productoOption.textContent.trim() : `Producto #${item.producto_id}`;
        const unidad = productoOption ? (productoOption.dataset.unidad || '') : '';
        const almacenNombre = almacenesMap.get(Number(item.almacen_id)) || `Almacén #${item.almacen_id}`;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${productoNombre}</td>
            <td>${almacenNombre}</td>
            <td>${formatearCantidad(item.cantidad)} ${unidad}</td>
            <td>${item.observaciones ? item.observaciones : '-'}</td>
            <td><button type="button" class="btn-inline-remove" data-index="${index}"><i class="fa fa-trash"></i></button></td>
        `;
        salidaItemsBody.appendChild(tr);

        const campos = [
            ['lineas_producto_id[]', item.producto_id],
            ['lineas_almacen_id[]', item.almacen_id],
            ['lineas_cantidad[]', item.cantidad],
            ['lineas_observaciones[]', item.observaciones]
        ];

        campos.forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            salidaItemsInputs.appendChild(input);
        });
    });
}

function agregarProductoALaCaptura() {
    const draft = construirBorradorSalida();
    if (!draft.valid) {
        if (!draft.empty) {
            window.alert(draft.message);
        }
        return;
    }

    salidaItems.push(draft.item);
    renderSalidaItems();
    limpiarBorradorSalida();
}

function aplicarFiltroProductos() {
    const texto = (filtroTexto.value || '').trim().toLowerCase();
    const tipo = filtroTipo.value;
    const categoria = filtroCategoria.value;
    const almacen = filtroAlmacen.value;
    const soloStock = filtroStock.checked;
    let visibles = 0;

    for (let i = 0; i < productosSelect.options.length; i++) {
        const option = productosSelect.options[i];
        if (!option.value) {
            option.hidden = false;
            option.disabled = false;
            continue;
        }

        const nombre = (option.textContent || '').toLowerCase();
        const codigo = (option.dataset.codigo || '').toLowerCase();
        const barras = (option.dataset.barras || '').toLowerCase();
        const tipoOpt = option.dataset.tipo || '';
        const categoriaOpt = option.dataset.categoria || '';
        const almacenOpt = option.dataset.almacen || '';
        const stockOpt = parseFloat(option.dataset.stock || '0');

        let coincide = true;
        if (texto) {
            coincide = nombre.includes(texto) || codigo.includes(texto) || barras.includes(texto);
        }
        if (coincide && tipo) {
            coincide = tipoOpt === tipo;
        }
        if (coincide && categoria) {
            coincide = categoriaOpt === categoria;
        }
        if (coincide && almacen) {
            coincide = almacenOpt === almacen;
        }
        if (coincide && soloStock) {
            coincide = stockOpt > 0;
        }

        option.hidden = !coincide;
        option.disabled = !coincide;
        if (coincide) {
            visibles += 1;
        }
    }

    if (filtroResultados) {
        filtroResultados.textContent = visibles.toString();
    }

    const selected = productosSelect.options[productosSelect.selectedIndex];
    if (selected && selected.value && selected.hidden) {
        productosSelect.value = '';
        actualizarResumen();
    }
}

function actualizarResumen() {
    const option = productosSelect.options[productosSelect.selectedIndex];
    if (!option || !option.value) {
        summaryPlaceholder.hidden = false;
        summaryContent.hidden = true;
        return;
    }

    const stock = option.dataset.stock ? parseFloat(option.dataset.stock) : 0; //
    const min = option.dataset.min ? parseFloat(option.dataset.min) : 0;
    const almacenId = option.dataset.almacen ? parseInt(option.dataset.almacen, 10) : 0;
    const apodo = (option.dataset.unidad || '').trim();
    const categoria = (option.dataset.categoria || '').trim();

    summaryNombre.textContent = option.textContent.trim();
    summaryStock.textContent = `${Number.isNaN(stock) ? '-' : stock.toLocaleString(undefined, { maximumFractionDigits: 2 })} ${apodo}`;
    summaryMin.textContent = `${Number.isNaN(min) ? '-' : min.toLocaleString(undefined, { maximumFractionDigits: 2 })} ${apodo}`;
    summaryCategoria.textContent = categoria || '-';
    summaryAlmacen.textContent = almacenesMap.get(almacenId) || 'Sin Asignar';

    if (almacenId && almacenSelect && almacenSelect.value === '') {
        almacenSelect.value = almacenId.toString();
    }

    summaryPlaceholder.hidden = true;
    summaryContent.hidden = false;
}

productosSelect.addEventListener('change', actualizarResumen);
filtroTexto.addEventListener('input', aplicarFiltroProductos);
filtroTipo.addEventListener('change', aplicarFiltroProductos);
filtroCategoria.addEventListener('change', aplicarFiltroProductos);
filtroAlmacen.addEventListener('change', aplicarFiltroProductos);
filtroStock.addEventListener('change', aplicarFiltroProductos);
agregarProductoBtn.addEventListener('click', agregarProductoALaCaptura);
limpiarCapturaBtn.addEventListener('click', () => {
    salidaItems.length = 0;
    limpiarBorradorSalida();
    renderSalidaItems();
});

salidaItemsBody.addEventListener('click', (event) => {
    const button = event.target.closest('[data-index]');
    if (!button) {
        return;
    }

    const index = Number(button.dataset.index);
    if (Number.isNaN(index)) {
        return;
    }

    salidaItems.splice(index, 1);
    renderSalidaItems();
});

salidaForm.addEventListener('submit', function (event) {
    event.preventDefault();

    const draft = construirBorradorSalida();
    if (!draft.empty) {
        if (!draft.valid) {
            Swal.fire({
                icon: 'warning',
                title: 'Datos Incompletos',
                text: draft.message,
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        salidaItems.push(draft.item);
        limpiarBorradorSalida();
        renderSalidaItems();
    }

    if (!salidaItems.length) {
        Swal.fire({
            icon: 'warning',
            title: 'No Hay Productos',
            text: 'Agrega Productos a la Captura para Registrar la Salida.',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    Swal.fire({
        title: '¿Crear Salida?',
        text: 'La Solicitud será Guardada hasta que sea Autorizada.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa fa-save"></i> Sí, Registrar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            HTMLFormElement.prototype.submit.call(salidaForm);
        }
    });
});

aplicarFiltroProductos();
renderSalidaItems();
if (productosSelect.value) {
    actualizarResumen();
}

 //Alerta de confirmación para aprobar o rechazar solicitudes
   document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-confirmar').forEach(boton => {
        boton.addEventListener('click', function (e) {
            e.preventDefault();

            const id = this.dataset.id;
            const accion = this.dataset.accion;
            const folio = this.dataset.folio ? `folio ${this.dataset.folio}` : 'esta solicitud';
            const esAprobar = accion === 'aprobar';

            Swal.fire({
                title: esAprobar ? 'Aprobar Solicitud' : 'Rechazar Solicitud',
                text: esAprobar 
                    ? `¿Aprobar Solicitud con ${folio}?` 
                    : `¿Rechazar Solicitud con ${folio}? `,
                icon: esAprobar ? 'question' : 'warning',
                
                showCancelButton: true,
                confirmButtonColor: esAprobar ? '#28a745' : '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: esAprobar ? 'Sí, Aprobar' : 'Sí, Rechazar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,

            }).then((result) => {
                if (result.isConfirmed) {

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `${accion}_solicitud_baja`;

                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'id';
                    inputId.value = id;
                    form.appendChild(inputId);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
    });

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

