<?php
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';

$totalRegistros = $totalRegistros ?? count($rotacion);
$page = $page ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$perPage = $perPage ?? 10;
$perPageOptions = $perPageOptions ?? [10];
$offset = $offset ?? 0;
$desde = $totalRegistros > 0 ? $offset + 1 : 0;
$hasta = $totalRegistros > 0 ? min($offset + $perPage, $totalRegistros) : 0;

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
    <title>ROTACIÓN DE INVENTARIO | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/reportes.css">
    <link rel="stylesheet" href="assets/css/productos.css">
    <link rel="stylesheet" href="assets/css/inventario.css">
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
<?php $seccion_activa = 'rotacion_inventario'; ?>
<div class="main-layout">
     <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Toggle Menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    <div class="content-area">
         <?php
            require_once __DIR__ . '/../../helpers/Navigation.php';

            $role = Navigation::normalizeRole($role ?? ($_SESSION['role'] ?? ''));
        ?>
            <header class="top-header">
                <div class="top-header-left">
                </div>
                <div class="top-header-user">
                    <span><?= htmlspecialchars($nombre ?: 'Usuario') ?> (<?= htmlspecialchars($role) ?>)</span>
                    <i class="fa-solid fa-user-circle"></i>
                    <a href="dashboard_almacen" class="logout-btn" title="Ir al Dashboard"><i class="fa-solid fa-home"></i></a>
                    <a href="logout" class="logout-btn" title="Cerrar Sesión"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>    
                </div>
            </header>
        <main class="dashboard-main rotacion-main">
            <div class="rotacion-header">
                <div>
                    <h1>Rotación de Inventario</h1>
                    <p class="reportes-desc">Identifica Productos con Alto y Bajo Movimiento para Ajustar tus Niveles de Stock.</p>
                </div>
                <div class="section-actions">
                    <a class="btn-secondary" href="<?= $buildQuery(['export' => 'csv']) ?>"><i class="fa-solid fa-file-csv"></i> Exportar CSV</a>
                    <a class="btn-secondary" href="<?= $buildQuery(['export' => 'pdf']) ?>"><i class="fa-solid fa-file-pdf"></i> Exportar PDF</a>
                </div>
            </div>

            <section class="rotacion-filters " >
                <form method="get" class="inv-filter-row">
                    <div class="filter-row">
                    <div class="filter-field">
                        <label for="from">Desde</label>
                        <input type="date" id="from" name="from" value="<?= htmlspecialchars($desde) ?>">
                    </div>
                    <div class="filter-field">
                        <label for="to">Hasta</label>
                        <input type="date" id="to" name="to" value="<?= htmlspecialchars($hasta) ?>">
                    </div>
                    </div>
                    <div class="filter-field">
                        <label for="almacen_id">Almacén</label>
                        <select id="almacen_id" name="almacen_id">
                            <option value="">Todos los almacenes</option>
                            <?php foreach ($almacenes as $almacen): ?>
                                <option value="<?= $almacen['id'] ?>" <?= $almacenId == $almacen['id'] ? 'selected' : '' ?>><?= htmlspecialchars($almacen['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-row">
                    <div class="filter-field">
                        <label for="tipo">Tipo de Producto</label>
                        <select id="tipo" name="tipo">
                            <option value="">Todos</option>
                            <?php foreach ($tiposDisponibles as $tipo): ?>
                                <option value="<?= $tipo ?>" <?= $tipoFiltro === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="clasificacion">Clasificación</label>
                        <select id="clasificacion" name="clasificacion">
                            <option value="">Todos</option>
                            <option value="Alta" <?= ($_GET['clasificacion'] ?? '') === 'Alta' ? 'selected' : '' ?>>Alta</option>
                            <option value="Media" <?= ($_GET['clasificacion'] ?? '') === 'Media' ? 'selected' : '' ?>>Media</option>
                            <option value="Baja" <?= ($_GET['clasificacion'] ?? '') === 'Baja' ? 'selected' : '' ?>>Baja</option>
                            <option value="Sin movimiento" <?= ($_GET['clasificacion'] ?? '') === 'Sin Movimiento' ? 'selected' : '' ?>>Sin Movimiento</option>
                        </select>
                    </div>
                    </div>
                    <div class="logs-filter-actions">
                        <button type="submit" class="btn-main"><i class="fa fa-filter"></i> Aplicar</button>
                        <a class="btn-ghost" href="rotacion_inventario"><i class="fa fa-eraser"></i> Limpiar</a>
                    </div>
                </form>
            </section>

            <section class="reportes-section">
    <div class="reportes-card">
        
        <div class="reportes-table-wrapper">
            <table class="productos-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <!--th>Almacén</th-->
                        <th>Stock actual</th>
                        <th>Salidas</th>
                        <th>Entradas</th>
                        <!--th>Índice</th-->
                        <th>Clasificación</th>
                        <th>Último movimiento</th>
                        <th>Días sin movimiento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rotacion)): ?>
                        <tr><td colspan="11" style="text-align:center; padding:24px; color:#7d8bb0;">Sin Movimientos en el Periodo Seleccionado.</td></tr>
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
                                <!--td><//?= htmlspecialchars($row['almacen'] ?? '-') ?></td-->
                                <td><?= number_format((float) $row['stock_actual'], 2) ?></td>
                                <td><?= number_format((float) $row['salidas'], 2) ?></td>
                                <td><?= number_format((float) $row['entradas'], 2) ?></td>
                                <!--td><//?= number_format((float) $row['indice'], 2) ?></td-->
                                <td><span class="badge-rotacion <?= $badgeClass ?>"><?= htmlspecialchars($row['clasificacion']) ?></span></td>
                                <td><?= $row['ultimo_movimiento'] ? date('d/m/Y H:i', strtotime($row['ultimo_movimiento'])) : '-' ?></td>
                                <td><?= $row['dias_sin_movimiento'] !== null ? $row['dias_sin_movimiento'] . ' días' : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

    <div class="productos-pagination">
        <div class="productos-pagination-info">
            <?= $totalRegistros > 0
                ? "Mostrando <strong>$desde</strong> - <strong>$hasta</strong> de <strong>" . number_format($totalRegistros) . "</strong> registros"
                : "Sin registros disponibles" ?>
            </div>
            <div class="productos-pagination-controls">
                        <?php if ($page > 1): ?>
                            <a class="btn-ghost" href="<?= $buildQuery(['page' => $page - 1]) ?>"><i class="fa fa-chevron-left"></i> Anterior</a>
                        <?php endif; ?>
                        <span class="productos-pagination-page">Página <?= number_format($page) ?> de <?= number_format($totalPaginas) ?></span>
                        <?php if ($page < $totalPaginas): ?>
                            <a class="btn-ghost" href="<?= $buildQuery(['page' => $page + 1]) ?>">Siguiente <i class="fa fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
    </div>
        </main>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById('toggleSidebar');
   const sidebar = document.querySelector('.main_sidebar'); 
    const mainContent = document.querySelector('.content-area');

    if (toggleBtn && sidebar && mainContent) {
        toggleBtn.addEventListener('click', function () {
            console.log('Sidebar toggle script loaded');
            sidebar.classList.toggle('collapsed');
            
            mainContent.classList.toggle('collapsed');
            
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                if (sidebar.classList.contains('collapsed')) {
                    icon.className = 'fa-solid fa-bars'; // Icono normal
                } else {
                    icon.className = 'fa-solid fa-xmark'; // Icono de cerrar
                }
            }
        });
    }
});
</script>
</body>
</html>


