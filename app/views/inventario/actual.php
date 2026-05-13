<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin();

$role = $_SESSION['role'] ?? 'Empleado';
$nombre = $_SESSION['nombre'] ?? '';

$mostrarCostos = $role !== 'Empleado';
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
    <title>Gestión de Inventario | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/productos.css">
    <link rel="stylesheet" href="/assets/css/inventario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
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
                <a href="inventario_actual.php" class="active"><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href="prestamos_pendientes.php" ><i class="fa-solid fa-screwdriver-wrench"></i> Préstamos de herramientas</a>
                <a href="reportes_rotacion.php" ><i class="fa-solid fa-refresh"></i> Rotación de Inventario</a>
                <a href="revisar_solicitudes.php"><i class="fa-solid fa-plus-square"></i> Solicitudes de Material</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="ajustes.php"><i class="fa-solid fa-gear"></i> Configuración</a>
                <a href="documentacion.php"><i class="fa-solid fa-book"></i>Documentación</a>
            <?php elseif ($role === 'Almacen'): ?>
                <a href="productos.php"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php" class="active"><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href="prestamos_pendientes.php" ><i class="fa-solid fa-screwdriver-wrench"></i> Préstamos de herramientas</a>
                <a href="reportes_rotacion.php" ><i class="fa-solid fa-refresh"></i> Rotación de Inventario</a>
                <a href="revisar_solicitudes.php"><i class="fa-solid fa-plus-square"></i> Solicitudes de Material</a>
                <a href="mis_solicitudes.php"><i class="fa-solid fa-clipboard-list"></i> Mis Solicitudes</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="ajustes.php"><i class="fa-solid fa-gear"></i> Configuración</a>
                <a href="documentacion.php"><i class="fa-solid fa-book"></i>Documentación</a>
            <?php elseif ($role === 'Compras'): ?>
              <a href="productos.php"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php" class="active"><i class="fa-solid fa-list-check"></i> Inventario</a>
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
        <?php include __DIR__ . '/../partials/topbar.php'; ?>

        <main class="dashboard-main inventario-main">
            <div class="inventario-header">
                <div>
                    <h1>Inventario general</h1>
                    <p class="inventario-desc">Supervisa el estado del stock, ubicaciones y movimientos de productos.</p>
                </div>
                <?php if ($role !== 'Empleado'): ?>
                    <div class="inventario-actions">
                        <a class="btn-main" href="inventario_entradas.php"><i class="fa fa-plus"></i> Registrar entrada</a>
                        <a class="btn-secondary" href="inventario_salidas.php"><i class="fa fa-minus"></i> Registrar salida</a>
                        <a class="btn-secondary" href="inventario_transferencias.php"><i class="fa fa-right-left"></i> Transferir</a>
                    </div>
                <?php endif; ?>
            </div>

            <section class="inventario-stats-grid">
                <div class="inventario-stat-card primary">
                    <span class="stat-label">Productos registrados</span>
                    <span class="stat-value"><?= number_format($totalRegistros) ?></span>
                    <span class="stat-foot">Activos: <?= number_format((int) ($stats['activos'] ?? 0)) ?> ? Inactivos: <?= number_format((int) ($stats['inactivos'] ?? 0)) ?></span>
                </div>
                <div class="inventario-stat-card warning">
                    <span class="stat-label">Stock bajo</span>
                    <span class="stat-value"><?= number_format((int) ($stats['stock_bajo'] ?? 0)) ?></span>
                    <span class="stat-foot">Sin stock: <?= number_format((int) ($stats['sin_stock'] ?? 0)) ?></span>
                </div>
                <div class="inventario-stat-card sky">
                    <span class="stat-label">Composición</span>
                    <span class="stat-value"><?= number_format((int) ($stats['consumibles'] ?? 0)) ?> consumibles</span>
                    <span class="stat-foot">Herramientas: <?= number_format((int) ($stats['herramientas'] ?? 0)) ?></span>
                </div>
                <?php if ($mostrarCostos): ?>
                    <div class="inventario-stat-card success">
                        <span class="stat-label">Valor estimado</span>
                        <span class="stat-value">$<?= number_format((float) ($stats['valor_total'] ?? 0), 2) ?></span>
                        <span class="stat-foot">Costo acumulado de inventario con I.V.A.</span>
                    </div>
                <?php endif; ?>
            </section>

            <section class="inventario-filters-card">
                <form method="get" class="inventario-filters-form">
                    <div class="inv-filter-row">
                        <div class="inv-filter-field">
                            <label for="buscar">Búsqueda global</label>
                            <div class="filter-input-icon">
                                <i class="fa fa-search"></i>
                                <input type="text" id="buscar" name="buscar" placeholder="Nombre, código, descripción o proveedor" value="<?= $buscar ?>">
                            </div>
                        </div>
                        <div class="inv-filter-field">
                            <label for="codigo_barras">Código de barras</label>
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
                        <div class="inv-filter-field">
                            <label for="almacen_id">Almacén</label>
                            <select id="almacen_id" name="almacen_id">
                                <option value="">Todos</option>
                                <?php foreach ($almacenes as $almacen): ?>
                                    <option value="<?= $almacen['id'] ?>" <?= $almacenId == $almacen['id'] ? 'selected' : '' ?>><?= htmlspecialchars($almacen['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
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
                            <label for="estado">Estado físico</label>
                            <select id="estado" name="estado">
                                <option value="">Todos</option>
                                <?php foreach ($estadosProducto as $estado): ?>
                                    <option value="<?= $estado ?>" <?= $estadoFiltro === $estado ? 'selected' : '' ?>><?= $estado ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="inv-filter-field">
                            <label for="activo_id">Disponibilidad</label>
                            <select id="activo_id" name="activo_id">
                                <option value="">Todas</option>
                                <?php foreach ($estadosActivos as $estado): ?>
                                    <option value="<?= $estado['id'] ?>" <?= $activoFiltro == $estado['id'] ? 'selected' : '' ?>><?= htmlspecialchars($estado['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="inv-filter-field">
                            <label for="stock_flag">Estado de stock</label>
                            <select id="stock_flag" name="stock_flag">
                                <option value="">Todos</option>
                                <option value="bajo" <?= $stockFlag === 'bajo' ? 'selected' : '' ?>>Stock bajo</option>
                                <option value="sin" <?= $stockFlag === 'sin' ? 'selected' : '' ?>>Sin stock</option>
                                <option value="suficiente" <?= $stockFlag === 'suficiente' ? 'selected' : '' ?>>Stock suficiente</option>
                            </select>
                        </div>
                    </div>

                    <div class="inv-filter-row">
                        <div class="inv-filter-field">
                            <label for="valor_min">Valor mínimo (MXN)</label>
                            <input type="number" step="0.01" id="valor_min" name="valor_min" value="<?= $valorMin ?>">
                        </div>
                        <div class="inv-filter-field">
                            <label for="valor_max">Valor máximo (MXN)</label>
                            <input type="number" step="0.01" id="valor_max" name="valor_max" value="<?= $valorMax ?>">
                        </div>
                        <div class="inv-filter-field">
                            <label for="fecha_desde">Fecha de alta (desde)</label>
                            <input type="date" id="fecha_desde" name="fecha_desde" value="<?= $fechaDesde ?>">
                        </div>
                        <div class="inv-filter-field">
                            <label for="fecha_hasta">Fecha de alta (hasta)</label>
                            <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?= $fechaHasta ?>">
                        </div>
                    </div>

                    <div class="inv-filter-row">
                        <div class="inv-filter-field">
                            <label for="unidad_medida_id">Unidad de medida</label>
                            <select id="unidad_medida_id" name="unidad_medida_id">
                                <option value="">Todas</option>
                                <?php foreach ($unidades as $unidad): ?>
                                    <option value="<?= $unidad['id'] ?>" <?= $unidadMedidaId == $unidad['id'] ? 'selected' : '' ?>><?= htmlspecialchars($unidad['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="inv-filter-field">
                            <label for="per_page">Resultados por página</label>
                            <select id="per_page" name="per_page" onchange="this.form.submit()">
                                <?php foreach ($perPageOptions as $option): ?>
                                    <option value="<?= $option ?>" <?= (int)$perPage === (int)$option ? 'selected' : '' ?>><?= $option ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="inv-filter-field inv-filter-actions">
                            <button type="submit" class="btn-main"><i class="fa fa-filter"></i> Aplicar filtros</button>
                            <?php if ($hayFiltros): ?>
                                <a class="btn-ghost" href="inventario_actual.php"><i class="fa fa-eraser"></i> Limpiar</a>
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
                                    <th>Almacén</th>
                                    <th>Stock actual</th>
                                    <th>Stock mínimo</th>
                                    <?php if ($mostrarCostos): ?><th>Valor</th><?php endif; ?>
                                    <th>Estado</th>
                                    <th>Último movimiento</th>
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
                                        <td><?= htmlspecialchars($producto['almacen'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge badge-stock <?= $badgeStock ?>">
                                                <?= rtrim(rtrim(number_format($stockActual, 2), '0'), '.') ?> <?= htmlspecialchars($producto['unidad_abreviacion'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td><?= rtrim(rtrim(number_format($stockMinimo, 2), '0'), '.') ?> <?= htmlspecialchars($producto['unidad_abreviacion'] ?? '') ?></td>
                                        <?php if ($mostrarCostos): ?>
                                            <td>$<?= number_format($valor, 2) ?></td>
                                        <?php endif; ?>
                                        <td><span class="badge badge-activo <?= (int)($producto['activo_id'] ?? 1) === 1 ? 'activo' : 'inactivo' ?>"><?= htmlspecialchars($producto['estado_activo'] ?? '-') ?></span></td>
                                        <td><?= $fechaMovimiento ? date('d/m/Y H:i', strtotime($fechaMovimiento)) : 'Sin movimientos' ?></td>
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
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>

