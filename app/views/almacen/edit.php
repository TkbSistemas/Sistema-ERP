<?php
Session::requireLogin();

$role = $_SESSION['role'];
$nombre = $_SESSION['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Almacén - TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/config.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="module-inventory-warehouse">
<div class="main-layout">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="content-area">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <main class="dashboard-main">
            <div class="form-card">
                <div class="form-title">Editar Almacén</div>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form class="takab-form" method="post" action="">
                    <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                    <label for="nombre">Nombre:</label>
                    <input id="nombre" type="text" name="nombre" value="<?= htmlspecialchars($almacen['nombre'] ?? '') ?>" required>

                    <label for="ubicacion">Ubicación:</label>
                    <input id="ubicacion" type="text" name="ubicacion" value="<?= htmlspecialchars($almacen['ubicacion'] ?? '') ?>">

                    <label for="responsable_id">Responsable:</label>
                    <select id="responsable_id" name="responsable_id">
                        <option value="">Ninguno</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= (int) $u['id'] ?>" <?= (isset($almacen) && (int) $almacen['responsable_id'] === (int) $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['nombre_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="check-label">
                        <input type="checkbox" name="es_principal" value="1" <?= !empty($almacen['es_principal']) ? 'checked' : '' ?>>
                        ¿Es principal?
                    </label>

                    <button type="submit">Actualizar</button>
                </form>
                <a class="form-link" href="almacenes.php"><i class="fa fa-arrow-left"></i> Volver</a>
            </div>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
