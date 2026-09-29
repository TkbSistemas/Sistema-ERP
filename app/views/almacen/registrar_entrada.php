<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Almacen']);

$role = $_SESSION['role'] ?? 'Almacen';
$nombre = $_SESSION['nombre'] ?? '';
$recepcionesRecientes = is_array($recepcionesRecientes ?? null) ? $recepcionesRecientes : [];
$alertaSesion = $_SESSION['alerta'] ?? null;
unset($_SESSION['alerta']);
$seccion_activa = 'registrar_entrada';
$stylePath = __DIR__ . '/../../../public/assets/css/registrar-entrada.css';
$styleVersion = is_file($stylePath) ? (string) filemtime($stylePath) : '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ENTRADA MATERIAL | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/prestamos-pendientes.css">
    <link rel="stylesheet" href="assets/css/registrar-entrada.css?v=<?= rawurlencode($styleVersion) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body class="module-inventory-warehouse">
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Abrir o Cerrar Menú" aria-expanded="true">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>

        <main class="dashboard-main reception-search-main">
            <div class="dashboard-header-row reception-header">
                <div>
                    <h1 class="page-title-icon"><i class="fa-solid fa-truck-ramp-box" aria-hidden="true"></i> REGISTRAR ENTRADA</h1>
                    <p class="dashboard-desc">Localiza una Orden Procesada Mediante su Referencia para Iniciar la Recepción.</p>
                </div>
                <a class="btn-main" href="<?= htmlspecialchars(Session::url('entrada_rapida'), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa-solid fa-truck-fast"></i> Entrada Rápida
                </a>
            </div>

            <section class="reception-search-card">
                <div class="reception-search-intro">
                    <span class="reception-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <div>
                        <h2>Buscar Orden de Compra</h2>
                        <p>Ingresa los 11 Dígitos de la Referencia.</p>
                    </div>
                </div>

                <form method="post" action="<?= htmlspecialchars(Session::url('registrar_entrada'), ENT_QUOTES, 'UTF-8') ?>" id="reference-search-form" autocomplete="off" novalidate>
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <label for="referencia">Referencia de la Orden</label>
                    <div class="reference-search-row">
                        <div class="reference-input-wrap">
                            <span class="reference-prefix">REF</span>
                            <input type="text" id="referencia" name="referencia" inputmode="numeric"
                                   pattern="[0-9]{11}" minlength="11" maxlength="11"
                                   placeholder="20261234567" aria-describedby="reference-help reference-preview" required>
                        </div>
                        <button type="submit" class="btn-main reference-submit" id="reference-submit" disabled>
                            <i class="fa-solid fa-search"></i> Consultar Orden
                        </button>
                    </div>
                    <div class="reference-feedback">
                        <small id="reference-help">La Referencia debe Contener Exactamente 11 Números.</small>
                        <strong id="reference-preview" aria-live="polite">TAKAB-OC-AAAA-MM-00000</strong>
                    </div>
                </form>
            </section>

            <section class="reception-history-card">
                <div class="reception-history-heading">
                    <div>
                        <h2><i class="fa-solid fa-clock-rotate-left"></i> Recepciones Recientes</h2>
                        <p>Últimas entradas registradas desde órdenes de compra.</p>
                    </div>
                    <span><?= count($recepcionesRecientes) ?> <?= count($recepcionesRecientes) === 1 ? 'Registro' : 'Registros' ?></span>
                </div>
                <div class="table-responsive">
                    <table class="takab-table reception-history-table">
                        <thead>
                            <tr>
                                <th>Recepción</th>
                                <th>Orden</th>
                                <th>Almacén</th>
                                <th>Partidas</th>
                                <th>Cantidad Recibida</th>
                                <th>Estatus</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($recepcionesRecientes === []): ?>
                            <tr><td colspan="7" class="table-empty">Aún no Hay Recepciones de Órdenes Registradas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recepcionesRecientes as $recepcion): ?>
                                <?php $estatusClase = strtolower((string) ($recepcion['estatus'] ?? 'parcial')); ?>
                                <tr>
                                    <td class="mono"><?= htmlspecialchars((string) ($recepcion['folio_entrada'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="mono"><?= htmlspecialchars((string) ($recepcion['orden_folio'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($recepcion['almacen_nombre'] ?? 'Sin Almacén'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= number_format((int) ($recepcion['total_partidas'] ?? 0)) ?></td>
                                    <td><?= htmlspecialchars(rtrim(rtrim(number_format((float) ($recepcion['total_recibido'] ?? 0), 2, '.', ','), '0'), '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="solicitud-estatus solicitud-estatus--<?= htmlspecialchars($estatusClase, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($recepcion['estatus'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= !empty($recepcion['created_at']) ? date('d/m/Y H:i', strtotime((string) $recepcion['created_at'])) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../layouts/scripts.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('reference-search-form');
    const input = document.getElementById('referencia');
    const submit = document.getElementById('reference-submit');
    const help = document.getElementById('reference-help');
    const preview = document.getElementById('reference-preview');

    const updateReference = () => {
        input.value = input.value.replace(/\D/g, '').slice(0, 11);
        const valid = /^\d{11}$/.test(input.value);
        submit.disabled = !valid;
        input.classList.toggle('is-valid', valid);
        input.classList.toggle('is-invalid', input.value.length > 0 && !valid);
        help.textContent = valid
            ? 'Referencia válida. Ya puedes consultar la orden.'
            : `Faltan ${Math.max(0, 11 - input.value.length)} dígitos.`;
        preview.textContent = valid
            ? `TAKAB-OC-${input.value.slice(0, 6)}-${input.value.slice(6)}`
            : 'TAKAB-OC-AAAA-MM-00000';
    };

    input.addEventListener('input', updateReference);
    form.addEventListener('submit', (event) => {
        if (!/^\d{11}$/.test(input.value)) {
            event.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Referencia Incompleta',
                text: 'Captura exactamente 11 números antes de consultar.',
                confirmButtonColor: '#2563eb'
            }).then(() => input.focus());
        }
    });
    updateReference();

    const alerta = <?= json_encode($alertaSesion, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (alerta) {
        Swal.fire({
            icon: alerta.tipo || 'info',
            title: alerta.titulo || 'Aviso',
            text: alerta.mensaje || '',
            confirmButtonColor: '#2563eb'
        });
    }
});
</script>
</body>
</html>
