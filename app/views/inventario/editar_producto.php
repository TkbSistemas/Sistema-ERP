<?php
require_once __DIR__ . '/../../helpers/Session.php';
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
$values = isset($data) && is_array($data) ? $data : ($producto ?? []);
$errors = $errors ?? [];
$error = $error ?? '';
$breadcrumbs = [
    ['label' => 'Editar producto'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>EDITAR PRODUCTO | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/config.css">
    <link rel="stylesheet" href="assets/css/productos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body><?php $seccion_activa = 'catalogo_productos'; ?>
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
                    <h1>Editar Producto</h1>
                    <p class="productos-header-desc">Actualiza los Datos del Artículo Seleccionado.</p>
                </div>
                <div class="productos-header-actions">
                    <a class="btn-secondary" href="catalogo_productos"><i class="fa fa-arrow-left"></i> Volver</a>
                    <a class="btn-secondary" href="ver_producto?id=<?= (int) ($values['id'] ?? $producto['id']) ?>"><i class="fa fa-eye"></i> Ver detalle</a>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                <section class="productos-form-card">
                    <h2><i class="fa fa-info-circle"></i> Información General</h2>
                    <div class="productos-form-grid">
                        <div class="productos-form-field">
                            <label for="codigo">Código Interno *</label>
                            <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars($values['codigo'] ?? '') ?>" required>
                        </div>
                        <div class="productos-form-field">
                            <label for="codigo_barras">Código de Barras</label>
                            <input type="text" id="codigo_barras" name="codigo_barras" value="<?= htmlspecialchars($values['codigo_barras'] ?? '') ?>" placeholder="Generado automáticamente si se deja vacío">
                            <span class="productos-form-note">Escanea o Deja Vacío para Autogenerar.</span>
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
                            <label for="estado">Estado Físico *</label>
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
                            <textarea id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($values['descripcion'] ?? '') ?></textarea>
                        </div>
                        <div class="productos-form-field">
                            <label for="clase_categoria">SKU</label>
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
                            <input type="text" id="tags" name="tags" value="<?= htmlspecialchars($values['tags'] ?? '') ?>">
                        </div>
                    </div>
                </section>

                <section class="productos-form-card">
                    <h2><i class="fa fa-weight-hanging"></i> Dimensiones y Peso</h2>
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
                            <label for="unidad_medida_id">Unidad de Medida</label>
                            <select id="unidad_medida_id" name="unidad_medida_id">
                                <option value="">Selecciona una Unidad</option>
                                <?php foreach ($unidades as $unidad): ?>
                                    <option value="<?= $unidad['id'] ?>" <?= (($values['unidad_medida_id'] ?? '') == $unidad['id']) ? 'selected' : '' ?>><?= htmlspecialchars($unidad['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="productos-form-card">
                    <h2><i class="fa fa-warehouse"></i> Inventario y Costos</h2>
                    <div class="productos-form-grid">
                        <div class="productos-form-field">
                            <label for="almacen_id">Almacén Asignado *</label>
                            <select id="almacen_id" name="almacen_id" required>
                                <option value="">Selecciona un Almacén</option>
                                <?php foreach ($almacenes as $almacen): ?>
                                    <option value="<?= $almacen['id'] ?>" <?= (($values['almacen_id'] ?? '') == $almacen['id']) ? 'selected' : '' ?>><?= htmlspecialchars($almacen['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="productos-form-field">
                            <label for="ubicacion_fisica">Ubicación Física</label>
                            <input type="text" id="ubicacion_fisica" name="ubicacion_fisica" value="<?= htmlspecialchars($values['ubicacion_fisica'] ?? '') ?>" placeholder="Ej. Estante A-3">
                        </div>
                        <div class="productos-form-field">
                            <label for="stock_actual">Stock actual *</label>
                            <input type="number" step="0.01" id="stock_actual" name="stock_actual" min="0" value="<?= htmlspecialchars($values['stock_actual'] ?? '0') ?>" required>
                        </div>
                        <div class="productos-form-field">
                            <label for="stock_minimo">Stock Mínimo *</label>
                            <input type="number" step="0.01" id="stock_minimo" name="stock_minimo" min="0" value="<?= htmlspecialchars($values['stock_minimo'] ?? '0') ?>" required>
                        </div>
                        <div class="productos-form-field">
                            <label for="costo_compra">Costo de Compra (MXN)</label>
                            <input type="number" step="0.01" id="costo_compra" name="costo_compra" value="<?= htmlspecialchars($values['costo_compra'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="precio_venta">Precio de Venta (MXN)</label>
                            <input type="number" step="0.01" id="precio_venta" name="precio_venta" value="<?= htmlspecialchars($values['precio_venta'] ?? '') ?>">
                        </div>
                    </div>
                </section>

                <section class="productos-form-card">
                    <h2><i class="fa fa-image"></i> Imagen y Archivos</h2>
                    <div class="productos-form-grid">
                        <div class="productos-form-field current-image">
                            <label>Imagen Actual</label>
                            <?php
                                $imgPath = $values['imagen_url'] ?? '';
                                $src     = $imgPath ? '/' . ltrim(str_replace('\\', '/', $imgPath), '/') : '/assets/images/placeholder.png';
                            ?>
                            <img src="<?= htmlspecialchars($src) ?>" alt="Imagen actual del producto" class="producto-preview" onerror="this.onerror=null;this.src='/assets/images/placeholder.png';">
                        </div>
                        <div class="productos-form-field">
                            <label for="imagen_url">Actualizar Imagen</label>
                            <input type="file" id="imagen_url" name="imagen_url" accept="image/*">
                            <span class="productos-form-note">Si no Seleccionas Ningún Archivo se Conservará la Imagen Actual.</span>
                        </div>
                    </div>
                </section>

                <div class="form-actions">
                    <a class="btn-secondary" href="ver_producto?id=<?= (int) ($values['id'] ?? $producto['id']) ?>"><i class="fa fa-arrow-left"></i> Cancelar</a>
                    <button type="submit" class="btn-main"><i class="fa fa-save"></i> Guardar Cambios</button>
                </div>
            </form>
        </main>
    </div>
</div>
</body>
</html>

