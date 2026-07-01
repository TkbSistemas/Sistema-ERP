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
    <title>ETIQUETA DE PRODUCTO | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/config.css">
    <link rel="stylesheet" href="assets/css/productos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
            <div class="productos-header">
                <div>
                    <h1>Imprimir Etiquetas</h1>
                    <p class="productos-header-desc">Genera Etiquetas PDF con Código de Barras para el Producto Seleccionado.</p>
                </div>
                <div class="productos-header-actions">
                    <a class="btn-secondary" href="ver_producto?id=<?= (int) ($producto['id'] ?? 0) ?>"><i class="fa fa-arrow-left"></i> Volver al Producto</a>
                </div>
            </div>

            <section class="productos-detail-card productos-hero">
                <div>
                    <h1><?= htmlspecialchars($producto['nombre'] ?? 'Producto') ?></h1>
                    <div class="hero-meta">
                        <span class="badge badge-stock ok">Código Interno: <?= htmlspecialchars($producto['codigo'] ?? '-') ?></span>
                        <span class="badge badge-stock ok">Código de Barras: <?= htmlspecialchars($producto['codigo_barras'] ?? '-') ?></span>
                        <span class="badge badge-activo"><?= htmlspecialchars($producto['almacen'] ?? 'Sin almacen') ?></span>
                    </div>
                    <?php if (!empty($producto['descripcion'])): ?>
                        <p class="hero-description"><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="productos-detail-card">
                <h2><i class="fa fa-print"></i> Información de Etiquetas</h2>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" class="form-grid">
                    <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                    <div class="form-group">
                        <label for="almacen_id">Almacén</label>
                        <select id="almacen_id" name="almacen_id" required>
                            <option value="">Selecciona un Almacén</option>
                            <?php foreach ($almacenes as $almacen): ?>
                                <option value="<?= (int) $almacen['id'] ?>" <?= (int) $almacen['id'] === $almacenSeleccionado ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($almacen['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="lote">Lote o Referencia</label>
                        <input type="text" id="lote" name="lote" maxlength="50" value="<?= htmlspecialchars($loteValor) ?>">
                    </div>

                    <div class="form-group">
                        <label for="unidad_etiqueta">Unidad Mostrada</label>
                        <input type="text" id="unidad_etiqueta" name="unidad_etiqueta" maxlength="20" value="<?= htmlspecialchars($unidadEtiqueta) ?>">
                    </div>

                    <div class="form-group">
                        <label for="cantidad">Cantidad de Etiquetas</label>
                        <input type="number" id="cantidad" name="cantidad" min="1" max="50" value="<?= (int) $cantidadValor ?>">
                        <small>Maximo 50 Etiquetas.</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-main"><i class="fa fa-file-pdf"></i> Generar PDF</button>
                        <a class="btn-secondary" href="ver_producto?id=<?= (int) ($producto['id'] ?? 0) ?>"><i class="fa fa-times"></i> Cancelar</a>
                    </div>
                </form>
            </section>
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


