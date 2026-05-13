<?php
require_once __DIR__ . '/../app/helpers/Session.php';
Session::requireLogin();

$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración - TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/configuracion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="main-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="login-logo"><img src="/assets/images/icono_takab.png" alt="logo_TAKAB" width="90" height="55"></div>
            <div>
                <div class="sidebar-title">TAKAB</div>
                <div class="sidebar-desc">Dashboard</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <?php if ($role === 'Administrador'): ?>
                <a href="productos.php"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php"><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href="compras_proveedor.php"><i class="fa-solid fa-file-invoice"></i> Compras por proveedor</a>
                <a href="reportes_rotacion.php"><i class="fa-solid fa-arrows-rotate"></i> Rotación de inventario</a>
                <a href="revisar_solicitudes.php"><i class="fa-solid fa-comment-medical"></i> Solicitudes de Material</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="ajustes.php" class="active"><i class="fa-solid fa-gear"></i> Configuración</a>
            <?php elseif ($role === 'Almacen'): ?>
                <a href="productos.php"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php"><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href="compras_proveedor.php"><i class="fa-solid fa-file-invoice"></i> Compras por proveedor</a>
                <a href="reportes_rotacion.php"><i class="fa-solid fa-arrows-rotate"></i> Rotación de inventario</a>
                <a href="revisar_solicitudes.php"><i class="fa-solid fa-inbox"></i> Solicitudes de Material</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="ajustes.php" class="active"><i class="fa-solid fa-gear"></i> Configuración</a>
            <?php else: ?>
                <a href="solicitudes_crear.php"><i class="fa-solid fa-plus-square"></i> Solicitar Material</a>
                <a href="solicitar_material_general.php"><i class="fa-solid fa-cart-plus"></i> Solicitar Material General</a>
                <a href="mis_solicitudes.php"><i class="fa-solid fa-clipboard-list"></i> Mis Solicitudes</a>
            <?php endif; ?>
            <a href="documentacion.php"><i class="fa-solid fa-book"></i> Documentación</a>
            <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>

    <div class="content-area">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <header class="top-header">
            <div></div>
            <div class="top-header-user">
                <span><?= htmlspecialchars($nombre) ?></span>
                <i class="fa-solid fa-user-circle"></i>
                <a href="menu.php" class="logout-btn" title="Ir a menu"><i class="fa-solid fa-home"></i></a>
                <a href="logout.php" class="logout-btn" title="Cerrar sesión"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="config-page-header">
                <span class="config-header-icon"><i class="fa-solid fa-gear"></i></span>
                <div>
                    <h1 class="config-header-title">Configuración del Sistema</h1>
                    <div class="config-header-desc">Administra las configuraciones básicas del sistema de inventario</div>
                </div>
            </div>

            <div class="config-cards-grid">
                <div class="config-card">
                    <div class="config-card-header">
                        <span class="config-card-icon config-cat"><i class="fa-solid fa-cubes"></i></span>
                        <div>
                            <div class="config-card-title">Categorías</div>
                            <div class="config-card-desc">Gestiona las categorías de productos</div>
                        </div>
                    </div>
                    <a class="config-btn" href="categorias.php">Configurar</a>
                </div>
                <!--div class="config-card">
                    <div class="config-card-header">
                        <span class="config-card-icon config-prov"><i class="fa-solid fa-truck"></i></span>
                        <div>
                            < <div class="config-card-title">Proveedores</div> >
                            <div class="config-card-desc">Administra la información de proveedores</div>
                        </div>
                    </div>
                    <a class="config-btn" href="proveedores.php">Configurar</a>
                </div-->
                <div class="config-card">
                    <div class="config-card-header">
                        <span class="config-card-icon config-alm"><i class="fa-solid fa-warehouse"></i></span>
                        <div>
                            <div class="config-card-title">Almacenes</div>
                            <div class="config-card-desc">Configura los almacenes y ubicaciones</div>
                        </div>
                    </div>
                    <a class="config-btn" href="almacenes.php">Configurar</a>
                </div>
                <div class="config-card">
                    <div class="config-card-header">
                        <span class="config-card-icon config-uni"><i class="fa-solid fa-ruler-combined"></i></span>
                        <div>
                            <div class="config-card-title">Unidades de Medida</div>
                            <div class="config-card-desc">Define las unidades de medida</div>
                        </div>
                    </div>
                    <a class="config-btn" href="unidades.php">Configurar</a>
                </div>
            <!--/div-->

            <!--div class="config-bottom-row">
                <div class="config-card">
                    <div class="config-card-header">
                        <span class="config-card-icon config-client"><i class="fa-solid fa-building-user"></i></span>
                        <div>
                            <div class="config-card-title">Clientes</div>
                            <div class="config-card-desc">Consulta y gestiona la base de clientes</div>
                        </div>
                    </div>
                    <a class="config-btn" href="clientes.php">Configurar</a>
                </div-->
                <?php if ($role === 'Administrador'): ?>
                <div class="config-card">
                    <div class="config-card-header">
                        <span class="config-card-icon config-backup"><i class="fa-solid fa-database"></i></span>
                        <div>
                            <div class="config-card-title">Respaldos</div>
                            <div class="config-card-desc">Descarga la configuración en un archivo JSON</div>
                        </div>
                    </div>
                    <a class="config-btn" href="respaldo.php"><i class="fa-solid fa-download"></i> Descargar respaldo</a>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
