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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EDITAR PRODUCTO | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/config.css">
    <link rel="stylesheet" href="assets/css/productos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="module-inventory-warehouse"><?php $seccion_activa = 'catalogo_productos'; ?>
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
                    <h1 class="page-title-icon"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Editar Producto</h1>
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
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($values['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="productos-form-field">
                            <label for="codigo_fabricante">Código del Fabricante</label>
                            <input type="text" id="codigo_fabricante" name="codigo_fabricante" value="<?= htmlspecialchars($values['codigo_fabricante'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="codigos_barras">Código de Barras</label>
                            <input type="text" id="codigos_barras" name="codigos_barras" value="<?= htmlspecialchars($values['codigos_barras'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="num_serie">Número de Serie</label>
                            <input type="text" id="num_serie" name="num_serie" value="<?= htmlspecialchars($values['num_serie'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="codigo_sat">Código SAT</label>
                            <input type="text" id="codigo_sat" name="codigo_sat" value="<?= htmlspecialchars($values['codigo_sat'] ?? '') ?>">
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
                            <label for="categoria_id">Categoría *</label>
                            <select id="categoria_id" name="categoria_id" required>
                                <option value="">Selecciona una Categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria['id'] ?>" <?= (($values['categoria_id'] ?? '') == $categoria['id']) ? 'selected' : '' ?>><?= htmlspecialchars($categoria['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="productos-form-field">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($values['descripcion'] ?? '') ?></textarea>
                        </div>
                        <div class="productos-form-field">
                            <label for="marca">Marca *</label>
                            <input type="text" id="marca" name="marca" value="<?= htmlspecialchars($values['marca'] ?? '') ?>" required>
                        </div>
                        <div class="productos-form-field">
                            <label for="modelo">Modelo</label>
                            <input type="text" id="modelo" name="modelo" value="<?= htmlspecialchars($values['modelo'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="color">Color</label>
                            <input type="text" id="color" name="color" value="<?= htmlspecialchars($values['color'] ?? '') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="pais_origen">País de Origen</label>
                            <input type="text" id="pais_origen" name="pais_origen" value="<?= htmlspecialchars($values['pais_origen'] ?? '') ?>">
                        </div>
                    </div>
                </section>

                <section class="productos-form-card">
                    <h2><i class="fa fa-ruler-combined"></i> Unidad de Medida del Producto</h2>
                    <div class="productos-form-grid">
                        <div class="productos-form-field">
                            <label for="sistema">Sistema de Medida *</label>
                            <select id="sistema" name="sistema" required>
                                <option value="">Selecciona un Sistema</option>
                                <?php $sistemasMostrados = []; ?>
                                <?php foreach ($unidades as $unidad): ?>
                                    <?php
                                    $sistemaNombre = trim((string) ($unidad['sistema'] ?? ''));
                                    if ($sistemaNombre === '' || in_array($sistemaNombre, $sistemasMostrados, true)) continue;
                                    $sistemasMostrados[] = $sistemaNombre;
                                    ?>
                                    <option value="<?= htmlspecialchars($sistemaNombre) ?>" <?= (($values['sistema'] ?? '') === $sistemaNombre) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sistemaNombre) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="productos-form-field">
                            <label for="unidad_medida_id">Unidad de Medida *</label>
                            <select id="unidad_medida_id" name="unidad_medida_id" required>
                                <option value="">Selecciona una Unidad</option>
                                <?php foreach ($unidades as $unidad): ?>
                                    <option value="<?= $unidad['id'] ?>"
                                            data-sistema="<?= htmlspecialchars($unidad['sistema'] ?? '') ?>"
                                            <?= (($values['unidad_medida_id'] ?? '') == $unidad['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($unidad['nombre']) ?>
                                    </option>
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
                            <input type="text" id="ubicacion_fisica" name="ubicacion_fisica" maxlength="150" value="<?= htmlspecialchars($values['ubicacion_fisica'] ?? '') ?>" placeholder="Ej. Estante A-3">
                        </div>
                        <div class="productos-form-field">
                            <label for="stock_actual">Stock Total</label>
                            <input type="number" step="0.01" id="stock_actual" value="<?= htmlspecialchars($values['stock_actual'] ?? '0') ?>" readonly aria-describedby="stock_actual_note">
                            <span id="stock_actual_note" class="productos-form-note">El stock se modifica mediante entradas, salidas y transferencias por almacén.</span>
                        </div>
                        <div class="productos-form-field">
                            <label for="stock_minimo">Stock Mínimo *</label>
                            <input type="number" step="0.01" id="stock_minimo" name="stock_minimo" min="0" value="<?= htmlspecialchars($values['stock_minimo'] ?? '0') ?>" required>
                        </div>
                        <div class="productos-form-field">
                            <label for="precio_unitario">Precio Unitario (MXN)</label>
                            <input type="number" step="0.01" min="0" id="precio_unitario" name="precio_unitario" value="<?= htmlspecialchars($values['precio_unitario'] ?? '0.00') ?>">
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
<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectSistema = document.getElementById('sistema');
    const selectUnidad = document.getElementById('unidad_medida_id');
    if (!selectSistema || !selectUnidad) return;

    const filtrarUnidades = () => {
        const sistemaSeleccionado = selectSistema.value;
        let seleccionValida = false;

        Array.from(selectUnidad.options).forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                option.disabled = false;
                return;
            }
            const corresponde = sistemaSeleccionado !== '' && option.dataset.sistema === sistemaSeleccionado;
            option.hidden = !corresponde;
            option.disabled = !corresponde;
            if (corresponde && option.selected) seleccionValida = true;
        });

        if (!seleccionValida && selectUnidad.value !== '') {
            selectUnidad.value = '';
        }
    };

    selectSistema.addEventListener('change', filtrarUnidades);
    filtrarUnidades();
});
</script>
</body>
</html>

