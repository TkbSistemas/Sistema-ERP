<?php
//Compruebe que el usuario sea Administrador o Almacenista antes de mostrar el dashboard
if (!in_array($role, ['Administrador', 'Compras'])) { // Si el rol no es Administrador ni Almacenista, redirigir al inicio
    header('Location: ' . Session::url('index'));
    exit();
}
    
$role = $datos['role'] ?? 'Empleado';
$nombre = $datos['nombre'] ?? '';
$alertas = $datos['alertas'] ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TAKAB - COMPRAS</title>
    <link rel="stylesheet" href="assets/css/dashboard.css"> 
    <link rel="stylesheet" href="assets/css/dashboard_custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
</head>
<body>
    <?php $pagina_actual = basename($_SERVER['SCRIPT_NAME'], '.php'); ?>
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
                    <h1>DASHBOARD COMPRAS</h1>
                    <span class="dashboard-desc">Panel para Gestión de Procesos de Compras.</span>
                </div>
                <div class="dashboard-updated">
                    <div>Último Actualizado</div>
                    <?= htmlspecialchars($datos['last_update']) ?>
                </div>
            </div>

            <section class="dashboard-cards-row">
                <div class="dashboard-card blue">
                    <div class="card-info">
                        <div class="card-label">Proyectos Activos</div>
                        <div class="card-value">2</div>
                        <div class="card-sub">En Curso</div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-shape-outline"></span>
                    </div>
                </div>
                <div class="dashboard-card yellow">
                    <div class="card-info">
                        <div class="card-label">Pendintes de Compra</div>
                        <div class="card-value">1</div>
                        <div class="card-sub">En Espera de Aprobación</div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-file-document-outline"></span>
                    </div>
                </div>
                <div class="dashboard-card red">
                    <div class="card-info">
                        <div class="card-label">Cotizaciones Pendientes</div>
                        <div class="card-value">0</div>
                        <div class="card-sub">No Completadas</div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-alert-circle-outline"></span>
                    </div>
                </div>
            </section>

            <section class="dashboard-widget">
                <div class="widget-title sky"><i class="fa-solid fa-history"></i> Últimas Ordenes de Compra</div>
                <?php if (!empty($datos['ultimosMovimientos'])): ?>
                    <table class="dashboard-mini-table">
                        <thead><tr><th>Fecha</th><th>Folio</th><th>Proveedor</th><th>Valor Total</th></tr></thead>
                        <tbody>
                        <?php foreach ($datos['ultimosMovimientos'] as $mov): ?>
                            <tr>
                                <td><?= date('d/m H:i', strtotime($mov['fecha'])) ?></td>
                                <td><?= htmlspecialchars($mov['nombre'] ?? '-') ?> <span class="mono">(<?= htmlspecialchars($mov['codigo'] ?? '-') ?>)</span></td>
                                <td><span class="badge badge-tipo <?= strtolower($mov['tipo']) ?>"><?= htmlspecialchars($mov['tipo']) ?></span></td>
                                <td><?= number_format((float) $mov['cantidad'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="widget-empty">Sin Ordenes de Compra Generadas Recientemente.</p>
                <?php endif; ?>
            </section>
            
            <?php if(!empty($alertas)): ?>
                <section class="dashboard-widget">
                    <div class="widget-title red"><i class="fa-solid fa-bell"></i> Alertas del Sistema</div>
                    <div class="alertas-list">
                        <?php foreach ($alertas as $alerta): ?>
                            <div class="alerta-row">
                                <span class="alerta-text"><?= htmlspecialchars($alerta[0] ?? '-') ?></span>
                                <span class="alerta-date"><?= htmlspecialchars($alerta[1] ?? '-') ?></span>
                                <span class="alerta-badge <?= htmlspecialchars($alerta[2] ?? 'alta') ?>"><?= htmlspecialchars($alerta[2] ?? 'alta') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
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



