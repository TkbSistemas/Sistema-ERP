<?php
require_once __DIR__ . '/../../helpers/Session.php';

Session::start();
// Si ya hay sesión, saltar directo al menú de módulos.
if (isset($_SESSION['user_id'])) {
    header('Location: menu.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>INICIO TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/config.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body, html {
            color: var(--white);
            background: url('assets/images/edificios20.jpg') center center / cover no-repeat fixed;
        }
    </style>
</head>
<body>
    <div class="content-area">
        <main class="inicio-main">
            <div class="login-container">
                <div class="inicio-title">Bienvenido al 
                    <br> ERP TAKAB
                </div>
                <div class="inicio-desc">Inicia sesión para acceder al sistema.</div> 
                <a href="login" class="inicio-btn"><i class="fa-solid fa-right-to-bracket"></i> Iniciar sesión</a> 
                <footer>
                    <div>
                        <br>
                        <img src="assets/images/LogoTakab2.webp" alt="logo Takab" align-item="center justify-content-center" width="320px">
                    </div>
                </footer>        
            </div>
        </main>
    </div>
</body>
</html>
