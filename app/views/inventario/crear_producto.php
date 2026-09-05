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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NUEVO PRODUCTO | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/config.css">
    <link rel="stylesheet" href="assets/css/productos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="./assets/js/libs/sweetalert2.all.min.js"></script>
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
                    <h1>NUEVO PRODUCTO</h1>
                    <p class="productos-header-desc">Registra un Nuevo Artículo en el Catálogo.</p>
                </div>
                <div class="productos-header-actions">
                    <a class="btn-secondary" href="catalogo_productos"><i class="fa fa-arrow-left"></i> Volver al Listado</a>
                </div>
            </div>

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

            <form method="post" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                <section class="productos-form-card">
                    <h2><i class="fa fa-info-circle"></i> Información General</h2>
                    <div class="productos-form-grid">
                         <div class="productos-form-field">
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($values['nombre'] ?? '') ?>" placeholder="Nombre Ejemplo Producto" required>
                        </div>
                        <div class="productos-form-field">
                            <label for="codigo_fabricante">Código del Fabricante</label>
                            <input type="text" id="codigo_fabricante" name="codigo_fabricante" value="<?= htmlspecialchars($values['codigo_fabricante'] ?? '') ?>" placeholder="Código del Fabricante (Ej. Clave CT, )">
                        </div>
                        <div class="productos-form-field">
                            <label for="codigos_barras">Código de Barras</label>
                            <input type="text" id="codigos_barras" name="codigos_barras" value="<?= htmlspecialchars($values['codigos_barras'] ?? '') ?>" placeholder="Código de barras">
                            <span class="productos-form-note">Escanea o Captura.</span>
                        </div>
                        <div class="productos-form-field">
                            <label for="num_serie">Número de Serie</label>
                            <input type="text" id="num_serie" name="num_serie" value="<?= htmlspecialchars($values['num_serie'] ?? '') ?>" placeholder="Número de serie">
                        </div>
                        <div class="productos-form-field">
                            <label for="codigo_sat">Código SAT</label>
                            <input type="text" id="codigo_sat" name="codigo_sat" value="<?= htmlspecialchars($values['codigo_sat'] ?? '') ?>" placeholder="Código SAT">
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
                            <label for="marca">Marca *</label>
                            <input type="text" id="marca" name="marca" value="<?= htmlspecialchars($values['marca'] ?? '') ?>" placeholder="Marca del Producto" required>
                        </div>
                        <div class="productos-form-field">
                            <label for="modelo"> Modelo </label>
                            <input type="text" id="modelo" name="modelo" value="<?= htmlspecialchars($values['modelo'] ?? '') ?>" placeholder="Modelo del Producto">
                        </div>
                        <div class="productos-form-field">
                            <label for="pais_origen">País de Origen</label>
                            <input type="text" id="pais_origen" name="pais_origen" value="<?= htmlspecialchars($values['pais_origen'] ?? '') ?>" placeholder="País de Origen">
                        </div>
                        <div class="productos-form-field">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="3" placeholder="Detalles del Producto"><?= htmlspecialchars($values['descripcion'] ?? '') ?></textarea>
                        </div>
                        <div class="productos-form-field">
                            <label for="color">Color</label>
                            <input type="text" id="color" name="color" value="<?= htmlspecialchars($values['color'] ?? '') ?>" placeholder="Color del Producto">
                        </div>
                    </div>
                </section>

                <section class="productos-form-card">
                    <h2><i class="fa fa-weight-hanging"></i>Unidad de Medida del Producto</h2>
                    <div class="productos-form-grid">
                            <div class="productos-form-field">
                                <label for="sistema">Sistema de Medida</label>
                                <select id="sistema" name="sistema" required>
                                    <option value="">Selecciona un Sistema</option>
                                    <?php 
                                    $sistemasMostrados = []; 
                                    foreach ($unidades as $unidad): 
                                        $sistemaNombre = $unidad['sistema'] ?? '';
                                        if (empty($sistemaNombre) || in_array($sistemaNombre, $sistemasMostrados)) continue;
                                        $sistemasMostrados[] = $sistemaNombre;
                                    ?>
                                        <option value="<?= htmlspecialchars($sistemaNombre) ?>" <?= (($values['sistema'] ?? '') == $sistemaNombre) ? 'selected' : '' ?>>
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
                                                data-sistema="<?= htmlspecialchars($unidad['sistema']) ?>"
                                                <?= (($values['unidad_medida_id'] ?? '') == $unidad['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($unidad['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                    </div>
                </section>

                <section class="productos-form-card">
                    <h2><i class="fa fa-warehouse"></i> Almacén y Costos</h2>
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
                            <label for="stock_inicial">Stock inicial</label>
                            <input type="number" step="0.01" id="stock_inicial" name="stock_inicial" min="0" value="<?= htmlspecialchars($values['stock_inicial'] ?? '0') ?>">
                            <span class="productos-form-note">Se registrará en el almacén asignado.</span>
                        </div>
                        <div class="productos-form-field">
                            <label for="stock_minimo">Stock Mínimo</label>
                            <input type="number" step="1" id="stock_minimo" name="stock_minimo" min="0" value="<?= htmlspecialchars($values['stock_minimo'] ?? '0') ?>" >
                        </div>
                        <div class="productos-form-field">
                            <label for="precio_unitario">Precio de Unitario (MXN)</label>
                            <input type="number" step="0.01" id="precio_unitario" min="0" name="precio_unitario" value="<?= htmlspecialchars($values['precio_unitario'] ?? '0.00') ?>">
                        </div>
                        <div class="productos-form-field">
                            <label for="precio_unitario_usd">Precio de Unitario (USD)</label>
                            <input type="number" step="0.01" id="precio_unitario_usd" min="0" name="precio_unitario_usd" value="<?= htmlspecialchars($values['precio_unitario_usd'] ?? '0.00') ?>">
                        </div>
                    </div>
                </section>

                <section class="productos-form-card">
                    <h2><i class="fa fa-image"></i> Imagen y Archivos</h2>
                    <div class="productos-form-grid">
                        <div class="productos-form-field">
                            <label for="imagen_url">Fotografía del Producto</label>
                            <input type="file" id="imagen_url" name="imagen_url" accept="image/*">
                            <span class="productos-form-note">Formatos Permitidos: JPG, PNG, WEBP. Tamaño máximo 5&nbsp;MB.</span>
                        </div>
                    </div>
                </section>

                <div class="form-actions">
                    <a class="btn-secondary" href="catalogo_productos"><i class="fa fa-arrow-left"></i> Cancelar</a>
                    <button type="submit" class="btn-main"><i class="fa fa-save"></i> Guardar Producto</button>
                </div>
            </form>
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

document.addEventListener('DOMContentLoaded', () => {
    const selectSistema = document.getElementById('sistema');
    const selectUnidad = document.getElementById('unidad_medida_id');

    if (!selectSistema || !selectUnidad) {
        return;
    }

    function filtrarUnidades() {
        const sistemaSeleccionado = selectSistema.value;
        const opcionesUnidades = selectUnidad.querySelectorAll('option');

        let unidadAunValida = false;

        opcionesUnidades.forEach(option => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const sistemaDeEstaUnidad = option.dataset.sistema;
            if (sistemaSeleccionado !== '' && sistemaDeEstaUnidad === sistemaSeleccionado) {
                option.hidden = false;
                option.disabled = false;
                if (option.selected) unidadAunValida = true;
            } else {
                option.hidden = true;
                option.disabled = true;
            }
        });
        if (!unidadAunValida && selectUnidad.value !== '') {
            selectUnidad.value = '';
        }
    }
    selectSistema.addEventListener('change', filtrarUnidades);
    filtrarUnidades();
});
</script>
</body>
</html>

