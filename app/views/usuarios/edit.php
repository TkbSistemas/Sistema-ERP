<?php Session::requireLogin('Administrador');
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
$breadcrumbs = [
    ['label' => 'Editar usuario'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="main-layout">
   <aside class="sidebar">
        <div class="sidebar-header">
            <div class="login-logo"><img src="assets/images/icono_takab.png" alt="logo_TAKAB" width="90" height="55"></div>
            <div>
                <div class="sidebar-title">TAKAB</div>
                <div class="sidebar-desc">Gestión de usuarios</div>
            </div>
        </div>
        <nav class="sidebar-nav">     
            <?php if ($role === 'Administrador'): ?>
                <a href="usuarios.php" class="active"><i class="fa-solid fa-users-cog"></i> Gestión de Usuarios</a>
                <a href="logs.php"><i class="fa-solid fa-clipboard-list"></i> Bitácora del sistema</a>
            <?php endif; ?>
            <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>
    <div class="content-area">
        <!-- Topbar -->
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <!-- Formulario de edición -->
        <main class="dashboard-main">
            <div class="usuarios-header-row2">
                <h1>Editar Usuario</h1>
                <a class="btn-main" href="usuarios.php"><i class="fa fa-arrow-left"></i> Volver al listado</a>
            </div>
            <div class="form-box">
                <?php if (!empty($error)) echo "<p class='form-error'>$error</p>"; ?>
                <form class="usuario-form" method="post" action="">
                    <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                    <label>Nombre completo:</label>
                    <input type="text" name="nombre_completo" value="<?= htmlspecialchars($usuario['nombre_completo']) ?>" required>
                    <label>Usuario:</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($usuario['username']) ?>" required>
                    <label>Contraseña:</label>
                    <input type="password" name="password" placeholder="Dejar en blanco para no cambiar">
                    <label>Rol:</label>
                    <select name="role" required>
                        <option value="Empleado" <?= $usuario['role'] === 'Empleado' ? 'selected' : '' ?>>Empleado</option>
                        <option value="Almacen" <?= $usuario['role'] === 'Almacen' ? 'selected' : '' ?>>Almacén</option>
                        <option value="Compras" <?= $usuario['role'] === 'Compras' ? 'selected' : '' ?>>Compras</option>
                        <option value="Administrador" <?= $usuario['role'] === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                    </select>
                    <label class="check-label">
                        <input type="checkbox" name="activo" <?= $usuario['activo'] ? 'checked' : '' ?>>
                        Activo
                    </label>
                    <button type="submit" class="btn-main"><i class="fa fa-save"></i> Guardar</button>
                </form>
            </div>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>

