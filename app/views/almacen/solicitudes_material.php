<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Almacen']);
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';

// Variables necesarias:
// $prestamos           - array de registros a mostrar
// $pagina              - página actual (entero, desde 1)
// $total_paginas       - total de páginas (entero)

if (!isset($pagina)) $pagina = 1;
if (!isset($total_paginas)) $total_paginas = 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SOLICITUDES MATERIAL | TAKAB</title>
    <link rel="stylesheet" href="assets/css/prestamos-pendientes.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
</head>
<body>
<?php $seccion_activa = 'solicitudes_material'; ?>
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Toggle Menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    <div class="content-area">
        <?php 
            require_once __DIR__ . '/../../helpers/Navigation.php';

            $role = Navigation::normalizeRole($role ?? ($_SESSION['role'] ?? ''));
            ?>
            <header class="top-header">
                <div class="top-header-left">
                </div>
                <div class="top-header-user">
                    <span><?= htmlspecialchars($nombre ?: 'Usuario') ?> (<?= htmlspecialchars($role) ?>)</span>
                    <i class="fa-solid fa-user-circle"></i>
                    <a href="dashboard_almacen" class="logout-btn" title="Ir al Dashboard"><i class="fa-solid fa-home"></i></a>
                    <a href="logout" class="logout-btn" title="Cerrar Sesión"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>    
                </div>
            </header>

        <main class="prestamos-main">
        <div class="prestamos-title">
            <i class="fa-solid fa-toolbox"></i>
            SOLICITUDES DE MATERIAL
        </div>
        
            <section class="dashboard-cards-row">
                <div class="dashboard-card">
                    <div class="card-info">
                        <div class="card-label">Solicitudes Pendientes</div>
                        <div class="card-value"><?= number_format($datos['numSolicitudesPendientes'] ?? 0)?></div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-clock-alert"></span>
                    </div>
                </div>
                <div class="dashboard-card">
                    <div class="card-info">
                        <div class="card-label">Solicitudes Este Mes</div>
                        <div class="card-value"><?= number_format($datos['numSolicitudesEsteMes'] ?? 0)?></div>
                    </div>
                    <div class="card-icon-container">
                        <span class="mdi mdi-clock-check"></span>
                    </div>
                </div>
            </section>
        <div class="prestamos-tabs">
            <a href="solicitudes_pendientes.php" class="prestamos-tab active">Pendientes</a>
            <a href="solicitudes_historial.php" class="prestamos-tab">Historial</a>
        </div>
        <table class="takab-table">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Folio</th>
                    <th>Proyecto</th>
                    <th>Fecha Solicitud</th>
                    <th>Comentarios</th>
                    <th>Acción</th>
            </tr>
        </thead>
        <tbody>
           
        <?php foreach ($datos['solicitudesPendientes'] as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['usuario_id']) ?></td>
                    <td>Folio EJMP</td>
                    <td>Proyecto EMPL</td>
                    <td><?= htmlspecialchars($s['fecha_solicitud']) ?></td>
                    <td>Lorem, ipsum dolor sit amet consectetur adipisicing elit. Sit vel facere dolor et</td>
                    <td>
                        <a class="btn-table" title="Aprobar" href="ver_producto?id=<?= $s['id'] ?>"><i class="fa fa-eye"></i></a>
                        <a class="btn-table" title="Aprobar" href="ver_producto?id=<?= $s['id'] ?>"><i class="fa fa-circle-check"></i></a>
                        <form method="post" action="eliminar_producto" class="inline-form form-eliminar" style="display:inline-block">
                            <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                            <input type="hidden" name="active" value="0">
                                            
                            <button type="submit" 
                            class="btn-table btn-danger" 
                            title="Rechazar"
                            >
                            <i class="fa fa-circle-xmark"></i>
                            </button>
                        </form>
                    </td>
                </tr>
        <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- PAGINACIÓN -->
        <?php if ($total_paginas > 1): ?>
            <div class="takab-pagination">
                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?= $pagina - 1 ?>" class="pagination-btn">&laquo; Anterior</a>
                <?php endif; ?>
                <?php
                $inicio = max(1, $pagina - 3);
                $fin    = min($total_paginas, $pagina + 3);
                for ($i = $inicio; $i <= $fin; $i++):
                    if ($i == $pagina): ?>
                        <span class="pagination-current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?pagina=<?= $i ?>" class="pagination-btn"><?= $i ?></a>
                    <?php endif;
                endfor;
                if ($pagina < $total_paginas): ?>
                    <a href="?pagina=<?= $pagina + 1 ?>" class="pagination-btn">Siguiente &raquo;</a>
                <?php endif; ?>
            </div>
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
