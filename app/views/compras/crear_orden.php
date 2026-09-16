<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Compras']);

$role = $_SESSION['role'] ?? 'Empleado';
$nombre = $_SESSION['nombre'] ?? '';
$productos = is_array($productos ?? null) ? array_values(array_filter(
    $productos,
    static fn(array $producto): bool => !array_key_exists('activo', $producto) || (string) $producto['activo'] === '1'
)) : [];
$proyectos = is_array($proyectos ?? null) ? $proyectos : [];
$proveedores = is_array($proveedores ?? null) ? $proveedores : [];
$error = $error ?? '';
$msg = $msg ?? '';

$categoriasFiltro = [];
foreach ($productos as $producto) {
    $categoria = trim((string) ($producto['categoria'] ?? ''));
    if ($categoria !== '') {
        $categoriasFiltro[$categoria] = true;
    }
}
ksort($categoriasFiltro, SORT_NATURAL | SORT_FLAG_CASE);

$materialesIniciales = [];
$materialesPost = $_POST['material'] ?? '';
if (is_string($materialesPost) && $materialesPost !== '') {
    $materialesDecodificados = json_decode($materialesPost, true);
    if (is_array($materialesDecodificados)) {
        $materialesIniciales = $materialesDecodificados;
    }
} elseif (!empty($entradaItems) && is_array($entradaItems)) {
    $materialesIniciales = array_values($entradaItems);
}

