<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Almacen']);
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';

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
    <script src="./assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body>
<?php $seccion_activa = 'solicitudes_material'; ?>
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Toggle Menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>
        <main class="prestamos-main">
        <div class="prestamos-title">
            <i class="fa-solid fa-toolbox"></i>
            SOLICITUDES DE MATERIAL
        </div>
        
            <section class="dashboard-cards-row">
                <div class="dashboard-card warning">
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

            <?php $tab_activa = $_GET['tab'] ?? 'pendientes'; ?>

            <div class="tab-container">
                <a href="?tab=pendientes" class="prestamos-tab <?= $tab_activa === 'pendientes' ? 'active' : '' ?>">Pendientes</a>
                <a href="?tab=historial" class="prestamos-tab <?= $tab_activa === 'historial' ? 'active' : '' ?>">Historial</a>
            </div>
        <div id="wrapper-pendientes" <?= $tab_activa !== 'pendientes' ? 'hidden' : '' ?>>
        <table class="takab-table">
            <thead>
                <tr>
                    <th>Estatus</th>
                    <th>Empleado</th>
                    <th>Folio</th>
                    <th>Proyecto</th>
                    <th>Fecha Solicitud</th>
                    <th>Materiales</th>
                    <th>Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($datos['solicitudesPendientes'] as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['estatus']) ?></td>
                    <td><?= htmlspecialchars($s['nombre_solicitante']) ?></td>
                    <td><?= htmlspecialchars($s['folio']) ?></td>
                    <td><?= htmlspecialchars($s['proyecto_id']) ?></td>
                    <td><?= htmlspecialchars($s['fecha_solicitud']) ?></td>
                    <td><?= nl2br($s['materiales_resumen']) ?></td>
                    <td>
                        <a class="btn-table" title="Ver" href="ver_solicitud_material?id=<?= $s['id'] ?>"><i class="fa fa-eye"></i></a>
                        <?php if ($s['estatus'] === 'Pendiente'): ?>
                            <a class="btn-table btn-confirmar" 
                            title="Aprobar" 
                            href="javascript:void(0)" 
                            data-id="<?= $s['id'] ?>" 
                            data-accion="aprobar" 
                            data-folio="<?= htmlspecialchars($s['folio'] ?? '') ?>">
                                <i class="fa fa-circle-check"></i>
                            </a>
                        <?php endif; ?>

                        <?php if ($s['estatus'] === 'Aprobada'): ?>
                            <a class="btn-table btn-entrega" 
                            title="Procesar Entrega" 
                            href="construccion">
                                <i class="fa fa-circle-arrow-up"></i>
                            </a>
                        <?php endif; ?>

                        <?php if (in_array($s['estatus'], ['Pendiente', 'Aprobada'])): ?>
                            <a class="btn-table btn-confirmar" 
                            title="Rechazar" 
                            href="javascript:void(0)" 
                            data-id="<?= $s['id'] ?>" 
                            data-accion="rechazar" 
                            data-folio="<?= htmlspecialchars($s['folio'] ?? '') ?>">
                                <i class="fa fa-circle-xmark"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
        <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <div id="wrapper-historial" <?= $tab_activa !== 'historial' ? 'hidden' : '' ?>>
        <table class="takab-table">
            <thead>
                <tr>
                    <th>Estatus</th>
                    <th>Folio</th>
                    <th>Fecha Respuesta</th>
                    <th>Empleado</th>
                    <th>Comentario</th>
                    <th>Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($datos['solicitudesEsteMes'] as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['estatus']) ?></td>
                    <td><?= htmlspecialchars($s['folio']) ?></td>
                    <td><?= htmlspecialchars($s['fecha_respuesta']) ?></td>
                    <td><?= htmlspecialchars($s['nombre_solicitante']) ?></td>
                    <td><?= htmlspecialchars($s['comentario_responsable']) ?></td>
                    <td> <a class="btn-table" title="Ver" href="ver_producto?id=<?= $s['id'] ?>"><i class="fa fa-eye"></i></a> </td>
                </tr>
        <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        
        <?php if ($total_paginas > 1): ?>
            <div class="takab-pagination">
                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?= $pagina - 1 ?>&tab=<?= $tab_activa ?>" class="pagination-btn">&laquo; Anterior</a>
                <?php endif; ?>
                
                <?php
                $inicio = max(1, $pagina - 3);
                $fin    = min($total_paginas, $pagina + 3);
                for ($i = $inicio; $i <= $fin; $i++):
                    if ($i == $pagina): ?>
                        <span class="pagination-current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?pagina=<?= $i ?>&tab=<?= $tab_activa ?>" class="pagination-btn"><?= $i ?></a>
                    <?php endif;
                endfor;
                
                // Botón Siguiente
                if ($pagina < $total_paginas): ?>
                    <a href="?pagina=<?= $pagina + 1 ?>&tab=<?= $tab_activa ?>" class="pagination-btn">Siguiente &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        </main>
    </div>
</div>

<script>
    //Alerta de confirmación para aprobar o rechazar solicitudes
   document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-confirmar').forEach(boton => {
        boton.addEventListener('click', function (e) {
            e.preventDefault();

            const id = this.dataset.id;
            const accion = this.dataset.accion;
            const folio = this.dataset.folio ? `folio ${this.dataset.folio}` : 'esta solicitud';
            const esAprobar = accion === 'aprobar';

            Swal.fire({
                title: esAprobar ? 'Aprobar Solicitud' : 'Rechazar Solicitud',
                text: esAprobar 
                    ? `¿Aprobar Solicitud con ${folio}?` 
                    : `¿Rechazar Solicitud con ${folio}? Es Obligatorio Indicar el Motivo del Rechazo.`,
                icon: esAprobar ? 'question' : 'warning',
                
                input: 'textarea',
                inputPlaceholder: esAprobar ? 'Comentario u observaciones (Opcional)...' : 'Escribe aquí el motivo del rechazo *...',
                inputAttributes: {
                    'aria-label': 'Escribe tu comentario'
                },
                
                showCancelButton: true,
                confirmButtonColor: esAprobar ? '#28a745' : '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: esAprobar ? 'Sí, Aprobar' : 'Sí, Rechazar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,

                inputValidator: (value) => {
                    if (!esAprobar && !value.trim()) {
                        return '¡Debe Especificar el Motivo del Rechazo!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const comentario = result.value ? result.value.trim() : '';

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `${accion}_solicitud_materiales`;

                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'id';
                    inputId.value = id;
                    form.appendChild(inputId);

                    const inputComentario = document.createElement('input');
                    inputComentario.type = 'hidden';
                    inputComentario.name = 'comentario';
                    inputComentario.value = comentario;
                    form.appendChild(inputComentario);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
    });

    //Ocultar y mostrar el sidebar
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
