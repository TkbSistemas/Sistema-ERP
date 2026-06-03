<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Empleado', 'Almacen']);
$breadcrumbs = [['label' => 'Detalle de la solicitud']];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Solicitud | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/config.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f6f7fa; }
        .detalle-main { max-width: 650px; margin: 36px auto 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px 0 rgba(23,44,87,0.08); padding: 35px 36px 24px 36px;}
        .detalle-title { font-size: 2rem; font-weight: 800; margin-bottom: 20px; color: #223264;}
        .detalle-info { margin-bottom: 14px; }
        .detalle-info label { color: #6476a8; font-weight: 600; width: 170px; display: inline-block; }
        .detalle-badge {
            display: inline-block; font-size: .96rem; font-weight: 700; padding: 4px 13px; border-radius: 11px;
            background: #e7edfa; color: #285ac7; margin-left: 7px; letter-spacing: .2px;
        }
        .badge-pendiente { background: #fff7e1; color: #b78e17;}
        .badge-aprobada { background: #e7edfa; color: #285ac7;}
        .badge-entregada { background: #e9faef; color: #11a158;}
        .badge-cancelada { background: #f3f3f3; color: #888; }
        .badge-rechazada { background: #ffeaea; color: #e12d39;}
        .detalle-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 23px; }
        .detalle-table th, .detalle-table td { padding: 8px 10px; border: 1px solid #e2e8f0; text-align: left; }
        .detalle-table th { background: #f5f8fd; color: #223264; font-weight: 700;}
        .detalle-table td { color: #23315a; }
        .detalle-section-title { margin-top: 24px; font-size: 1.11rem; font-weight: 700; color: #2c3566; display: flex; align-items:center; gap:7px;}
        .detalle-observ { background: #f7f8fa; border-radius: 7px; padding: 10px 15px; margin: 8px 0 18px 0; color: #495a89;}
        .detalle-btn-back {
            display: inline-block; margin-top: 18px; padding: 8px 24px; background: #2563eb;
            color: #fff; font-weight: 600; border-radius: 8px; text-decoration: none; font-size: 1.05rem;
            transition: background 0.13s;
        }
        .detalle-btn-back:hover { background: #1741a6; color: #fff;}
        @media (max-width:750px){
            .detalle-main{padding:18px 4vw;}
            .detalle-title{font-size:1.33rem;}
        }
    </style>
</head>
<body>
    <div class="detalle-main">
        <div class="detalle-title"><i class="fa-solid fa-receipt"></i> Detalle de Solicitud</div>
        <?php if ($solicitud): ?>
            <div class="detalle-info">
                <label>Destino/Motivo:</label> <?= htmlspecialchars($solicitud['comentario']) ?>
            </div>
            <div class="detalle-info">
                <label>Observación del empleado:</label>
                <div class="detalle-observ"><?= htmlspecialchars($solicitud['observacion']) ?></div>
            </div>
            <div class="detalle-info">
                <label>Tipo de Solicitud:</label> <?= htmlspecialchars($solicitud['tipo_solicitud']) ?>
            </div>
            <div class="detalle-info">
                <label>Estado:</label>
                <?php
                $estado = strtolower($solicitud['estado']);
                $clase = match($estado) {
                    'pendiente' => 'badge-pendiente',
                    'aprobada', 'aprobado' => 'badge-aprobada',
                    'entregada', 'entregado' => 'badge-entregada',
                    'cancelada' => 'badge-cancelada',
                    'rechazada', 'rechazado' => 'badge-rechazada',
                    default => 'detalle-badge'
                };
                echo "<span class='detalle-badge $clase'>" . ucfirst(htmlspecialchars($solicitud['estado'])) . "</span>";
                ?>
            </div>
            <div class="detalle-info">
                <label>Fecha de Solicitud:</label> <?= htmlspecialchars($solicitud['fecha_solicitud']) ?>
            </div>
            <?php if (!empty($solicitud['fecha_respuesta'])): ?>
                <div class="detalle-info">
                    <label>Fecha de Respuesta:</label> <?= htmlspecialchars($solicitud['fecha_respuesta']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($solicitud['observaciones_respuesta'])): ?>
                <div class="detalle-info">
                    <label>Comentario de revisión/entrega:</label>
                    <span style="color:#2b62c9;"><?= htmlspecialchars($solicitud['observaciones_respuesta']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (($solicitud['tipo_solicitud'] ?? 'servicio') !== 'General'): ?>
                <div class="detalle-section-title"><i class="fa fa-box-open"></i> Productos Solicitados</div>
                <table class="detalle-table">
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Observación</th>
                    </tr>
                    <?php foreach ($detalles as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['producto']) ?></td>
                            <td><?= (float)$d['cantidad'] ?></td>
                            <td><?= htmlspecialchars($d['observacion']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>

            <?php
            if (!empty($solicitud['extras'])):
                $extras = json_decode($solicitud['extras'], true);
                if ($extras && count($extras) > 0): ?>
                <div class="detalle-section-title"><i class="fa fa-shopping-basket"></i> Materiales/Herramientas para Comprar</div>
                <table class="detalle-table">
                    <tr>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Observación</th>
                    </tr>
                    <?php foreach ($extras as $ex): ?>
                        <tr>
                            <td><?= htmlspecialchars($ex['descripcion'] ?? $ex['producto_nombre']) ?></td>
                            <td><?= (float)$ex['cantidad'] ?></td>
                            <td><?= htmlspecialchars($ex['observacion']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; endif; ?>
        <?php else: ?>
            <p>Solicitud no encontrada.</p>
        <?php endif; ?>
        <a href="mis_solicitudes.php#" class="detalle-btn-back"><i class="fa fa-arrow-left"></i> Regresar</a>
    </div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
