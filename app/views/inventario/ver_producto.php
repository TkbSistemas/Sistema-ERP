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
$estadoActivo = $producto['estado_activo'] ?? 'Activo';
$unidad = $producto['unidad_abreviacion'] ?? $producto['unidad_medida_nombre'] ?? '';
$tags = array_filter(array_map('trim', explode(',', $producto['tags'] ?? '')));

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
	<title>DETALLES DE PRODUCTO | TAKAB</title>
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
					<h1>Detalles del Producto</h1>
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
						<span class="badge badge-tipo <?= safe_css_class($producto['tipo'] ?? '') ?>"><?= htmlspecialchars($producto['tipo'] ?? '') ?></span>
						<span class="badge badge-stock <?= $stockActual <= 0 ? 'sin' : ($stockActual < $stockMinimo ? 'bajo' : 'ok') ?>">
							Código: <?= htmlspecialchars($producto['codigo'] ?? '') ?>
						</span>
						<?php if (!empty($producto['categoria'])): ?>
							<span class="badge" style="background:#eef1ff;color:#3546a5;">Categoría: <?= htmlspecialchars($producto['categoria']) ?></span>
						<?php endif; ?>
					</div>
					<?php if (!empty($producto['descripcion'])): ?>
						<p class="hero-description"><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></p>
					<?php endif; ?>
					<?php if ($tags): ?>
						<div class="producto-tags">
							<i class="fa fa-tags"></i>
							<?= htmlspecialchars(implode(', ', $tags)) ?>
						</div>
					<?php endif; ?>
					<div class="hero-stats">
						<div class="hero-stat">
							<span class="label">Stock Mínimo: </span>
							<!--span class="value"><//?= format_stock($stockActual) ?> <?= htmlspecialchars($unidad) ?></span-->
							<span class="stat-foot"><?= format_stock($stockMinimo) ?></span>
						</div>
						<div class="hero-stat">
							<span class="label">Costo Referencia: </span>
							<span class="value">$<?= number_format((float)($producto['costo_compra'] ?? 0), 2) ?></span>
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
					<?php
                        $imgPath = $producto['imagen_url'] ?? '';
                        $src     = $imgPath ? '/' . ltrim(str_replace('\\', '/', $imgPath), '/') : 'assets/images/placeholder.png';
                    ?>
					<img src="<?= htmlspecialchars($src) ?>" alt="Imagen del Producto" onerror="this.onerror=null;this.src='assets/images/placeholder.png';">
				</div>
			</section>
			<section class="productos-detail-card">
				<h2><i class="fa fa-list"></i> Información General</h2>
				<div class="detail-grid">
					<div class="detail-item">
						<span class="label">Código Interno</span>
						<span class="value mono"><?= htmlspecialchars($producto['codigo'] ?? '') ?></span>
					</div>
                    <div class="detail-item">
                    	<span class="label">Código de Barras</span>
                    	<span class="value mono"><?= htmlspecialchars($producto['codigo_barras'] ?? '-') ?></span>
                    </div>
					<div class="detail-item">
						<span class="label">Categoría</span>
						<span class="value"><?= htmlspecialchars($producto['categoria'] ?? '-') ?></span>
					</div>
					<div class="detail-item">
						<span class="label">Proveedor</span>
						<span class="value"><?= htmlspecialchars($producto['proveedor'] ?? '-') ?></span>
					</div>
					<div class="detail-item">
						<span class="label">Ubicación Física</span>
						<span class="value"><?= htmlspecialchars($producto['ubicacion_fisica'] ?? '-') ?></span>
					</div>
					<div class="detail-item">
						<span class="label">Clase Interna</span>
						<span class="value"><?= htmlspecialchars($producto['clase_categoria'] ?? '-') ?></span>
					</div>
					<div class="detail-item">
						<span class="label">Marca</span>
						<span class="value"><?= htmlspecialchars($producto['marca'] ?? '-') ?></span>
					</div>
					<div class="detail-item">
						<span class="label">Color</span>
						<span class="value"><?= htmlspecialchars($producto['color'] ?? '-') ?></span>
					</div>
					<div class="detail-item">
						<span class="label">Forma</span>
						<span class="value"><?= htmlspecialchars($producto['forma'] ?? '-') ?></span>
					</div>
					<div class="detail-item">
						<span class="label">Origen</span>
						<span class="value"><?= htmlspecialchars($producto['origen'] ?? '-') ?></span>
					</div>
				</div>
			</section>
			<section class="productos-detail-card">
				<h2><i class="fa fa-ruler"></i> Dimensiones y Unidades</h2>
				<div class="detail-grid">
					<div class="detail-item">
						<span class="label">Peso</span>
						<span class="value"><?= htmlspecialchars($producto['peso'] ?? '0') ?> kg</span>
					</div>
					<div class="detail-item">
						<span class="label">Ancho</span>
						<span class="value"><?= htmlspecialchars($producto['ancho'] ?? '0') ?> cm</span>
					</div>
					<div class="detail-item">
						<span class="label">Alto</span>
						<span class="value"><?= htmlspecialchars($producto['alto'] ?? '0') ?> cm</span>
					</div>
					<div class="detail-item">
						<span class="label">Profundidad</span>
						<span class="value"><?= htmlspecialchars($producto['profundidad'] ?? '0') ?> cm</span>
					</div>
					<div class="detail-item">
						<span class="label">Unidad de medida</span>
						<span class="value"><?= htmlspecialchars($producto['unidad_medida_nombre'] ?? '-') ?></span>
					</div>
				</div>
			</section>
			<section class="productos-detail-card">
				<h2><i class="fa fa-clock"></i> Historial Interno</h2>
				<div class="detail-grid">
					<div class="detail-item">
						<span class="label">Último Solicitante</span>
						<span class="value">
							<?= htmlspecialchars($producto['last_user'] ?? 'Sin registros') ?>
							<?php if (!empty($producto['last_requested_by_user_id'])): ?>
								<small>ID: <?= (int)$producto['last_requested_by_user_id'] ?></small>
							<?php endif; ?>
						</span>
					</div>
					<div class="detail-item">
						<span class="label">Última Solicitud</span>
						<span class="value"><?= !empty($producto['last_request_date']) ? date('d/m/Y H:i', strtotime($producto['last_request_date'])) : 'Sin registros' ?></span>
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

