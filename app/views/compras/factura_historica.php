<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Compras']);

$role = $_SESSION['role'] ?? 'Compras';
$nombre = $_SESSION['nombre'] ?? '';
$productos = is_array($productos ?? null) ? $productos : [];
$proyectos = is_array($proyectos ?? null) ? $proyectos : [];
$proveedores = is_array($proveedores ?? null) ? $proveedores : [];
$almacenes = is_array($almacenes ?? null) ? $almacenes : [];
$error = (string) ($error ?? '');
$seccion_activa = 'facturas_compras';

$materialesIniciales = [];
if (!empty($_POST['materiales']) && is_string($_POST['materiales'])) {
    $decodificados = json_decode($_POST['materiales'], true);
    if (is_array($decodificados)) {
        $materialesIniciales = $decodificados;
    }
}

$productosCliente = array_map(static function (array $producto): array {
    $unidad = trim((string) ($producto['unidad_abreviacion'] ?? $producto['unidad_medida_nombre'] ?? ''));
    return [
        'id' => (int) ($producto['id'] ?? 0),
        'nombre' => (string) ($producto['nombre'] ?? ''),
        'nomenclatura' => (string) ($producto['nomenclatura'] ?? ''),
        'sku' => (string) ($producto['sku'] ?? ''),
        'codigo_fabricante' => (string) ($producto['codigo_fabricante'] ?? ''),
        'numero_serie' => (string) ($producto['num_serie'] ?? ''),
        'marca' => (string) ($producto['marca'] ?? ''),
        'modelo' => (string) ($producto['modelo'] ?? ''),
        'unidad' => $unidad,
        'precio' => (float) ($producto['precio_unitario'] ?? 0),
    ];
}, $productos);

