<?php
require_once __DIR__ . '/../../helpers/Session.php';
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
$values = isset($data) && is_array($data) ? $data : [];
$errors = $errors ?? [];
$error = $error ?? '';
$breadcrumbs = [
    ['label' => 'Nuevo producto'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Producto | TAKAB</title>
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
                <a href="ajustes.php"><i class="fa-solid fa-gear"></i> Configuración</a>
                <a href="documentacion.php"><i class="fa-solid fa-book"></i>Documentación</a>
            <?php elseif ($role === 'Almacen'): ?>
                <a href="productos.php" class="active"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php" ><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href="prestamos_pendientes.php" ><i class="fa-solid fa-screwdriver-wrench"></i> Préstamos de herramientas</a>
                <a href="reportes_rotacion.php" ><i class="fa-solid fa-refresh"></i> Rotación de Inventario</a>
                <a href="revisar_solicitudes.php"><i class="fa-solid fa-plus-square"></i> Solicitudes de Material</a>
                <a href="mis_solicitudes.php"><i class="fa-solid fa-clipboard-list"></i> Mis Solicitudes</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="ajustes.php"><i class="fa-solid fa-gear"></i> Configuración</a>
                <a href="documentacion.php"><i class="fa-solid fa-book"></i>Documentación</a>
            <?php elseif ($role === 'Compras'): ?>
              <a href="productos.php" class="active|"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
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
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fa fa-circle-exclamation"></i>
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger"><i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="productos-header">
                <div>
                    <h1>Nuevo producto</h1>
                    <p class="productos-header-desc">Registra un nuevo artículo en el inventario de TAKAB.</p>
                </div>
                <div class="productos-header-actions">
                    <a class="btn-secondary" href="productos.php"><i class="fa fa-arrow-left"></i> Volver al listado</a>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                <section class="productos-form-card">
                    <h2><i class="fa fa-info-circle"></i> Información general</h2>
                    <div class="productos-form-grid">
                        <div class="productos-form-field">
                            <label for="codigo">Código interno *</label>
                            <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars($values['codigo'] ?? '') ?>" required>
                        </div>
                        <div class="productos-form-field">
                            <label for="codigo_barras">Código de barras</label>
                            <input type="text" id="codigo_barras" name="codigo_barras" value="<?= htmlspecialchars($values['codigo_barras'] ?? '') ?>" placeholder="Generado automaticamente si se deja vacio">
                            <span class="productos-form-note">Escanea o deja vacio para autogenerar.</span>
                        </div>
                        <div class="productos-form-field">
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($values['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="productos-form-field">
                            <label for="tipo">Tipo *</label>
                            <select id="tipo" name="tipo" required>
                                <?php foreach ($tiposProducto as $tipo): ?>
                                    <option value="<?= $tipo ?>" <?= (($values['tipo'] ?? '') === $tipo) ? 'selected' : '' ?>><?= htmlspecialchars($tipo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="productos-form-field">
                            <label for="estado">Estado físico *</label>
                            <select id="estado" name="estado" required>
                                <?php foreach ($estadosProducto as $estado): ?>
                                    <option value="<?= $estado ?>" <?= (($values['estado'] ?? '') === $estado) ? 'selected' : '' ?>><?= htmlspecialchars($estado) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="productos-form-field">
                            <label for="categoria_id">Categoría *</label>
                            <select id="categoria_id" name="categoria_id" required>
                                <option value="">Selecciona una categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria['id'] ?>" <?= (($values['categoria_id'] ?? '') == $categoria['id']) ? 'selected' : '' ?>><?= htmlspecialchars($categoria['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="productos-form-field">
                            <label for="proveedor_id">Proveedor</label>
                            <select id="proveedor_id" name="proveedor_id">
                                <option value="">Selecciona un proveedor</option>
                                <?php foreach ($proveedores as $proveedor): ?>
                                    <option value="<?= $proveedor['id'] ?>" <?= (($values['proveedor_id'] ?? '') == $proveedor['id']) ? 'selected' : '' ?>><?= htmlspecialchars($proveedor['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="productos-form-field">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="3" placeholder="Detalles adicionales"><?= htmlspecialchars($values['descripcion'] ?? '') ?></textarea>
                        </div>
                        <div class="productos-form-field">
                            <label for="clase_categoria"> SKU </label>
                            <input type="text" id="clase_categoria" name="clase_categoria" value="<?= htmlspecialchars($values['clase_categoria'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="marca">Marca</label>
                            <input type="text" id="marca" name="marca" value="<?= htmlspecialchars($values['marca'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="color">Color</label>
                            <input type="text" id="color" name="color" value="<?= htmlspecialchars($values['color'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="forma">Forma</label>
                            <input type="text" id="forma" name="forma" value="<?= htmlspecialchars($values['forma'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="especificaciones_tecnicas">Especificaciones técnicas</label>
                            <textarea id="especificaciones_tecnicas" name="especificaciones_tecnicas" rows="3"><?= htmlspecialchars($values['especificaciones_tecnicas'] ?? '') ?></textarea>
                        </div>
                        <div class="productos-form-field">
                            <label for="origen">Origen</label>
                            <input type="text" id="origen" name="origen" value="<?= htmlspecialchars($values['origen'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="tags">Etiquetas</label>
                            <input type="text" id="tags" name="tags" value="<?= htmlspecialchars($values['tags'] ?? '') ?>" placeholder="Ej. fibra, red, herramienta">
                        </div>
                    </div>
                </section>

                <section class="productos-form-card">
                    <h2><i class="fa fa-weight-hanging"></i> Dimensiones y peso</h2>
                    <div class="productos-form-grid">
                        <div class="productos-form-field">
                            <label for="peso">Peso (kg)</label>
                            <input type="number" step="0.01" id="peso" name="peso" value="<?= htmlspecialchars($values['peso'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="ancho">Ancho (cm)</label>
                            <input type="number" step="0.01" id="ancho" name="ancho" value="<?= htmlspecialchars($values['ancho'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="alto">Alto (cm)</label>
                            <input type="number" step="0.01" id="alto" name="alto" value="<?= htmlspecialchars($values['alto'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="profundidad">Profundidad (cm)</label>
                            <input type="number" step="0.01" id="profundidad" name="profundidad" value="<?= htmlspecialchars($values['profundidad'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="unidad_medida_id">Unidad de medida</label>
                            <select id="unidad_medida_id" name="unidad_medida_id">
                                <option value="">Selecciona una unidad</option>
                                <?php foreach ($unidades as $unidad): ?>
                                    <option value="<?= $unidad['id'] ?>" <?= (($values['unidad_medida_id'] ?? '') == $unidad['id']) ? 'selected' : '' ?>><?= htmlspecialchars($unidad['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="productos-form-note">Define cómo se descuenta del inventario (piezas, metros, kilos...).</span>
                        </div>
                    </div>
                </section>

                <section class="productos-form-card">
                    <h2><i class="fa fa-warehouse"></i> Inventario y costos</h2>
                    <div class="productos-form-grid">
                        <div class="productos-form-field">
                            <label for="almacen_id">Almacén asignado *</label>
                            <select id="almacen_id" name="almacen_id" required>
                                <option value="">Selecciona un almacén</option>
                                <?php foreach ($almacenes as $almacen): ?>
                                    <option value="<?= $almacen['id'] ?>" <?= (($values['almacen_id'] ?? '') == $almacen['id']) ? 'selected' : '' ?>><?= htmlspecialchars($almacen['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="productos-form-field">
                            <label for="ubicacion_fisica">Ubicación física</label>
                            <input type="text" id="ubicacion_fisica" name="ubicacion_fisica" value="<?= htmlspecialchars($values['ubicacion_fisica'] ?? '') ?>" placeholder="Ej. Estante A-3">
                        </div>
                        <div class="productos-form-field">
                            <label for="stock_actual">Stock actual *</label>
                            <input type="number" step="0.01" id="stock_actual" name="stock_actual" min="0" value="<?= htmlspecialchars($values['stock_actual'] ?? '0') ?>" required>
                        </div>
                        <div class="productos-form-field">
                            <label for="stock_minimo">Stock mínimo *</label>
                            <input type="number" step="0.01" id="stock_minimo" name="stock_minimo" min="0" value="<?= htmlspecialchars($values['stock_minimo'] ?? '0') ?>" required>
                        </div>
                        <div class="productos-form-field">
                            <label for="costo_compra">Costo de compra (MXN)</label>
                            <input type="number" step="0.01" id="costo_compra" name="costo_compra" value="<?= htmlspecialchars($values['costo_compra'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="precio_venta">Precio de venta (MXN)</label>
                            <input type="number" step="0.01" id="precio_venta" name="precio_venta" value="<?= htmlspecialchars($values['precio_venta'] ?? '') ?>">
                        </div>
                    </div>
                </section>

                <section class="productos-form-card">
                    <h2><i class="fa fa-image"></i> Imagen y archivos</h2>
                    <div class="productos-form-grid">
                        <div class="productos-form-field">
                            <label for="imagen_url">Fotografía del producto</label>
                            <input type="file" id="imagen_url" name="imagen_url" accept="image/*">
                            <span class="productos-form-note">Formatos permitidos: JPG, PNG, WEBP. Tamaño máximo 5&nbsp;MB.</span>
                        </div>
                    </div>
                </section>

                <div class="form-actions">
                    <a class="btn-secondary" href="productos.php"><i class="fa fa-arrow-left"></i> Cancelar</a>
                    <button type="submit" class="btn-main"><i class="fa fa-save"></i> Guardar producto</button>
                </div>
            </form>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>

