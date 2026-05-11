<?php
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
$totalFacturas = count($facturas ?? []);
$importeTotal = array_sum(array_map(fn($row) => (float) ($row['total'] ?? 0), $facturas ?? []));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Facturas de compra | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/reportes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .facturas-main { padding: 32px 32px 48px; }
        .facturas-header { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; margin-bottom:26px; }
        .facturas-header h1 { margin:0; font-size:2rem; color:#12305f; }
        .facturas-header p { margin:6px 0 0; color:#61729f; }
        .facturas-header .acciones { display:flex; gap:12px; flex-wrap:wrap; }
        .btn-primary { background:#2563eb; color:#fff; border-radius:10px; padding:11px 22px; text-decoration:none; font-weight:700; display:inline-flex; align-items:center; gap:8px; }
        .btn-primary:hover { background:#1e4dc2; }
        .btn-link { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:8px; background:#eef2ff; color:#1d3d7a; text-decoration:none; font-size:0.85rem; }
        .btn-link:hover { background:#dbe1ff; }
        .facturas-filters { background:#fff; border-radius:16px; padding:24px 26px; border:1px solid #e4e8f3; box-shadow:0 2px 16px rgba(23,44,87,0.05); margin-bottom:26px; }
        .facturas-filters form { display:grid; gap:18px; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); }
        .facturas-filters label { font-weight:600; color:#3a4a7a; margin-bottom:6px; display:block; }
        .facturas-filters input, .facturas-filters select { padding:10px 12px; border-radius:9px; border:1px solid #d6dbea; background:#fafbff; color:#1a2c51; width:100%; }
        .filter-actions { display:flex; gap:10px; align-items:center; }
        .facturas-summary { display:grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap:18px; margin-bottom:26px; }
        .facturas-card { background:#fff; border-radius:14px; padding:22px 24px; border:1px solid #e4e8f3; box-shadow:0 2px 16px rgba(23,44,87,0.05); display:flex; flex-direction:column; gap:6px; }
        .facturas-card .label { font-size:0.9rem; color:#6575a1; text-transform:uppercase; letter-spacing:.6px; }
        .facturas-card .value { font-size:1.8rem; font-weight:800; color:#142b55; }
        .facturas-table { width:100%; border-collapse:collapse; min-width:980px; }
        .facturas-table th, .facturas-table td { padding:12px 14px; border-bottom:1px solid #edf0f6; text-align:left; font-size:0.95rem; color:#1a2c51; vertical-align:top; }
        .facturas-table th { background:#f3f6fc; text-transform:uppercase; letter-spacing:.4px; color:#5a6a94; font-size:0.88rem; }
        .badge-estado { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:999px; font-weight:600; font-size:0.82rem; }
        .badge-estado i { font-size:0.85rem; }
        .badge-pendiente { background:#fff6e5; color:#c17a12; }
        .badge-recepcion { background:#e9f4ff; color:#1f6bb0; }
        .badge-registrada { background:#e6f7ed; color:#1a7a4b; }
        @media (max-width:768px) {
            .facturas-main { padding:22px 18px 36px; }
            .facturas-table { min-width:auto; }
            .facturas-header { flex-direction:column; }
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
        <main class="facturas-main">
            <div class="facturas-header">
                <div>
                    <h1>Facturas de compra</h1>
                    <p>Consulta y registra las facturas que ingresan inventario.</p>
                </div>
                <?php if (in_array($role, ['Administrador', 'Compras', 'Almacen'], true)): ?>
                    <div class="acciones">
                        <a class="btn-primary" href="facturas_create.php"><i class="fa-solid fa-file-circle-plus"></i> Nueva factura</a>
                    </div>
                <?php endif; ?>
            </div>

            <section class="facturas-filters">
                <form method="get">
                    <div>
                        <label for="proveedor_id">Proveedor</label>
                        <select name="proveedor_id" id="proveedor_id">
                            <option value="">Todos</option>
                            <?php foreach ($proveedores as $prov): ?>
                                <option value="<?= (int) $prov['id'] ?>" <?= ($filters['proveedor_id'] ?? '') == $prov['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prov['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="almacen_id">Almacén</label>
                        <select name="almacen_id" id="almacen_id">
                            <option value="">Todos</option>
                            <?php foreach ($almacenes as $alm): ?>
                                <option value="<?= (int) $alm['id'] ?>" <?= ($filters['almacen_id'] ?? '') == $alm['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($alm['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="desde">Desde</label>
                        <input type="date" name="desde" id="desde" value="<?= htmlspecialchars($filters['desde'] ?? '') ?>">
                    </div>
                    <div>
                        <label for="hasta">Hasta</label>
                        <input type="date" name="hasta" id="hasta" value="<?= htmlspecialchars($filters['hasta'] ?? '') ?>">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-primary" style="padding:10px 18px;"><i class="fa-solid fa-magnifying-glass"></i> Aplicar</button>
                        <a href="facturas.php" class="btn-link"><i class="fa-solid fa-rotate-left"></i> Limpiar</a>
                    </div>
                </form>
            </section>

            <section class="facturas-summary">
                <div class="facturas-card">
                    <span class="label">Facturas</span>
                    <span class="value"><?= number_format($totalFacturas) ?></span>
                </div>
                <div class="facturas-card">
                    <span class="label">Importe registrado</span>
                    <span class="value">$<?= number_format($importeTotal, 2) ?></span>
                </div>
            </section>

            <section>
                <div class="reportes-table-wrapper">
                    <table class="facturas-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Factura</th>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Almacén</th>
                            <th>Órden</th>
                            <th>Subtotal</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($facturas)): ?>
                            <tr>
                                <td colspan="9" style="text-align:center; padding:24px; color:#7d8bb0;">Sin resultados con los filtros seleccionados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($facturas as $factura): ?>
                                <tr>
                                    <td>#<?= (int) $factura['id'] ?></td>
                                    <td><?= htmlspecialchars($factura['numero_factura'] ?: 'Sin numero') ?></td>
                                    <td><?= date('d/m/Y', strtotime($factura['fecha'])) ?></td>
                                    <td><?= htmlspecialchars($factura['proveedor'] ?? 'Desconocido') ?></td>
                                    <td><?= htmlspecialchars($factura['almacen'] ?? '-') ?></td>
                                    <td>
                                        <?php if (!empty($factura['orden_id'])): ?>
                                            <span class="badge-estado badge-recepcion"><i class="fa-solid fa-file"></i> #<?= (int) $factura['orden_id'] ?></span>
                                        <?php else: ?>
                                            <span class="badge-estado badge-pendiente"><i class="fa-solid fa-circle-info"></i> Manual</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?= number_format((float) ($factura['subtotal'] ?? 0), 2) ?></td>
                                    <td>$<?= number_format((float) ($factura['total'] ?? 0), 2) ?></td>
                                    <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                        <a class="btn-link" href="facturas_detalle.php?id=<?= (int) $factura['id'] ?>"><i class="fa-solid fa-eye"></i> Ver</a>
                                        <?php if (!empty($factura['orden_id'])): ?>
                                            <a class="btn-link" href="ordenes_compra_detalle.php?id=<?= (int) $factura['orden_id'] ?>"><i class="fa-solid fa-arrow-up-right-from-square"></i> Orden</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
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
