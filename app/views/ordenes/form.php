<?php
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
$isEdit = isset($orden) && !empty($orden['id']);
$titulo = $isEdit ? 'Editar orden de compra' : 'Nueva orden de compra';

$ordenDatos = $orden ?? [
    'proveedor_id' => $_POST['proveedor_id'] ?? '',
    'rfc' => $_POST['rfc'] ?? '',
    'numero_factura' => $_POST['numero_factura'] ?? '',
    'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
    'estado' => $_POST['estado'] ?? 'Pendiente',
    'almacen_destino_id' => $_POST['almacen_destino_id'] ?? '',
    'detalles' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($ordenDatos['detalles'])) {
    // Reconstruir items enviados para mostrar en el formulario
    $tipos = $_POST['item_tipo'] ?? [];
    foreach ($tipos as $idx => $tipo) {
        $ordenDatos['detalles'][] = [
            'tipo_fila' => $tipo,
            'producto_id' => $_POST['item_producto_id'][$idx] ?? '',
            'cantidad' => $_POST['item_cantidad'][$idx] ?? '',
            'precio_unitario' => $_POST['item_costo'][$idx] ?? '',
            'precio_venta' => $_POST['item_precio_venta'][$idx] ?? '',
            'codigo' => $_POST['item_codigo'][$idx] ?? '',
            'nombre' => $_POST['item_nombre'][$idx] ?? '',
            'tipo_producto' => $_POST['item_tipo_producto'][$idx] ?? '',
            'unidad_medida_id' => $_POST['item_unidad'][$idx] ?? '',
            'categoria_id' => $_POST['item_categoria'][$idx] ?? '',
            'stock_minimo' => $_POST['item_stock_minimo'][$idx] ?? '',
        ];
    }
}

