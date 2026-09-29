<?php
Session::requireLogin(['Administrador', 'Almacen']);

$role = $_SESSION['role'];
$nombre = $_SESSION['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include __DIR__ . '/../layouts/favicon.php'; ?>
    <meta charset="UTF-8">
    <title>EDITAR UNIDAD DE MEDIDA | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/config.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="main-layout">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="content-area">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <main class="dashboard-main">
            <div class="form-card">
                <div class="form-title">Editar unidad de medida</div>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form class="takab-form" method="post" action="">
                    <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                    <label for="nombre">Nombre:</label>
                    <input id="nombre" type="text" name="nombre" value="<?= htmlspecialchars($unidad['nombre'] ?? '') ?>" required>

                    <label for="abreviacion">Abreviación:</label>
                    <input id="abreviacion" type="text" name="abreviacion" value="<?= htmlspecialchars($unidad['abreviacion'] ?? '') ?>" required>

                    <button type="submit">Actualizar</button>
                </form>
                <a class="form-link" href="unidades.php"><i class="fa fa-arrow-left"></i> Volver</a>
            </div>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
