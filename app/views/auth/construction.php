<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CREANDO - TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/config.css">
    <link rel="stylesheet" href="assets/css/proyectos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body, html { 
            background-color: #f5f7fa;
            background: url('assets/images/edificios20.jpg') center center / cover no-repeat fixed;
     }
        .wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background:  linear-gradient(90deg, rgba(9, 19, 46, 0.67) 0%, rgba(12, 27, 63, 0.57) 58%, rgba(12, 27, 63, 0.9) 100%),
            radial-gradient(circle at 18% 70%, rgba(255, 255, 255, 0.10), transparent 45%);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="header">
                <div>
                    <h1 class="title">EN CONSTRUCCIÓN</h1>
                    <p class="sub">TAKAB LO MEJOR LO ESTAMOS CREANDO</p>
                </div>
                <img src="assets/images/LogoTakab2.webp" alt="Logo Takab" style="height:60px;width:auto;">
            </div>

            <div class="note">
                <i class="fa fa-tools" style="color: #dbac02; margin-right:10px; font-size: 3.5rem;"></i>
                <b>Esta Función Aún No Está Implementada.</b> 
            </div>

            <div class="actions">
                <a class="btn secondary" href="javascript:history.back()"><i class="fa-solid fa-layer-group"></i> Volver</a>
                <a class="btn" style="background:#d32323" href="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar Sesión</a>
            </div>
        </div>
    </div>
</body>
</html>
