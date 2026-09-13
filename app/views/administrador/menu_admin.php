<?php
Session::requireLogin();
$modulos = [
    [
        'slug'           => 'dashboard_compras',
        'titulo'         => 'Costos y Presupuestos',
        'subtitulo'      => 'Cotizaciones y Catálogos',
        'icono'          => 'fa-coins',
        'color_neutro'   => '#59749e', 
        'color_vibrante' => '#2563eb', 
        'sombra'         => 'rgba(37, 99, 235, 0.4)'
    ],
    [
        'slug'           => 'proyectos',
        'titulo'         => 'Gestión de Proyectos',
        'subtitulo'      => 'Licitaciones y Entregables',
        'icono'          => 'fa-diagram-project',
        'color_neutro'   => '#7b6b94', 
        'color_vibrante' => '#7c3aed', 
        'sombra'         => 'rgba(124, 58, 237, 0.4)'
    ],
    [
        'slug'           => 'dashboard_empleado',
        'titulo'         => 'Ingeniería en Campo',
        'subtitulo'      => 'Solicitudes de Materiales',
        'icono'          => 'fa-id-badge',
        'color_neutro'   => '#c4ae72', 
        'color_vibrante' => '#eab308', 
        'sombra'         => 'rgba(234, 179, 8, 0.4)'
    ],
    [
        'slug'           => 'dashboard_almacen',
        'titulo'         => 'Almacén',
        'subtitulo'      => 'Entradas, Salidas y Solicitudes',
        'icono'          => 'fa-warehouse',
        'color_neutro'   => '#56827c', 
        'color_vibrante' => '#0f766e', 
        'sombra'         => 'rgba(15, 118, 110, 0.4)'
    ],
    [
        'slug'           => 'dashboard_inventario',
        'titulo'         => 'Inventario',
        'subtitulo'      => 'Catálogo de Stock',
        'icono'          => 'fa-boxes-stacked',
        'color_neutro'   => '#c47a54', // Naranja terracota suave
        'color_vibrante' => '#ea580c', // Naranja original
        'sombra'         => 'rgba(234, 88, 12, 0.4)'
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MENÚ | TAKAB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/radial_menu.css">
</head>
<body>

    <div class="backdrop-overlay"></div>

    <div class="main-container">
        <div class="radial-menu-wrapper">
            <div class="orbital-ring"></div>

            <!-- Hub Central: Dashboard administrativo -->
            <a href="<?= htmlspecialchars(Session::url('dashboard_admin'), ENT_QUOTES, 'UTF-8') ?>"
               class="center-hub"
               aria-label="Ir al dashboard administrativo"
               title="Dashboard administrativo">
                <img src="assets/images/LogoTakab2.webp" alt="Logo TAKAB" onerror="this.style.display='none'">
            </a>

            <!-- Nodos Circulares -->
            <?php foreach ($modulos as $index => $mod): ?>
                <a href="<?= htmlspecialchars(Session::url($mod['slug']), ENT_QUOTES, 'UTF-8') ?>"
                   class="radial-node node-<?= $index ?>" 
                   style="
                        --node-color: <?= $mod['color_neutro'] ?>; 
                        --node-color-hover: <?= $mod['color_vibrante'] ?>; 
                        --node-shadow: <?= $mod['sombra'] ?>;
                   ">
                    <i class="fa-solid <?= htmlspecialchars($mod['icono']) ?> node-icon"></i>
                    <div>
                        <div class="node-title"><?= htmlspecialchars($mod['titulo']) ?></div>
                        <div class="node-sub"><?= htmlspecialchars($mod['subtitulo']) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="bottom-actions">
            <a href="logout" class="btn-logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar Sesión
            </a>
        </div>
    </div>

</body>
</html>
