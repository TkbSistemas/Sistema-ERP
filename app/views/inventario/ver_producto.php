<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
	@session_start();
}

// Variables seguras por defecto
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
$producto = is_array($producto ?? null) ? $producto : [];

// Valores derivados y tipados
$stockActual = (float) ($producto['stock_actual'] ?? 0);
$stockMinimo = (float) ($producto['stock_minimo'] ?? 0);
$valorInventario = (float) ($producto['costo_compra'] ?? 0) * $stockActual;
$unidad = $producto['unidad_abreviacion'] ?? $producto['unidad_medida_nombre'] ?? '';

$breadcrumbs = [
    ['label' => 'Detalle del producto'],
];

function safe_css_class($s) {
	$s = strtolower((string) $s);
	return preg_replace('/[^a-z0-9_-]/', '', $s);
}

function format_stock($value) {
	$num = (float) $value;
	if (abs($num - round($num)) < 0.00001) {
		return number_format($num, 0, '.', ',');
	}
	return number_format($num, 2, '.', ',');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<title>DETALLES DE PRODUCTO | TAKAB</title>
	<link rel="stylesheet" href="assets/css/dashboard.css">
	<link rel="stylesheet" href="assets/css/config.css">
	<link rel="stylesheet" href="assets/css/productos.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="module-inventory-warehouse">
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
					<h1>DETALLES DEL PRODUCTO</h1>
					<p class="productos-header-desc">Visualiza Toda la Información del Artículo Seleccionado.</p>
				</div>
				<div class="productos-header-actions">
					<a class="btn-secondary" href="catalogo_productos"><i class="fa fa-arrow-left"></i> Volver</a>
					<a class="btn-secondary" href="etiqueta_producto?id=<?= (int)($producto['id'] ?? 0) ?>"><i class="fa fa-barcode"></i> Imprimir Etiqueta</a>
				<a class="btn-main" href="editar_producto?id=<?= (int)($producto['id'] ?? 0) ?>"><i class="fa fa-pen"></i> Editar</a>
				</div>
			</div>
			<section class="productos-detail-card productos-hero">
				<div>
					<h1><?= htmlspecialchars($producto['nombre'] ?? '') ?></h1>
					<div class="hero-meta">
						<span class="badge badge-tipo <?= safe_css_class($producto['tipo'] ?? '') ?>">Tipo: <?= htmlspecialchars($producto['tipo'] ?? '') ?></span>
						<?php if (!empty($producto['categoria'])): ?>
							<span class="badge" style="background:#eef1ff;color:#3546a5;">Categoría: <?= htmlspecialchars($producto['categoria']) ?></span>
						<?php endif; ?>
					</div>
					<?php if (!empty($producto['descripcion'])): ?>
						<p class="hero-description"><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></p>
					<?php endif; ?>
					<div class="hero-stats">
						<div class="hero-stat">
							<span class="label">Stock Mínimo: </span>
							<!--span class="value"><//?= format_stock($stockActual) ?> <?= htmlspecialchars($unidad) ?></span-->
							<span class="value"><?= format_stock($stockMinimo) ?></span>
						</div>
						<div class="hero-stat">
							<span class="label">Costo Referencia: </span>
							<span class="value">$<?= number_format((float)($producto['precio_unitario'] ?? 0), 2) ?></span>
							<!--span class="stat-foot">Precio venta: $<//?= number_format((float)($producto['precio_venta'] ?? 0), 2) ?></span-->
						</div>
						<!--div class="hero-stat">
							<span class="label">Valor Inventario</span>
							<span class="value">$<//?= number_format($valorInventario, 2) ?></span>
							<span class="stat-foot">Almacén <//?= htmlspecialchars($producto['almacen'] ?? '-') ?></span>
						</div-->
						<div class="hero-stat">
							<span class="label">Ultimo Proveedor: </span>
							<span class="value"><?= htmlspecialchars($producto['proveedor'] ?? '-') ?></span>
						</div>
					</div>
				</div>
				<div class="hero-image">
					<?php if (!empty($producto['imagen_url'])): ?>
                                            <img src="<?= BASE_URL . htmlspecialchars($producto['imagen_url']) ?>" alt="Imagen de <?= htmlspecialchars($producto['nombre']) ?>" class="producto-imagen" style="max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 8px;">
                                        <?php else: ?>
                                            <span class="sin-imagen">Sin Imagen</span>
                                        <?php endif; ?>
				</div>
			</section>
			<section class="productos-detail-card">
				<h2><i class="fa fa-list"></i> Información General</h2>
				<div class="detail-grid">
					<div class="detail-item">
						<span class="label">Nomenclatura</span>
						<span class="value"><?= htmlspecialchars($producto['nomenclatura'] ?? '-') ?></span>
					</div>
					<div class="detail-item">
						<span class="label">Código Fabricante</span>
						<span class="value mono"><?= htmlspecialchars($producto['codigo_fabricante'] ?? '') ?></span>
					</div>
                    <div class="detail-item">
                    	<span class="label">Código de Barras</span>
                    	<span class="value mono"><?= htmlspecialchars($producto['codigos_barras'] ?? '-') ?></span>
                    </div>
                    <div class="detail-item">
                    	<span class="label">Número de Serie</span>
                    	<span class="value mono"><?= htmlspecialchars($producto['num_serie'] ?? '-') ?></span>
                    </div>
					<div class="detail-item">
						<span class="label">País de Origen</span>
						<span class="value"><?= htmlspecialchars($producto['pais_origen'] ?? '-') ?></span>
					</div>
					<div class="detail-item">
						<span class="label">Marca</span>
						<span class="value"><?= htmlspecialchars($producto['marca'] ?? '-') ?></span>
					</div>
					<div class="detail-item">
						<span class="label">Modelo</span>
						<span class="value"><?= htmlspecialchars($producto['modelo'] ?? '-') ?></span>
					</div>
					<div class="detail-item">
						<span class="label">Unidad de Medida</span>
						<span class="value"><?= htmlspecialchars($producto['unidad_medida_nombre'] ?? '-') ?></span>
					</div>
					<div class="detail-item">
						<span class="label">Color</span>
						<span class="value"><?= htmlspecialchars($producto['color'] ?? '-') ?></span>
					</div>
				</div>
			</section>
			<!--section class="productos-detail-card">
				<h2><i class="fa fa-ruler"></i> Dimensiones y Unidades</h2>
				<div class="detail-grid">
					<div class="detail-item">
						<span class="label">Peso</span>
						<span class="value"><//?= htmlspecialchars($producto['peso'] ?? '0') ?> kg</span>
					</div>
					<div class="detail-item">
						<span class="label">Ancho</span>
						<span class="value"><//?= htmlspecialchars($producto['ancho'] ?? '0') ?> cm</span>
					</div>
					<div class="detail-item">
						<span class="label">Alto</span>
						<span class="value"><//?= htmlspecialchars($producto['alto'] ?? '0') ?> cm</span>
					</div>
					<div class="detail-item">
						<span class="label">Profundidad</span>
						<span class="value"><//?= htmlspecialchars($producto['profundidad'] ?? '0') ?> cm</span>
					</div>
					<div class="detail-item">
						<span class="label">Unidad de medida</span>
						<span class="value"></?= htmlspecialchars($producto['unidad_medida_nombre'] ?? '-') ?></span>
					</div>
				</div>
			</section-->
			<section class="productos-detail-card">
				<h2><i class="fa fa-clock"></i> Historial Interno</h2>
				<div class="detail-grid">
					<div class="detail-item">
						<span class="label">Última Solicitud</span>
						<span class="value"><?= !empty($producto['last_request_date']) ? date('d/m/Y H:i', strtotime($producto['last_request_date'])) : 'Sin Registros' ?></span>
					</div>
					<div class="detail-item">
						<span class="label">Fecha de Registro</span>
						<span class="value"><?= !empty($producto['created_at']) ? date('d/m/Y H:i', strtotime($producto['created_at'])) : '-' ?></span>
					</div>
				</div>
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

