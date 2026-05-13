<?php
require_once __DIR__ . '/../app/helpers/Session.php';

Session::requireLogin();

$nombre = $_SESSION['nombre'] ?? 'TAKAB';
$role = $_SESSION['role'] ?? '';

// nombre, icono, color principal, color suave, color sombra
$groups = [
    'capital_humano' => ['Capital Humano', 'fa-people-group', '#eab308', '#fffefb', 'rgba(234, 179, 8, 0.18)'],
    'finanzas' => ['Finanzas', 'fa-coins', '#2563eb', '#eff6ff', 'rgba(37, 99, 235, 0.18)'],
    'operaciones' => ['Operaciones y logística', 'fa-truck-fast', '#7c3aed', '#f5f3ff', 'rgba(124, 58, 237, 0.18)'],
    'gestion' => ['Gestión Especializada', 'fa-layer-group', '#111827', '#f3f4f6', 'rgba(17, 24, 39, 0.18)'],

];

// poner modulos en gris si no están desarrollados, o si el usuario no tiene permisos para acceder a ellos
$modules = [
    // Capital Humano
    'nomina' => ['Nómina', '', 'fa-money-check-dollar', 'nomina.php', 'capital_humano'],
    'rh' => ['Recursos Humanos', '', 'fa-users', 'rh.php', 'capital_humano'],
    'gestion_usuarios' => ['Gestión de Usuarios', 'Crear/editar usuarios, asignar roles, historial de accesos.', 'fa-user-gear', 'usuarios.php', 'capital_humano'],

    // Finanzas
    'bancos' => ['', 'fa-building-columns', 'bancos.php', 'finanzas'],
    'contabilidad' => ['Contabilidad', '', 'fa-file-invoice-dollar', 'contabilidad.php', 'finanzas'],

    // Operaciones y logística
    'compras' => ['Compras', 'Órdenes de compra, facturas de compra, historial, proveedores.', 'fa-bag-shopping', 'ordenes_compra.php', 'operaciones'],
    'inventario' => ['Almacén e Inventarios', 'Productos, entradas/salidas, préstamos, mantenimientos de herramientas.', 'fa-boxes-stacked', 'dashboard.php', 'operaciones'],
    'ventas' => ['Ventas', 'Cotizaciones/presupuestos, facturas de venta.', 'fa-cart-shopping', 'ventas.php', 'operaciones'],

    // Gestión Especializada
    'proyectos' => ['Proyectos TAKAB', 'Evidencias, entregables, dashboard de avance de proyecto.', 'fa-screwdriver-wrench', 'proyectos.php', 'gestion'],
    'clientes' => ['Clientes', 'Registro de nuevo cliente, editar, eliminar.', 'fa-handshake', 'clientes.php', 'gestion'],
];

