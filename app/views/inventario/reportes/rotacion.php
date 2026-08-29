<?php
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';

$buildQuery = function(array $overrides = []) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rotación de inventario | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/reportes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .rotacion-main { padding: 32px 32px 48px; }
        .rotacion-header { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; margin-bottom:26px; }
        .rotacion-header h1 { margin:0; font-size:2rem; color:#12305f; }
        .rotacion-table { width:100%; border-collapse:collapse; min-width:960px; }
        .rotacion-table th, .rotacion-table td { padding:12px 14px; border-bottom:1px solid #edf0f6; text-align:left; font-size:0.95rem; color:#1a2c51; }
        .rotacion-table th { background:#f3f6fc; text-transform:uppercase; letter-spacing:.4px; color:#5a6a94; font-size:0.88rem; }
        .badge-rotacion { display:inline-block; padding:4px 10px; border-radius:999px; font-weight:600; font-size:0.82rem; }
        .badge-rotacion.alta { background:#e6f7ed; color:#1a7a4b; }
        .badge-rotacion.media { background:#fff6e5; color:#bb7a15; }
        .badge-rotacion.baja { background:#ede9ff; color:#5b34c9; }
        .badge-rotacion.sin { background:#ffe8e8; color:#c44545; }
        .rotacion-filters { background:#fff; border-radius:16px; padding:24px 26px; border:1px solid #e4e8f3; box-shadow:0 2px 16px rgba(23,44,87,0.05); margin-bottom:24px; }
        .rotacion-filters form { display:grid; gap:18px; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); }
        .rotacion-filters label { font-weight:600; color:#3a4a7a; margin-bottom:6px; display:block; }
        .rotacion-filters input, .rotacion-filters select { padding:10px 12px; border-radius:9px; border:1px solid #d6dbea; background:#fafbff; color:#1a2c51; }
        .logs-filter-actions { display:flex; gap:10px; align-items:center; }
        @media (max-width:768px){
            .rotacion-main { padding:22px 18px 36px; }
            .rotacion-header { flex-direction:column; align-items:flex-start; }
        }
    </style>
</head>
<body class="module-inventory-warehouse">
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
                <a href="inventario_actual.php" ><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href="prestamos_pendientes.php" ><i class="fa-solid fa-screwdriver-wrench"></i> Préstamos de herramientas</a>
                <a href="reportes_rotacion.php" class="active"><i class="fa-solid fa-refresh"></i> Rotación de Inventario</a>
                <a href="revisar_solicitudes.php"><i class="fa-solid fa-plus-square"></i> Solicitudes de Material</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="ajustes.php"><i class="fa-solid fa-gear"></i> Configuración</a>
                <a href="documentacion.php"><i class="fa-solid fa-book"></i>Documentación</a>
            <?php elseif ($role === 'Almacen'): ?>
                <a href="productos.php"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php" ><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href="prestamos_pendientes.php" ><i class="fa-solid fa-screwdriver-wrench"></i> Préstamos de herramientas</a>
                <a href="reportes_rotacion.php" class="active"><i class="fa-solid fa-refresh"></i> Rotación de Inventario</a>
                <a href="revisar_solicitudes.php"><i class="fa-solid fa-plus-square"></i> Solicitudes de Material</a>
                <a href="mis_solicitudes.php"><i class="fa-solid fa-clipboard-list"></i> Mis Solicitudes</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="ajustes.php"><i class="fa-solid fa-gear"></i> Configuración</a>
                <a href="documentacion.php"><i class="fa-solid fa-book"></i>Documentación</a>
            <?php elseif ($role === 'Compras'): ?>
              <a href="productos.php"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php" ><i class="fa-solid fa-list-check"></i> Inventario</a>
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
        <?php include __DIR__ . '/../../partials/topbar.php'; ?>

        <main class="dashboard-main rotacion-main">
            <div class="rotacion-header">
                <div>
                    <h1>Rotación de inventario</h1>
                    <p class="reportes-desc">Identifica productos con alto y bajo movimiento para ajustar tus niveles de stock.</p>
                </div>
                <div class="section-actions">
                    <a class="btn-secondary" href="<?= $buildQuery(['export' => 'csv']) ?>"><i class="fa-solid fa-file-csv"></i> Exportar CSV</a>
                    <a class="btn-secondary" href="<?= $buildQuery(['export' => 'pdf']) ?>"><i class="fa-solid fa-file-pdf"></i> Exportar PDF</a>
                </div>
            </div>

            <section class="rotacion-filters">
                <form method="get">
                    <div>
                        <label for="from">Desde</label>
                        <input type="date" id="from" name="from" value="<?= htmlspecialchars($desde) ?>">
                    </div>
                    <div>
                        <label for="to">Hasta</label>
                        <input type="date" id="to" name="to" value="<?= htmlspecialchars($hasta) ?>">
                    </div>
                    <div>
                        <label for="tipo">Tipo de producto</label>
                        <select id="tipo" name="tipo">
                            <option value="">Todos</option>
                            <?php foreach ($tiposDisponibles as $tipo): ?>
                                <option value="<?= $tipo ?>" <?= $tipoFiltro === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="almacen_id">Almacén</label>
                        <select id="almacen_id" name="almacen_id">
                            <option value="">Todos</option>
                            <?php foreach ($almacenes as $almacen): ?>
                                <option value="<?= $almacen['id'] ?>" <?= $almacenId == $almacen['id'] ? 'selected' : '' ?>><?= htmlspecialchars($almacen['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="logs-filter-actions">
                        <button type="submit" class="btn-main"><i class="fa fa-filter"></i> Aplicar</button>
                        <a class="btn-ghost" href="reportes_rotacion.php"><i class="fa fa-eraser"></i> Restablecer</a>
                    </div>
                </form>
            </section>

            <section class="reportes-section">
                <div class="reportes-table-wrapper">
                    <table class="rotacion-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th>Almacén</th>
                                <th>Stock actual</th>
                                <th>Salidas</th>
                                <th>Entradas</th>
                                <th>Índice</th>
                                <th>Clasificación</th>
                                <th>Último movimiento</th>
                                <th>Días sin movimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rotacion)): ?>
                                <tr><td colspan="11" style="text-align:center; padding:24px; color:#7d8bb0;">Sin movimientos en el periodo seleccionado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rotacion as $row): ?>
                                    <?php
                                        $badgeClass = match (strtolower($row['clasificacion'])) {
                                            'alta' => 'alta',
                                            'media' => 'media',
                                            'baja' => 'baja',
                                            default => 'sin',
                                        };
                                    ?>
                                    <tr>
                                        <td><span class="mono"><?= htmlspecialchars($row['codigo']) ?></span></td>
                                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                                        <td><span class="badge badge-tipo <?= strtolower($row['tipo']) ?>"><?= htmlspecialchars($row['tipo']) ?></span></td>
                                        <td><?= htmlspecialchars($row['almacen'] ?? '-') ?></td>
                                        <td><?= number_format((float) $row['stock_actual'], 2) ?></td>
                                        <td><?= number_format((float) $row['salidas'], 2) ?></td>
                                        <td><?= number_format((float) $row['entradas'], 2) ?></td>
                                        <td><?= number_format((float) $row['indice'], 2) ?></td>
                                        <td><span class="badge-rotacion <?= $badgeClass ?>"><?= htmlspecialchars($row['clasificacion']) ?></span></td>
                                        <td><?= $row['ultimo_movimiento'] ? date('d/m/Y H:i', strtotime($row['ultimo_movimiento'])) : '-' ?></td>
                                        <td><?= $row['dias_sin_movimiento'] !== null ? $row['dias_sin_movimiento'] . ' días' : '-' ?></td>
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


