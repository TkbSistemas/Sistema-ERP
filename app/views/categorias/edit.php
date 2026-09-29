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
    <title>EDITAR CATEGORÍA | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/config.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="main-layout">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="content-area">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <main class="dashboard-main">
            <div class="form-card">
                <div class="form-title">Editar categoría</div>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form class="takab-form" method="post" action="">
                    <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                    <label for="nombre">Nombre:</label>
                    <input id="nombre" type="text" name="nombre" value="<?= htmlspecialchars($categoria['nombre'] ?? '') ?>" required>

                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($categoria['descripcion'] ?? '') ?></textarea>

                    <button type="submit">Actualizar</button>
                </form>
                <a class="form-link" href="categorias.php"><i class="fa fa-arrow-left"></i> Volver</a>
            </div>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
