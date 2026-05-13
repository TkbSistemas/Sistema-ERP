<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

$role = $_SESSION['role'] ?? 'Administrador';
$nombre = $_SESSION['nombre'] ?? '';
$selectedProducto = $_POST['producto_id'] ?? '';
$selectedAlmacen = $_POST['almacen_id'] ?? '';
$cantidadIngresada = $_POST['cantidad'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';
$entradaItems = $entradaItems ?? [];
$error = $error ?? '';
$categoriasFiltro = [];
foreach ($productos as $producto) {
    if (!empty($producto['categoria'])) {
        $categoriasFiltro[$producto['categoria']] = true;
    }
}
ksort($categoriasFiltro);
$breadcrumbs = [
    ['label' => 'Registrar entrada'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Entrada de Inventario | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/productos.css">
    <link rel="stylesheet" href="/assets/css/inventario_form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="main-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="login-logo"><img src="assets/images/icono_takab.png" alt="logo_TAKAB" width="90" height="55"></div>
            <div>
                <div class="sidebar-title">TAKAB</div>
                <div class="sidebar-desc">Inventario y almacén</div>
            </div>
        </div>
        <nav class="sidebar-nav">
           <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <?php if ($role === 'Administrador'): ?>
                <a href="productos.php"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php" class="active"><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href="prestamos_pendientes.php" ><i class="fa-solid fa-screwdriver-wrench"></i> Préstamos de herramientas</a>
                <a href="reportes_rotacion.php" ><i class="fa-solid fa-refresh"></i> Rotación de Inventario</a>
                <a href="revisar_solicitudes.php"><i class="fa-solid fa-plus-square"></i> Solicitudes de Material</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="ajustes.php"><i class="fa-solid fa-gear"></i> Configuración</a>
                <a href="documentacion.php"><i class="fa-solid fa-book"></i>Documentación</a>
            <?php elseif ($role === 'Almacen'): ?>
                <a href="productos.php"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php" class="active"><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href="prestamos_pendientes.php" ><i class="fa-solid fa-screwdriver-wrench"></i> Préstamos de herramientas</a>
                <a href="reportes_rotacion.php" ><i class="fa-solid fa-refresh"></i> Rotación de Inventario</a>
                <a href="revisar_solicitudes.php"><i class="fa-solid fa-plus-square"></i> Solicitudes de Material</a>
                <a href="mis_solicitudes.php"><i class="fa-solid fa-clipboard-list"></i> Mis Solicitudes</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="ajustes.php"><i class="fa-solid fa-gear"></i> Configuración</a>
                <a href="documentacion.php"><i class="fa-solid fa-book"></i>Documentación</a>
            <?php elseif ($role === 'Compras'): ?>
              <a href="productos.php"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php" class="active"><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="documentacion.php"><i class="fa-solid fa-book"></i>Documentación</a>  
            <?php elseif ($role === 'Empleado'): ?>
                <a href="solicitudes_crear.php"><i class="fa-solid fa-plus-square"></i> Solicitar Material</a>
                <a href="mis_solicitudes.php"><i class="fa-solid fa-clipboard-list"></i> Mis Solicitudes</a>
            <?php endif; ?>
            <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>

    <div class="content-area">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <main class="dashboard-main inventario-form-main">
            <div class="inventario-form-header">
                <div>
                    <h1><i class="fa fa-arrow-down"></i> Registrar entrada de inventario</h1>
                    <p class="form-desc">Captura las nuevas existencias entregadas al almacén y actualiza el stock en tiempo real.</p>
                </div>
                <a class="btn-secondary" href="inventario_actual.php"><i class="fa fa-arrow-left"></i> Volver al inventario</a>
            </div>

            <?php if (!empty($msg)): ?>
                <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="inventario-form-grid">
                
                <section class="inventario-form-card">
                    <h2><i class="fa fa-clipboard"></i> Detalles de la entrada</h2>
                    <form method="post" enctype="application/x-www-form-urlencoded" autocomplete="off" class="inventario-entry-form" id="inventario-entry-form">
                        <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">

                        <div class="form-field" style="margin-bottom:14px;">
                            <label>Buscador avanzado</label>
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:10px;">
                                <input type="text" id="filtro_texto" placeholder="Buscar por nombre, código o barras">
                                <select id="filtro_tipo">
                                    <option value="">Tipo (todos)</option>
                                    <option value="Consumible">Consumible</option>
                                    <option value="Herramienta">Herramienta</option>
                                    <option value="Equipo">Equipo</option>
                                </select>
                                <select id="filtro_categoria">
                                    <option value="">Categoría (todas)</option>
                                    <?php foreach (array_keys($categoriasFiltro) as $categoriaNombre): ?>
                                        <option value="<?= htmlspecialchars($categoriaNombre) ?>"><?= htmlspecialchars($categoriaNombre) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select id="filtro_almacen">
                                    <option value="">Almacén asignado (todos)</option>
                                    <?php foreach ($almacenes as $almacen): ?>
                                        <option value="<?= (int) $almacen['id'] ?>"><?= htmlspecialchars($almacen['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label style="display:flex; align-items:center; gap:8px; font-size:0.95rem;">
                                    <input type="checkbox" id="filtro_stock" style="width:auto; margin:0;"> Solo con stock
                                </label>
                            </div>
                            <div style="margin-top:8px; font-size:0.9rem; color:#5c6a96;">
                                Productos visibles: <span id="filtro_resultados">0</span>
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="producto_id">Producto *</label>
                            <select id="producto_id" name="producto_id">
                                <option value="">Selecciona un producto...</option>
                                <?php foreach ($productos as $producto): ?>
                                    <option value="<?= $producto['id'] ?>"
                                        data-stock="<?= (float) ($producto['stock_actual'] ?? 0) ?>"
                                        data-min="<?= (float) ($producto['stock_minimo'] ?? 0) ?>"
                                        data-unidad="<?= htmlspecialchars($producto['unidad_abreviacion'] ?? '') ?>"
                                        data-almacen="<?= (int) ($producto['almacen_id'] ?? 0) ?>"
                                        data-tipo="<?= htmlspecialchars($producto['tipo'] ?? '') ?>"
                                        data-categoria="<?= htmlspecialchars($producto['categoria'] ?? '') ?>"
                                        data-codigo="<?= htmlspecialchars($producto['codigo'] ?? '') ?>"
                                        data-barras="<?= htmlspecialchars($producto['codigo_barras'] ?? '') ?>"
                                        <?= $selectedProducto == $producto['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($producto['nombre']) ?> (<?= htmlspecialchars($producto['codigo']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="almacen_id">Almacén destino *</label>
                            <select id="almacen_id" name="almacen_id">
                                <option value="">Selecciona un almacén...</option>
                                <?php foreach ($almacenes as $almacen): ?>
                                    <option value="<?= $almacen['id'] ?>" <?= $selectedAlmacen == $almacen['id'] ? 'selected' : '' ?>><?= htmlspecialchars($almacen['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="cantidad">Cantidad recibida *</label>
                            <input type="number" id="cantidad" name="cantidad" min="0" step="0.01" placeholder="Ej. 25" value="<?= htmlspecialchars($cantidadIngresada) ?>">
                        </div>

                        <div class="form-field">
                            <label for="observaciones">Observaciones</label>
                            <textarea id="observaciones" name="observaciones" placeholder="Número de factura, lote, notas adicionales..." rows="3"><?= htmlspecialchars($observaciones) ?></textarea>
                        </div>

                        <div class="entry-batch-actions">
                            <button type="button" class="btn-secondary" id="agregar-producto"><i class="fa fa-plus"></i> Agregar producto</button>
                            <button type="button" class="btn-ghost" id="limpiar-captura"><i class="fa fa-eraser"></i> Limpiar captura</button>
                            <button type="submit" class="btn-main" id="registrar-entrada"><i class="fa fa-save"></i> Registrar entrada</button>
                        </div>

                        <section class="entry-items-panel">
                            <div class="entry-items-header">
                                <h3><i class="fa fa-list-check"></i> Productos seleccionados</h3>
                                <span id="entrada-items-count">0 productos en la captura</span>
                            </div>
                            <div class="inventario-empty entry-items-empty" id="entrada-items-empty">
                                <i class="fa fa-box-open"></i>
                                <p>Agrega productos a la captura para registrarlos todos juntos.</p>
                            </div>
                            <div class="entry-items-table-wrapper" id="entrada-items-wrapper" hidden>
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
                                    <tbody id="entrada-items-body"></tbody>
                                </table>
                            </div>
                        </section>

                        <div id="entrada-items-inputs"></div>
                    </form>
                </section>

                <section class="inventario-form-card form-summary">
                    <h2><i class="fa fa-circle-info"></i> Resumen del producto</h2>
                    <div class="summary-placeholder" id="summary-placeholder">
                        <i class="fa fa-box"></i>
                        <p>Selecciona un producto para consultar su stock actual y ubicación.</p>
                    </div>
                    <div class="summary-content" id="summary-content" hidden>
                        <div class="summary-item">
                            <span class="label">Producto</span>
                            <span class="value" id="summary-nombre">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Stock actual</span>
                            <span class="value" id="summary-stock">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Stock mínimo</span>
                            <span class="value" id="summary-min">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Unidad</span>
                            <span class="value" id="summary-unidad">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Almacén sugerido</span>
                            <span class="value" id="summary-almacen">-</span>
                        </div>
                    </div>
                </section>
            </div>

            <section class="inventario-form-card inventario-recents">
                <div class="recents-header">
                    <h2><i class="fa fa-clock"></i> Últimas entradas registradas</h2>
                    <span class="recents-sub">Ayuda a verificar duplicidades o confirmar capturas recientes</span>
                </div>
                <?php if (empty($movimientosRecientes)): ?>
                    <div class="inventario-empty">
                        <i class="fa fa-inbox"></i>
                        <p>Aún no se registran entradas de inventario.</p>
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
                                        <td><?= date('d/m/Y H:i', strtotime($mov['fecha'])) ?></td>
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
const entradaForm = document.getElementById('inventario-entry-form');
const productosSelect = document.getElementById('producto_id');
const almacenSelect = document.getElementById('almacen_id');
const cantidadInput = document.getElementById('cantidad');
const observacionesInput = document.getElementById('observaciones');
const agregarProductoBtn = document.getElementById('agregar-producto');
const limpiarCapturaBtn = document.getElementById('limpiar-captura');
const entradaItemsInputs = document.getElementById('entrada-items-inputs');
const entradaItemsBody = document.getElementById('entrada-items-body');
const entradaItemsWrapper = document.getElementById('entrada-items-wrapper');
const entradaItemsEmpty = document.getElementById('entrada-items-empty');
const entradaItemsCount = document.getElementById('entrada-items-count');
const summaryPlaceholder = document.getElementById('summary-placeholder');
const summaryContent = document.getElementById('summary-content');
const summaryNombre = document.getElementById('summary-nombre');
const summaryStock = document.getElementById('summary-stock');
const summaryMin = document.getElementById('summary-min');
const summaryUnidad = document.getElementById('summary-unidad');
const summaryAlmacen = document.getElementById('summary-almacen');
const filtroTexto = document.getElementById('filtro_texto');
const filtroTipo = document.getElementById('filtro_tipo');
const filtroCategoria = document.getElementById('filtro_categoria');
const filtroAlmacen = document.getElementById('filtro_almacen');
const filtroStock = document.getElementById('filtro_stock');
const filtroResultados = document.getElementById('filtro_resultados');
const lineasIniciales = <?= json_encode(array_values($entradaItems), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const almacenesMap = new Map([
    <?php foreach ($almacenes as $almacen): ?>
    [<?= (int) $almacen['id'] ?>, "<?= addslashes($almacen['nombre']) ?>"],
    <?php endforeach; ?>
]);
const entradaItems = Array.isArray(lineasIniciales) ? lineasIniciales.map((item) => ({
    producto_id: String(item.producto_id || ''),
    almacen_id: String(item.almacen_id || ''),
    cantidad: String(item.cantidad || ''),
    observaciones: String(item.observaciones || ''),
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

function construirBorradorEntrada() {
    const productoId = String(productosSelect.value || '').trim();
    const almacenId = String(almacenSelect.value || '').trim();
    const cantidad = String(cantidadInput.value || '').trim();
    const observaciones = String(observacionesInput.value || '').trim();
    const tieneDatos = productoId !== '' || cantidad !== '' || observaciones !== '';

    if (!tieneDatos) {
        return { empty: true, valid: false, item: null, message: '' };
    }

    if (!productoId || !almacenId || !cantidad || Number(cantidad) <= 0) {
        return {
            empty: false,
            valid: false,
            item: null,
            message: 'Selecciona producto, almacén y una cantidad mayor a cero antes de continuar.'
        };
    }

    return {
        empty: false,
        valid: true,
        item: {
            producto_id: productoId,
            almacen_id: almacenId,
            cantidad: cantidad,
            observaciones: observaciones,
        },
        message: ''
    };
}

function limpiarBorradorEntrada() {
    productosSelect.value = '';
    cantidadInput.value = '';
    observacionesInput.value = '';
    actualizarResumen();
}

function renderEntradaItems() {
    entradaItemsBody.innerHTML = '';
    entradaItemsInputs.innerHTML = '';
    entradaItemsCount.textContent = `${entradaItems.length} producto${entradaItems.length === 1 ? '' : 's'} en la captura`;

    if (!entradaItems.length) {
        entradaItemsEmpty.hidden = false;
        entradaItemsWrapper.hidden = true;
        return;
    }

    entradaItemsEmpty.hidden = true;
    entradaItemsWrapper.hidden = false;

    entradaItems.forEach((item, index) => {
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
        entradaItemsBody.appendChild(tr);

        const campos = [
            ['lineas_producto_id[]', item.producto_id],
            ['lineas_almacen_id[]', item.almacen_id],
            ['lineas_cantidad[]', item.cantidad],
            ['lineas_observaciones[]', item.observaciones],
        ];

        campos.forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            entradaItemsInputs.appendChild(input);
        });
    });
}

function agregarProductoALaCaptura() {
    const draft = construirBorradorEntrada();
    if (!draft.valid) {
        if (!draft.empty) {
            window.alert(draft.message);
        }
        return;
    }

    entradaItems.push(draft.item);
    renderEntradaItems();
    limpiarBorradorEntrada();
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

    const stock = option.dataset.stock ? parseFloat(option.dataset.stock) : 0;
    const min = option.dataset.min ? parseFloat(option.dataset.min) : 0;
    const unidad = option.dataset.unidad || '';
    const almacenId = option.dataset.almacen ? parseInt(option.dataset.almacen, 10) : 0;

    summaryNombre.textContent = option.textContent.trim();
    summaryStock.textContent = `${Number.isNaN(stock) ? '-' : stock.toLocaleString(undefined, { maximumFractionDigits: 2 })} ${unidad}`;
    summaryMin.textContent = Number.isNaN(min) ? '-' : min.toLocaleString(undefined, { maximumFractionDigits: 2 });
    summaryUnidad.textContent = unidad || '-';
    summaryAlmacen.textContent = almacenesMap.get(almacenId) || 'Sin asignar';

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
limpiarCapturaBtn.addEventListener('click', limpiarBorradorEntrada);

entradaItemsBody.addEventListener('click', (event) => {
    const button = event.target.closest('[data-index]');
    if (!button) {
        return;
    }

    const index = Number(button.dataset.index);
    if (Number.isNaN(index)) {
        return;
    }

    entradaItems.splice(index, 1);
    renderEntradaItems();
});

entradaForm.addEventListener('submit', (event) => {
    const draft = construirBorradorEntrada();
    if (!draft.empty) {
        if (!draft.valid) {
            event.preventDefault();
            window.alert(draft.message);
            return;
        }
        entradaItems.push(draft.item);
        limpiarBorradorEntrada();
    }

    if (!entradaItems.length) {
        event.preventDefault();
        window.alert('Agrega al menos un producto a la captura antes de registrar la entrada.');
        return;
    }

    renderEntradaItems();
});

aplicarFiltroProductos();
renderEntradaItems();
if (productosSelect.value) {
    actualizarResumen();
}
</script>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>

