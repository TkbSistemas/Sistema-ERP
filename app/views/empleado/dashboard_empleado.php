<?php
if (!in_array($role, ['Administrador', 'Empleado'], true)) {
    header('Location: ' . Session::url('index'));
    exit();
}

$role = $datos['role'] ?? 'Empleado';
$nombre = $datos['nombre'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TAKAB - EMPLEADO</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/dashboard_custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
</head>
<body class="module-inventory-warehouse">
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Toggle Menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>

        <main class="dashboard-main">
            <div class="dashboard-header-row">
                <div>
                    <h1>DASHBOARD EMPLEADO</h1>
                    <span class="dashboard-desc">Panel para Gestión de Procesos de Empleado.</span>
                </div>
                <div class="dashboard-updated">
                    <div>Último Actualizado</div>
                    <?= htmlspecialchars($datos['last_update']) ?>
                </div>
            </div>

            <section class="dashboard-cards-row">
                <div class="dashboard-card warning">
                    <div class="card-info">
                        <div class="card-label">Solicitudes Pendientes</div>
                        <div class="card-value"><?= number_format($datos['numSolicitudesPendientes'] ?? 0) ?></div>
                        <div class="card-sub">Pendientes de Respuesta</div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-clock-alert"></span>
                    </div>
                </div>
                <div class="dashboard-card waiting">
                    <div class="card-info">
                        <div class="card-label">Solicitudes en Entrega</div>
                        <div class="card-value"><?= number_format($datos['numSolicitudesEnEntrega'] ?? 0) ?></div>
                        <div class="card-sub">Pendientes de Recepción</div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-package"></span>
                    </div>
                </div>
            </section>

            <section class="dashboard-widget">
                <div class="widget-title sky"><i class="fa-solid fa-history"></i> Solicitudes Recientes</div>
                <?php if (!empty($datos['ultimasSolicitudes'])): ?>
                    <table class="dashboard-mini-table">
                        <thead><tr><th>Folio</th><th>Estatus</th><th>Fecha Respuesta</th><th>Proyecto</th><th>Comentario</th></tr></thead>
                        <tbody>
                        <?php foreach ($datos['ultimasSolicitudes'] as $mov): ?>
                            <tr>
                                <td><?= htmlspecialchars($mov['folio'] ?? '-') ?> </td>
                                <td><span class="mono"><?= htmlspecialchars($mov['estatus'] ?? '-') ?></span></td>
                                <td><?= date('d/m/Y', strtotime($mov['fecha_respuesta'])) ?></td>
                                <td><?= htmlspecialchars($mov['proyecto_nombre'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($mov['comentario_responsable'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="widget-empty">Sin Solicitudes Recientes.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById('toggleSidebar');
   const sidebar = document.querySelector('.main_sidebar');
    const mainContent = document.querySelector('.content-area');

    if (toggleBtn && sidebar && mainContent) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');

            mainContent.classList.toggle('collapsed');

            const icon = toggleBtn.querySelector('i');
            if (icon) {
                if (sidebar.classList.contains('collapsed')) {
                    icon.className = 'fa-solid fa-bars';
                } else {
                    icon.className = 'fa-solid fa-xmark';
                }
            }
        });
    }
});
</script>
</body>
</html>
