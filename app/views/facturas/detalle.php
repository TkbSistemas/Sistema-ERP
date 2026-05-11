<?php
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
$detalles = $factura['detalles'] ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #<?= (int) $factura['id'] ?> | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/reportes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .detalle-main { padding:32px 32px 48px; }
        .detalle-head { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
        .detalle-head h1 { margin:0; font-size:2rem; color:#12305f; }
        .detalle-head p { margin:6px 0 0; color:#61729f; }
        .btn-secondary { background:#e3e9ff; border-radius:8px; padding:10px 18px; color:#213c7a; text-decoration:none; font-weight:600; }
        .btn-secondary:hover { background:#cdd8ff; }
        .btn-link { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:8px; background:#eef2ff; color:#1d3d7a; text-decoration:none; font-size:0.9rem; }
        .btn-link:hover { background:#dbe1ff; }
        .alert-success { background:#e7f7ee; border:1px solid #b8e0c1; color:#1b6d3b; padding:14px 18px; border-radius:10px; margin-bottom:20px; }
        .alert-info { background:#e8f0ff; border:1px solid #c3d4ff; color:#1d3b8b; padding:14px 18px; border-radius:10px; margin-bottom:20px; }
        .summary-grid { display:grid; gap:18px; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); margin-bottom:26px; }
        .summary-card { background:#fff; border:1px solid #e4e8f3; border-radius:14px; padding:20px 22px; box-shadow:0 2px 16px rgba(23,44,87,0.05); }
        .summary-card .label { color:#6373a1; text-transform:uppercase; letter-spacing:.6px; font-size:0.82rem; margin-bottom:6px; display:block; }
        .summary-card .value { font-size:1.6rem; font-weight:800; color:#122c57; }
        .detalle-card { background:#fff; border:1px solid #e4e8f3; border-radius:16px; padding:24px; box-shadow:0 2px 16px rgba(23,44,87,0.05); margin-bottom:26px; }
        .detalle-card h2 { margin:0 0 16px; color:#1b2f58; }
        .detalle-layout { display:grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap:16px; }
        .detalle-layout span { display:block; }
        .detalle-label { font-size:0.85rem; text-transform:uppercase; color:#6a759f; letter-spacing:.4px; }
        .detalle-value { font-size:1rem; font-weight:600; color:#122c57; }
        .detalle-table { width:100%; border-collapse:collapse; }
        .detalle-table th, .detalle-table td { border:1px solid #e1e5f2; padding:12px; font-size:0.95rem; text-align:left; }
        .detalle-table th { background:#eef2ff; color:#20356f; text-transform:uppercase; font-size:0.82rem; }
        .notes-box { background:#f7f9ff; border:1px dashed #c9d3ef; border-radius:12px; padding:16px; color:#33416d; }
        @media (max-width:768px) {
            .detalle-main { padding:22px 18px 36px; }
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
            <div class="detalle-head">
                <div>
                    <h1>Factura #<?= (int) $factura['id'] ?></h1>
                    <p>Registro asociado a <?= htmlspecialchars($factura['proveedor'] ?? 'Proveedor') ?>.</p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <?php if (!empty($factura['orden_id'])): ?>
                        <a href="ordenes_compra_detalle.php?id=<?= (int) $factura['orden_id'] ?>" class="btn-link"><i class="fa-solid fa-file-circle-check"></i> Ver orden</a>
                    <?php endif; ?>
                    <a href="facturas.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Volver</a>
                </div>
            </div>

            <?php if (!empty($_GET['created'])): ?>
                <div class="alert-success"><i class="fa-solid fa-circle-check"></i> La factura se registro y el inventario fue actualizado.</div>
            <?php endif; ?>

            <div class="alert-info"><i class="fa-solid fa-circle-info"></i> Cada producto listado incremento su stock en el almacén seleccionado.</div>

            <section class="summary-grid">
                <div class="summary-card">
                    <span class="label">Subtotal</span>
                    <span class="value">$<?= number_format((float) ($factura['subtotal'] ?? 0), 2) ?></span>
                </div>
                <div class="summary-card">
                    <span class="label">Impuestos</span>
                    <span class="value">$<?= number_format((float) ($factura['impuestos'] ?? 0), 2) ?></span>
                </div>
                <div class="summary-card">
                    <span class="label">Total</span>
                    <span class="value">$<?= number_format((float) ($factura['total'] ?? 0), 2) ?></span>
                </div>
            </section>

            <section class="detalle-card">
                <h2>Datos generales</h2>
                <div class="detalle-layout">
                    <div>
                        <span class="detalle-label">Número de factura</span>
                        <span class="detalle-value"><?= htmlspecialchars($factura['numero_factura'] ?: 'Sin folio') ?></span>
                    </div>
                    <div>
                        <span class="detalle-label">Proveedor</span>
                        <span class="detalle-value"><?= htmlspecialchars($factura['proveedor'] ?? 'Desconocido') ?></span>
                    </div>
                    <div>
                        <span class="detalle-label">Fecha</span>
                        <span class="detalle-value"><?= date('d/m/Y', strtotime($factura['fecha'])) ?></span>
                    </div>
                    <div>
                        <span class="detalle-label">Almacén</span>
                        <span class="detalle-value"><?= htmlspecialchars($factura['almacen'] ?? '-') ?></span>
                    </div>
                    <div>
                        <span class="detalle-label">Orden asociada</span>
                        <span class="detalle-value">
                            <?php if (!empty($factura['orden_id'])): ?>
                                #<?= (int) $factura['orden_id'] ?>
                            <?php else: ?>
                                Manual
                            <?php endif; ?>
                        </span>
                    </div>
                    <div>
                        <span class="detalle-label">Registrada por</span>
                        <span class="detalle-value">Usuario #<?= htmlspecialchars($factura['usuario_id'] ?? 'N/D') ?></span>
                    </div>
                </div>
            </section>

            <section class="detalle-card">
                <h2>Detalle de productos</h2>
                <div class="reportes-table-wrapper">
                    <table class="detalle-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Costo unitario</th>
                            <th>Impuesto</th>
                            <th>Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($detalles)): ?>
                            <tr><td colspan="7" style="text-align:center; padding:22px; color:#7a88b2;">Sin productos registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($detalles as $idx => $item): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><?= htmlspecialchars($item['codigo'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($item['producto'] ?? 'Producto #' . $item['producto_id']) ?></td>
                                    <td><?= number_format((float) ($item['cantidad'] ?? 0), 2) ?></td>
                                    <td>$<?= number_format((float) ($item['costo_unitario'] ?? 0), 2) ?></td>
                                    <td>$<?= number_format((float) ($item['impuesto'] ?? 0), 2) ?></td>
                                    <td>$<?= number_format((float) ($item['total'] ?? 0), 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <?php if (!empty($factura['notas'])): ?>
                <section class="detalle-card">
                    <h2>Notas</h2>
                    <div class="notes-box"><?= nl2br(htmlspecialchars($factura['notas'])) ?></div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
