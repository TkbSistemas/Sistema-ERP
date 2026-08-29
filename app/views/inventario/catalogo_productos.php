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
    return $params ? ('?' . http_build_query($params)) : '?';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CATÁLOGO DE PRODUCTOS | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/config.css">
    <link rel="stylesheet" href="assets/css/productos.css">
    <link rel="stylesheet" href="assets/css/inventario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="./assets/js/libs/sweetalert2.all.min.js"></script>
    <style>
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
<body class="module-inventory-warehouse">
<?php $seccion_activa = 'catalogo_productos'; ?>
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Toggle Menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>
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
                                    <li>Se Omitieron <?= count($importErrors) - 8 ?> Mensajes Adicionales.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['alerta'])): ?>
                    <script>
                        Swal.fire({
                            icon: '<?php echo $_SESSION['alerta']['tipo']; ?>',
                            title: '<?php echo $_SESSION['alerta']['titulo']; ?>',
                            text: '<?php echo $_SESSION['alerta']['mensaje']; ?>',
                            confirmButtonColor: '#3085d6'
                        });
                    </script>
                    <?php 
                        unset($_SESSION['alerta']); 
                    ?>
                <?php endif; ?>

            <div class="productos-header">
                <div>
                    <h1>CATÁLOGO DE PRODUCTOS</h1>
                    <p class="productos-header-desc">Administra el Catálogo de Materiales y Herramientas.</p>
                    <!-- ESTO DEBER SER UNA SWEET ALERT
                    <p class="productos-import-note desktop-only">Usa la plantilla para cargar múltiples productos. Los valores deben corresponder con los IDs de catálogos ya registrados (categorías, proveedores, almacenes, unidades).</p-->
                </div>
                <div class="productos-header-actions">
                    <a class="btn-secondary" href=""><i class="fa-solid fa-download"></i> Descargar Plantilla</a>
                    <form class="productos-import-form" id="importForm" action="importar_catalogo" method="post" enctype="multipart/form-data">
                        <input type="file" id="csvFileInput" name="productos_archivo" accept=".csv,text/csv" style="display: none;" required>
                        
                        <button type="button" id="btnImportar" class="btn-main">
                            <i class="fa-solid fa-file-csv"></i> Importar Catálogo
                        </button>
                    </form>
                    <!-- <a class="btn-secondary" href="productos_barcode.php"><i class="fa fa-barcode"></i> Buscar por código</a> -->
                    <a class="btn-main" href="producto_nuevo"><i class="fa fa-plus"></i> Nuevo Producto</a>
                </div>
                <p class="productos-import-note mobile-only">Usa la plantilla para cargar múltiples productos. Los valores deben corresponder con los IDs de catálogos ya registrados (categorías, proveedores, almacenes, unidades).</p>
            </div>

            <section class="inventario-filters-card">
                <form method="get" class="productos-filters-form">

                    <div class="filter-row">
                        <div class="filter-field">
                            <label for="buscar">Búsqueda Global</label>
                            <div class="filter-input-icon">
                                <i class="fa fa-search"></i>
                                <input type="text" id="buscar" name="buscar" placeholder="Nombre, Código, Descripción, Marca o Modelo" value="<?= htmlspecialchars($filtros['buscar']) ?>" style="width: 100% !important;">
                            </div>
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
                            <label for="fecha_desde">Fecha de Alta (Desde)</label>
                            <input type="date" id="fecha_desde" name="fecha_desde" value="<?= htmlspecialchars($filtros['fecha_desde']) ?>">
                        </div>
                        <div class="filter-field">
                            <label for="fecha_hasta">Fecha de Alta (Hasta)</label>
                            <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?= htmlspecialchars($filtros['fecha_hasta']) ?>">
                        </div>
                    </div>

                    <div class="filter-row">
                        <div class="filter-field">
                            <label for="codigo">Código Barras:</label>
                            <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars($filtros['codigo']) ?>" placeholder="Ej. 1156161">
                        </div>
                        <div class="filter-field">
                            <label for="tipo">Tipo:</label>
                            <select id="tipo" name="tipo">
                                <option value="">Todos</option>
                                <?php foreach ($tiposProducto as $tipo): ?>
                                    <option value="<?= $tipo ?>" <?= $filtros['tipo'] === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
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
                            <label for="marca">Marca:</label>
                            <input type="text" id="marca" name="marca" value="<?= htmlspecialchars($filtros['marca']) ?>" placeholder="Buscar por Marca">
                        </div>
                    </div>

                    <div class="inv-filter-row">
                        <div class="inv-filter-actions">
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
                            <p>No se Encontraron Productos con los Criterios Seleccionados.</p>
                        </div>
                    <?php else: ?>
                        <table class="productos-table">
                            <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th>Categoría</th>
                                <th class="col-stock">Stock</th>
                                <th class="col-actions">Acciones</th>
                                <th >Imagen</th>
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
                                    <td><span class="mono"><?= htmlspecialchars($producto['nomenclatura']) ?></span></td>
                                    <td>
                                        <strong><?= htmlspecialchars($producto['nombre']) ?></strong>
                                    </td>
                                    <td><span class="badge badge-tipo <?= strtolower($producto['tipo'] ?? '') ?>"><?= htmlspecialchars($producto['tipo']) ?></span></td>
                                    <td><?= htmlspecialchars($producto['categoria'] ?? 'Sin categor?a') ?></td>
                                    <td class="col-stock">
                                        <span class="badge badge-stock <?= $badgeStock ?>">
                                            <?= format_stock($stockActual) ?> <?= htmlspecialchars($producto['unidad_apodo'] ?? '') ?>
                                        </span>
                                        <small>Mín: <?= format_stock($stockMinimo) ?></small>
                                    </td>
                                    <td class="col-actions">
                                        <div class="acciones-celda">
                                        <a class="btn-table" title="Ver Detalles" href="ver_producto?id=<?= $producto['id'] ?>"><i class="fa fa-eye"></i></a>
                                        <form method="post" action="eliminar_producto" class="inline-form form-eliminar" style="display:inline-block">
                                            <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= (int) $producto['id'] ?>">
                                            <input type="hidden" name="active" value="0">
                                            
                                            <button type="submit" 
                                                    class="btn-table btn-danger" 
                                                    title="Eliminar Producto"
                                                    data-nombre="<?= htmlspecialchars($producto['nombre']) ?>">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($producto['imagen_url'])): ?>
                                            <img src="<?= BASE_URL . htmlspecialchars($producto['imagen_url']) ?>" alt="Imagen de <?= htmlspecialchars($producto['nombre']) ?>" class="producto-imagen" style="max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 8px;">
                                        <?php else: ?>
                                            <span class="sin-imagen">Sin Imagen</span>
                                        <?php endif; ?>
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
    document.addEventListener("DOMContentLoaded", function() {
        const btnImportar = document.getElementById('btnImportar');
        const fileInput = document.getElementById('csvFileInput');
        const importForm = document.getElementById('importForm');
        btnImportar.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            if (fileInput.files.length > 0) {
                btnImportar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Subiendo Archivo...';
                
                importForm.submit();
            }
        });
    });

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

document.addEventListener("DOMContentLoaded", function() {
    const formularios = document.querySelectorAll('.form-eliminar');

    formularios.forEach(formulario => {
        formulario.addEventListener('submit', function(e) {
            e.preventDefault(); // Frenamos el envío automático

            const boton = formulario.querySelector('button[type="submit"]');
            const nombreProducto = boton.getAttribute('data-nombre');

            Swal.fire({
                title: '¿Estás Seguro de Eliminar este Producto?',
                text: `"${nombreProducto}" Será Removido del Inventario.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33', // Rojo de eliminación
                cancelButtonColor: '#7286a6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    formulario.submit(); // Enviamos el formulario si confirma
                }
            });
        });
    });
});
</script>
</body>
</html>



