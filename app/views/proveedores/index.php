<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Almacen', 'Compras']);
$role   = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proveedores | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
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
                <a href="proveedores.php"><i class="fa-solid fa-address-book"></i> Proveedores</a>
                <a href="compras_proveedor.php"><i class="fa-solid fa-shopping-cart"></i> Compras por proveedor</a>
            <?php elseif ($role === 'Almacen'): ?>
                <a href="ordenes_compra.php"><i class="fa-solid fa-file-invoice-dollar"></i> Órdenes de compra</a>
                <a href="facturas.php"><i class="fa-solid fa-file-circle-check"></i> Facturas de compra</a>
                <a href="ordenes_compra_crear.php"><i class="fa-solid fa-plus"></i> Nueva Orden</a>
                <a href="proveedores.php"><i class="fa-solid fa-address-book"></i> Proveedores</a>
                <a href="compras_proveedor.php"><i class="fa-solid fa-shopping-cart"></i> Compras por proveedor</a>
            <?php elseif ($role === 'Compras'): ?>
                <a href="ordenes_compra.php"><i class="fa-solid fa-file-invoice-dollar"></i> Órdenes de compra</a>
                <a href="facturas.php"><i class="fa-solid fa-file-circle-check"></i> Facturas de compra</a>
                <a href="ordenes_compra_crear.php"><i class="fa-solid fa-plus"></i> Nueva Orden</a>
                <a href="proveedores.php"><i class="fa-solid fa-address-book"></i> Proveedores</a>
                <a href="compras_proveedor.php"><i class="fa-solid fa-shopping-cart"></i> Compras por proveedor</a>
            <?php endif; ?>
            <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>

    <div class="content-area">

        <?php include __DIR__ . '/../partials/topbar.php'; ?>

        <main class="dashboard-main config-page">
            <div class="main-table-card">
                <div class="config-section-header">
                    <div>
                        <div class="config-section-title">
                            <span class="config-icon gradient-blue"><i class="fa-solid fa-truck"></i></span>
                            Proveedores
                        </div>
                        <p class="config-section-desc">Registra a tus proveedores y mantén sus datos de contacto actualizados.</p>
                    </div>
                </div>
                <div class="config-section-actions" style="margin-bottom:18px;">
                    <a class="btn-secondary-ghost" href="ajustes.php"><i class="fa fa-arrow-left"></i> Ajustes</a>
                    <a href="proveedores_create.php" class="btn-main"><i class="fa fa-plus"></i> Nuevo proveedor</a>
                </div>

                <?php if (isset($_GET['error']) && $_GET['error'] === 'csrf'): ?>
                    <div class="alert alert-danger"><i class="fa fa-triangle-exclamation"></i> No pudimos completar la acción por motivos de seguridad.</div>
                <?php endif; ?>
                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success"><i class="fa fa-check-circle"></i> Proveedor eliminado correctamente.</div>
                <?php endif; ?>

                <table class="takab-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Contacto</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Dirección</th>
                            <th>Condiciones de pago</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proveedores as $prov): ?>
                            <tr>
                                <td><?= htmlspecialchars($prov['nombre']) ?></td>
                                <td><?= htmlspecialchars($prov['contacto']) ?></td>
                                <td><?= htmlspecialchars($prov['telefono']) ?></td>
                                <td><?= htmlspecialchars($prov['email']) ?></td>
                                <td><?= htmlspecialchars($prov['direccion']) ?></td>
                                <td><?= htmlspecialchars($prov['condiciones_pago']) ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="proveedores_edit.php?id=<?= (int) $prov['id'] ?>" class="btn-inline btn-edit"><i class="fa fa-pen"></i> Editar</a>
                                        <form method="post" action="proveedores_delete.php" class="inline-form" data-confirm="?Eliminar este proveedor?">
                                            <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= (int) $prov['id'] ?>">
                                            <button type="submit" class="btn-inline btn-delete"><i class="fa fa-trash"></i> Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($proveedores)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center;">Sin proveedores registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>

    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>

