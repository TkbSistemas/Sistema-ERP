<?php
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
$producto = $producto ?? [];
$almacenes = $almacenes ?? [];

$unidadPredeterminada = $producto['unidad_abreviacion'] ?? ($producto['unidad_medida_nombre'] ?? '');
$loteValor = trim($_POST['lote'] ?? '');
$almacenSeleccionado = (int) ($_POST['almacen_id'] ?? ($producto['almacen_id'] ?? 0));
$cantidadValor = max(1, min(50, (int) ($_POST['cantidad'] ?? 1)));
$unidadEtiqueta = trim($_POST['unidad_etiqueta'] ?? $unidadPredeterminada);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas de producto | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/config.css">
    <link rel="stylesheet" href="/assets/css/productos.css">
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
                <a href="productos.php" class="active"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php" ><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href="prestamos_pendientes.php" ><i class="fa-solid fa-screwdriver-wrench"></i> Préstamos de herramientas</a>
                <a href="reportes_rotacion.php" ><i class="fa-solid fa-refresh"></i> Rotación de Inventario</a>
                <a href="revisar_solicitudes.php"><i class="fa-solid fa-plus-square"></i> Solicitudes de Material</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="configuracion.php"><i class="fa-solid fa-gear"></i> Configuración</a>
                <a href="documentacion.php"><i class="fa-solid fa-book"></i>Documentación</a>
            <?php elseif ($role === 'Almacen'): ?>
                <a href="productos.php" class="active"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php" ><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href="prestamos_pendientes.php" ><i class="fa-solid fa-screwdriver-wrench"></i> Préstamos de herramientas</a>
                <a href="reportes_rotacion.php" ><i class="fa-solid fa-refresh"></i> Rotación de Inventario</a>
                <a href="revisar_solicitudes.php"><i class="fa-solid fa-plus-square"></i> Solicitudes de Material</a>
                <a href="mis_solicitudes.php"><i class="fa-solid fa-clipboard-list"></i> Mis Solicitudes</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="configuracion.php"><i class="fa-solid fa-gear"></i> Configuración</a>
                <a href="documentacion.php"><i class="fa-solid fa-book"></i>Documentación</a>
            <?php elseif ($role === 'Compras'): ?>
              <a href="productos.php" class="active"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
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
        <?php include __DIR__ . '/../partials/topbar.php'; ?>

        <main class="dashboard-main productos-main">
            <div class="productos-header">
                <div>
                    <h1>Imprimir etiquetas</h1>
                    <p class="productos-header-desc">Genera etiquetas PDF con código de barras para el producto seleccionado.</p>
                </div>
                <div class="productos-header-actions">
                    <a class="btn-secondary" href="productos_view.php?id=<?= (int) ($producto['id'] ?? 0) ?>"><i class="fa fa-arrow-left"></i> Volver al producto</a>
                </div>
            </div>

            <section class="productos-detail-card productos-hero">
                <div>
                    <h1><?= htmlspecialchars($producto['nombre'] ?? 'Producto') ?></h1>
                    <div class="hero-meta">
                        <span class="badge badge-stock ok">Código interno: <?= htmlspecialchars($producto['codigo'] ?? '-') ?></span>
                        <span class="badge badge-stock ok">Código de barras: <?= htmlspecialchars($producto['codigo_barras'] ?? '-') ?></span>
                        <span class="badge badge-activo"><?= htmlspecialchars($producto['almacen'] ?? 'Sin almacen') ?></span>
                    </div>
                    <?php if (!empty($producto['descripcion'])): ?>
                        <p class="hero-description"><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="productos-detail-card">
                <h2><i class="fa fa-print"></i> Configuración de etiquetas</h2>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" class="form-grid">
                    <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                    <div class="form-group">
                        <label for="almacen_id">Almacén</label>
                        <select id="almacen_id" name="almacen_id" required>
                            <option value="">Selecciona un almacén</option>
                            <?php foreach ($almacenes as $almacen): ?>
                                <option value="<?= (int) $almacen['id'] ?>" <?= (int) $almacen['id'] === $almacenSeleccionado ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($almacen['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="lote">Lote o referencia</label>
                        <input type="text" id="lote" name="lote" maxlength="50" value="<?= htmlspecialchars($loteValor) ?>">
                    </div>

                    <div class="form-group">
                        <label for="unidad_etiqueta">Unidad mostrada</label>
                        <input type="text" id="unidad_etiqueta" name="unidad_etiqueta" maxlength="20" value="<?= htmlspecialchars($unidadEtiqueta) ?>">
                    </div>

                    <div class="form-group">
                        <label for="cantidad">Cantidad de etiquetas</label>
                        <input type="number" id="cantidad" name="cantidad" min="1" max="50" value="<?= (int) $cantidadValor ?>">
                        <small>Maximo 50 etiquetas por generacion.</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-main"><i class="fa fa-file-pdf"></i> Generar PDF</button>
                        <a href="productos.php" class="btn-secondary"><i class="fa fa-times"></i> Cancelar</a>
                    </div>
                </form>
            </section>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>


