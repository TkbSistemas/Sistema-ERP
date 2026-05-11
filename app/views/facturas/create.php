<?php
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
$items = $facturaData['items'] ?? [];
if (empty($items)) {
    $items = [[
        'producto_id'   => '',
        'cantidad'      => '',
        'costo_unitario'=> '',
        'impuesto'      => '',
    ]];
}
$previewSubtotal = 0;
$previewImpuestos = 0;
foreach ($items as $item) {
    $cant = (float) ($item['cantidad'] ?? 0);
    $cost = (float) ($item['costo_unitario'] ?? 0);
    $sub  = $cant * $cost;
    $tax  = $sub * ((float) ($item['impuesto'] ?? 0) / 100);
    $previewSubtotal += $sub;
    $previewImpuestos += $tax;
}
$previewTotal = $previewSubtotal + $previewImpuestos;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar factura | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .factura-form { padding:32px 32px 48px; }
        .factura-head { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
        .factura-head h1 { margin:0; font-size:2rem; color:#12305f; }
        .factura-head p { margin:6px 0 0; color:#61729f; }
        .btn-secondary { background:#e3e9ff; border-radius:8px; padding:10px 18px; color:#213c7a; text-decoration:none; font-weight:600; }
        .btn-secondary:hover { background:#cdd8ff; }
        .btn-primary { background:#2563eb; color:#fff; border:none; border-radius:10px; padding:12px 22px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
        .btn-primary:hover { background:#1e4dc2; }
        .alert-info { background:#e8f0ff; border:1px solid #c3d4ff; color:#1d3b8b; padding:14px 18px; border-radius:10px; margin-bottom:20px; }
        .alert-error { background:#ffe8e8; border:1px solid #f5c2c7; color:#8a1c1c; padding:14px 18px; border-radius:10px; margin-bottom:20px; }
        .form-grid { display:grid; gap:18px; grid-template-columns: repeat(auto-fit,minmax(240px,1fr)); margin-bottom:24px; }
        .form-grid label { display:block; font-weight:600; margin-bottom:6px; color:#2b3d68; }
        .form-grid input, .form-grid select, .form-grid textarea { width:100%; padding:10px 12px; border-radius:10px; border:1px solid #d8dfee; background:#f7f9ff; color:#1b2d56; }
        textarea { min-height:90px; resize:vertical; }
        .items-card { background:#fff; border-radius:16px; border:1px solid #e4e8f3; padding:20px 22px; box-shadow:0 2px 16px rgba(23,44,87,0.05); }
        .items-card header { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
        .items-table { width:100%; border-collapse:collapse; margin-top:16px; }
        .items-table th, .items-table td { border:1px solid #e1e5f2; padding:10px; vertical-align:top; font-size:0.92rem; }
        .items-table th { background:#eef2ff; color:#1e3470; text-transform:uppercase; font-size:0.82rem; }
        .btn-add { display:inline-flex; align-items:center; gap:8px; padding:10px 16px; background:#2563eb; color:#fff; border-radius:10px; border:none; font-weight:600; cursor:pointer; }
        .btn-add:hover { background:#1f4fc6; }
        .btn-icon { background:#eef2ff; border:none; color:#1d3d7a; padding:8px 10px; border-radius:8px; cursor:pointer; }
        .btn-icon:hover { background:#dbe1ff; }
        .summary-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap:14px; margin-top:20px; }
        .summary-card { background:#f7f9ff; border-radius:12px; border:1px solid #e0e6f7; padding:16px 18px; }
        .summary-card .label { font-size:0.85rem; color:#5b6a94; text-transform:uppercase; letter-spacing:.5px; }
        .summary-card .value { font-size:1.5rem; font-weight:700; color:#12305f; }
        .form-actions { display:flex; justify-content:space-between; align-items:center; margin-top:30px; flex-wrap:wrap; gap:16px; }
        .danger-link { color:#c24141; text-decoration:none; font-weight:600; }
        .danger-link:hover { text-decoration:underline; }
        @media (max-width:768px) {
            .factura-form { padding:22px 18px 36px; }
            .items-table th, .items-table td { font-size:0.85rem; }
            .form-actions { flex-direction:column; align-items:stretch; }
            .btn-primary { width:100%; justify-content:center; }
        }
    </style>
</head>
<body>
<div class="main-layout">
   <aside class="sidebar">
        <div class="sidebar-header">
            <div class="login-logo"><img src="assets/images/icono_takab.png" alt="logo_TAKAB" width="90" height="55"></div>
            <div>
                <div class="sidebar-title">TAKAB</div>
                <div class="sidebar-desc">Compras</div>
            </div>
        </div>
        <nav class="sidebar-nav">     
            <?php if ($role === 'Administrador'): ?>
                <a href="ordenes_compra.php"><i class="fa-solid fa-file-invoice-dollar"></i> Órdenes de compra</a>
                <a href="facturas.php"><i class="fa-solid fa-file-circle-check"></i> Facturas de compra</a>
                <a href="ordenes_compra_crear.php"><i class="fa-solid fa-plus"></i> Nueva Orden</a>
                <a href="proveedores.php"><i class="fa-solid fa-address-book"></i> Proveedores</a>
                <a href="compras_proveedor.php"><i class="fa-solid fa-shopping-cart"></i> Compras por proveedor</a>
            <?php elseif ($role === 'Almacen'): ?>
                <a href="ordenes_compra.php"><i class="fa-solid fa-file-invoice-dollar"></i> Órdenes de compra</a>
                <a href="facturas.php"><i class="fa-solid fa-file-circle-check"></i> Facturas de compra</a>
                <a href="ordenes_compra_crear.php"><i class="fa-solid fa-plus"></i> Nueva Orden</a>
                <a href="proveedores.php"><i class="fa-solid fa-address-book"></i> Proveedores</a>
                <a href="compras_proveedor.php"><i class="fa-solid fa-shopping-cart"></i> Compras por proveedor</a>
            <?php elseif ($role === 'Compras'): ?>
                <a href="ordenes_compra.php"><i class="fa-solid fa-file-invoice-dollar"></i> Órdenes de compra</a>
                <a href="facturas.php"><i class="fa-solid fa-file-circle-check"></i> Facturas de compra</a>
                <a href="ordenes_compra_crear.php"><i class="fa-solid fa-plus"></i> Nueva Orden</a>
                <a href="proveedores.php"><i class="fa-solid fa-address-book"></i> Proveedores</a>
                <a href="compras_proveedor.php"><i class="fa-solid fa-shopping-cart"></i> Compras por proveedor</a>
            <?php endif; ?>
            <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>
    <div class="content-area">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <main class="factura-form">
            <div class="factura-head">
                <div>
                    <h1>Registrar factura</h1>
                    <p>Integra la mercancia recibida al inventario.</p>
                </div>
                <a href="facturas.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Volver</a>
            </div>

            <div class="alert-info"><i class="fa-solid fa-circle-info"></i> Las órdenes de compra solo registran compromisos. Usa esta pantalla para sumar productos al inventario.</div>

            <?php if (!empty($errors)): ?>
                <div class="alert-error">
                    <strong>Corrige lo siguiente:</strong>
                    <ul style="margin:8px 0 0 18px;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                <section class="form-grid">
                    <div>
                        <label for="numero_factura">Número de factura</label>
                        <input type="text" name="numero_factura" id="numero_factura" value="<?= htmlspecialchars($facturaData['numero_factura'] ?? '') ?>" placeholder="Folio del proveedor">
                    </div>
                    <div>
                        <label for="fecha">Fecha</label>
                        <input type="date" name="fecha" id="fecha" value="<?= htmlspecialchars($facturaData['fecha'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div>
                        <label for="orden_id">Orden de compra (opcional)</label>
                        <select name="orden_id" id="orden_id">
                            <option value="">Sin orden asociada</option>
                            <?php foreach ($ordenes as $orden): ?>
                                <option value="<?= (int) $orden['id'] ?>"
                                        data-proveedor="<?= (int) $orden['proveedor_id'] ?>"
                                        data-almacen="<?= (int) ($orden['almacen_destino_id'] ?? 0) ?>"
                                        <?= (int) ($facturaData['orden_id'] ?? 0) === (int) $orden['id'] ? 'selected' : '' ?>>
                                    #<?= (int) $orden['id'] ?>  -  <?= htmlspecialchars($orden['proveedor'] ?? 'Proveedor') ?> (<?= date('d/m/Y', strtotime($orden['fecha'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="proveedor_id">Proveedor *</label>
                        <select name="proveedor_id" id="proveedor_id" required>
                            <option value="">Selecciona proveedor</option>
                            <?php foreach ($proveedores as $prov): ?>
                                <option value="<?= (int) $prov['id'] ?>" <?= (int) ($facturaData['proveedor_id'] ?? 0) === (int) $prov['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prov['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="almacen_id">Almacén que recibe *</label>
                        <select name="almacen_id" id="almacen_id" required>
                            <option value="">Selecciona almacén</option>
                            <?php foreach ($almacenes as $alm): ?>
                                <option value="<?= (int) $alm['id'] ?>" <?= (int) ($facturaData['almacen_id'] ?? 0) === (int) $alm['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($alm['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="grid-column: span 2;">
                        <label for="notas">Notas</label>
                        <textarea name="notas" id="notas" placeholder="Observaciones internas"><?= htmlspecialchars($facturaData['notas'] ?? '') ?></textarea>
                    </div>
                </section>

                <section class="items-card">
                    <header>
                        <h2 style="margin:0; font-size:1.3rem; color:#1c2f59;">Productos</h2>
                        <button type="button" class="btn-add" id="btnAddItem"><i class="fa-solid fa-plus"></i> Agregar producto</button>
                    </header>
                    <div class="table-responsive">
                        <table class="items-table" id="itemsTable">
                            <thead>
                            <tr>
                                <th style="width:320px;">Producto *</th>
                                <th style="width:120px;">Cantidad *</th>
                                <th style="width:140px;">Costo unitario *</th>
                                <th style="width:120px;">Impuesto %</th>
                                <th style="width:140px;">Total linea</th>
                                <th style="width:60px;"></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($items as $idx => $item): ?>
                                <?php $lineTotal = (float) ($item['cantidad'] ?? 0) * (float) ($item['costo_unitario'] ?? 0);
                                      $lineTotal += $lineTotal * ((float) ($item['impuesto'] ?? 0) / 100);
                                ?>
                                <tr data-row="<?= $idx ?>">
                                    <td>
                                        <select name="item_producto_id[]" required>
                                            <option value="">Selecciona producto</option>
                                            <?php foreach ($productos as $prod): ?>
                                                <option value="<?= (int) $prod['id'] ?>" <?= (int) ($item['producto_id'] ?? 0) === (int) $prod['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($prod['codigo'] . '  -  ' . $prod['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="number" name="item_cantidad[]" min="0" step="0.01" value="<?= htmlspecialchars($item['cantidad'] ?? '') ?>" required></td>
                                    <td><input type="number" name="item_costo[]" min="0" step="0.01" value="<?= htmlspecialchars($item['costo_unitario'] ?? '') ?>" required></td>
                                    <td><input type="number" name="item_impuesto[]" min="0" step="0.01" value="<?= htmlspecialchars($item['impuesto'] ?? '') ?>"></td>
                                    <td><strong class="line-total">$<?= number_format($lineTotal, 2) ?></strong></td>
                                    <td style="text-align:center;"><button type="button" class="btn-icon btnRemove"><i class="fa-solid fa-trash"></i></button></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="summary-grid" id="totalesPreview">
                        <div class="summary-card">
                            <span class="label">Subtotal</span>
                            <span class="value" data-field="subtotal">$<?= number_format($previewSubtotal, 2) ?></span>
                        </div>
                        <div class="summary-card">
                            <span class="label">Impuestos</span>
                            <span class="value" data-field="impuestos">$<?= number_format($previewImpuestos, 2) ?></span>
                        </div>
                        <div class="summary-card">
                            <span class="label">Total estimado</span>
                            <span class="value" data-field="total">$<?= number_format($previewTotal, 2) ?></span>
                        </div>
                    </div>
                </section>

                <div class="form-actions">
                    <a class="danger-link" href="facturas.php"><i class="fa-solid fa-arrow-left"></i> Cancelar</a>
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Guardar factura</button>
                </div>
            </form>
        </main>
    </div>
</div>

<template id="itemTemplate">
    <tr>
        <td>
            <select name="item_producto_id[]" required>
                <option value="">Selecciona producto</option>
                <?php foreach ($productos as $prod): ?>
                    <option value="<?= (int) $prod['id'] ?>"><?= htmlspecialchars($prod['codigo'] . '  -  ' . $prod['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" name="item_cantidad[]" min="0" step="0.01" required></td>
        <td><input type="number" name="item_costo[]" min="0" step="0.01" required></td>
        <td><input type="number" name="item_impuesto[]" min="0" step="0.01"></td>
        <td><strong class="line-total">$0.00</strong></td>
        <td style="text-align:center;"><button type="button" class="btn-icon btnRemove"><i class="fa-solid fa-trash"></i></button></td>
    </tr>
</template>

<script>
(function(){
    const ordenSelect = document.getElementById('orden_id');
    const proveedorSelect = document.getElementById('proveedor_id');
    const almacenSelect = document.getElementById('almacen_id');
    const numeroFacturaInput = document.getElementById('numero_factura');
    const tableBody = document.querySelector('#itemsTable tbody');
    const template = document.getElementById('itemTemplate');
    const btnAdd = document.getElementById('btnAddItem');
    const totalsBox = document.getElementById('totalesPreview');
    let lastOrdenId = ordenSelect ? ordenSelect.value : '';

    function applyOrdenDefaults(){
        const option = ordenSelect.options[ordenSelect.selectedIndex];
        if (!option || !option.value) { return; }
        const proveedor = option.getAttribute('data-proveedor');
        const almacen = option.getAttribute('data-almacen');
        if (proveedor && proveedorSelect)
            proveedorSelect.value = proveedor;
        if (almacen && almacenSelect && almacen !== '0')
            almacenSelect.value = almacen;
    }
    function buildRedirectUrl(ordenId){
        const params = new URLSearchParams();
        if (ordenId) {
            params.set('orden_id', ordenId);
        }
        const numero = numeroFacturaInput ? numeroFacturaInput.value.trim() : '';
        if (numero) {
            params.set('numero', numero);
        }
        return 'facturas_create.php' + (params.toString() ? ('?' + params.toString()) : '');
    }

    function hasItemData(){
        return Array.from(tableBody.querySelectorAll('tr')).some(row => {
            const prod = row.querySelector('select[name="item_producto_id[]"]')?.value || '';
            const qty = row.querySelector('input[name="item_cantidad[]"]')?.value || '';
            const cost = row.querySelector('input[name="item_costo[]"]')?.value || '';
            return prod !== '' || qty !== '' || cost !== '';
        });
    }

    function handleOrdenChange(){
        const selected = ordenSelect.value;
        if (selected === lastOrdenId) {
            return;
        }
        const shouldReload = hasItemData();
        if (shouldReload) {
            const ok = confirm('Al cambiar la orden se recargara la pagina y se reemplazaran los productos actuales. ¿Deseas continuar?');
            if (!ok) {
                ordenSelect.value = lastOrdenId;
                return;
            }
        }
        window.location.href = buildRedirectUrl(selected);
    }

    if (ordenSelect) {
        ordenSelect.addEventListener('change', () => {
            applyOrdenDefaults();
            handleOrdenChange();
        });
    }

    function recalc(){
        let subtotal = 0;
        let impuestos = 0;
        tableBody.querySelectorAll('tr').forEach(row => {
            const qty = parseFloat(row.querySelector('input[name="item_cantidad[]"]').value) || 0;
            const cost = parseFloat(row.querySelector('input[name="item_costo[]"]').value) || 0;
            const taxPct = parseFloat(row.querySelector('input[name="item_impuesto[]"]').value) || 0;
            const lineBase = qty * cost;
            const lineTax = lineBase * (taxPct / 100);
            const total = lineBase + lineTax;
            row.querySelector('.line-total').textContent = '$' + total.toFixed(2);
            subtotal += lineBase;
            impuestos += lineTax;
        });
        totalsBox.querySelector('[data-field="subtotal"]').textContent = '$' + subtotal.toFixed(2);
        totalsBox.querySelector('[data-field="impuestos"]').textContent = '$' + impuestos.toFixed(2);
        totalsBox.querySelector('[data-field="total"]').textContent = '$' + (subtotal + impuestos).toFixed(2);
    }

    function attachRowEvents(row){
        row.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', recalc);
        });
        const removeBtn = row.querySelector('.btnRemove');
        removeBtn.addEventListener('click', () => {
            if (tableBody.children.length === 1) {
                alert('Debe existir al menos un producto.');
                return;
            }
            row.remove();
            recalc();
        });
    }

    tableBody.querySelectorAll('tr').forEach(attachRowEvents);
    recalc();

    btnAdd.addEventListener('click', () => {
        const clone = template.content.firstElementChild.cloneNode(true);
        attachRowEvents(clone);
        tableBody.appendChild(clone);
    });
})();
</script>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
