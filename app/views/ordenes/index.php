<?php
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
$ordenes = $historial['ordenes'] ?? [];
$detalles = $historial['detalles'] ?? [];

$totalOrdenes = count($ordenes);
$importeTotal = array_sum(array_map(fn($row) => (float) ($row['total'] ?? 0), $ordenes));

$queryWith = function(array $overrides = []) {
    $params = array_merge($_GET, $overrides);
    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        }
    }
    return $params ? '?' . http_build_query($params) : '?';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Órdenes de compra | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/reportes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .ordenes-main { padding: 32px 32px 48px; }
        .ordenes-header { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; margin-bottom:26px; }
        .ordenes-header h1 { margin:0; font-size:2rem; color:#12305f; }
        .ordenes-header .acciones { display:flex; gap:12px; flex-wrap:wrap; }
        .btn-primary { background:#2563eb; color:#fff; border-radius:10px; padding:11px 22px; text-decoration:none; font-weight:700; display:inline-flex; align-items:center; gap:8px; }
        .btn-primary:hover { background:#1e4dc2; }
        .ordenes-filters { background:#fff; border-radius:16px; padding:24px 26px; border:1px solid #e4e8f3; box-shadow:0 2px 16px rgba(23,44,87,0.05); margin-bottom:26px; }
        .ordenes-filters form { display:grid; gap:18px; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); }
        .ordenes-filters label { font-weight:600; color:#3a4a7a; margin-bottom:6px; display:block; }
        .ordenes-filters input, .ordenes-filters select { padding:10px 12px; border-radius:9px; border:1px solid #d6dbea; background:#fafbff; color:#1a2c51; width:100%; }
        .ordenes-filters .filter-actions { display:flex; gap:10px; align-items:center; }
        .ordenes-summary { display:grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap:18px; margin-bottom:26px; }
        .ordenes-card { background:#fff; border-radius:14px; padding:22px 24px; border:1px solid #e4e8f3; box-shadow:0 2px 16px rgba(23,44,87,0.05); display:flex; flex-direction:column; gap:6px; }
        .ordenes-card .label { font-size:0.9rem; color:#6575a1; text-transform:uppercase; letter-spacing:.6px; }
        .ordenes-card .value { font-size:1.8rem; font-weight:800; color:#142b55; }
        .ordenes-table { width:100%; border-collapse:collapse; min-width:980px; }
        .ordenes-table th, .ordenes-table td { padding:12px 14px; border-bottom:1px solid #edf0f6; text-align:left; font-size:0.95rem; color:#1a2c51; vertical-align:top; }
        .ordenes-table th { background:#f3f6fc; text-transform:uppercase; letter-spacing:.4px; color:#5a6a94; font-size:0.88rem; }
        .badge-estado { display:inline-block; padding:4px 12px; border-radius:999px; font-weight:600; font-size:0.82rem; }
        .badge-pendiente { background:#fff6e5; color:#c17a12; }
        .badge-enviada { background:#e9f4ff; color:#1f6bb0; }
        .badge-recibida { background:#e6f7ed; color:#1a7a4b; }
        .badge-cancelada { background:#ffe8e8; color:#c44545; }
        .ordenes-acciones { display:flex; gap:8px; flex-wrap:wrap; }
        .btn-link { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:8px; background:#eef2ff; color:#1d3d7a; text-decoration:none; font-size:0.85rem; }
        .btn-link:hover { background:#dbe1ff; }
        .ordenes-detalle { background:#f9fbff; border-left:3px solid #d6e3ff; }
        @media (max-width:768px) {
            .ordenes-main { padding:22px 18px 36px; }
            .ordenes-header { flex-direction:column; align-items:flex-start; }
            .ordenes-table { min-width:auto; }
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
        <main class="ordenes-main">
            <div class="ordenes-header">
                <div>
                    <h1>Órdenes de compra</h1>
                    <p style="margin:6px 0 0; color:#61729f;">Gestiona la creacion y seguimiento de compras. El inventario se incrementa cuando registras la factura.</p>
                </div>
                <?php if (in_array($role, ['Administrador', 'Compras', 'Almacen'], true)): ?>
                <div class="acciones">
                    <a class="btn-primary" href="ordenes_compra_crear.php"><i class="fa-solid fa-plus"></i> Nueva Orden</a>
                </div>
                <?php endif; ?>
            </div>

            <section class="ordenes-filters">
                <form method="get">
                    <div>
                        <label for="proveedor_id">Proveedor</label>
                        <select name="proveedor_id" id="proveedor_id">
                            <option value="">Todos</option>
                            <?php foreach ($proveedores as $prov): ?>
                                <option value="<?= (int) $prov['id'] ?>" <?= ($filtros['proveedor_id'] ?? '') == $prov['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prov['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="estado">Estado</label>
                        <select name="estado" id="estado">
                            <option value="">Todos</option>
                            <?php foreach ($estados as $estado): ?>
                                <option value="<?= $estado ?>" <?= ($filtros['estado'] ?? '') === $estado ? 'selected' : '' ?>>
                                    <?= $estado ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="desde">Desde</label>
                        <input type="date" name="desde" id="desde" value="<?= htmlspecialchars($filtros['desde'] ?? '') ?>">
                    </div>
                    <div>
                        <label for="hasta">Hasta</label>
                        <input type="date" name="hasta" id="hasta" value="<?= htmlspecialchars($filtros['hasta'] ?? '') ?>">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-primary" style="padding:10px 18px;"><i class="fa-solid fa-magnifying-glass"></i> Aplicar</button>
                        <a href="ordenes_compra.php" class="btn-link"><i class="fa-solid fa-rotate-left"></i> Limpiar</a>
                    </div>
                </form>
            </section>    

            <section class="ordenes-summary">
                <div class="ordenes-card">
                    <span class="label">Órdenes</span>
                    <span class="value"><?= number_format($totalOrdenes) ?></span>
                </div>
                <div class="ordenes-card">
                    <span class="label">Importe registrado</span>
                    <span class="value">$<?= number_format($importeTotal, 2) ?></span>
                </div>
            </section>

            <section>
                <div class="reportes-table-wrapper">
                    <table class="ordenes-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>RFC</th>
                            <th>Factura</th>
                            <th>Estado</th>
                            <th>Total</th>
                            <th>Almacén destino</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($ordenes)): ?>
                            <tr>
                                <td colspan="9" style="text-align:center; padding:24px; color:#7d8bb0;">Sin resultados con los filtros seleccionados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ordenes as $orden): ?>
                                <?php
                                    $estado = strtolower($orden['estado'] ?? 'Pendiente');
                                    $badge = match($estado) {
                                        'enviada' => 'badge-enviada',
                                        'recibida' => 'badge-recibida',
                                        'cancelada' => 'badge-cancelada',
                                        default => 'badge-pendiente'
                                    };
                                ?>
                                <tr>
                                    <td>#<?= (int) $orden['id'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($orden['fecha'])) ?></td>
                                    <td><?= htmlspecialchars($orden['proveedor'] ?? 'Desconocido') ?></td>
                                    <td><?= htmlspecialchars($orden['rfc'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($orden['numero_factura'] ?? '-') ?></td>
                                    <td><span class="badge-estado <?= $badge ?>"><?= htmlspecialchars($orden['estado']) ?></span></td>
                                    <td>$<?= number_format((float) ($orden['total'] ?? 0), 2) ?></td>
                                    <td><?= htmlspecialchars($orden['almacen_destino'] ?? '-') ?></td>
                                    <td class="ordenes-acciones">
                                        <a class="btn-link" href="ordenes_compra_detalle.php?id=<?= (int) $orden['id'] ?>"><i class="fa-solid fa-eye"></i> Ver</a>
                                        <?php if (in_array($role, ['Administrador', 'Compras'], true) && !in_array($estado, ['recibida', 'cancelada'], true)): ?>
                                            <a class="btn-link" href="ordenes_compra_editar.php?id=<?= (int) $orden['id'] ?>"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
                                        <?php endif; ?>
                                        <?php if (in_array($role, ['Administrador', 'Compras', 'Almacen'], true) && $estado !== 'cancelada'): ?>
                                            <a class="btn-link" href="facturas_create.php?orden_id=<?= (int) $orden['id'] ?>&proveedor_id=<?= (int) $orden['proveedor_id'] ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Registrar factura</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if (!empty($detalles[$orden['id']])): ?>
                                    <tr class="ordenes-detalle">
                                        <td colspan="9">
                                            <strong>Detalle:</strong>
                                            <ul style="margin:8px 0 0 20px; color:#3d4b76; font-size:0.9rem;">
                                                <?php foreach ($detalles[$orden['id']] as $detalle): ?>
                                                    <li>
                                                        <?= htmlspecialchars($detalle['codigo_producto'] ?? '-') ?> - <?= htmlspecialchars($detalle['producto'] ?? ('Producto #' . $detalle['producto_id'])) ?>
                                                        &nbsp;|&nbsp; Cantidad: <?= number_format((float) $detalle['cantidad'], 2) ?>
                                                        &nbsp;|&nbsp; Costo: $<?= number_format((float) $detalle['precio_unitario'], 2) ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