if (empty($ordenDatos['detalles'])) {
    $ordenDatos['detalles'][] = [
        'tipo_fila' => 'existente',
        'producto_id' => '',
        'cantidad' => '',
        'precio_unitario' => '',
        'precio_venta' => '',
        'codigo' => '',
        'nombre' => '',
        'tipo_producto' => '',
        'unidad_medida_id' => '',
        'categoria_id' => '',
        'stock_minimo' => '',
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($titulo) ?> Nueva órden de compra | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/inventario_form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .orden-form { padding:32px; }
        .orden-grid { display:grid; gap:20px; grid-template-columns: repeat(auto-fit,minmax(280px,1fr)); margin-bottom:26px; }
        .orden-grid label { display:block; font-weight:600; margin-bottom:6px; color:#2b3d68; }
        .orden-grid input, .orden-grid select, .orden-grid textarea { width:100%; padding:10px 12px; border-radius:10px; border:1px solid #d8dfee; background:#f7f9ff; color:#1b2d56; }
        .items-table { width:100%; border-collapse:collapse; margin-top:16px; }
        .items-table th, .items-table td { border:1px solid #e1e5f2; padding:10px; vertical-align:top; font-size:0.92rem; }
        .items-table th { background:#eef2ff; color:#1e3470; text-transform:uppercase; font-size:0.82rem; }
        .item-row { background:#fff; }
        .item-controls { display:flex; gap:8px; flex-wrap:wrap; }
        .btn-add { display:inline-flex; align-items:center; gap:8px; padding:12px 20px; background:#2563eb; color:#fff; border-radius:10px; border:none; font-weight:700; cursor:pointer; }
        .btn-add:hover { background:#1f4fc6; }
        .btn-secondary { background:#e3e9ff; border-radius:8px; padding:8px 12px; color:#213c7a; cursor:pointer; border:none; font-weight:600; }
        .btn-secondary:hover { background:#cdd8ff; }
        .danger-link { color:#c24141; text-decoration:none; font-weight:600; }
        .danger-link:hover { text-decoration:underline; }
        .form-actions { display:flex; justify-content:space-between; align-items:center; margin-top:30px; flex-wrap:wrap; gap:15px; }
        .alert-error { background:#ffe8e8; border:1px solid #f5c2c7; color:#8a1c1c; padding:14px 18px; border-radius:10px; margin-bottom:20px; }
        .alert-success { background:#e7f7ee; border:1px solid #b8e0c1; color:#1b6d3b; padding:14px 18px; border-radius:10px; margin-bottom:20px; }
        .item-new-group { display:grid; gap:10px; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); margin-top:12px; }
        .item-new-group label { font-weight:600; color:#2f3f6d; font-size:0.85rem; }
        .item-new-group input, .item-new-group select { width:100%; padding:8px 10px; border-radius:8px; border:1px solid #d8dfee; background:#f8faff; }
        @media (max-width:768px) {
            .orden-form { padding:22px 18px; }
            .items-table th, .items-table td { font-size:0.84rem; }
        }
        @media (max-width:640px) {
            .orden-grid { grid-template-columns: 1fr; }
            .form-actions { flex-direction: column; align-items: stretch; }
            .btn-add { width: 100%; justify-content: center; }
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
        <main class="orden-form">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
                <div>
                    <h1 style="margin:0; font-size:2rem; color:#12305f;"><?= htmlspecialchars($titulo) ?></h1>
                    <p style="margin:6px 0 0; color:#61729f;">Captura los datos principales y los productos comprados.</p>
                </div>
                <div>
                    <a href="ordenes_compra.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Volver</a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert-error">
                    <strong>Corrige lo siguiente:</strong>
                    <ul style="margin:8px 0 0 18px;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php elseif (!empty($msg)): ?>
                <div class="alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <form method="post" enctype="application/x-www-form-urlencoded"><input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                <section class="orden-grid">
                    <div>
                        <label for="proveedor_id">Proveedor *</label>
                        <select name="proveedor_id" id="proveedor_id" required>
                            <option value="">Selecciona proveedor</option>
                            <?php foreach ($proveedores as $prov): ?>
                                <option value="<?= (int) $prov['id'] ?>" <?= (int) $ordenDatos['proveedor_id'] === (int) $prov['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prov['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="almacen_destino_id">Almacén destino *</label>
                        <select name="almacen_destino_id" id="almacen_destino_id" required>
                            <option value="">Selecciona almacén</option>
                            <?php foreach ($almacenes as $almacen): ?>
                                <option value="<?= (int) $almacen['id'] ?>" <?= (int) $ordenDatos['almacen_destino_id'] === (int) $almacen['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($almacen['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="fecha">Fecha</label>
                        <input type="date" name="fecha" id="fecha" value="<?= htmlspecialchars(substr($ordenDatos['fecha'], 0, 10)) ?>">
                    </div>
                    <div>
                        <label for="estado">Estado</label>
                        <select name="estado" id="estado">
                            <?php foreach (['Pendiente','Enviada','Recibida','Cancelada'] as $estado): ?>
                                <option value="<?= $estado ?>" <?= ($ordenDatos['estado'] ?? '') === $estado ? 'selected' : '' ?>><?= $estado ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="rfc">RFC (opcional)</label>
                        <input type="text" name="rfc" id="rfc" maxlength="13" value="<?= htmlspecialchars($ordenDatos['rfc'] ?? '') ?>">
                    </div>
                    <div>
                        <label for="numero_factura">Número de factura (opcional)</label>
                        <input type="text" name="numero_factura" id="numero_factura" value="<?= htmlspecialchars($ordenDatos['numero_factura'] ?? '') ?>">
                    </div>
                </section>

                <section>
                    <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                        <h2 style="margin:0; font-size:1.4rem; color:#1c2f59;">Productos</h2>
                        <button type="button" class="btn-add" id="btnAgregarItem"><i class="fa-solid fa-plus"></i> Añadir producto</button>
                    </header>
                    <div class="table-responsive">
                        <table class="items-table" id="tablaItems">
                            <thead>
                            <tr>
                                <th style="width:120px;">Tipo</th>
                                <th style="width:220px;">Producto</th>
                                <th style="width:120px;">Cantidad</th>
                                <th style="width:120px;">Costo unitario</th>
                                <th style="width:120px;">Precio venta</th>
                                <th>Datos producto nuevo</th>
                                <th style="width:60px;"></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($ordenDatos['detalles'] as $idx => $fila): ?>
                                <?php
                                    $tipoFila = strtolower($fila['tipo_fila'] ?? ($fila['producto_id'] ? 'existente' : 'nuevo'));
                                    $productoId = $fila['producto_id'] ?? '';
                                ?>
                                <tr class="item-row" data-index="<?= $idx ?>">
                                    <td>
                                        <select name="item_tipo[]" class="item-tipo">
                                            <option value="existente" <?= $tipoFila === 'existente' ? 'selected' : '' ?>>Existente</option>
                                            <option value="nuevo" <?= $tipoFila === 'nuevo' ? 'selected' : '' ?>>Nuevo</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="item_producto_id[]" class="item-producto" <?= $tipoFila === 'nuevo' ? 'disabled' : '' ?>>
                                            <option value="">Selecciona producto</option>
                                            <?php foreach ($productos as $prod): ?>
                                                <option value="<?= (int) $prod['id'] ?>" <?= (int) $productoId === (int) $prod['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($prod['codigo'] . ' - ' . $prod['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="item_cantidad[]" value="<?= htmlspecialchars($fila['cantidad'] ?? '') ?>" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="item_costo[]" value="<?= htmlspecialchars($fila['precio_unitario'] ?? '') ?>" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="item_precio_venta[]" value="<?= htmlspecialchars($fila['precio_venta'] ?? '') ?>">
                                    </td>
                                    <td>
                                        <div class="item-new-group" <?= $tipoFila === 'existente' ? 'style="display:none;"' : '' ?>>
                                            <div>
                                                <label>Código *</label>
                                                <input type="text" name="item_codigo[]" value="<?= htmlspecialchars($fila['codigo'] ?? '') ?>" <?= $tipoFila === 'nuevo' ? 'required' : '' ?>>
                                            </div>
                                            <div>
                                                <label>Nombre *</label>
                                                <input type="text" name="item_nombre[]" value="<?= htmlspecialchars($fila['nombre'] ?? '') ?>" <?= $tipoFila === 'nuevo' ? 'required' : '' ?>>
                                            </div>
                                            <div>
                                                <label>Tipo *</label>
                                                <select name="item_tipo_producto[]" <?= $tipoFila === 'nuevo' ? 'required' : '' ?>>
                                                    <option value="">Selecciona</option>
                                                    <?php foreach (Producto::tiposDisponibles() as $tipoProd): ?>
                                                        <option value="<?= $tipoProd ?>" <?= ($fila['tipo_producto'] ?? '') === $tipoProd ? 'selected' : '' ?>>
                                                            <?= $tipoProd ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label>Unidad</label>
                                                <select name="item_unidad[]">
                                                    <option value="">N/A</option>
                                                    <?php foreach ($unidades as $unidad): ?>
                                                        <option value="<?= (int) $unidad['id'] ?>" <?= (int) ($fila['unidad_medida_id'] ?? 0) === (int) $unidad['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($unidad['nombre']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label>Categoría</label>
                                                <select name="item_categoria[]">
                                                    <option value="">N/A</option>
                                                    <?php foreach ($categorias as $cat): ?>
                                                        <option value="<?= (int) $cat['id'] ?>" <?= (int) ($fila['categoria_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($cat['nombre']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label>Stock mínimo</label>
                                                <input type="number" step="0.01" min="0" name="item_stock_minimo[]" value="<?= htmlspecialchars($fila['stock_minimo'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </td>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn-secondary btnRemoveRow" title="Eliminar fila"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="form-actions">
                    <a class="danger-link" href="ordenes_compra.php"><i class="fa-solid fa-arrow-left"></i> Cancelar</a>
                    <button type="submit" class="btn-add" style="background:#1f8a4d;"><i class="fa-solid fa-save"></i> Guardar orden</button>
                </div>
            </form>
        </main>
    </div>
</div>

<template id="templateRow">
    <tr class="item-row" data-index="__INDEX__">
        <td>
            <select name="item_tipo[]" class="item-tipo">
                <option value="existente">Existente</option>
                <option value="nuevo">Nuevo</option>
            </select>
        </td>
        <td>
            <select name="item_producto_id[]" class="item-producto">
                <option value="">Selecciona producto</option>
                <?php foreach ($productos as $prod): ?>
                    <option value="<?= (int) $prod['id'] ?>"><?= htmlspecialchars($prod['codigo'] . ' - ' . $prod['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" step="0.01" min="0" name="item_cantidad[]" required></td>
        <td><input type="number" step="0.01" min="0" name="item_costo[]" required></td>
        <td><input type="number" step="0.01" min="0" name="item_precio_venta[]"></td>
        <td>
            <div class="item-new-group" style="display:none;">
                <div>
                    <label>Código *</label>
                    <input type="text" name="item_codigo[]">
                </div>
                <div>
                    <label>Nombre *</label>
                    <input type="text" name="item_nombre[]">
                </div>
                <div>
                    <label>Tipo *</label>
                    <select name="item_tipo_producto[]">
                        <option value="">Selecciona</option>
                        <?php foreach (Producto::tiposDisponibles() as $tipoProd): ?>
                            <option value="<?= $tipoProd ?>"><?= $tipoProd ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Unidad</label>
                    <select name="item_unidad[]">
                        <option value="">N/A</option>
                        <?php foreach ($unidades as $unidad): ?>
                            <option value="<?= (int) $unidad['id'] ?>"><?= htmlspecialchars($unidad['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Categoría</label>
                    <select name="item_categoria[]">
                        <option value="">N/A</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Stock mínimo</label>
                    <input type="number" step="0.01" min="0" name="item_stock_minimo[]">
                </div>
            </div>
        </td>
        <td style="text-align:center;">
            <button type="button" class="btn-secondary btnRemoveRow" title="Eliminar fila"><i class="fa-solid fa-trash"></i></button>
        </td>
    </tr>
</template>

<script>
    (function() {
        const tabla = document.getElementById('tablaItems').querySelector('tbody');
        const template = document.getElementById('templateRow').innerHTML;
        document.getElementById('btnAgregarItem').addEventListener('click', () => {
            const index = tabla.children.length;
            const html = template.replace(/__INDEX__/g, index);
            const temp = document.createElement('tbody');
            temp.innerHTML = html.trim();
            const row = temp.firstElementChild;
            tabla.appendChild(row);
            setupRow(row);
        });

        document.querySelectorAll('.item-row').forEach(setupRow);

        function setupRow(row) {
            const tipoSelect = row.querySelector('.item-tipo');
            const productoSelect = row.querySelector('.item-producto');
            const newGroup = row.querySelector('.item-new-group');
            const newInputs = newGroup ? newGroup.querySelectorAll('input, select') : [];

            const toggle = () => {
                const isNuevo = tipoSelect.value === 'nuevo';
                if (productoSelect) {
                    productoSelect.disabled = isNuevo;
                    if (isNuevo) {
                        productoSelect.value = '';
                    }
                }
                if (newGroup) {
                    newGroup.style.display = isNuevo ? 'grid' : 'none';
                    newInputs.forEach(el => {
                        const label = el.closest('div')?.querySelector('label');
                        const must = label && label.textContent.includes('*');
                        el.required = !!(isNuevo && must);
                        if (!isNuevo) {
                            el.value = '';
                        }
                    });
                }
            };

            tipoSelect.addEventListener('change', toggle);
            toggle();

            const btnRemove = row.querySelector('.btnRemoveRow');
            btnRemove.addEventListener('click', () => {
                if (tabla.children.length === 1) {
                    alert('Debe existir al menos un producto en la orden.');
                    return;
                }
                row.remove();
            });
        }
    })();
</script>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>

