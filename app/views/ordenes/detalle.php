<?php
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
$ordenId = (int) ($orden['id'] ?? 0);
$estado = strtolower($orden['estado'] ?? 'pendiente');
$puedeEditar = in_array($role, ['Administrador', 'Compras'], true) && !in_array($estado, ['recibida', 'cancelada'], true);
$puedeFacturar = in_array($role, ['Administrador', 'Compras', 'Almacen'], true);
$totalDetalle = array_sum(array_map(fn($item) => (float) ($item['cantidad'] ?? 0) * (float) ($item['precio_unitario'] ?? 0), $orden['detalles'] ?? []));
$facturasRelacionadas = $facturasRelacionadas ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de orden #<?= $ordenId ?> | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/reportes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .detalle-main { padding:32px; }
        .summary-grid { display:grid; gap:18px; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); margin-bottom:26px; }
        .summary-card { background:#fff; border:1px solid #e4e8f3; border-radius:14px; padding:20px 22px; box-shadow:0 2px 16px rgba(23,44,87,0.05); }
        .summary-card .label { color:#6373a1; text-transform:uppercase; letter-spacing:.6px; font-size:0.82rem; margin-bottom:6px; display:block; }
        .summary-card .value { font-size:1.6rem; font-weight:800; color:#122c57; }
        .detalle-table { width:100%; border-collapse:collapse; margin-bottom:28px; }
        .detalle-table th, .detalle-table td { border:1px solid #e1e5f2; padding:12px; font-size:0.95rem; text-align:left; }
        .detalle-table th { background:#eef2ff; color:#20356f; text-transform:uppercase; font-size:0.82rem; }
        .badge-estado { display:inline-block; padding:6px 14px; border-radius:999px; font-weight:600; font-size:0.85rem; }
        .badge-pendiente { background:#fff6e5; color:#c17a12; }
        .badge-enviada { background:#e9f4ff; color:#1f6bb0; }
        .badge-recibida { background:#e6f7ed; color:#1a7a4b; }
        .badge-cancelada { background:#ffe8e8; color:#c44545; }
        .alert-success { background:#e7f7ee; border:1px solid #b8e0c1; color:#1b6d3b; padding:14px 18px; border-radius:10px; margin-bottom:20px; }
        .alert-error { background:#ffe8e8; border:1px solid #f5c2c7; color:#8a1c1c; padding:14px 18px; border-radius:10px; margin-bottom:20px; }
        .alert-info { background:#e8f0ff; border:1px solid #c3d4ff; color:#1d3b8b; padding:14px 18px; border-radius:10px; margin-bottom:20px; }
        .actions { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:28px; }
        .btn-secondary { background:#e3e9ff; border-radius:8px; padding:10px 18px; color:#213c7a; text-decoration:none; font-weight:600; }
        .btn-secondary:hover { background:#d2dcff; }
        .btn-action { background:#2563eb; color:#fff; border:none; border-radius:10px; padding:11px 20px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
        .btn-action:hover { background:#1e4dc2; }
        .btn-danger { background:#d94a4a; color:#fff; border:none; border-radius:10px; padding:11px 20px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
        .btn-danger:hover { background:#b93c3c; }
        .facturas-card { background:#fff; border:1px solid #e4e8f3; border-radius:16px; padding:24px; box-shadow:0 2px 16px rgba(23,44,87,0.05); margin-top:32px; }
        .facturas-card header { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
        .facturas-table { width:100%; border-collapse:collapse; }
        .facturas-table th, .facturas-table td { border:1px solid #e1e5f2; padding:12px; font-size:0.92rem; text-align:left; }
        .facturas-table th { background:#eef2ff; text-transform:uppercase; font-size:0.8rem; color:#20356f; }
        .empty-state { padding:18px; border:1px dashed #cbd4f2; border-radius:12px; color:#4b5c92; text-align:center; background:#f8faff; }
        @media (max-width:768px) {
            .detalle-main { padding:22px 18px; }
            .detalle-table th, .detalle-table td { font-size:0.85rem; }
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
        <main class="detalle-main">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
                <div>
                    <h1 style="margin:0; font-size:2rem; color:#12305f;">Orden #<?= $ordenId ?></h1>
                    <p style="margin:6px 0 0; color:#61729f;">Proveedor: <?= htmlspecialchars($orden['proveedor'] ?? 'Desconocido') ?></p>
                </div>
                <div class="actions">
                    <a href="ordenes_compra.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Volver</a>
                    <?php if ($puedeEditar): ?>
                        <a href="ordenes_compra_editar.php?id=<?= $ordenId ?>" class="btn-secondary"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
                    <?php endif; ?>
                    <?php if ($puedeFacturar): ?>
                        <a href="facturas_create.php?orden_id=<?= $ordenId ?>&proveedor_id=<?= (int) ($orden['proveedor_id'] ?? 0) ?>" class="btn-action"><i class="fa-solid fa-file-invoice-dollar"></i> Registrar factura</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($_GET['created'])): ?>
                <div class="alert-success"><i class="fa-solid fa-circle-check"></i> Orden registrada correctamente.</div>
            <?php endif; ?>
            <?php if (!empty($_GET['updated'])): ?>
                <div class="alert-success"><i class="fa-solid fa-circle-check"></i> Orden actualizada correctamente.</div>
            <?php endif; ?>
            <?php if (!empty($_GET['received'])): ?>
                <div class="alert-success"><i class="fa-solid fa-box-open"></i> Estado actualizado. Recuerda registrar la factura para ingresar el inventario.</div>
            <?php endif; ?>
            <?php if (!empty($_GET['cancelled'])): ?>
                <div class="alert-success"><i class="fa-solid fa-ban"></i> Orden cancelada correctamente.</div>
            <?php endif; ?>
            <?php if (!empty($_GET['locked'])): ?>
                <div class="alert-error"><i class="fa-solid fa-circle-info"></i> La orden no puede editarse porque esta cerrada.</div>
            <?php endif; ?>
            <?php if (!empty($msg)): ?>
                <div class="alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="alert-info">
                <i class="fa-solid fa-circle-info"></i>
                Las órdenes de compra no ingresan inventario automaticamente. Utiliza el botón <strong>Registrar factura</strong> para dar de alta la mercancía en almacén.
            </div>

            <section class="summary-grid">
                <div class="summary-card">
                    <span class="label">Estado</span>
                    <?php
                        $badge = match($estado) {
                            'enviada' => 'badge-enviada',
                            'recibida' => 'badge-recibida',
                            'cancelada' => 'badge-cancelada',
                            default => 'badge-pendiente'
                        };
                    ?>
                    <span class="value"><span class="badge-estado <?= $badge ?>"><?= htmlspecialchars($orden['estado'] ?? 'Pendiente') ?></span></span>
                </div>
                <div class="summary-card">
                    <span class="label">Fecha</span>
                    <span class="value"><?= date('d/m/Y H:i', strtotime($orden['fecha'])) ?></span>
                </div>
                <div class="summary-card">
                    <span class="label">Total</span>
                    <span class="value">$<?= number_format((float) ($orden['total'] ?? $totalDetalle), 2) ?></span>
                </div>
                <div class="summary-card">
                    <span class="label">Almacen destino</span>
                    <span class="value" style="font-size:1.1rem;"><?= htmlspecialchars($orden['almacen_destino'] ?? '-') ?></span>
                </div>
                <div class="summary-card">
                    <span class="label">RFC</span>
                    <span class="value" style="font-size:1.1rem;"><?= htmlspecialchars($orden['rfc'] ?? '-') ?></span>
                </div>
                <div class="summary-card">
                    <span class="label">Factura proveedor</span>
                    <span class="value" style="font-size:1.1rem;"><?= htmlspecialchars($orden['numero_factura'] ?? '-') ?></span>
                </div>
            </section>

            <section>
                <h2 style="margin:0 0 14px; color:#1b2f58;">Detalle de productos</h2>
                <div class="reportes-table-wrapper">
                    <table class="detalle-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th> Código </th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Costo unitario</th>
                            <th>Subtotal</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($orden['detalles'])): ?>
                            <tr><td colspan="7" style="text-align:center; padding:22px; color:#7a88b2;">Sin productos registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orden['detalles'] as $idx => $detalle): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><?= htmlspecialchars($detalle['codigo_producto'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($detalle['producto'] ?? 'Producto #' . $detalle['producto_id']) ?></td>
                                    <td><?= htmlspecialchars($detalle['tipo_producto'] ?? '-') ?></td>
                                    <td><?= number_format((float) ($detalle['cantidad'] ?? 0), 2) ?></td>
                                    <td>$<?= number_format((float) ($detalle['precio_unitario'] ?? 0), 2) ?></td>
                                    <td>$<?= number_format((float) ($detalle['cantidad'] ?? 0) * (float) ($detalle['precio_unitario'] ?? 0), 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th colspan="6" style="text-align:right;">Total</th>
                            <th>$<?= number_format($totalDetalle, 2) ?></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <section class="facturas-card" id="facturas">
                <header>
                    <div>
                        <h2 style="margin:0; color:#1b2f58;">Facturas asociadas</h2>
                        <p style="margin:6px 0 0; color:#5c6c97;">Cada factura suma inventario en el almacén seleccionado.</p>
                    </div>
                    <?php if ($puedeFacturar): ?>
                        <a href="facturas_create.php?orden_id=<?= $ordenId ?>&proveedor_id=<?= (int) ($orden['proveedor_id'] ?? 0) ?>" class="btn-action" style="padding:10px 18px;"><i class="fa-solid fa-file-circle-plus"></i> Nueva factura</a>
                    <?php endif; ?>
                </header>
                <?php if (empty($facturasRelacionadas)): ?>
                    <div class="empty-state">
                        No hay facturas registradas para esta orden. Usa el botón "Nueva factura" para agregar una y actualizar el inventario.
                    </div>
                <?php else: ?>
                    <div class="reportes-table-wrapper">
                        <table class="facturas-table">
                            <thead>
                            <tr>
                                <th>Factura</th>
                                <th>Fecha</th>
                                <th>Subtotal</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($facturasRelacionadas as $factura): ?>
                                <tr>
                                    <td><?= htmlspecialchars($factura['numero_factura'] ?: 'Sin folio') ?></td>
                                    <td><?= date('d/m/Y', strtotime($factura['fecha'])) ?></td>
                                    <td>$<?= number_format((float) ($factura['subtotal'] ?? 0), 2) ?></td>
                                    <td>$<?= number_format((float) ($factura['total'] ?? 0), 2) ?></td>
                                    <td style="display:flex; gap:10px; flex-wrap:wrap;">
                                        <a class="btn-secondary" style="padding:6px 12px;" href="facturas_detalle.php?id=<?= (int) $factura['id'] ?>"><i class="fa-solid fa-eye"></i> Ver</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <?php if (in_array($role, ['Administrador', 'Compras'], true) && $estado === 'pendiente'): ?>
                <section style="margin-top:24px;">
                    <form method="post" data-confirm="Cancelar la orden? Esta accion no afecta el inventario.">
                        <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                        <input type="hidden" name="accion" value="cancelar">
                        <button type="submit" class="btn-danger"><i class="fa-solid fa-ban"></i> Cancelar orden</button>
                    </form>
                </section>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