$alertaSesion = $_SESSION['alerta'] ?? null;
unset($_SESSION['alerta']);
$seccion_activa = 'ordenes_compra';
$formStylePath = __DIR__ . '/../../../public/assets/css/inventario_form.css';
$formStyleVersion = is_file($formStylePath) ? (string) filemtime($formStylePath) : '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CREAR ORDEN DE COMPRA | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/productos.css">
    <link rel="stylesheet" href="assets/css/inventario_form.css?v=<?= rawurlencode($formStyleVersion) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="./assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body class="module-inventory-warehouse solicitud-material-page crear-orden-page">
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Abrir o Cerrar Menú" aria-expanded="true">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>

        <main class="dashboard-main inventario-form-main">
            <div class="inventario-form-header">
                <div>
                    <h1 class="page-title-icon"><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i> CREAR ORDEN DE COMPRA</h1>
                    <p class="form-desc">Selecciona un Proveedor General y Agrega los Materiales que Formarán Parte de la Orden.</p>
                </div>
            </div>

            <?php if ($msg !== ''): ?>
                <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="inventario-form-grid">
                <section class="inventario-form-card">
                    <h2><i class="fa-solid fa-clipboard-list"></i> Datos de la Orden</h2>
                    <form action="<?= htmlspecialchars(Session::url('orden_nueva'), ENT_QUOTES, 'UTF-8') ?>"
                          method="post"
                          autocomplete="off"
                          class="inventario-entry-form"
                          id="orden-form">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="material" id="material" value="">

                        <div class="double-field">
                            <div class="form-field">
                                <label for="proveedor_id">Proveedor *</label>
                                <select id="proveedor_id" name="proveedor_id" required>
                                    <option value="">Selecciona un Proveedor...</option>
                                    <?php foreach ($proveedores as $proveedor): ?>
                                        <option value="<?= (int) ($proveedor['id'] ?? 0) ?>"
                                            <?= (string) ($_POST['proveedor_id'] ?? '') === (string) ($proveedor['id'] ?? '') ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($proveedor['nombre'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="fecha_compra">Fecha Requerida *</label>
                                <input type="date" id="fecha_compra" name="fecha_compra" min="<?= date('Y-m-d') ?>"
                                       value="<?= htmlspecialchars($_POST['fecha_compra'] ?? '') ?>" required>
                            </div>

                            <div class="form-field">
                                <label for="metodo_entrega">Método de Entrega *</label>
                                <select id="metodo_entrega" name="metodo_entrega" required>
                                    <option value="">Selecciona un Método de Entrega...</option>
                                    <?php foreach (['Reparto', 'Recolección', 'Por Confirmar'] as $metodo): ?>
                                        <option value="<?= htmlspecialchars($metodo) ?>" <?= ($_POST['metodo_entrega'] ?? '') === $metodo ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($metodo) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div id="modo_catalogo">
                            <div class="form-field">
                                <label for="filtro_texto">Buscar en el Catálogo</label>
                                <div class="double-field">
                                    <input type="search" id="filtro_texto" placeholder="Nombre, Nomenclatura, SKU, Fabricante o Serie">
                                    <select id="filtro_tipo" aria-label="Filtrar por Tipo">
                                        <option value="">Todos los Tipos</option>
                                        <option value="Consumible">Consumible</option>
                                        <option value="Herramienta">Herramienta</option>
                                        <option value="Equipo">Equipo</option>
                                    </select>
                                    <select id="filtro_categoria" aria-label="Filtrar por Categoría">
                                        <option value="">Todas las Categorías</option>
                                        <?php foreach (array_keys($categoriasFiltro) as $categoriaNombre): ?>
                                            <option value="<?= htmlspecialchars($categoriaNombre) ?>"><?= htmlspecialchars($categoriaNombre) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <small>Productos Visibles: <span id="filtro_resultados">0</span></small>
                            </div>

                            <div class="form-field">
                                <label for="producto_id">Producto del Catálogo *</label>
                                <select id="producto_id">
                                    <option value="">Selecciona un Producto...</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <?php
                                        $codigo = $producto['nomenclatura'] ?? $producto['codigo_fabricante'] ?? '';
                                        $stockActual = (float) ($producto['stock_actual'] ?? 0);
                                        $unidadProducto = $producto['unidad_medida_nombre'] ?? $producto['unidad_abreviacion'] ?? '';
                                        $textoBusqueda = implode(' ', [
                                            $producto['nombre'] ?? '',
                                            $producto['nomenclatura'] ?? '',
                                            $producto['sku'] ?? '',
                                            $producto['codigo_fabricante'] ?? '',
                                            $producto['num_serie'] ?? '',
                                            $producto['codigos_barras'] ?? '',
                                        ]);
                                        ?>
                                        <option value="<?= (int) ($producto['id'] ?? 0) ?>"
                                                data-nombre="<?= htmlspecialchars($producto['nombre'] ?? '') ?>"
                                                data-busqueda="<?= htmlspecialchars($textoBusqueda) ?>"
                                                data-stock="<?= $stockActual ?>"
                                                data-precio="<?= htmlspecialchars((string) ($producto['precio_unitario'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"
                                                data-unidad="<?= htmlspecialchars($unidadProducto) ?>"
                                                data-tipo="<?= htmlspecialchars($producto['tipo'] ?? '') ?>"
                                                data-categoria="<?= htmlspecialchars($producto['categoria'] ?? '') ?>">
                                            <?= htmlspecialchars($producto['nombre'] ?? '') ?><?= $codigo !== '' ? ' — ' . htmlspecialchars($codigo) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="double-field">
                            <div class="form-field">
                                <label for="cantidad">Cantidad Necesaria *</label>
                                <input type="number" id="cantidad" min="0.01" step="0.01" placeholder="Ej. 25">
                            </div>
                            <div class="form-field">
                                <label for="precio_unitario">Precio Unitario *</label>
                                <input type="number" id="precio_unitario" min="0.01" step="0.01" placeholder="Ej. 25">
                            </div>
                        </div>

                        <div class="entry-batch-actions">
                            <button type="button" class="btn-secondary" id="agregar-material"><i class="fa fa-plus"></i> Agregar a la Orden</button>
                            <button type="button" class="btn-ghost" id="limpiar-captura"><i class="fa fa-eraser"></i> Vaciar Lista</button>
                            <button type="submit" class="btn-main"><i class="fa fa-save"></i> Registrar Orden</button>
                        </div>

                        <section class="entry-items-panel" aria-live="polite">
                            <div class="entry-items-header">
                                <h3><i class="fa fa-list-check"></i> Materiales Seleccionados</h3>
                                <span id="items-count">0 Materiales</span>
                            </div>
                            <div class="inventario-empty entry-items-empty" id="items-empty">
                                <i class="fa fa-box-open"></i>
                                <p>Agrega Productos del Catálogo para Construir la Orden.</p>
                            </div>
                            <div class="entry-items-table-wrapper" id="items-wrapper" hidden>
                                <table class="entry-items-table">
                                    <thead><tr><th>Material</th><th>Cantidad</th><th>Precio Unitario</th><th>Subtotal</th><th>Acción</th></tr></thead>
                                    <tbody id="items-body"></tbody>
                                </table>
                            </div>
                        </section>

                        <div id="lineas-inputs"></div>
                    </form>
                </section>

                <aside class="inventario-form-card form-summary">
                    <h2><i class="fa fa-circle-info"></i> Resumen de la Orden</h2>
                    <div class="summary-placeholder" id="summary-placeholder">
                        <i class="fa fa-box"></i>
                        <p>Agrega un Material para Consultar el Resumen.</p>
                    </div>
                    <div class="summary-content" id="summary-content" hidden>
                        <div class="summary-item"><span class="label">Partidas</span><span class="value" id="summary-total">0</span></div>
                        <div class="summary-item"><span class="label">Total Estimado</span><span class="value" id="summary-importe">$0.00</span></div>
                        <div class="summary-item"><span class="label">Proveedor General</span><span class="value" id="summary-proveedor">Sin Seleccionar</span></div>
                        <div class="summary-item"><span class="label">Fecha Requerida</span><span class="value" id="summary-fecha">Sin Seleccionar</span></div>
                    </div>
                </aside>
            </div>
        </main>
    </div>
</div>

<script>
(() => {
    'use strict';

    const form = document.getElementById('orden-form');
    const productoSelect = document.getElementById('producto_id');
    const cantidadInput = document.getElementById('cantidad');
    const precioUnitarioInput = document.getElementById('precio_unitario');
    const proveedorSelect = document.getElementById('proveedor_id');
    const fechaCompra = document.getElementById('fecha_compra');
    const filtroTexto = document.getElementById('filtro_texto');
    const filtroTipo = document.getElementById('filtro_tipo');
    const filtroCategoria = document.getElementById('filtro_categoria');
    const filtroResultados = document.getElementById('filtro_resultados');
    const itemsBody = document.getElementById('items-body');
    const itemsWrapper = document.getElementById('items-wrapper');
    const itemsEmpty = document.getElementById('items-empty');
    const itemsCount = document.getElementById('items-count');
    const lineasInputs = document.getElementById('lineas-inputs');
    const materialInput = document.getElementById('material');
    const summaryPlaceholder = document.getElementById('summary-placeholder');
    const summaryContent = document.getElementById('summary-content');

    const materialesIniciales = <?= json_encode($materialesIniciales, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const materiales = Array.isArray(materialesIniciales) ? materialesIniciales.map(normalizarMaterial).filter(Boolean) : [];

    function normalizarMaterial(item) {
        if (!item || typeof item !== 'object') return null;
        const productoId = String(item.producto_id ?? '').trim();
        const nombreMaterial = String(item.producto_nombre ?? item.nombre ?? '').trim();
        const cantidad = Number.parseFloat(item.cantidad ?? 0);
        const precioUnitario = Number.parseFloat(item.precio_unitario ?? 0);
        if (!productoId || !nombreMaterial || !Number.isFinite(cantidad) || cantidad <= 0
            || !Number.isFinite(precioUnitario) || precioUnitario <= 0) return null;
        return {
            tipo: String(item.tipo || 'Consumible'),
            producto_id: productoId,
            producto_nombre: nombreMaterial,
            cantidad,
            precio_unitario: precioUnitario,
            unidad: String(item.unidad ?? '').trim()
        };
    }

    function avisar(titulo, mensaje) {
        if (window.Swal) {
            Swal.fire({ icon: 'warning', title: titulo, text: mensaje, confirmButtonColor: '#3085d6' });
        } else {
            window.alert(`${titulo}: ${mensaje}`);
        }
    }

    function crearInputOculto(nombreCampo, valor) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = nombreCampo;
        input.value = valor ?? '';
        lineasInputs.appendChild(input);
    }

    function textoCantidad(item) {
        const cantidad = Number(item.cantidad).toLocaleString('es-MX', { maximumFractionDigits: 2 });
        return item.unidad ? `${cantidad} ${item.unidad}` : cantidad;
    }

    function textoMoneda(valor) {
        return Number(valor).toLocaleString('es-MX', {
            style: 'currency', currency: 'MXN', minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    function textoFecha(valor) {
        if (!valor) return 'Sin Seleccionar';
        const partes = valor.split('-');
        return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : valor;
    }

    function renderMateriales() {
        itemsBody.replaceChildren();
        lineasInputs.replaceChildren();
        itemsCount.textContent = `${materiales.length} Material${materiales.length === 1 ? '' : 'es'}`;
        itemsEmpty.hidden = materiales.length > 0;
        itemsWrapper.hidden = materiales.length === 0;

        materiales.forEach((item, index) => {
            const row = document.createElement('tr');
            [
                item.producto_nombre,
                textoCantidad(item),
                textoMoneda(item.precio_unitario),
                textoMoneda(item.cantidad * item.precio_unitario)
            ].forEach((valor) => {
                const cell = document.createElement('td');
                cell.textContent = valor;
                row.appendChild(cell);
            });
            const actionCell = document.createElement('td');
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'btn-inline-remove';
            removeButton.dataset.index = String(index);
            removeButton.title = 'Quitar Material';
            removeButton.setAttribute('aria-label', `Quitar ${item.producto_nombre}`);
            removeButton.innerHTML = '<i class="fa fa-trash"></i>';
            actionCell.appendChild(removeButton);
            row.appendChild(actionCell);
            itemsBody.appendChild(row);

            crearInputOculto('lineas_producto_id[]', item.producto_id || '');
            crearInputOculto('lineas_cantidad[]', item.cantidad);
            crearInputOculto('lineas_precio_unitario[]', item.precio_unitario);
        });

        materialInput.value = JSON.stringify(materiales);
        actualizarResumen();
    }

    function actualizarResumen() {
        const total = materiales.length;
        const importe = materiales.reduce((suma, item) => suma + (item.cantidad * item.precio_unitario), 0);
        summaryPlaceholder.hidden = total > 0;
        summaryContent.hidden = total === 0;
        document.getElementById('summary-total').textContent = String(total);
        document.getElementById('summary-importe').textContent = textoMoneda(importe);
        document.getElementById('summary-proveedor').textContent = proveedorSelect.selectedOptions[0]?.value
            ? proveedorSelect.selectedOptions[0].textContent.trim() : 'Sin Seleccionar';
        document.getElementById('summary-fecha').textContent = textoFecha(fechaCompra.value);
    }

    function limpiarBorrador() {
        productoSelect.value = '';
        cantidadInput.value = '';
        precioUnitarioInput.value = '';
    }

    function agregarMaterial() {
        if (!proveedorSelect.value) {
            avisar('Proveedor Requerido', 'Selecciona el Proveedor General Antes de Agregar Materiales.');
            proveedorSelect.focus();
            return;
        }

        const cantidad = Number.parseFloat(cantidadInput.value);
        if (!Number.isFinite(cantidad) || cantidad <= 0) {
            avisar('Cantidad Inválida', 'Captura una Cantidad Mayor a Cero.');
            cantidadInput.focus();
            return;
        }

        const precioUnitario = Number.parseFloat(precioUnitarioInput.value);
        if (!Number.isFinite(precioUnitario) || precioUnitario <= 0) {
            avisar('Precio Inválido', 'Captura un Precio Unitario Mayor a Cero.');
            precioUnitarioInput.focus();
            return;
        }

        const option = productoSelect.selectedOptions[0];
        if (!option?.value) {
            avisar('Producto Incompleto', 'Selecciona un Producto del Catálogo.');
            productoSelect.focus();
            return;
        }

        const material = {
            tipo: option.dataset.tipo || 'Consumible', producto_id: option.value,
            producto_nombre: option.dataset.nombre || option.textContent.trim(), cantidad,
            precio_unitario: precioUnitario,
            unidad: option.dataset.unidad || ''
        };

        materiales.push(material);
        limpiarBorrador();
        renderMateriales();
    }

    function aplicarFiltros() {
        const texto = filtroTexto.value.trim().toLocaleLowerCase('es');
        const tipo = filtroTipo.value;
        const categoria = filtroCategoria.value;
        let visibles = 0;
        Array.from(productoSelect.options).forEach((option, index) => {
            if (index === 0) return;
            const busqueda = (option.dataset.busqueda || option.textContent).toLocaleLowerCase('es');
            const coincide = (!texto || busqueda.includes(texto))
                && (!tipo || option.dataset.tipo === tipo)
                && (!categoria || option.dataset.categoria === categoria);
            option.hidden = !coincide;
            option.disabled = !coincide;
            if (coincide) visibles += 1;
        });
        if (productoSelect.selectedOptions[0]?.disabled) productoSelect.value = '';
        filtroResultados.textContent = String(visibles);
    }

    document.getElementById('agregar-material').addEventListener('click', agregarMaterial);
    document.getElementById('limpiar-captura').addEventListener('click', () => {
        materiales.length = 0;
        limpiarBorrador();
        renderMateriales();
    });
    itemsBody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-index]');
        if (!button) return;
        materiales.splice(Number(button.dataset.index), 1);
        renderMateriales();
    });
    filtroTexto.addEventListener('input', aplicarFiltros);
    filtroTipo.addEventListener('change', aplicarFiltros);
    filtroCategoria.addEventListener('change', aplicarFiltros);
    proveedorSelect.addEventListener('change', actualizarResumen);
    fechaCompra.addEventListener('input', actualizarResumen);
    fechaCompra.addEventListener('change', actualizarResumen);
    productoSelect.addEventListener('change', () => {
        const precioSugerido = Number.parseFloat(productoSelect.selectedOptions[0]?.dataset.precio || '0');
        precioUnitarioInput.value = precioSugerido > 0 ? precioSugerido.toFixed(2) : '';
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;
        if (materiales.length === 0) {
            avisar('Orden Vacía', 'Agrega al Menos un Producto a la Orden.');
            return;
        }
        materialInput.value = JSON.stringify(materiales);

        const enviar = () => HTMLFormElement.prototype.submit.call(form);
        if (!window.Swal) {
            if (window.confirm('¿Deseas Registrar esta Orden de Compra?')) enviar();
            return;
        }
        Swal.fire({
            title: '¿Registrar Orden?', text: `Se Registrarán ${materiales.length} Partidas con el Proveedor Seleccionado.`,
            icon: 'question', showCancelButton: true, confirmButtonColor: '#10b981', cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, Registrar', cancelButtonText: 'Cancelar', reverseButtons: true
        }).then((result) => { if (result.isConfirmed) enviar(); });
    });

    aplicarFiltros();
    renderMateriales();

    const alertaSesion = <?= json_encode($alertaSesion, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (alertaSesion && window.Swal) {
        Swal.fire({
            icon: alertaSesion.tipo || 'info', title: alertaSesion.titulo || 'Aviso',
            text: alertaSesion.mensaje || '', confirmButtonColor: '#3085d6'
        });
    }
})();
</script>
<?php include __DIR__ . '/../layouts/scripts.php'; ?>
</body>
</html>
