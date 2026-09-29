<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador']);

$role = $datos['role'] ?? 'Administrador';
$nombre = $datos['nombre'] ?? ($_SESSION['nombre'] ?? '');
$actividadReciente = is_array($datos['actividadReciente'] ?? null) ? $datos['actividadReciente'] : [];
$seccion_activa = 'dashboard_admin';
$alertaSesion = $_SESSION['alerta'] ?? null;
unset($_SESSION['alerta']);

$grupos = [
    [
        'titulo' => 'Inventario',
        'icono' => 'fa-boxes-stacked',
        'descripcion' => 'Estado General del Catálogo y las Existencias.',
        'cards' => [
            ['label' => 'Productos Activos', 'value' => number_format((int) ($datos['productosActivos'] ?? 0)), 'sub' => 'Elementos Disponibles en el Catálogo', 'icon' => 'fa-cubes', 'tone' => 'blue', 'url' => 'dashboard_inventario'],
            ['label' => 'Productos con Stock Bajo', 'value' => number_format((int) ($datos['stockBajo'] ?? 0)), 'sub' => 'Por Debajo del Stock Mínimo', 'icon' => 'fa-triangle-exclamation', 'tone' => 'red', 'url' => 'dashboard_inventario'],
            ['label' => 'Valor del Inventario', 'value' => '$' . number_format((float) ($datos['valorTotalInventario'] ?? 0), 2), 'sub' => 'Existencias por Precio Unitario', 'icon' => 'fa-sack-dollar', 'tone' => 'green', 'url' => 'dashboard_inventario', 'compact' => true],
        ],
    ],
    [
        'titulo' => 'Almacén y Solicitudes',
        'icono' => 'fa-warehouse',
        'descripcion' => 'Solicitudes que Requieren Atención del Almacén.',
        'cards' => [
            ['label' => 'Solicitudes Pendientes', 'value' => number_format((int) ($datos['solicitudesPendientes'] ?? 0)), 'sub' => 'En Espera de Aprobación', 'icon' => 'fa-file-circle-question', 'tone' => 'yellow', 'url' => 'solicitudes_material'],
            ['label' => 'Solicitudes por Entregar', 'value' => number_format((int) ($datos['solicitudesPorEntregar'] ?? 0)), 'sub' => 'Aprobadas y Pendientes de Entrega', 'icon' => 'fa-box-open', 'tone' => 'cyan', 'url' => 'solicitudes_material'],
            ['label' => 'Almacenes Activos', 'value' => number_format((int) ($datos['almacenesActivos'] ?? 0)), 'sub' => 'Ubicaciones Operativas', 'icon' => 'fa-building-circle-check', 'tone' => 'slate', 'url' => 'dashboard_almacen'],
        ],
    ],
    [
        'titulo' => 'Compras',
        'icono' => 'fa-cart-shopping',
        'descripcion' => 'Seguimiento de Órdenes, Procesamiento y Facturación.',
        'cards' => [
            ['label' => 'Órdenes Pendientes', 'value' => number_format((int) ($datos['ordenesPendientes'] ?? 0)), 'sub' => 'En Espera de Aprobación', 'icon' => 'fa-file-signature', 'tone' => 'yellow', 'url' => 'ordenes_compra?tab=pendientes'],
            ['label' => 'Órdenes por Procesar', 'value' => number_format((int) ($datos['ordenesPorProcesar'] ?? 0)), 'sub' => 'Aprobadas o Parciales', 'icon' => 'fa-gears', 'tone' => 'blue', 'url' => 'ordenes_compra?tab=pendientes'],
            ['label' => 'Órdenes sin Factura', 'value' => number_format((int) ($datos['ordenesSinFactura'] ?? 0)), 'sub' => 'Procesadas o Recibidas', 'icon' => 'fa-file-circle-exclamation', 'tone' => 'red', 'url' => 'facturas_compras?factura=pendiente'],
        ],
    ],
    [
        'titulo' => 'Estructura del Sistema',
        'icono' => 'fa-sitemap',
        'descripcion' => 'Recursos Activos que Soportan la Operación.',
        'cards' => [
            ['label' => 'Proveedores Activos', 'value' => number_format((int) ($datos['proveedoresActivos'] ?? 0)), 'sub' => 'Disponibles para Compras', 'icon' => 'fa-building-user', 'tone' => 'purple', 'url' => 'proveedores'],
            ['label' => 'Usuarios Activos', 'value' => number_format((int) ($datos['usuariosActivos'] ?? 0)), 'sub' => 'Cuentas Habilitadas', 'icon' => 'fa-users', 'tone' => 'cyan', 'url' => null],
            ['label' => 'Proyectos Registrados', 'value' => number_format((int) ($datos['proyectosRegistrados'] ?? 0)), 'sub' => 'Proyectos Disponibles', 'icon' => 'fa-diagram-project', 'tone' => 'purple', 'url' => 'proyectos'],
        ],
    ],
];

