<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Almacen']);

$role = $_SESSION['role'] ?? 'Empleado';
$nombre = $_SESSION['nombre'] ?? '';

$mostrarCostos = $role !== 'Almacen';
$stats = $stats ?? ['valor_total' => 0, 'stock_bajo' => 0, 'sin_stock' => 0, 'consumibles' => 0, 'herramientas' => 0, 'activos' => 0, 'inactivos' => 0];
$totalRegistros = $totalRegistros ?? count($productos);
$page = $page ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$perPage = $perPage ?? 15;
$perPageOptions = $perPageOptions ?? [10, 15, 25, 50, 100];
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
$estadoFiltro = htmlspecialchars($filtros['estado'] ?? '', ENT_QUOTES, 'UTF-8');
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
    <title>INVENTARIO | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/productos.css">
    <link rel="stylesheet" href="assets/css/inventario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
</head>
<body>
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

        <main class="dashboard-main">
            <div class="dashboard-header-row">
                <div>
                    <h1>INVENTARIO GENERAL</h1>
                    <span class="dashboard-desc">Supervisa el Estado del Stock, Ubicaciones y Movimientos de Productos.</p>
                </div>
                <!--  
                <div class="dashboard-updated">
                    <div>Último Actualizado</div>
                    <//?= htmlspecialchars($datos['last_update']) ?>
                </div> -->
            </div>

            <section class="dashboard-cards-row">
                <div class="dashboard-card blue">
                    <div class="card-info">
                        <div class="card-label">Productos Registrados</div>
                        <div class="card-value"><?= number_format($totalRegistros) ?></div>
                        <div class="card-sub">En este Almacén</div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-shape-outline"></span>
                    </div>
                </div>
                <div class="dashboard-card red">
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
                        <div class="card-sub">Costo acumulado de inventario con I.V.A.</div>
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
                                <input type="text" id="buscar" name="buscar" placeholder="Nombre, Código, Descripción o proveedor" value="<?= $buscar ?>">
                            </div>
                        </div>
                        <div class="inv-filter-field">
                            <label for="codigo_barras">Código de Barras</label>
                            <input type="text" id="codigo_barras" name="codigo_barras" value="<?= htmlspecialchars($filtros['codigo_barras'] ?? '') ?>" placeholder="Escanea o escribe codigo">
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
                        <!--div class="inv-filter-field">
                            <label for="almacen_id">Almacén</label>
                            <select id="almacen_id" name="almacen_id">
                                <option value="">Todos</option>
                                <//?php foreach ($almacenes as $almacen): ?>
                                    <option value="<//?= $almacen['id'] ?>" <//?= $almacenId == $almacen['id'] ? 'selected' : '' ?>><//?= htmlspecialchars($almacen['nombre']) ?></option>
                                <//?php endforeach; ?>
                            </select>
                        </div-->
                        <div class="inv-filter-field">
                            <label for="proveedor_id">Proveedor</label>
                            <select id="proveedor_id" name="proveedor_id">
                                <option value="">Todos</option>
                                <?php foreach ($proveedores as $proveedor): ?>
                                    <option value="<?= $proveedor['id'] ?>" <?= $proveedorId == $proveedor['id'] ? 'selected' : '' ?>><?= htmlspecialchars($proveedor['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="inv-filter-row">
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
                            <label for="estado">Estado Físico</label>
                            <select id="estado" name="estado">
                                <option value="">Todos</option>
                                <?php foreach ($estadosProducto as $estado): ?>
                                    <option value="<?= $estado ?>" <?= $estadoFiltro === $estado ? 'selected' : '' ?>><?= $estado ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="inv-filter-field">
                            <label for="stock_flag">Estado de Stock</label>
                            <select id="stock_flag" name="stock_flag">
                                <option value="">Todos</option>
                                <option value="bajo" <?= $stockFlag === 'bajo' ? 'selected' : '' ?>>Stock bajo</option>
                                <option value="sin" <?= $stockFlag === 'sin' ? 'selected' : '' ?>>Sin stock</option>
                                <option value="suficiente" <?= $stockFlag === 'suficiente' ? 'selected' : '' ?>>Stock suficiente</option>
                            </select>
                        </div>
                        <div class="inv-filter-field">
                            <label for="unidad_medida_id">Unidad de Medida</label>
                            <select id="unidad_medida_id" name="unidad_medida_id">
                                <option value="">Todas</option>
                                <?php foreach ($unidades as $unidad): ?>
                                    <option value="<?= $unidad['id'] ?>" <?= $unidadMedidaId == $unidad['id'] ? 'selected' : '' ?>><?= htmlspecialchars($unidad['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="inv-filter-row">
                        <?php if ($mostrarCostos):?>
                        <div class="inv-filter-field">
                            <label for="valor_min">Valor Mínimo (MXN)</label>
                            <input type="number" step="10" id="valor_min" name="valor_min" value="<?= $valorMin ?>">
                        </div>
                        <div class="inv-filter-field">
                            <label for="valor_max">Valor Máximo (MXN)</label>
                            <input type="number" step="10" id="valor_max" name="valor_max" value="<?= $valorMax ?>">
                        </div>
                        <?php endif; ?>
                        <div class="inv-filter-field">
                            <label for="fecha_desde">Fecha de Alta (desde)</label>
                            <input type="date" id="fecha_desde" name="fecha_desde" value="<?= $fechaDesde ?>">
                        </div>
                        <div class="inv-filter-field">
                            <label for="fecha_hasta">Fecha de Alta (hasta)</label>
                            <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?= $fechaHasta ?>">
                        </div>
                        <div class="inv-filter-field">
                            <label for="per_page">Resultados por Página</label>
                            <select id="per_page" name="per_page" onchange="this.form.submit()">
                                <?php foreach ($perPageOptions as $option): ?>
                                    <option value="<?= $option ?>" <?= (int)$perPage === (int)$option ? 'selected' : '' ?>><?= $option ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="inv-filter-row">
                        <div class="inv-filter-actions">
                            <button type="submit" class="btn-main"><i class="fa fa-filter"></i> Aplicar Filtros</button>
                            <?php if ($hayFiltros): ?>
                                <a class="btn-ghost" href="inventario_actual"><i class="fa fa-eraser"></i> Limpiar</a>
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
                                    <!--th>Almacén</th-->
                                    <th>Stock Actual</th>
                                    <th>Stock Mínimo</th>
                                    <?php if ($mostrarCostos): ?><th>Valor</th><?php endif; ?>
                                    <!--th>Estado</th-->
                                    <!--th>Último movimiento</th-->
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $producto): ?>
                                    <?php
                                        $stockActual = (float) ($producto['stock_actual'] ?? 0);
                                        $stockMinimo = (float) ($producto['stock_minimo'] ?? 0);
                                        $valor = (float) ($producto['valor_total'] ?? 0);
                                        $badgeStock = 'ok';
                                        if ($stockActual <= 0) {
                                            $badgeStock = 'sin';
                                        } elseif ($stockActual < $stockMinimo) {
                                            $badgeStock = 'bajo';
                                        }
                                        $fechaMovimiento = $producto['ultimo_movimiento'] ?? null;
                                    ?>
                                    <tr>
                                        <td><span class="mono"><?= htmlspecialchars($producto['codigo'] ?? '-') ?></span></td>
                                        <td>
                                            <div class="tabla-producto-nombre"><?= htmlspecialchars($producto['nombre'] ?? '-') ?></div>
                                            <?php if (!empty($producto['tags'])): ?>
                                                <div class="tabla-tags"><i class="fa fa-tags"></i> <?= htmlspecialchars($producto['tags']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge badge-tipo <?= strtolower($producto['tipo'] ?? '') ?>"><?= htmlspecialchars($producto['tipo'] ?? '-') ?></span></td>
                                        <td><?= htmlspecialchars($producto['categoria'] ?? '-') ?></td>
                                        <!--td><//?= htmlspecialchars($producto['almacen'] ?? '-') ?></td-->
                                        <td>
                                            <span class="badge badge-stock <?= $badgeStock ?>">
                                                <?= rtrim(rtrim(number_format($stockActual, 2), '0'), '.') ?> <?= htmlspecialchars($producto['unidad_abreviacion'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td><?= rtrim(rtrim(number_format($stockMinimo, 2), '0'), '.') ?> <?= htmlspecialchars($producto['unidad_abreviacion'] ?? '') ?></td>
                                        <?php if ($mostrarCostos): ?>
                                            <td>$<?= number_format($valor, 2) ?></td>
                                        <?php endif; ?>
                                        <!--td><span class="badge badge-activo <//?= (int)($producto['activo_id'] ?? 1) === 1 ? 'activo' : 'inactivo' ?>"><//?= htmlspecialchars($producto['estado_activo'] ?? '-') ?></span></td-->
                                        <!--td><//?= $fechaMovimiento ? date('d/m/Y H:i', strtotime($fechaMovimiento)) : 'Sin movimientos' ?></td-->
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

