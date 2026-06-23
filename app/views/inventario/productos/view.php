<?php
// Asegurar que la sesi?n est? iniciada (la app normalmente lo hace antes)
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

// Helper para clases CSS seguras a partir del tipo
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
	<title>Detalle de Producto | TAKAB</title>
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
					<h1>Detalle de producto</h1>
					<p class="productos-header-desc">Visualiza toda la información del artículo seleccionado.</p>
				</div>
				<div class="productos-header-actions">
					<a class="btn-secondary" href="productos.php"><i class="fa fa-arrow-left"></i> Volver</a>
					<a class="btn-secondary" href="productos_etiqueta.php?id=<?= (int)($producto['id'] ?? 0) ?>"><i class="fa fa-barcode"></i> Imprimir etiqueta</a>
				<a class="btn-main" href="productos_edit.php?id=<?= (int)($producto['id'] ?? 0) ?>"><i class="fa fa-pen"></i> Editar</a>
				</div>
			</div>
			<section class="productos-detail-card productos-hero">
				<div>
					<h1><?= htmlspecialchars($producto['nombre'] ?? '') ?></h1>
					<div class="hero-meta">
						<span class="badge badge-activo <?= (int)($producto['activo_id'] ?? 1) === 1 ? 'activo' : 'inactivo' ?>"><?= htmlspecialchars($estadoActivo) ?></span>
						<span class="badge badge-tipo <?= safe_css_class($producto['tipo'] ?? '') ?>"><?= htmlspecialchars($producto['tipo'] ?? '') ?></span>
						<span class="badge badge-stock <?= $stockActual <= 0 ? 'sin' : ($stockActual < $stockMinimo ? 'bajo' : 'ok') ?>">
							Código <?= htmlspecialchars($producto['codigo'] ?? '') ?>
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
							<span class="label">Stock actual</span>
							<span class="value"><?= format_stock($stockActual) ?> <?= htmlspecialchars($unidad) ?></span>
							<span class="stat-foot">Mínimo: <?= format_stock($stockMinimo) ?></span>
						</div>
						<div class="hero-stat">
							<span class="label">Costo unitario</span>
							<span class="value">$<?= number_format((float)($producto['costo_compra'] ?? 0), 2) ?></span>
							<span class="stat-foot">Precio venta: $<?= number_format((float)($producto['precio_venta'] ?? 0), 2) ?></span>
						</div>
						<div class="hero-stat">
							<span class="label">Valor inventario</span>
							<span class="value">$<?= number_format($valorInventario, 2) ?></span>
							<span class="stat-foot">Almacén <?= htmlspecialchars($producto['almacen'] ?? '-') ?></span>
						</div>
						<div class="hero-stat">
							<span class="label">Proveedor</span>
							<span class="value"><?= htmlspecialchars($producto['proveedor'] ?? '-') ?></span>
						</div>
					</div>
				</div>
				<div class="hero-image">
					<?php
                        $imgPath = $producto['imagen_url'] ?? '';
                        $src     = $imgPath ? '/' . ltrim(str_replace('\\', '/', $imgPath), '/') : '/assets/images/placeholder.png';
                    ?>
					<img src="<?= htmlspecialchars($src) ?>" alt="Imagen del producto" onerror="this.onerror=null;this.src='/assets/images/placeholder.png';">
				</div>
			</section>
			<section class="productos-detail-card">
				<h2><i class="fa fa-list"></i> Información general</h2>
				<div class="detail-grid">
					<div class="detail-item">
						<span class="label">Código interno</span>
						<span class="value mono"><?= htmlspecialchars($producto['codigo'] ?? '') ?></span>
					</div>
                    <div class="detail-item">
                    	<span class="label">Código de barras</span>
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
						<span class="label">Ubicación física</span>
						<span class="value"><?= htmlspecialchars($producto['ubicacion_fisica'] ?? '-') ?></span>
					</div>
					<div class="detail-item">
						<span class="label">Clase interna</span>
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
				<h2><i class="fa fa-ruler"></i> Dimensiones y unidades</h2>
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
				<h2><i class="fa fa-clock"></i> Historial interno</h2>
				<div class="detail-grid">
					<div class="detail-item">
						<span class="label">Último solicitante</span>
						<span class="value">
							<?= htmlspecialchars($producto['last_user'] ?? 'Sin registros') ?>
							<?php if (!empty($producto['last_requested_by_user_id'])): ?>
								<small>ID: <?= (int)$producto['last_requested_by_user_id'] ?></small>
							<?php endif; ?>
						</span>
					</div>
					<div class="detail-item">
						<span class="label">Última solicitud</span>
						<span class="value"><?= !empty($producto['last_request_date']) ? date('d/m/Y H:i', strtotime($producto['last_request_date'])) : 'Sin registros' ?></span>
					</div>
					<div class="detail-item">
						<span class="label">Fecha de registro</span>
						<span class="value"><?= !empty($producto['created_at']) ? date('d/m/Y H:i', strtotime($producto['created_at'])) : '-' ?></span>
					</div>
				</div>
			</section>
		</main>
	</div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>

