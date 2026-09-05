<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Almacen', 'Inventario']);

$role = $_SESSION['role'] ?? 'Empleado';
$nombre = $_SESSION['nombre'] ?? '';
function format_stock($value) {
    $num = (float) $value;
    if (abs($num - round($num)) < 0.00001) {
        return number_format($num, 0, '.', ',');
    }
    return number_format($num, 2, '.', ',');
}

$mostrarCostos = $role !== 'Almacen';
$stats = $stats ?? ['valor_total' => 0, 'stock_bajo' => 0, 'sin_stock' => 0, 'consumibles' => 0, 'herramientas' => 0, 'activos' => 0, 'inactivos' => 0];
$totalRegistros = $totalRegistros ?? count($productos);
$page = $page ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$perPage = $perPage ?? 10;
$perPageOptions = $perPageOptions ?? [10];
$offset = $offset ?? 0;
$filtros = $filtros ?? [];
$hayFiltros = $hayFiltros ?? false;

$desde = $totalRegistros > 0 ? $offset + 1 : 0;
$hasta = $totalRegistros > 0 ? min($offset + $perPage, $totalRegistros) : 0;

$buscar = htmlspecialchars($filtros['buscar'] ?? '', ENT_QUOTES, 'UTF-8');
$categoriaId = htmlspecialchars($filtros['categoria_id'] ?? '', ENT_QUOTES, 'UTF-8');
$almacenId = htmlspecialchars($filtros['almacen_id'] ?? '', ENT_QUOTES, 'UTF-8');
$proveedorId = htmlspecialchars($filtros['proveedor_id'] ?? '', ENT_QUOTES, 'UTF-8');
$tipoFiltro = htmlspecialchars($filtros['tipo'] ?? '', ENT_QUOTES, 'UTF-8');
$activoFiltro = htmlspecialchars($filtros['activo_id'] ?? '', ENT_QUOTES, 'UTF-8');
$stockFlag = htmlspecialchars($filtros['stock_flag'] ?? '', ENT_QUOTES, 'UTF-8');
$valorMin = htmlspecialchars($filtros['valor_min'] ?? '', ENT_QUOTES, 'UTF-8');
$valorMax = htmlspecialchars($filtros['valor_max'] ?? '', ENT_QUOTES, 'UTF-8');
$fechaDesde = htmlspecialchars($filtros['fecha_desde'] ?? '', ENT_QUOTES, 'UTF-8');
$fechaHasta = htmlspecialchars($filtros['fecha_hasta'] ?? '', ENT_QUOTES, 'UTF-8');
$unidadMedidaId = htmlspecialchars($filtros['unidad_medida_id'] ?? '', ENT_QUOTES, 'UTF-8');
$codigoBarrasFiltro = htmlspecialchars($filtros['codigo_barras'] ?? '', ENT_QUOTES, 'UTF-8');

