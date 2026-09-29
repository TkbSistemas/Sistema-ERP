<!DOCTYPE html>
<html lang="es">
<head>
    <?php include __DIR__ . '/../layouts/favicon.php'; ?>
    <meta charset="UTF-8">
    <title>RECUPERAR CONTRASEÑA | TAKAB</title>
    <link rel="stylesheet" href="assets/css/login2.css">
    <style>
        body {
            color: var(--white);
            background: url('assets/images/edificios20.jpg') center center / cover no-repeat fixed;
        }
        
    </style>
</head>
<body>
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
                    Recupera tu contraseña para acceder al ERP TAKAB y consultar los módulos disponibles.
                </p>
            </div>
        </div>
    <aside class="login-panel">
    <div class="login-panel-inner">
        <div class="login-header">
            <h2>Recuperar Contraseña</h2>
            <p>Ingresa tu usuario para restaurar el acceso</p>
            <p>El equipo de sistemas validará tu solicitud.</p>
        </div>

        <div class="field-group">
            <?php if (isset($mensaje)) echo "<p class='login-info'>$mensaje</p>"; ?>
            <form method="POST" action="">
                <label for="username">Usuario</label>
                <div class="input-wrap">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" id="username" name="username" required autofocus placeholder="Ingresa tu usuario">
                </div>
                <div class="login-links">
                    <a class="text-link" href="index.php">Volver al inicio</a> 
                </div>

                    <button type="submit" class="btn-login">Recuperar</button>
                </form>
            </div>
        
        </div>
    </div>
    <script src="https://kit.fontawesome.com/8b82fe7e0b.js" crossorigin="anonymous"></script>
</body>
</html>