$stylePath = __DIR__ . '/../../../public/assets/css/factura-historica.css';
$styleVersion = is_file($stylePath) ? (string) filemtime($stylePath) : '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AÑADIR FACTURA HISTÓRICA | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/prestamos-pendientes.css">
    <link rel="stylesheet" href="assets/css/inventory-warehouse-responsive.css">
    <link rel="stylesheet" href="assets/css/factura-historica.css?v=<?= rawurlencode($styleVersion) ?>">
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

        <main class="dashboard-main historical-invoice-main">
            <div class="dashboard-header-row historical-invoice-header">
                <div>
                    <h1 class="page-title-icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> AÑADIR FACTURA HISTÓRICA</h1>
                    <span class="dashboard-desc">Registra una Compra Anterior y Genera su Entrada de Inventario sin Recorrer el Flujo Ordinario.</span>
                </div>
                <a class="btn-secondary historical-back" href="<?= htmlspecialchars(Session::url('facturas_compras'), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Regresar a Facturas
                </a>
            </div>

            <div class="historical-notice">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <div>
                    <strong>Registro Histórico con Trazabilidad</strong>
                    <span>Al confirmar se generarán la Orden, Factura, Recepción, Movimientos de Entrada y Actualización del Stock en el Almacén Seleccionado.</span>
                </div>
            </div>

            <form method="post" action="<?= htmlspecialchars(Session::url('factura_historica'), ENT_QUOTES, 'UTF-8') ?>" id="historicalInvoiceForm" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="materiales" id="materialesInput">

                <section class="historical-card">
                    <div class="historical-section-title">
                        <div>
                            <h2><i class="fa-solid fa-file-invoice-dollar"></i> Datos de la Factura</h2>
                            <p>Identifica el Documento y el Destino Contable de la Compra.</p>
                        </div>
                    </div>
                    <div class="historical-fields">
                        <label class="historical-field">
                            <span>Folio Fiscal *</span>
                            <input type="text" name="folio_fiscal" maxlength="100" required
                                   value="<?= htmlspecialchars((string) ($_POST['folio_fiscal'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="UUID o Folio de la Factura">
                        </label>
                        <label class="historical-field">
                            <span>Fecha de Emisión *</span>
                            <input type="date" name="fecha_emision" max="<?= date('Y-m-d') ?>" required
                                   value="<?= htmlspecialchars((string) ($_POST['fecha_emision'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="historical-field">
                            <span>Proveedor *</span>
                            <select name="proveedor_id" required>
                                <option value="">Selecciona un Proveedor...</option>
                                <?php foreach ($proveedores as $proveedor): ?>
                                    <option value="<?= (int) ($proveedor['id'] ?? 0) ?>" <?= (string) ($_POST['proveedor_id'] ?? '') === (string) ($proveedor['id'] ?? '') ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($proveedor['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="historical-field">
                            <span>Proyecto *</span>
                            <select name="proyecto_id" required>
                                <option value="">Selecciona un Proyecto...</option>
                                <?php foreach ($proyectos as $proyecto): ?>
                                    <?php $proyectoEtiqueta = trim((string) ($proyecto['codigo'] ?? '')) !== ''
                                        ? ($proyecto['codigo'] . ' — ' . $proyecto['nombre'])
                                        : ($proyecto['nombre'] ?? ''); ?>
                                    <option value="<?= (int) ($proyecto['id'] ?? 0) ?>" <?= (string) ($_POST['proyecto_id'] ?? '') === (string) ($proyecto['id'] ?? '') ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) $proyectoEtiqueta, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="historical-field">
                            <span>Almacén Destino *</span>
                            <select name="almacen_id" required>
                                <option value="">Selecciona un Almacén...</option>
                                <?php foreach ($almacenes as $almacen): ?>
                                    <option value="<?= (int) ($almacen['id'] ?? 0) ?>" <?= (string) ($_POST['almacen_id'] ?? '') === (string) ($almacen['id'] ?? '') ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($almacen['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                </section>

                <section class="historical-card">
                    <div class="historical-section-title">
                        <div>
                            <h2><i class="fa-solid fa-boxes-stacked"></i> Materiales de la Factura</h2>
                            <p>Busca Productos del Catálogo y Captura la Cantidad y el Precio Pagado.</p>
                        </div>
                        <span class="historical-count" id="itemsCount">0 Partidas</span>
                    </div>

                    <div class="historical-capture-grid">
                        <label class="historical-field historical-search">
                            <span>Buscar en el Catálogo</span>
                            <div>
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" id="productSearch" placeholder="Nombre, Nomenclatura, SKU, Fabricante o Serie">
                            </div>
                        </label>
                        <label class="historical-field historical-product-field">
                            <span>Producto *</span>
                            <select id="productSelect">
                                <option value="">Selecciona un Producto...</option>
                                <?php foreach ($productosCliente as $producto): ?>
                                    <option value="<?= $producto['id'] ?>">
                                        <?= htmlspecialchars($producto['nombre'] . ($producto['nomenclatura'] !== '' ? ' — ' . $producto['nomenclatura'] : ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small><span id="visibleProducts"><?= count($productosCliente) ?></span> Productos Disponibles</small>
                        </label>
                        <label class="historical-field">
                            <span>Cantidad *</span>
                            <input type="number" id="quantityInput" min="0.01" max="99999999.99" step="0.01" placeholder="Ej. 10">
                        </label>
                        <label class="historical-field">
                            <span>Precio Unitario *</span>
                            <input type="number" id="priceInput" min="0.01" max="999999999999.99" step="0.01" placeholder="Ej. 125.50">
                        </label>
                        <button type="button" class="btn-main historical-add" id="addItem">
                            <i class="fa-solid fa-plus"></i> Añadir Partida
                        </button>
                    </div>

                    <div class="historical-empty" id="itemsEmpty">
                        <i class="fa-solid fa-box-open"></i>
                        <strong>Sin Materiales Capturados</strong>
                        <span>Agrega los Productos Incluidos en la Factura.</span>
                    </div>

                    <div class="historical-table-wrap" id="itemsTableWrap" hidden>
                        <table class="historical-table">
                            <thead>
                            <tr>
                                <th>Material</th>
                                <th>Marca / Modelo</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Importe</th>
                                <th>Acción</th>
                            </tr>
                            </thead>
                            <tbody id="itemsBody"></tbody>
                        </table>
                    </div>
                </section>

                <section class="historical-card historical-summary">
                    <div class="historical-total-block">
                        <span>Partidas Capturadas</span>
                        <strong id="summaryItems">0</strong>
                    </div>
                    <div class="historical-total-block historical-total-money">
                        <span>Monto Total</span>
                        <strong id="summaryTotal">$0.00</strong>
                    </div>
                    <div class="historical-summary-note">
                        <i class="fa-solid fa-warehouse"></i>
                        <span>Las Cantidades se Sumarán al Stock del Almacén Destino.</span>
                    </div>
                    <button type="submit" class="btn-main historical-submit">
                        <i class="fa-solid fa-circle-check"></i> Registrar Factura y Entrada
                    </button>
                </section>
            </form>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../layouts/scripts.php'; ?>
<script>
(() => {
    'use strict';

    const products = <?= json_encode($productosCliente, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const initialItems = <?= json_encode($materialesIniciales, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const productMap = new Map(products.map((product) => [String(product.id), product]));
    const items = [];
    const form = document.getElementById('historicalInvoiceForm');
    const productSearch = document.getElementById('productSearch');
    const productSelect = document.getElementById('productSelect');
    const quantityInput = document.getElementById('quantityInput');
    const priceInput = document.getElementById('priceInput');
    const itemsBody = document.getElementById('itemsBody');
    const itemsEmpty = document.getElementById('itemsEmpty');
    const itemsTableWrap = document.getElementById('itemsTableWrap');
    const materialesInput = document.getElementById('materialesInput');

    const money = (value) => Number(value).toLocaleString('es-MX', {
        style: 'currency', currency: 'MXN', minimumFractionDigits: 2, maximumFractionDigits: 2
    });
    const number = (value) => Number(value).toLocaleString('es-MX', { maximumFractionDigits: 2 });

    function alertWarning(title, text) {
        if (window.Swal) {
            Swal.fire({ icon: 'warning', title, text, confirmButtonColor: '#2563eb' });
        } else {
            window.alert(`${title}: ${text}`);
        }
    }

    function normalizedSearch(product) {
        return [product.nombre, product.nomenclatura, product.sku, product.codigo_fabricante, product.numero_serie, product.marca, product.modelo]
            .join(' ').toLocaleLowerCase('es');
    }

    function filterProducts() {
        const query = productSearch.value.trim().toLocaleLowerCase('es');
        let visible = 0;
        Array.from(productSelect.options).forEach((option, index) => {
            if (index === 0) return;
            const product = productMap.get(option.value);
            const matches = product && (!query || normalizedSearch(product).includes(query));
            option.hidden = !matches;
            option.disabled = !matches;
            if (matches) visible += 1;
        });
        if (productSelect.selectedOptions[0]?.disabled) productSelect.value = '';
        document.getElementById('visibleProducts').textContent = String(visible);
    }

    function render() {
        itemsBody.replaceChildren();
        items.forEach((item, index) => {
            const product = productMap.get(String(item.producto_id));
            if (!product) return;
            const row = document.createElement('tr');
            const material = document.createElement('td');
            material.innerHTML = `<strong></strong><span></span>`;
            material.querySelector('strong').textContent = product.nombre;
            material.querySelector('span').textContent = product.nomenclatura || product.sku || 'Sin Código';
            row.appendChild(material);

            [
                [product.marca || 'Sin Marca', product.modelo || 'Sin Modelo'],
                [`${number(item.cantidad)}${product.unidad ? ` ${product.unidad}` : ''}`],
                [money(item.precio_unitario)],
                [money(item.cantidad * item.precio_unitario)]
            ].forEach((parts) => {
                const cell = document.createElement('td');
                parts.forEach((part, partIndex) => {
                    const line = document.createElement(partIndex === 0 ? 'span' : 'small');
                    line.textContent = part;
                    cell.appendChild(line);
                });
                row.appendChild(cell);
            });

            const action = document.createElement('td');
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'historical-remove';
            remove.dataset.index = String(index);
            remove.title = 'Eliminar Partida';
            remove.setAttribute('aria-label', `Eliminar ${product.nombre}`);
            remove.innerHTML = '<i class="fa-solid fa-trash"></i>';
            action.appendChild(remove);
            row.appendChild(action);
            itemsBody.appendChild(row);
        });

        const total = items.reduce((sum, item) => sum + item.cantidad * item.precio_unitario, 0);
        document.getElementById('itemsCount').textContent = `${items.length} Partida${items.length === 1 ? '' : 's'}`;
        document.getElementById('summaryItems').textContent = String(items.length);
        document.getElementById('summaryTotal').textContent = money(total);
        itemsEmpty.hidden = items.length > 0;
        itemsTableWrap.hidden = items.length === 0;
        materialesInput.value = JSON.stringify(items);
    }

    function addItem() {
        const productId = productSelect.value;
        const quantity = Number.parseFloat(quantityInput.value);
        const price = Number.parseFloat(priceInput.value);
        if (!productId || !productMap.has(productId)) {
            alertWarning('Producto Requerido', 'Selecciona un Producto del Catálogo.');
            productSelect.focus();
            return;
        }
        if (items.some((item) => String(item.producto_id) === productId)) {
            alertWarning('Producto Repetido', 'El Producto ya Está Incluido en la Factura.');
            return;
        }
        if (!Number.isFinite(quantity) || quantity <= 0) {
            alertWarning('Cantidad Inválida', 'Captura una Cantidad Mayor a Cero.');
            quantityInput.focus();
            return;
        }
        if (!Number.isFinite(price) || price <= 0) {
            alertWarning('Precio Inválido', 'Captura un Precio Unitario Mayor a Cero.');
            priceInput.focus();
            return;
        }
        items.push({ producto_id: Number(productId), cantidad: quantity, precio_unitario: price });
        productSelect.value = '';
        quantityInput.value = '';
        priceInput.value = '';
        render();
    }

    initialItems.forEach((item) => {
        const productId = String(item?.producto_id ?? '');
        const quantity = Number.parseFloat(item?.cantidad ?? 0);
        const price = Number.parseFloat(item?.precio_unitario ?? 0);
        if (productMap.has(productId) && quantity > 0 && price > 0 && !items.some((existing) => String(existing.producto_id) === productId)) {
            items.push({ producto_id: Number(productId), cantidad: quantity, precio_unitario: price });
        }
    });

    productSearch.addEventListener('input', filterProducts);
    productSelect.addEventListener('change', () => {
        const product = productMap.get(productSelect.value);
        priceInput.value = product?.precio > 0 ? Number(product.precio).toFixed(2) : '';
    });
    document.getElementById('addItem').addEventListener('click', addItem);
    itemsBody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-index]');
        if (!button) return;
        items.splice(Number(button.dataset.index), 1);
        render();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;
        if (items.length === 0) {
            alertWarning('Factura Vacía', 'Agrega al Menos un Material a la Factura.');
            return;
        }
        materialesInput.value = JSON.stringify(items);
        if (!window.Swal) {
            if (window.confirm('¿Registrar la factura y actualizar el inventario?')) HTMLFormElement.prototype.submit.call(form);
            return;
        }
        const result = await Swal.fire({
            icon: 'question',
            title: '¿Registrar Factura Histórica?',
            html: `Se Generará la Entrada de <strong>${items.length} Partida${items.length === 1 ? '' : 's'}</strong> por <strong>${document.getElementById('summaryTotal').textContent}</strong> y se Actualizará el Stock.`,
            showCancelButton: true,
            confirmButtonText: 'Sí, Registrar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#15803d',
            cancelButtonColor: '#64748b',
            reverseButtons: true
        });
        if (result.isConfirmed) HTMLFormElement.prototype.submit.call(form);
    });

    filterProducts();
    render();

    <?php if ($error !== ''): ?>
    if (window.Swal) {
        Swal.fire({
            icon: 'error',
            title: 'No Fue Posible Registrar la Factura',
            text: <?= json_encode($error, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            confirmButtonColor: '#2563eb'
        });
    }
    <?php endif; ?>
})();
</script>
</body>
</html>