$urlModulo = static function (?string $ruta): string {
    if (!$ruta) {
        return '';
    }
    $partes = explode('?', $ruta, 2);
    $url = Session::url($partes[0]);
    return $url . (isset($partes[1]) ? '?' . $partes[1] : '');
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resumen Administrativo | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/dashboard-admin.css?v=20260927-2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body>
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Abrir o Cerrar Menú" aria-expanded="true">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>

        <main class="dashboard-main admin-overview">
            <div class="dashboard-header-row admin-overview-header">
                <div>
                    <h1 class="page-title-icon"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i> RESUMEN ADMINISTRATIVO</h1>
                    <span class="dashboard-desc">Síntesis General de Inventario, Almacén, Compras y Recursos del Sistema.</span>
                </div>
                <a class="admin-main-menu-link" href="<?= htmlspecialchars(Session::url('menu_admin'), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa-solid fa-grip"></i> Abrir Menú Principal
                </a>
            </div>

            <section class="admin-quick-actions" aria-labelledby="accionesRapidasTitulo">
                <div class="admin-quick-actions-heading">
                    <span class="admin-section-icon"><i class="fa-solid fa-bolt"></i></span>
                    <div>
                        <h2 id="accionesRapidasTitulo">Acciones Rápidas</h2>
                        <p>Altas Provisionales para la Administración del Sistema.</p>
                    </div>
                </div>
                <div class="admin-quick-actions-buttons">
                    <button type="button" class="admin-quick-button" id="crearUsuarioButton">
                        <span><i class="fa-solid fa-user-plus"></i></span>
                        <div><strong>Crear Usuario de Acceso</strong><small>Nombre, Credenciales y Rol</small></div>
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <button type="button" class="admin-quick-button" id="crearProyectoButton">
                        <span><i class="fa-solid fa-folder-plus"></i></span>
                        <div><strong>Crear Proyecto</strong><small>Código y Nombre del Proyecto</small></div>
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </section>

            <?php foreach ($grupos as $grupo): ?>
                <section class="admin-summary-section">
                    <header class="admin-section-header">
                        <span class="admin-section-icon"><i class="fa-solid <?= htmlspecialchars($grupo['icono'], ENT_QUOTES, 'UTF-8') ?>"></i></span>
                        <div>
                            <h2><?= htmlspecialchars($grupo['titulo'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p><?= htmlspecialchars($grupo['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </header>

                    <div class="admin-metrics-grid">
                        <?php foreach ($grupo['cards'] as $card): ?>
                            <?php if (!empty($card['url'])): ?>
                                <a class="admin-metric-card admin-metric-card--<?= htmlspecialchars($card['tone'], ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($urlModulo($card['url']), ENT_QUOTES, 'UTF-8') ?>">
                            <?php else: ?>
                                <div class="admin-metric-card admin-metric-card--<?= htmlspecialchars($card['tone'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                                <div class="admin-metric-content">
                                    <span class="admin-metric-label"><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <strong class="admin-metric-value <?= !empty($card['compact']) ? 'admin-metric-value--compact' : '' ?>"><?= htmlspecialchars($card['value'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span class="admin-metric-sub"><?= htmlspecialchars($card['sub'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <span class="admin-metric-icon"><i class="fa-solid <?= htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></span>
                            <?php if (!empty($card['url'])): ?>
                                </a>
                            <?php else: ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <section class="admin-activity-card">
                <header class="admin-activity-header">
                    <div>
                        <h2><i class="fa-solid fa-clock-rotate-left"></i> Actividad Reciente</h2>
                        <p>Últimas Acciones Registradas en el Sistema.</p>
                    </div>
                    <a href="<?= htmlspecialchars(Session::url('logs.php'), ENT_QUOTES, 'UTF-8') ?>">Ver Registro Completo <i class="fa-solid fa-arrow-right"></i></a>
                </header>

                <?php if ($actividadReciente === []): ?>
                    <div class="admin-empty-state"><i class="fa-solid fa-clock"></i><span>Sin Actividad Registrada.</span></div>
                <?php else: ?>
                    <div class="admin-activity-list">
                        <?php foreach ($actividadReciente as $actividad): ?>
                            <?php
                            $accion = str_replace(['.', '_'], ' ', (string) ($actividad['accion'] ?? 'actividad'));
                            $fecha = strtotime((string) ($actividad['created_at'] ?? ''));
                            ?>
                            <article class="admin-activity-row">
                                <span class="admin-activity-dot"></span>
                                <div class="admin-activity-description">
                                    <strong><?= htmlspecialchars((string) ($actividad['descripcion_limpia'] ?? 'Actividad Registrada'), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span><?= htmlspecialchars(ucwords($accion), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) ($actividad['usuario'] ?? 'Sistema'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <time datetime="<?= htmlspecialchars((string) ($actividad['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= $fecha ? date('d/m/Y H:i', $fecha) : 'Sin Fecha' ?>
                                </time>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<form id="adminQuickForm" method="post" action="<?= htmlspecialchars(Session::url('dashboard_admin'), ENT_QUOTES, 'UTF-8') ?>" hidden>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="accion">
    <input type="hidden" name="nombre">
    <input type="hidden" name="username">
    <input type="hidden" name="password">
    <input type="hidden" name="password_confirmacion">
    <input type="hidden" name="role">
    <input type="hidden" name="codigo">
    <input type="hidden" name="nombre_proyecto">
</form>

<?php include __DIR__ . '/../layouts/scripts.php'; ?>
<script>
const quickForm = document.getElementById('adminQuickForm');

function enviarAccion(accion, datos) {
    quickForm.reset();
    quickForm.elements.accion.value = accion;
    Object.entries(datos).forEach(([campo, valor]) => {
        if (quickForm.elements[campo]) quickForm.elements[campo].value = valor;
    });
    quickForm.submit();
}

document.getElementById('crearUsuarioButton').addEventListener('click', async () => {
    const resultado = await Swal.fire({
        title: 'Crear Usuario de Acceso',
        html: `<div class="admin-swal-grid">
            <label class="admin-swal-field admin-swal-field--wide"><span>Nombre Completo <b>*</b></span><input id="adminUserName" class="swal2-input" maxlength="100" autocomplete="name" placeholder="Nombre del Usuario"></label>
            <label class="admin-swal-field"><span>Usuario <b>*</b></span><input id="adminUsername" class="swal2-input" maxlength="25" autocomplete="off" placeholder="usuario.acceso"></label>
            <label class="admin-swal-field"><span>Rol <b>*</b></span><select id="adminRole" class="swal2-select"><option value="">Selecciona un Rol</option><option value="Administrador">Administrador</option><option value="Almacen">Almacén</option><option value="Compras">Compras</option><option value="Empleado">Empleado</option><option value="Proyectos">Proyectos</option></select></label>
            <label class="admin-swal-field"><span>Contraseña <b>*</b></span><input id="adminPassword" class="swal2-input" type="password" maxlength="72" autocomplete="new-password" placeholder="Mínimo 8 Caracteres"></label>
            <label class="admin-swal-field"><span>Confirmar Contraseña <b>*</b></span><input id="adminPasswordConfirm" class="swal2-input" type="password" maxlength="72" autocomplete="new-password" placeholder="Repite la Contraseña"></label>
        </div>`,
        width: 690,
        showCancelButton: true,
        confirmButtonText: 'Crear Usuario',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        focusConfirm: false,
        customClass: { popup: 'admin-swal-popup' },
        preConfirm: () => {
            const nombre = document.getElementById('adminUserName').value.trim();
            const username = document.getElementById('adminUsername').value.trim().toLowerCase();
            const role = document.getElementById('adminRole').value;
            const password = document.getElementById('adminPassword').value;
            const password_confirmacion = document.getElementById('adminPasswordConfirm').value;
            if (nombre.length < 2 || !/^[a-z0-9._-]{4,25}$/.test(username) || !role || password.length < 8) {
                Swal.showValidationMessage('Completa Correctamente Todos los Campos Obligatorios.');
                return false;
            }
            if (password !== password_confirmacion) {
                Swal.showValidationMessage('La Confirmación de la Contraseña no Coincide.');
                return false;
            }
            return { nombre, username, role, password, password_confirmacion };
        }
    });

    if (resultado.isConfirmed) enviarAccion('crear_usuario', resultado.value);
});

document.getElementById('crearProyectoButton').addEventListener('click', async () => {
    const resultado = await Swal.fire({
        title: 'Crear Proyecto',
        html: `<div class="admin-swal-grid">
            <label class="admin-swal-field"><span>Código <b>*</b></span><input id="adminProjectCode" class="swal2-input" maxlength="50" autocomplete="off" placeholder="Ej. PROY-001"></label>
            <label class="admin-swal-field"><span>Nombre del Proyecto <b>*</b></span><input id="adminProjectName" class="swal2-input" maxlength="255" autocomplete="off" placeholder="Nombre Descriptivo"></label>
        </div>`,
        width: 620,
        showCancelButton: true,
        confirmButtonText: 'Crear Proyecto',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        focusConfirm: false,
        customClass: { popup: 'admin-swal-popup' },
        preConfirm: () => {
            const codigo = document.getElementById('adminProjectCode').value.trim().toUpperCase();
            const nombre_proyecto = document.getElementById('adminProjectName').value.trim();
            if (!/^[A-Z0-9._-]{2,50}$/.test(codigo) || nombre_proyecto.length < 2) {
                Swal.showValidationMessage('Captura un Código y un Nombre de Proyecto Válidos.');
                return false;
            }
            return { codigo, nombre_proyecto };
        }
    });

    if (resultado.isConfirmed) enviarAccion('crear_proyecto', resultado.value);
});

<?php if (is_array($alertaSesion)): ?>
Swal.fire({
    icon: <?= json_encode($alertaSesion['tipo'] ?? 'info', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    title: <?= json_encode($alertaSesion['titulo'] ?? 'Aviso', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    text: <?= json_encode($alertaSesion['mensaje'] ?? '', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    confirmButtonColor: '#2563eb'
});
<?php endif; ?>
</script>
</body>
</html>
