<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INICIO DE SESIÓN - TAKAB</title>
    <link rel="stylesheet" href="assets/css/login2.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">  
    <script src="./assets/js/libs/sweetalert2.all.min.js"></script>
    <style>
        body {
            color: var(--white);
            background: url('assets/images/edificios20.jpg') center center / cover no-repeat fixed; 
        }
    </style>
</head>
<body>
<?php

    $moduleLabels = [
        'nomina' => 'Nómina',
        'contabilidad' => 'Contabilidad',
        'bancos' => 'Bancos',
        'compras' => 'Compras',
        'inventario' => 'Inventarios',
        'ventas' => 'Ventas',
        'proyectos' => 'Realización de proyectos',
        'mantenimientos' => 'Mantenimientos',
    ];
    $moduleKey = $module ?? null;
    $moduleName = ($moduleKey && isset($moduleLabels[$moduleKey])) ? $moduleLabels[$moduleKey] : 'Acceso general';
?>

<main class="portal-login">
    <div class="portal-bg-overlay"></div>

    <section class="portal-layout">
        <div class="portal-left">
            <div class="portal-brand-wrap">
                <div class="portal-brand-row">
                    <div class="brand-logo-block" aria-hidden="true">
                        <img src="assets/images/icono_takab.png" alt="TAKAB" class="brand-logo-img">
                    </div>

                    <div class="brand-separator"></div>

                    <div class="brand-portal-block">
                        <div class="brand-portal-text">
                            <span class="brand-portal-mini">TAKAB TECHNOLOGY</span>
                            <h1>SISTEMA ERP</h1>
                        </div>
                    </div>
                </div>

                <p class="portal-copy">
                    Accede al ERP TAKAB para Consultar los Módulos Disponibles.
                </p>
            </div>
        </div>

        <aside class="login-panel">
            <div class="login-panel-inner">
               <div class="login-header">
                        <h2>INICIO DE SESIÓN</h2>
                        <p>Ingresa tus credenciales para acceder al sistema.</p>
                </div>

                <?php if (isset($_SESSION['alerta'])): ?>
                    <script>
                        Swal.fire({
                            icon: '<?php echo $_SESSION['alerta']['tipo']; ?>',
                            title: '<?php echo $_SESSION['alerta']['titulo']; ?>',
                            text: '<?php echo $_SESSION['alerta']['mensaje']; ?>',
                            confirmButtonColor: '#3085d6'
                        });
                    </script>
                    <?php 
                        unset($_SESSION['alerta']); 
                    ?>
                <?php endif; ?>

                <form method="POST" action="<?= htmlspecialchars(Session::url('login')); ?>" class="login-form">
                        <?php if ($moduleKey): ?>
                            <input type="hidden" name="module" value="<?= htmlspecialchars($moduleKey); ?>">
                        <?php endif; ?>
                        <?php if (!empty($next)): ?>
                            <input type="hidden" name="next" value="<?= htmlspecialchars($next); ?>">
                        <?php endif; ?>

                        <div class="field-group">
                            <label for="username">Usuario</label>
                            <div class="input-wrap">
                                <i class="fa-regular fa-user"></i>
                                <input type="text" id="username" name="username" required autofocus placeholder="Ingresa tu usuario">
                            </div>
                        </div>

                        <div class="field-group">
                            <label for="password">Contraseña</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" id="password" name="password" required placeholder="Ingresa tu contraseña">
                                <button type="button" id="togglePassword" aria-label="Mostrar contraseña">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                       <div class="login-links">
                          <a class="text-link" href="<?= htmlspecialchars(Session::url('index')); ?>">Volver al inicio</a> 
                            <a class="text-link" href="<?= htmlspecialchars(Session::url('forgot.php')); ?>">Olvidé mi contraseña</a>
                        </div>

                    <button type="submit" class="btn-login">Iniciar sesión</button>
                </form>
                
                <!--details class="login-test-users">
                    <summary>Usuarios de prueba</summary>
                    <ul>
                        <li><b>Admin:</b> admin / 123456</li>
                    </ul>
                </details-->
            </div>
        </aside>
    </section>
</main>

<script>

(function () {
    const toggleBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.className = isPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
            }
        });
    }
})();
</script>
</body>
</html>