$groupedModules = [];
foreach ($modules as $key => $m) {
    $groupKey = $m[4] ?? 'operaciones';
    if (!isset($groupedModules[$groupKey])) {
        $groupedModules[$groupKey] = [];
    }
    $groupedModules[$groupKey][$key] = $m;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú de Módulos - TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/config.css">  
    <!--link rel="stylesheet" href="assets/css/menu.css"-->  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            color: var(--white);
            background: url('assets/images/edificios20_1.jpg') center center / cover no-repeat fixed;
        }

        .content-area {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background:
                linear-gradient(90deg, rgba(219, 223, 233, 0.4) 0%, rgba(197, 211, 243, 0.49) 58%, rgba(209, 223, 255, 0.32) 100%),
                radial-gradient(circle at 18% 70%, rgba(255, 255, 255, 0.19), transparent 45%);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .menu-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .menu-card {
            background: rgba(255, 255, 255, 0.72);
            border-radius: 25px;
            box-shadow: 0 6px 30px 0 rgb(9, 38, 95);
            width: min(1200px, 100%);
            padding: 32px;
            background: #fafdffec;
        }

        .menu-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .menu-title {
            font-size: 2.1rem;
            font-weight: 800;
            color: #05104e;
            margin: 0;
        }

        .menu-subtitle {
            margin: 6px 0 0 0;
            color: #0e197c;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .menu-subtitle2 {
            margin: 6px 0 0 0;
            color: #202a8af6;
            font-size: 1.05rem;
            font-style: italic;
        }

        .menu-logo img {
            height: 90px;
            width: auto;
        }

        .module-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .group {
            margin-top: 18px;
        }

        .group:first-of-type {
            margin-top: 0;
        }

        .group-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 14px 0 10px 0;
            font-size: 1.20rem;
            font-weight: 900;
            color: var(--group-color);
            letter-spacing: 0.2px;
        }

        .group-badge {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: var(--group-color);
            color: #ffffff;
            flex: 0 0 auto;
            box-shadow: 0 4px 12px var(--group-shadow);
        }

        .module-btn {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            border-radius: 14px;
            text-decoration: none;
            background: linear-gradient(135deg, var(--group-soft) 0%, rgba(255, 255, 255, 0.96) 45%, rgba(255, 255, 255, 0.98) 100%);
            border: 1px solid color-mix(in srgb, var(--group-color) 18%, white);
            box-shadow: 0 5px 12px var(--group-shadow);
            transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
        }

        .module-btn:hover {
            transform: translateY(-2px);
            border-color: var(--group-color);
            box-shadow: 0 10px 24px var(--group-shadow);
        }

        .module-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            color: #ffffff;
            background: linear-gradient(135deg, var(--group-color) 0%, color-mix(in srgb, var(--group-color) 78%, black) 100%);
            flex: 0 0 auto;
            box-shadow: 0 4px 10px var(--group-shadow);
        }

        .group-badge {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--group-color) 0%, color-mix(in srgb, var(--group-color) 78%, black) 100%);
            color: #ffffff;
            flex: 0 0 auto;
            box-shadow: 0 4px 12px var(--group-shadow);
        }

        .module-name {
            margin: 0;
            font-weight: 800;
            color: var(--group-color);
            font-size: 1.05rem;
        }

        .module-desc {
            margin: 4px 0 0 0;
            color: #4b5563;
            font-size: 1rem;
            line-height: 1.3;
        }

        .menu-actions {
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .back-link {
            color: #223264;
            text-decoration: none;
            font-weight: 700;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 800;
            background: #b60c0c;
            color: #ffffff;
        }

        .hint {
            color: #6476a8;
            font-size: 0.95rem;
        }

        .note {
            color: #0029b1;
            font-size: 1.05rem;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (max-width: 900px) {
            .module-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 580px) {
            .module-grid {
                grid-template-columns: 1fr;
            }

            .menu-card {
                padding: 18px;
            }

            .menu-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="content-area">
        <div class="menu-wrap">
            <div class="menu-card">
                <div class="menu-header">
                    <div>
                        <h1 class="menu-title">Menú de módulos</h1>
                        <p class="menu-subtitle">
                            Selecciona un módulo para continuar.
                        </p>
                        <p class="menu-subtitle2">
                            <span style="opacity:.9"><?= htmlspecialchars($nombre); ?><?= $role ? ' • ' . htmlspecialchars($role) : ''; ?></span>
                        </p>
                    </div>
                    <div class="menu-logo">
                        <img src="assets/images/LogoTakab2.webp" alt="Logo Takab">
                    </div>
                </div>

                <?php foreach ($groups as $gKey => $gMeta): ?>
                    <div class="group"
                         style="
                            --group-color: <?= htmlspecialchars($gMeta[2]); ?>;
                            --group-soft: <?= htmlspecialchars($gMeta[3]); ?>;
                            --group-shadow: <?= htmlspecialchars($gMeta[4]); ?>;
                         ">
                        <div class="group-title">
                            <div class="group-badge">
                                <i class="fa-solid <?= htmlspecialchars($gMeta[1]); ?>"></i>
                            </div>
                            <span><?= htmlspecialchars($gMeta[0]); ?></span>
                        </div>

                        <div class="module-grid">
                            <?php foreach (($groupedModules[$gKey] ?? []) as $m): ?>
                                <a class="module-btn" href="<?= htmlspecialchars($m[3]); ?>">
                                    <div class="module-icon">
                                        <i class="fa-solid <?= htmlspecialchars($m[2]); ?>"></i>
                                    </div>
                                    <div>
                                        <p class="module-name"><?= htmlspecialchars($m[0]); ?></p>
                                        <p class="module-desc"><?= htmlspecialchars($m[1]); ?></p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="menu-actions">
                    <p class="note">Nota: si aún no están desarrollados algunos módulos, verás un panel "En construcción"</p>
                    <a class="btn" href="logout.php">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>