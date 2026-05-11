<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Almacen', 'Compras']);
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar proveedor | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/proveedores.css">
    <link rel="stylesheet" href="/assets/css/config.css">
    <link rel="stylesheet" href="/assets/css/config-pages.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="main-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="login-logo"><img src="assets/images/icono_takab.png" alt="logo_TAKAB" width="90" height="55"></div>
            <div>
                <div class="sidebar-title">TAKAB</div>
                <div class="sidebar-desc">Compras</div>
            </div>
        </div>
        <nav class="sidebar-nav">     
            <?php if ($role === 'Administrador'): ?>
                <a href="ordenes_compra.php"><i class="fa-solid fa-file-invoice-dollar"></i> Órdenes de compra</a>
                <a href="facturas.php"><i class="fa-solid fa-file-circle-check"></i> Facturas de compra</a>
                <a href="ordenes_compra_crear.php"><i class="fa-solid fa-plus"></i> Nueva Orden</a>
                <a href="proveedores.php" class="active"><i class="fa-solid fa-address-book"></i> Proveedores</a>
                <a href="compras_proveedor.php"><i class="fa-solid fa-shopping-cart"></i> Compras por proveedor</a>
            <?php elseif ($role === 'Almacen'): ?>
                <a href="ordenes_compra.php"><i class="fa-solid fa-file-invoice-dollar"></i> Órdenes de compra</a>
                <a href="facturas.php"><i class="fa-solid fa-file-circle-check"></i> Facturas de compra</a>
                <a href="ordenes_compra_crear.php"><i class="fa-solid fa-plus"></i> Nueva Orden</a>
                <a href="proveedores.php" class="active"><i class="fa-solid fa-address-book"></i> Proveedores</a>
                <a href="compras_proveedor.php"><i class="fa-solid fa-shopping-cart"></i> Compras por proveedor</a>
            <?php elseif ($role === 'Compras'): ?>
                <a href="ordenes_compra.php"><i class="fa-solid fa-file-invoice-dollar"></i> Órdenes de compra</a>
                <a href="facturas.php"><i class="fa-solid fa-file-circle-check"></i> Facturas de compra</a>
                <a href="ordenes_compra_crear.php"><i class="fa-solid fa-plus"></i> Nueva Orden</a>
                <a href="proveedores.php" class="active"><i class="fa-solid fa-address-book"></i> Proveedores</a>
                <a href="compras_proveedor.php"><i class="fa-solid fa-shopping-cart"></i> Compras por proveedor</a>
            <?php endif; ?>
            <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>
    <div class="content-area">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="main-content">
        <div class="form-card">
            <div class="form-title"><i class="fa fa-plus"></i> Registrar proveedor</div>
            <?php if (!empty($msg)): ?>
                <div class="alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert-error">
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
                        <form method="post" class="form-takab">
                <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                <div class="form-row">
                    <label>Nombre:<input type="text" name="nombre" value="<?= htmlspecialchars($data['nombre'] ?? '') ?>" required></label>
                    <label>Contacto:<input type="text" name="contacto" value="<?= htmlspecialchars($data['contacto'] ?? '') ?>" required></label>
                </div>
                <div class="form-row">
                    <label>RFC:<input type="text" name="rfc" value="<?= htmlspecialchars($data['rfc'] ?? '') ?>"></label>
                    <label>Teléfono:<input type="text" name="telefono" value="<?= htmlspecialchars($data['telefono'] ?? '') ?>"></label>
                </div>
                <label>Email:<input type="email" name="email" value="<?= htmlspecialchars($data['email'] ?? '') ?>"></label>
                <label>Dirección:<input type="text" name="direccion" value="<?= htmlspecialchars($data['direccion'] ?? '') ?>"></label>
                <label>Condiciones de pago:<input type="text" name="condiciones_pago" value="<?= htmlspecialchars($data['condiciones_pago'] ?? '') ?>"></label>
                <div class="form-actions">
                    <button type="submit" class="btn-principal">Registrar</button>
                    <a href="proveedores.php" class="btn-secundario">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>