$buildQuery = function(array $overrides = []) {
    $params = array_merge($_GET, $overrides);
    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        }
    }
    return $params ? ('?' . http_build_query($params)) : '?';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>INVENTARIO | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/productos.css">
    <link rel="stylesheet" href="assets/css/inventario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesigncss.min.css">
    <style>
        .inventario-table .col-stock { width: 140px; min-width: 120px; text-align: center; }
        .inventario-table td.col-stock { white-space: nowrap; }
        .inventario-table td.col-stock .badge { display: inline-block; }
        .inventario-table td.col-stock small { display: block; font-size: 0.75rem; color: #666; }
        @media (max-width: 700px) {
            .inventario-table .col-stock { width: auto; min-width: 0; }
            .inventario-table td.col-stock { white-space: normal; }
        }
    </style>
</head>
<body class="module-inventory-warehouse">
<?php $seccion_activa = 'inventario'; ?>
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Toggle Menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

    <div class="content-area">
        
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>

        <main class="dashboard-main">
            <div class="dashboard-header-row">
                <div>
                    <h1>INVENTARIO GENERAL</h1>
                    <span class="dashboard-desc">Supervisa el Estado del Stock, Ubicaciones y Movimientos de Productos.</span>
                </div>
            </div>

            <section class="dashboard-cards-row">
                <div class="dashboard-card">
                    <div class="card-info">
                        <div class="card-label">Productos Registrados</div>
                        <div class="card-value"><?= number_format($totalRegistros) ?></div>
                        <div class="card-sub">En este Almacén</div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-shape-outline"></span>
                    </div>
                </div>
                <div class="dashboard-card caution">
                    <div class="card-info">
                        <div class="card-label">Stock Bajo</div>
                        <div class="card-value"><?= number_format((int) ($stats['stock_bajo'] ?? 0)) ?></div>
                        <div class="card-sub">Pendientes de Atención</div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-alert-circle-outline"></span>
                    </div>
                </div>
                <?php if ($mostrarCostos): ?>
                <div class="dashboard-card">
                    <div class="card-info">
                        <div class="card-label">Valor Estimado</div>
                        <div class="card-value"><?= number_format((float) ($stats['valor_total'] ?? 0), 2) ?></div>
                        <div class="card-sub">Costo Acumulado de Inventario Sin I.V.A.</div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-alert-circle-outline"></span>
                    </div>
                </div>
                <?php endif; ?>
            </section>

            <section class="inventario-filters-card">
                <form method="get" class="inventario-filters-form">
                    <div class="inv-filter-row">
                        <div class="inv-filter-field">
                            <label for="buscar">Búsqueda Global</label>
                            <div class="filter-input-icon">
                                <i class="fa fa-search"></i>
                                <input type="text" id="buscar" name="buscar" placeholder="Nombre, nomenclatura, SKU, fabricante o número de serie" value="<?= $buscar ?>">
                            </div>
                        </div>

                        <div class="inv-filter-field">
                            <label for="almacen_id">Almacén</label>
                            <select id="almacen_id" name="almacen_id">
                                <option value="">Todos los almacenes</option>
                                <?php foreach ($almacenes as $almacen): ?>
                                    <option value="<?= $almacen['id'] ?>" <?= $almacenId == $almacen['id'] ? 'selected' : '' ?>><?= htmlspecialchars($almacen['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="inv-filter-field">
                            <label for="fecha_desde">Fecha de Alta (desde)</label>
                            <input type="date" id="fecha_desde" name="fecha_desde" value="<?= $fechaDesde ?>">
                        </div>
                        <div class="inv-filter-field">
                            <label for="fecha_hasta">Fecha de Alta (hasta)</label>
                            <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?= $fechaHasta ?>">
                        </div>
                    </div>

                    <div class="inv-filter-row">
                        <div class="inv-filter-field">
                            <label for="codigo_barras">Código de Barras</label>
                            <input type="text" id="codigo_barras" name="codigo_barras" value="<?= htmlspecialchars($filtros['codigo_barras'] ?? '') ?>" placeholder="Escanea o Escribe Código">
                        </div>
                        <div class="inv-filter-field">
                            <label for="tipo">Tipo</label>
                            <select id="tipo" name="tipo">
                                <option value="">Todos</option>
                                <?php foreach ($tiposProducto as $tipo): ?>
                                    <option value="<?= $tipo ?>" <?= $tipoFiltro === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="inv-filter-field">
                            <label for="categoria_id">Categoría</label>
                            <select id="categoria_id" name="categoria_id">
                                <option value="">Todas</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria['id'] ?>" <?= $categoriaId == $categoria['id'] ? 'selected' : '' ?>><?= htmlspecialchars($categoria['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="inv-filter-field">
                            <label for="marca">Marca:</label>
                            <input type="text" id="marca" name="marca" value="<?= htmlspecialchars($filtros['marca'] ?? '') ?>" placeholder="Buscar por Marca">
                        </div>
                    </div>

                    <div class="inv-filter-row">
                        <div class="inv-filter-field">
                            <label for="stock_flag">Estado de Stock</label>
                            <select id="stock_flag" name="stock_flag">
                                <option value="">Todos</option>
                                <option value="bajo" <?= $stockFlag === 'bajo' ? 'selected' : '' ?>>Stock bajo</option>
                                <option value="sin" <?= $stockFlag === 'sin' ? 'selected' : '' ?>>Sin stock</option>
                                <option value="suficiente" <?= $stockFlag === 'suficiente' ? 'selected' : '' ?>>Stock suficiente</option>
                            </select>
                        </div>
                        <?php if ($mostrarCostos):?>
                        <div class="inv-filter-field">
                            <label for="valor_min">Precio Unitario Mínimo (MXN)</label>
                            <input type="number" step="10" id="valor_min" name="valor_min" value="<?= $valorMin ?>">
                        </div>
                        <div class="inv-filter-field">
                            <label for="valor_max">Precio Unitario Máximo (MXN)</label>
                            <input type="number" step="10" id="valor_max" name="valor_max" value="<?= $valorMax ?>">
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="inv-filter-row">
                        <div class="inv-filter-actions">
                            <button type="submit" class="btn-main"><i class="fa fa-filter"></i> Aplicar Filtros</button>
                            <?php if ($hayFiltros): ?>
                                <a class="btn-ghost" href="inventario"><i class="fa fa-eraser"></i> Limpiar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </section>

            <section class="inventario-table-card">
                <div class="inventario-table-header">
                    <h2><i class="fa-solid fa-cubes"></i> Resultados</h2>
                    <span class="inventario-table-sub">Mostrando <?= number_format($desde) ?> - <?= number_format($hasta) ?> de <?= number_format($totalRegistros) ?> productos</span>
                </div>
                <div class="inventario-table-wrapper">
                    <?php if (empty($productos)): ?>
                        <div class="inventario-empty">
                            <i class="fa fa-box-open"></i>
                            <p>No se encontraron productos con los filtros aplicados.</p>
                        </div>
                    <?php else: ?>
                        <table class="inventario-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Tipo</th>
                                    <th>Categoría</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th class="col-stock">Stock</th>
                                    <?php if ($mostrarCostos): ?><th>Precio Unitario</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $producto): ?>
                                    <?php
                                        $stockActual = (float) ($producto['stock_actual'] ?? 0);
                                        $stockMinimo = (float) ($producto['stock_minimo'] ?? 0);
                                        $precioUnitario = (float) ($producto['precio_unitario'] ?? 0);

                                        $badgeStock = 'ok';
                                        if ($stockActual <= 0) {
                                            $badgeStock = 'sin';
                                        } elseif ($stockActual < $stockMinimo) {
                                            $badgeStock = 'bajo';
                                        }

                                        // Manejo de compatibilidad para la unidad de medida
                                        $unidad = $producto['unidad_apodo'] ?? $producto['unidad_abreviacion'] ?? '';
                                    ?>
                                    <tr>
                                        <td><span class="mono"><?= htmlspecialchars($producto['codigo'] ?? '-') ?></span></td>
                                        <td>
                                            <div class="tabla-producto-nombre"><?= htmlspecialchars($producto['nombre'] ?? '-') ?></div>
                                        </td>
                                        <td><span class="badge badge-tipo <?= strtolower($producto['tipo'] ?? '') ?>"><?= htmlspecialchars($producto['tipo'] ?? '-') ?></span></td>
                                        <td><?= htmlspecialchars($producto['categoria'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($producto['marca'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($producto['modelo'] ?? '-') ?></td>
                                        <td class="col-stock">
                                            <span class="badge badge-stock <?= $badgeStock ?>">
                                                <?= format_stock($stockActual) ?> <?= htmlspecialchars($unidad) ?>
                                            </span>
                                            <small>Mín: <?= format_stock($stockMinimo) ?></small>
                                        </td>
                                        <?php if ($mostrarCostos): ?>
                                            <td>$<?= number_format($precioUnitario, 2) ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>

            <div class="inventario-pagination">
                <div class="inventario-pagination-info">
                    <?= $totalRegistros > 0
                        ? "Mostrando $desde - $hasta de " . number_format($totalRegistros) . " registros"
                        : "Sin registros disponibles" ?>
                </div>
                <div class="inventario-pagination-controls">
                    <?php if ($page > 1): ?>
                        <a class="btn-ghost" href="<?= $buildQuery(['page' => $page - 1]) ?>"><i class="fa fa-chevron-left"></i> Anterior</a>
                    <?php endif; ?>
                    <span class="inventario-pagination-page">Página <?= number_format($page) ?> de <?= number_format($totalPaginas) ?></span>
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

