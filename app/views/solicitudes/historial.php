<?php
require_once __DIR__ . '/../../helpers/Session.php';
require_once __DIR__ . '/../../models/SolicitudMaterial.php';
Session::requireLogin(['Empleado', 'Almacen']);

$breadcrumbs = [['label' => 'Historial de solicitudes']];
$role = $_SESSION['role'];
$nombre = $_SESSION['nombre'];

$estadoFiltro = $_GET['estado'] ?? '';
$tabs = [
    'Todas' => '',
    'Pendientes' => 'pendiente',
    'Aprobadas' => 'aprobada',
    'Entregadas' => 'entregada',
    'Rechazadas' => 'rechazada',
];

// Agrega productos a cada solicitud (siempre tendrás 'items')
foreach ($solicitudes as &$s) {
    $s['items'] = SolicitudMaterial::detalles($s['id']);
}
unset($s);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Solicitudes de Material/Herramienta | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/config.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f6f7fa; }
        .sol-main { max-width: 1200px; margin: 0 auto; padding: 38px 18px 34px 18px; }
        .sol-header-title { font-size: 2.1rem; font-weight: 800; margin-bottom: 5px; color: #232a4d; }
        .sol-header-desc { color: #7b89b0; font-size: 1.08rem; margin-bottom: 28px;}
        .sol-tabs { display: flex; gap: 10px; margin-bottom: 25px; }
        .sol-tab {
            background: #f3f6fb; border-radius: 8px; font-weight: 600; color: #37498e;
            padding: 9px 28px; border: none; font-size: 1.07rem; cursor: pointer;
            transition: background 0.15s, color 0.15s; text-decoration: none;
        }
        .sol-tab.active, .sol-tab:hover { background: #e8edfa; color: #1741a6; }
        .sol-nueva-btn {
            float: right; margin-top: -12px; margin-bottom: 23px; background: #1741a6; color: #fff;
            font-size: 1.08rem; border-radius: 8px; border: none; padding: 10px 25px; font-weight: 700;
            box-shadow: 0 2px 12px 0 rgba(23,44,87,0.08); transition: background 0.18s;
            text-decoration: none; display: inline-flex; align-items: center; gap: 10px;
        }
        .sol-nueva-btn:hover { background: #2563eb; color: #fff;}
        .sol-card {
            background: #fff; border-radius: 13px; box-shadow: 0 2px 11px 0 rgba(23,44,87,0.07);
            border: 1.2px solid #eef2f8; margin-bottom: 22px; padding: 28px 36px 22px 36px; position: relative;
        }
        .sol-card-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 18px; margin-bottom: 10px;
        }
        .sol-card-title { font-size: 1.22rem; font-weight: 700; color: #162049; margin-bottom: 2px; }
        .sol-card-meta {
            font-size: 1rem; color: #6b7db6; display: flex; align-items: center; gap: 19px; margin-bottom: 2px;
        }
        .sol-badges { display: flex; gap: 7px; margin-top: 5px; }
        .sol-badge { display: inline-block; font-size: .98rem; font-weight: 600; padding: 3px 13px 4px 13px;
            border-radius: 11px; background: #f3f6fb; color: #5363a3; text-transform: lowercase;
        }
        .badge-pendiente { background: #fff7e1; color: #b78e17; }
        .badge-aprobada, .badge-aprobado { background: #e6f1ff; color: #2563eb; }
        .badge-entregada, .badge-entregado { background: #e9faef; color: #11a158;}
        .badge-cancelada { background: #f3f3f3; color: #888; }
        .badge-rechazada, .badge-rechazado { background: #ffeaea; color: #e12d39;}
        .sol-card-section { margin-top: 13px; }
        .sol-card-section h4 {
            font-size: 1.03rem; font-weight: 700; margin-bottom: 8px; color: #23315a;
            display: flex; align-items: center; gap: 6px;
        }
        .sol-items-list {
            background: #f7f8fa; border-radius: 8px; padding: 13px 24px; margin-bottom: 10px; margin-top: 2px;
        }
        .sol-items-list-row {
            display: flex; justify-content: space-between;
            padding: 3px 0; color: #27345a; font-size: 1.04rem;
        }
        .sol-detalle-btn {
            display: inline-block; margin-top: 18px; padding: 8px 20px;
            background: #2563eb; color: #fff; font-weight: 600; border-radius: 8px;
            text-decoration: none; font-size: 1.01rem; transition: background 0.13s;
        }
        .sol-detalle-btn:hover { background: #1741a6; color: #fff;}

        /* Estilos del buscador/filtro por fechas */
        .sol-date-filter-container {
            background: #fff;
            border-radius: 12px;
            padding: 16px 24px;
            margin-bottom: 25px;
            border: 1px solid #eef2f8;
            box-shadow: 0 2px 10px 0 rgba(23,44,87,0.04);
            margin-top: 15px;
        }
        .sol-date-filter-form {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filter-group label {
            font-weight: 600;
            color: #37498e;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .filter-input {
            padding: 8px 12px;
            border: 1.5px solid #dcdfe6;
            border-radius: 6px;
            font-size: 0.95rem;
            color: #2c3e50;
            background-color: #fcfdfe;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .filter-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
        }
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .filter-btn {
            padding: 8px 16px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .submit-btn {
            background: #2563eb;
            color: #fff;
            border: none;
        }
        .submit-btn:hover {
            background: #1741a6;
        }
        .clear-btn {
            background: #f1f3f7;
            color: #4e5e8c;
            border: 1.5px solid #dcdfe6;
        }
        .clear-btn:hover {
            background: #e4e7ed;
            color: #2c3e50;
        }

        @media (max-width:900px){
            .sol-card { padding: 15px 4vw; }
            .sol-main { padding: 19px 5vw 24px 5vw;}
            .sol-date-filter-form {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
            .filter-group {
                flex-direction: column;
                align-items: stretch;
                gap: 5px;
            }
            .filter-actions {
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
<div class="main-layout">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="content-area">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <main class="dashboard-main sol-main">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:18px;">
                <div>
                    <div class="sol-header-title">Mis Solicitudes de Material/Herramienta</div>
                    <div class="sol-header-desc">Historial de solicitudes que has realizado</div>
                </div>
                <a href="solicitudes_crear.php" class="sol-nueva-btn">
                    <i class="fa fa-plus"></i> Nueva Solicitud
                </a>
            </div>

            <!-- Buscador Avanzado -->
            <div class="sol-date-filter-container">
                <form method="GET" action="mis_solicitudes.php" class="sol-date-filter-form">
                    <?php if ($estadoFiltro): ?>
                        <input type="hidden" name="estado" value="<?= htmlspecialchars($estadoFiltro) ?>">
                    <?php endif; ?>
                    <div class="filter-group">
                        <label for="search"><i class="fa fa-search"></i> Buscar:</label>
                        <input type="text" id="search" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Nombre de solicitud/motivo..." class="filter-input" style="min-width: 220px;">
                    </div>
                    <div class="filter-group">
                        <label for="fecha_inicio"><i class="fa-regular fa-calendar"></i> Desde:</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label for="fecha_fin"><i class="fa-regular fa-calendar"></i> Hasta:</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>" class="filter-input">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="filter-btn submit-btn"><i class="fa fa-filter"></i> Buscar</button>
                        <?php if (!empty($_GET['fecha_inicio']) || !empty($_GET['fecha_fin']) || !empty($_GET['search'])): ?>
                            <a href="mis_solicitudes.php<?= $estadoFiltro ? '?estado=' . urlencode($estadoFiltro) : '' ?>" class="filter-btn clear-btn"><i class="fa fa-times"></i> Limpiar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="sol-tabs">
                <?php foreach ($tabs as $tabNombre => $tabEstado): 
                    $active = ($estadoFiltro === $tabEstado) ? 'active' : '';
                    $queryParams = [];
                    if ($tabEstado !== '') {
                        $queryParams['estado'] = $tabEstado;
                    }
                    if (!empty($_GET['fecha_inicio'])) {
                        $queryParams['fecha_inicio'] = $_GET['fecha_inicio'];
                    }
                    if (!empty($_GET['fecha_fin'])) {
                        $queryParams['fecha_fin'] = $_GET['fecha_fin'];
                    }
                    if (!empty($_GET['search'])) {
                        $queryParams['search'] = $_GET['search'];
                    }
                    $url = "mis_solicitudes.php" . (!empty($queryParams) ? "?" . http_build_query($queryParams) : "");
                ?>
                    <a href="<?= $url ?>" class="sol-tab <?= $active ?>"><?= $tabNombre ?></a>
                <?php endforeach; ?>
            </div>
            <?php if (empty($solicitudes)): ?>
                <div style="margin-top:40px;text-align:center;color:#9ab;">No hay solicitudes.</div>
            <?php endif; ?>
            <?php foreach ($solicitudes as $s): ?>
                <div class="sol-card">
                    <div class="sol-card-header">
                        <div>
                            <div class="sol-card-title"><?= htmlspecialchars($s['comentario'] ?? '(Sin comentario)') ?></div>
                            <div class="sol-card-meta">
                                <span><i class="fa fa-calendar"></i> <?= htmlspecialchars($s['fecha_solicitud'] ?? '') ?></span>
                                <span><i class="fa fa-layer-group"></i> <?= htmlspecialchars($s['tipo_solicitud'] ?? '') ?></span>
                                <span><i class="fa fa-cubes"></i> <?= (int)$s['total_productos'] ?> productos</span>
                                <span><i class="fa fa-cubes"></i> <?= (int)$s['total_productos'] ?> productos</span>

                            </div>
                        </div>
                        <div class="sol-badges">
                            <?php
                                $estado = strtolower($s['estado']);
                                $clase = match($estado) {
                                    'pendiente' => 'badge-pendiente',
                                    'aprobada', 'aprobado' => 'badge-aprobada',
                                    'entregada', 'entregado' => 'badge-entregada',
                                    'cancelada' => 'badge-cancelada',
                                    'rechazada', 'rechazado' => 'badge-rechazada',
                                    default => 'sol-badge'
                                };
                                echo "<span class='sol-badge $clase'>" . ucfirst(htmlspecialchars($s['estado'])) . "</span>";
                            ?>
                        </div>
                    </div>
                    <?php
                        $items = (!empty($s['items']) && is_array($s['items'])) ? $s['items'] : [];
                        if (!empty($items)):
                    ?>
                    <div class="sol-card-section">
                        <h4><i class="fa fa-box-open"></i> Herramientas/Material Solicitado</h4>
                        <div class="sol-items-list">
                            <?php foreach ($items as $item): ?>
                                <div class="sol-items-list-row">
                                    <span><?= htmlspecialchars($item['producto'] ?? $item['nombre'] ?? '') ?></span>
                                    <span style="color:#7281a8;">
                                        <?= htmlspecialchars($item['cantidad'] ?? '') ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <a href="solicitud_detalle.php?id=<?= $s['id'] ?>" class="sol-detalle-btn">
                        <i class="fa fa-eye"></i> Ver Detalle
                    </a>
                </div>
            <?php endforeach; ?>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
