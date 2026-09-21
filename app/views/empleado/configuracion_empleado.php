<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Empleado', 'Administrador']);

$role = $role ?? ($_SESSION['role'] ?? '');
$nombre = $nombre ?? ($_SESSION['nombre'] ?? '');
$error = $error ?? '';
$alertaSesion = $_SESSION['alerta'] ?? null;
unset($_SESSION['alerta']);
$seccion_activa = 'configuracion_empleado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración | TAKAB</title>
    <link rel="stylesheet" href="assets/css/prestamos-pendientes.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/configuracion-empleado.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body class="module-inventory-warehouse">
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Mostrar u Ocultar Menú">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>

        <main class="dashboard-main employee-settings-main">
            <div class="dashboard-header-row">
                <div>
                    <h1 class="page-title-icon"><i class="fa-solid fa-user-gear" aria-hidden="true"></i> CONFIGURACIÓN</h1>
                    <span class="dashboard-desc">Administra la Seguridad de tu Cuenta.</span>
                </div>
            </div>

            <section class="password-settings-card">
                <div class="password-settings-heading">
                    <span class="password-settings-icon"><i class="fa-solid fa-key"></i></span>
                    <div>
                        <h2>Cambiar Contraseña</h2>
                        <p>Escribe tu Contraseña Actual y Define una Nueva Contraseña.</p>
                    </div>
                </div>

                <form method="post" action="<?= htmlspecialchars(Session::url('configuracion_empleado'), ENT_QUOTES, 'UTF-8') ?>" class="password-settings-form" autocomplete="off">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

                    <label class="password-field">
                        <span>Contraseña Actual</span>
                        <span class="password-input-wrap">
                            <i class="fa-solid fa-lock" aria-hidden="true"></i>
                            <input type="password" name="password_actual" autocomplete="current-password" required>
                            <button type="button" class="password-toggle" aria-label="Mostrar Contraseña"><i class="fa-solid fa-eye"></i></button>
                        </span>
                    </label>

                    <div class="password-fields-row">
                        <label class="password-field">
                            <span>Nueva Contraseña</span>
                            <span class="password-input-wrap">
                                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                                <input type="password" name="password_nueva" minlength="8" maxlength="72" autocomplete="new-password" required>
                                <button type="button" class="password-toggle" aria-label="Mostrar Contraseña"><i class="fa-solid fa-eye"></i></button>
                            </span>
                        </label>

                        <label class="password-field">
                            <span>Confirmar Nueva Contraseña</span>
                            <span class="password-input-wrap">
                                <i class="fa-solid fa-shield-check" aria-hidden="true"></i>
                                <input type="password" name="password_confirmacion" minlength="8" maxlength="72" autocomplete="new-password" required>
                                <button type="button" class="password-toggle" aria-label="Mostrar Contraseña"><i class="fa-solid fa-eye"></i></button>
                            </span>
                        </label>
                    </div>

                    <div class="password-guidance">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Usa entre 8 y 72 Caracteres. La Nueva Contraseña Debe ser Diferente de la Actual.</span>
                    </div>

                    <div class="password-settings-actions">
                        <button type="submit" class="password-save-button">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar Nueva Contraseña
                        </button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggleButton = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.main_sidebar');
    const content = document.querySelector('.content-area');

    toggleButton?.addEventListener('click', () => {
        sidebar?.classList.toggle('collapsed');
        content?.classList.toggle('collapsed');
        const icon = toggleButton.querySelector('i');
        if (icon) icon.className = sidebar?.classList.contains('collapsed') ? 'fa-solid fa-bars' : 'fa-solid fa-xmark';
    });

    document.querySelectorAll('.password-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.parentElement?.querySelector('input');
            if (!input) return;
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.setAttribute('aria-label', visible ? 'Mostrar Contraseña' : 'Ocultar Contraseña');
            const icon = button.querySelector('i');
            if (icon) icon.className = visible ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        });
    });

    <?php if (is_array($alertaSesion)): ?>
    Swal.fire({
        icon: <?= json_encode($alertaSesion['tipo'] ?? 'info', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        title: <?= json_encode($alertaSesion['titulo'] ?? 'Aviso', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        text: <?= json_encode($alertaSesion['mensaje'] ?? '', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        confirmButtonColor: '#2563eb'
    });
    <?php elseif ($error !== ''): ?>
    Swal.fire({
        icon: 'error',
        title: 'No Fue Posible Cambiar la Contraseña',
        text: <?= json_encode($error, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        confirmButtonColor: '#2563eb'
    });
    <?php endif; ?>
});
</script>
</body>
</html>
