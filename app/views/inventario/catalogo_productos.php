<?php
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';

$mensajeExito = null;
if (!empty($alerta['success'])) {
    $mensajeExito = $alerta['success'] == 2
        ? 'Producto actualizado correctamente.'
        : 'Producto registrado correctamente.';
}
$mensajeEliminado = !empty($alerta['deleted']) ? 'Producto eliminado correctamente.' : null;
$mensajeError = null;
$errorCode = $_GET['error'] ?? '';
if ($errorCode === 'relaciones') {
    $mensajeError = 'No se pudo eliminar el producto porque tiene movimientos, solicitudes o préstamos vinculados.';
} elseif ($errorCode === 'csrf') {
    $mensajeError = 'El formulario expiró, intenta nuevamente.';
}
$importResultado = $importAlert ?? null;
function format_stock($value) {
    $num = (float) $value;
    if (abs($num - round($num)) < 0.00001) {
        return number_format($num, 0, '.', ',');
    }
    return number_format($num, 2, '.', ',');
}

$totalRegistros = $totalRegistros ?? count($productos);
$page = $page ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$perPage = $perPage ?? 15;
$perPageOptions = $perPageOptions ?? [10, 15, 25, 50, 100];
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
    return $params ? ('?' . http_build_query($params)) : '?';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CATÁLOGO DE PRODUCTOS | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/config.css">
    <link rel="stylesheet" href="assets/css/productos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Ensures the Stock column is a bit wider and numbers don't wrap/cut */
        .productos-table .col-stock { width: 140px; min-width: 120px; text-align: center; }
        .productos-table td.col-stock { white-space: nowrap; }
        .productos-table td.col-stock .badge { display: inline-block; }
        .productos-table td.col-stock small { display: block; font-size: 0.75rem; color: #666; }
        @media (max-width: 700px) {
            .productos-table .col-stock { width: auto; min-width: 0; }
            .productos-table td.col-stock { white-space: normal; }
        }
    </style>
</head>
<body>
<?php $seccion_activa = 'catalogo_productos'; ?>
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


        <main class="dashboard-main productos-main">
            <?php if ($mensajeError): ?>
                <div class="alert alert-danger"><i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($mensajeError) ?></div>
            <?php endif; ?>
            <?php if ($mensajeExito): ?>
                <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($mensajeExito) ?></div>
            <?php endif; ?>
            <?php if ($mensajeEliminado): ?>
                <div class="alert alert-danger"><i class="fa fa-trash"></i> <?= htmlspecialchars($mensajeEliminado) ?></div>
            <?php endif; ?>
            <?php if (!empty($importResultado)): ?>
                <?php
                    $importSuccess = (int) ($importResultado['success'] ?? 0);
                    $importProcessed = (int) ($importResultado['processed'] ?? 0);
                    $importSkipped = (int) ($importResultado['skipped'] ?? 0);
                    $importErrors = $importResultado['errors'] ?? [];
                    $hayErroresImport = !empty($importErrors);
                ?>
                <div class="alert <?= $hayErroresImport ? 'alert-danger' : 'alert-success' ?>">
                    <i class="fa <?= $hayErroresImport ? 'fa-circle-exclamation' : 'fa-check-circle' ?>"></i>
                    Se procesaron <?= $importProcessed ?> filas. Importados correctamente: <?= $importSuccess ?><?= $importSkipped > 0 ? " ? Saltados: {$importSkipped}" : '' ?>.
                    <?php if ($hayErroresImport): ?>
                        <div class="alert-detail">
                            <strong>Observaciones:</strong>
                            <ul>
                                <?php foreach (array_slice($importErrors, 0, 8) as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                                <?php if (count($importErrors) > 8): ?>
                                    <li>Se omitieron <?= count($importErrors) - 8 ?> mensajes adicionales.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="productos-header">
                <div>
                    <h1>Catálogo de Productos</h1>
                    <p class="productos-header-desc">Administra el Catálogo de Materiales y Herramientas.</p>
                    <!-- ESTO DEBER SER UNA SWEET ALERT
                    <p class="productos-import-note desktop-only">Usa la plantilla para cargar múltiples productos. Los valores deben corresponder con los IDs de catálogos ya registrados (categorías, proveedores, almacenes, unidades).</p-->
                </div>
                <div class="productos-header-actions">
                    <a class="btn-secondary" href="productos_template.php"><i class="fa-solid fa-download"></i> Descargar Plantilla</a>
                    <form class="productos-import-form" action="productos_import.php" method="post" enctype="multipart/form-data">
                        <!--label class="btn-secondary btn-file">
                            <i class="fa-solid fa-file-csv"></i> Seleccionar CSV
                            <input type="file" name="productos_archivo" accept=".csv,text/csv" required>
                        </label-->
                        <button type="submit" class="btn-main"><i class="fa-solid fa-upload"></i> Importar Catálogo</button>
                    </form>
                    <!-- <a class="btn-secondary" href="productos_barcode.php"><i class="fa fa-barcode"></i> Buscar por código</a> -->
                    <a class="btn-main" href="producto_nuevo"><i class="fa fa-plus"></i> Nuevo Producto</a>
                </div>
                <p class="productos-import-note mobile-only">Usa la plantilla para cargar múltiples productos. Los valores deben corresponder con los IDs de catálogos ya registrados (categorías, proveedores, almacenes, unidades).</p>
            </div>

            <section class="productos-filters-card">
                <form method="get" class="productos-filters-form">
                    <div class="filter-row">
                        <div class="filter-field">
                            <label for="buscar">Búsqueda Global</label>
                            <div class="filter-input-icon">
                                <i class="fa fa-search"></i>
                                <input type="text" id="buscar" name="buscar" placeholder="Nombre, código, descripción o tags" value="<?= htmlspecialchars($filtros['buscar']) ?>">
                            </div>
                        </div>
                        <div class="filter-field">
                            <label for="codigo">Código Interno</label>
                            <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars($filtros['codigo']) ?>" placeholder="Ej. H001">
                        </div>
                        <div class="filter-field">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($filtros['nombre']) ?>" placeholder="Buscar por nombre exacto">
                        </div>
                        <div class="filter-field">
                            <label for="tipo">Tipo</label>
                            <select id="tipo" name="tipo">
                                <option value="">Todos</option>
                                <?php foreach ($tiposProducto as $tipo): ?>
                                    <option value="<?= $tipo ?>" <?= $filtros['tipo'] === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-row">
                        <div class="filter-field">
                            <label for="categoria_id">Categoría</label>
                            <select id="categoria_id" name="categoria_id">
                                <option value="">Todas</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria['id'] ?>" <?= $filtros['categoria_id'] == $categoria['id'] ? 'selected' : '' ?>><?= htmlspecialchars($categoria['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label for="almacen_id">Almacén</label>
                            <select id="almacen_id" name="almacen_id">
                                <option value="">Todos</option>
                                <?php foreach ($almacenes as $almacen): ?>
                                    <option value="<?= $almacen['id'] ?>" <?= $filtros['almacen_id'] == $almacen['id'] ? 'selected' : '' ?>><?= htmlspecialchars($almacen['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label for="proveedor_id">Proveedor</label>
                            <select id="proveedor_id" name="proveedor_id">
                                <option value="">Todos</option>
                                <?php foreach ($proveedores as $proveedor): ?>
                                    <option value="<?= $proveedor['id'] ?>" <?= $filtros['proveedor_id'] == $proveedor['id'] ? 'selected' : '' ?>><?= htmlspecialchars($proveedor['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label for="estado">Estado Físico</label>
                            <select id="estado" name="estado">
                                <option value="">Todos</option>
                                <?php foreach ($estadosProducto as $estado): ?>
                                    <option value="<?= $estado ?>" <?= $filtros['estado'] === $estado ? 'selected' : '' ?>><?= $estado ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-row">
                        <div class="filter-field">
                            <label for="activo_id">Disponibilidad</label>
                            <select id="activo_id" name="activo_id">
                                <option value="">Todas</option>
                                <?php foreach ($estadosActivos as $estadoActivo): ?>
                                    <option value="<?= $estadoActivo['id'] ?>" <?= $filtros['activo_id'] == $estadoActivo['id'] ? 'selected' : '' ?>><?= htmlspecialchars($estadoActivo['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label for="stock_flag">Estado de Stock</label>
                            <select id="stock_flag" name="stock_flag">
                                <option value="">Todos</option>
                                <option value="bajo" <?= $filtros['stock_flag'] === 'bajo' ? 'selected' : '' ?>>Stock bajo</option>
                                <option value="sin" <?= $filtros['stock_flag'] === 'sin' ? 'selected' : '' ?>>Sin stock</option>
                                <option value="suficiente" <?= $filtros['stock_flag'] === 'suficiente' ? 'selected' : '' ?>>Stock suficiente</option>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label for="unidad_medida_id">Unidad</label>
                            <select id="unidad_medida_id" name="unidad_medida_id">
                                <option value="">Todas</option>
                                <?php foreach ($unidades as $unidad): ?>
                                    <option value="<?= $unidad['id'] ?>" <?= $filtros['unidad_medida_id'] == $unidad['id'] ? 'selected' : '' ?>><?= htmlspecialchars($unidad['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label for="tags">Tags</label>
                            <input type="text" id="tags" name="tags" value="<?= htmlspecialchars($filtros['tags']) ?>" placeholder="Palabras clave">
                        </div>
                    </div>

                    <div class="filter-row">
                        <div class="filter-field">
                            <label for="fecha_desde">Fecha de Alta (Desde)</label>
                            <input type="date" id="fecha_desde" name="fecha_desde" value="<?= htmlspecialchars($filtros['fecha_desde']) ?>">
                        </div>
                        <div class="filter-field">
                            <label for="fecha_hasta">Fecha de Alta (Hasta)</label>
                            <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?= htmlspecialchars($filtros['fecha_hasta']) ?>">
                        </div>
                        <div class="filter-field">
                            <label for="valor_min">Valor Inventario mínimo</label>
                            <input type="number" step="0.01" id="valor_min" name="valor_min" value="<?= htmlspecialchars($filtros['valor_min']) ?>" placeholder="Ej. 1000">
                        </div>
                        <div class="filter-field">
                            <label for="valor_max">Valor Inventario máximo</label>
                            <input type="number" step="0.01" id="valor_max" name="valor_max" value="<?= htmlspecialchars($filtros['valor_max']) ?>" placeholder="Ej. 5000">
                        </div>
                    </div>

                    <div class="filter-row">
                        <div class="filter-field">
                            <label for="per_page">Resultados por Página</label>
                            <select id="per_page" name="per_page" onchange="this.form.submit()">
                                <?php foreach ($perPageOptions as $option): ?>
                                    <option value="<?= $option ?>" <?= (int)$perPage === (int)$option ? 'selected' : '' ?>><?= $option ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn-main"><i class="fa fa-filter"></i> Aplicar Filtros</button>
                            <?php if ($hayFiltros): ?>
                                <a class="btn-ghost" href="catalogo_productos"><i class="fa fa-eraser"></i> Limpiar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </section>

            <section class="productos-table-card">
                <div class="productos-table-header">
                    <h2><i class="fa-solid fa-cubes"></i> Catálogo (<?= number_format($stats['total']) ?>)</h2>
                    <span class="productos-table-sub">Resultados Según Filtros Aplicados</span>
                </div>
                <div class="productos-table-wrapper">
                    <?php if (empty($productos)): ?>
                        <div class="productos-empty">
                            <i class="fa fa-inbox"></i>
                            <p>No se encontraron productos con los criterios seleccionados.</p>
                        </div>
                    <?php else: ?>
                        <table class="productos-table">
                            <thead>
                            <tr>
                                <th>Código</th>
                                <!--th>Código de barras</th-->
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th>Categoría</th>
                                <th class="col-stock">Stock</th>
                                <!--th>Estado</th-->
                                <!--th>Disponibilidad</th-->
                                <!--th>Almacén</th-->
                                <th>Proveedor</th>
                                <!--th>Valor</th-->
                                <th >Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($productos as $producto): ?>
                                <?php
                                $stockActual = (float) ($producto['stock_actual'] ?? 0);
                                $stockMinimo = (float) ($producto['stock_minimo'] ?? 0);
                                $valorInventario = (float) ($producto['costo_compra'] ?? 0) * $stockActual;
                                $badgeStock = 'ok';
                                if ($stockActual <= 0) {
                                    $badgeStock = 'sin';
                                } elseif ($stockActual < $stockMinimo) {
                                    $badgeStock = 'bajo';
                                }
                                ?>
                                <tr>
                                    <td><span class="mono"><?= htmlspecialchars($producto['codigo']) ?></span></td>
                                    <!--td><span class="mono"><//?= htmlspecialchars($producto['codigo_barras'] ?? '') ?></span></td-->
                                    <td>
                                        <strong><?= htmlspecialchars($producto['nombre']) ?></strong>
                                        <?php if (!empty($producto['tags'])): ?>
                                            <div class="producto-tags"><?= htmlspecialchars($producto['tags']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-tipo <?= strtolower($producto['tipo'] ?? '') ?>"><?= htmlspecialchars($producto['tipo']) ?></span></td>
                                    <td><?= htmlspecialchars($producto['categoria'] ?? 'Sin categor?a') ?></td>
                                    <td class="col-stock">
                                        <span class="badge badge-stock <?= $badgeStock ?>">
                                            <?= format_stock($stockActual) ?> <?= htmlspecialchars($producto['unidad_abreviacion'] ?? '') ?>
                                        </span>
                                        <small>Mín: <?= format_stock($stockMinimo) ?></small>
                                    </td>
                                    <!--td><//?= htmlspecialchars($producto['estado'] ?? '-') ?></td-->
                                    <!--td>
                                        <span class="badge badge-activo <?= (int)($producto['activo_id'] ?? 1) === 1 ? 'activo' : 'inactivo' ?>">
                                            <//?= htmlspecialchars($producto['estado_activo'] ?? 'Activo') ?>
                                        </span>
                                    </td-->
                                    <!--td><//?= htmlspecialchars($producto['almacen'] ?? '-') ?></td-->
                                    <td><?= htmlspecialchars($producto['proveedor'] ?? '-') ?></td>
                                    <!--td>$<//?= number_format($valorInventario, 2) ?></td-->
                                    <td class="col-actions">
                                        <a class="btn-table" title="Ver detalle" href="productos_view.php?id=<?= $producto['id'] ?>"><i class="fa fa-eye"></i></a>
                                        <a class="btn-table" title="Editar" href="productos_edit.php?id=<?= $producto['id'] ?>"><i class="fa fa-pen"></i></a>
                                        <!--a class="btn-table" title="Imprimir etiqueta" href="productos_etiqueta.php?id=<?= $producto['id'] ?>"><i class="fa fa-barcode"></i></a-->
                                        <form method="post" action="productos_setactive.php" class="inline-form" style="display:inline-block">
                                            <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= (int) $producto['id'] ?>">
                                            <input type="hidden" name="active" value="<?= (int)($producto['activo_id'] ?? 1) === 1 ? 0 : 1 ?>">
                                            <button type="submit" class="btn-table btn-danger" title="Eliminar">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                            <!--button type="submit"
                                                    class="btn-table"
                                                    title="<//?= (int)($producto['activo_id'] ?? 1) === 1 ? 'Desactivar' : 'Activar' ?>"
                                                    data-confirm-click="<//?= (int)($producto['activo_id'] ?? 1) === 1 ? '?Desactivar este producto?' : '?Activar este producto?' ?>">
                                                <i class="fa <//?= (int)($producto['activo_id'] ?? 1) === 1 ? 'fa-toggle-off' : 'fa-toggle-on' ?>"></i>
                                            </button-->
                                        </form>
                                        <!--form method="post" action="productos_delete.php" class="inline-form" style="display:inline-block" data-confirm="?Eliminar el producto seleccionado? Esta acci?n no se puede deshacer.">
                                            <input type="hidden" name="csrf" value="<//?= Session::csrfToken() ?>">
                                            <input type="hidden" name="id" value="<//?= (int) $producto['id'] ?>">
                                            <button type="submit" class="btn-table btn-danger" title="Eliminar">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form-->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>

            <div class="productos-pagination">
                <div class="productos-pagination-info">
                    <?= $totalRegistros > 0
                        ? "Mostrando $desde - $hasta de " . number_format($totalRegistros) . " registros"
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
            // Añade o quita la clase '.collapsed' al menú
            console.log('Sidebar toggle script loaded');
            sidebar.classList.toggle('collapsed');
            
            // Añade o quita la clase '.collapsed' al contenido para que se estire
            mainContent.classList.toggle('collapsed');
            
            // Opcional: Cambia el icono de barras (三) a una equis (X) cuando abra/cierre
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



