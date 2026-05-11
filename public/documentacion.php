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
    <title>Documentación del sistema | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .doc-main { padding: 32px 32px 48px; }
        .doc-section { background:#fff; border-radius:16px; padding:26px 28px; border:1px solid #e4e8f3; box-shadow:0 2px 16px rgba(23,44,87,0.05); margin-bottom:24px; }
        .doc-section h2 { margin:0 0 12px; font-size:1.45rem; color:#12305f; display:flex; align-items:center; gap:10px; }
        .doc-section p, .doc-section ul { color:#4a5a85; line-height:1.55; font-size:0.98rem; }
        .doc-section ul { padding-left:22px; margin:8px 0; }
        @media (max-width:768px) { .doc-main { padding:22px 18px 36px; } }
    </style>
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
                <a href="productos.php"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php" ><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href="prestamos_pendientes.php" ><i class="fa-solid fa-screwdriver-wrench"></i> Préstamos de herramientas</a>
                <a href="reportes_rotacion.php" ><i class="fa-solid fa-refresh"></i> Rotación de Inventario</a>
                <a href="revisar_solicitudes.php"><i class="fa-solid fa-plus-square"></i> Solicitudes de Material</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="ajustes.php"><i class="fa-solid fa-gear"></i> Configuración</a>
                <a href="documentacion.php" class="active"><i class="fa-solid fa-book"></i>Documentación</a>
            <?php elseif ($role === 'Almacen'): ?>
                <a href="productos.php"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php" ><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href="prestamos_pendientes.php" ><i class="fa-solid fa-screwdriver-wrench"></i> Préstamos de herramientas</a>
                <a href="reportes_rotacion.php" ><i class="fa-solid fa-refresh"></i> Rotación de Inventario</a>
                <a href="revisar_solicitudes.php"><i class="fa-solid fa-plus-square"></i> Solicitudes de Material</a>
                <a href="mis_solicitudes.php"><i class="fa-solid fa-clipboard-list"></i> Mis Solicitudes</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="ajustes.php"><i class="fa-solid fa-gear"></i> Configuración</a>
                <a href="documentacion.php" class="active"><i class="fa-solid fa-book"></i>Documentación</a>
            <?php elseif ($role === 'Compras'): ?>
              <a href="productos.php"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php" ><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="documentacion.php" class="active"><i class="fa-solid fa-book"></i>Documentación</a>  
            <?php elseif ($role === 'Empleado'): ?>
                <a href="solicitudes_crear.php"><i class="fa-solid fa-plus-square"></i> Solicitar Material</a>
                <a href="mis_solicitudes.php"><i class="fa-solid fa-clipboard-list"></i> Mis Solicitudes</a>
            <?php endif; ?>
            <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>
    <div class="content-area">
        <header class="top-header">
            <div></div>
            <div class="top-header-user">
                <span><?= htmlspecialchars($nombre) ?> (<?= htmlspecialchars($role) ?>)</span>
                <i class="fa-solid fa-user-circle"></i>
                <a href="logout.php" class="logout-btn" title="Cerrar sesión"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
            </div>
        </header>

        <main class="dashboard-main doc-main">
            <div class="doc-section">
                <h2><i class="fa-solid fa-book-open"></i> Acerca del sistema</h2>
                <p>Este portal gestiona el inventario, préstamos y solicitudes de materiales para la empresa TAKAB. A continuación encontrarás guías rápidas de los módulos más recientes y accesos a recursos clave.</p>
            </div>

            <div class="doc-section">
                <h2><i class="fa-solid fa-file-invoice"></i> Historial de compras por proveedor</h2>
                <ul>
                    <li>Ingresa a <strong>Reportes &gt; Compras por proveedor</strong>.</li>
                    <li>Filtra por proveedor y rango de fechas para obtener el detalle de cada orden registrada.</li>
                    <li>Descarga el resultado en CSV para analizar los totales y los productos adquiridos.</li>
                </ul>
            </div>

            <div class="doc-section">
                <h2><i class="fa-solid fa-arrows-rotate"></i> Rotación de inventario</h2>
                <ul>
                    <li>Consulta <strong>Reportes &gt; Rotación de inventario</strong> para identificar productos con alto o bajo movimiento.</li>
                    <li>El índice se calcula con base en las salidas del periodo comparadas contra el stock promedio.</li>
                    <li>Puedes exportar el análisis a CSV o PDF para compartirlo con tu equipo.</li>
                </ul>
            </div>

            <div class="doc-section">
                <h2><i class="fa-solid fa-clipboard-list"></i> Bitácora de actividad</h2>
                <ul>
                    <li>Disponible para administradores desde <strong>Reportes &gt; Bitácora</strong>.</li>
                    <li>Registra operaciones clave como altas de productos, movimientos de inventario e importaciones masivas.</li>
                    <li>Utiliza los filtros por fecha, usuario o acción para localizar eventos específicos.</li>
                </ul>
            </div>

            <div class="doc-section">
                <h2><i class="fa-solid fa-database"></i> Respaldos de configuración</h2>
                <ul>
                    <li>En la sección de <strong>Configuración</strong> encontrarás la tarjeta "Respaldos".</li>
                    <li>Genera un JSON con categorías, almacenes, proveedores, unidades y usuarios activos.</li>
                    <li>Guárdalo en un lugar seguro para recuperar la configuración ante incidencias.</li>
                </ul>
            </div>

            <div class="doc-section">
                <h2><i class="fa-solid fa-circle-info"></i> Importación de productos</h2>
                <ul>
                    <li>Descarga la plantilla CSV desde <strong>Gestión de productos</strong>.</li>
                    <li>Completa los campos respetando los IDs de catálogo existentes y vuelve a subir el archivo.</li>
                    <li>El sistema mostrará un resumen con filas importadas, omitidas y los errores detectados.</li>
                </ul>
            </div>
        </main>
    </div>
</div>
</body>
</html>

