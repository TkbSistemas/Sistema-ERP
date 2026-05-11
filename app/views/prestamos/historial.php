<?php
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
$busqueda = $busqueda ?? '';
$estado = $estado ?? '';
$desde = $desde ?? '';
$hasta = $hasta ?? '';
$page = $page ?? 1;
$porPagina = $porPagina ?? 9;
$totalPages = max(1, $totalPages ?? 1);
$breadcrumbs = [['label' => 'Historial de préstamos']];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Prestamos de Herramientas | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/prestamo-historial.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="main-layout">
      <aside class="sidebar">
        <div class="sidebar-header">
            <div class="login-logo"><img src="assets/images/icono_takab.png" alt="logo_TAKAB" width="90" height="55""></div>
            <div>
                <div class="sidebar-title">TAKAB</div>
                <div class="sidebar-desc">Dashboard</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="usuarios.php"><i class="fa-solid fa-users-cog"></i> Gestión de Usuarios</a>
                <a href="productos.php"><i class="fa-solid fa-boxes-stacked"></i> Gestión de Productos</a>
                <a href="inventario_actual.php"><i class="fa-solid fa-list-check"></i> Inventario</a>
                <a href='revisar_solicitudes.php'><i class="fa-solid fa-comment-medical"></i>Solicitudes de Material</a>
                <a href='prestamos_pendientes.php'>Préstamos Pendientes</a>
                <a href='prestamos_historial.php' class="active">Historial de Préstamos</a>
                <a href="reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a>
                <a href="ajustes.php"><i class="fa-solid fa-gear"></i> Configuración</a>
            <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>
    <div class="content-area">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>

        <main class="historial-main">
        <div class="historial-title">
            <i class="fa-solid fa-history"></i>
            Historial de Prestamos de Herramientas
        </div>
        <div class="prestamos-tabs">
            <a href="prestamos_pendientes.php" class="prestamos-tab">Pendientes</a>
            <a href="prestamos_historial.php" class="prestamos-tab active">Historial</a>
        </div>

        <div class="search-bar">
            <form method="get" action="">
                <input type="text" name="q" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar por codigo o trabajador..." class="takab-search-input">
                <select name="estado" class="takab-search-input">
                    <option value="">Todos los estados</option>
                    <option value="Prestado" <?= $estado === 'Prestado' ? 'selected' : '' ?>>Prestado</option>
                    <option value="Devuelto" <?= $estado === 'Devuelto' ? 'selected' : '' ?>>Devuelto</option>
                </select>
                <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>" class="takab-search-input" title="Desde">
                <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>" class="takab-search-input" title="Hasta">
                <button type="submit" class="takab-search-btn">
                    <i class="fa fa-filter"></i>
                    <span class="hidden-xs">Filtrar</span>
                </button>
            </form>
        </div>
        <table class="takab-table">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Codigo</th>
                    <th>Herramienta</th>
                    <th>Fecha Prestamo</th>
                    <th>Fecha Devolucion</th>
                    <th>Estado del Prestamo</th>
                    <th>Estado al devolver</th>
                    <th>Observaciones al devolver</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prestamos as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['empleado']) ?></td>
                        <td><?= htmlspecialchars($p['codigo_producto']) ?></td>
                        <td><?= htmlspecialchars($p['producto']) ?></td>
                        <td><?= htmlspecialchars($p['fecha_prestamo']) ?></td>
                        <td><?= htmlspecialchars($p['fecha_devolucion']) ?></td>
                        <td>
                            <?php
                            $estadoPrestamo = strtolower($p['estado']);
                            $clase = match($estadoPrestamo) {
                                'pendiente' => 'badge-pendiente',
                                'devuelto', 'devuelta' => 'badge-devuelto',
                                'vencido' => 'badge-vencido',
                                default => 'badge'
                            };
                            echo "<span class='badge $clase'>" . ucfirst(htmlspecialchars($p['estado'])) . "</span>";
                            ?>
                        </td>
                        <td>
                            <?php
                            $ed = strtolower($p['estado_devolucion']);
                            $claseEd = match($ed) {
                                'bueno' => 'badge-bueno',
                                'danado' => 'badge-danado',
                                'perdido' => 'badge-perdido',
                                default => 'badge'
                            };
                            echo "<span class='badge $claseEd'>" . ucfirst(htmlspecialchars($p['estado_devolucion'])) . "</span>";
                            ?>
                        </td>
                        <td><?= htmlspecialchars($p['observaciones_devolucion'] ?? $p['observaciones'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?q=<?= urlencode($busqueda) ?>&estado=<?= urlencode($estado) ?>&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>

