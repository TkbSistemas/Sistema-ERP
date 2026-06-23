<?php
Session::requireLogin('Administrador');

$role = $_SESSION['role'];
$nombre = $_SESSION['nombre'];
$deleteStatus = $_GET['deleted'] ?? null;
$deleteError = $_GET['error'] ?? null;
$usuarios_activos = [];
$usuarios_inactivos = [];
foreach ($usuarios as $u) {
    if (!empty($u['activo'])) {
        $usuarios_activos[] = $u;
    } else {
        $usuarios_inactivos[] = $u;
    }
}
$csrfToken = Session::csrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .alert-banner {display:flex;align-items:center;gap:12px;padding:14px 18px;border-radius:10px;margin-bottom:22px;font-weight:600;box-shadow:0 4px 18px rgba(19,39,89,0.08);} 
        .alert-banner i {font-size:1.2rem;} 
        .alert-banner.success {background:linear-gradient(135deg,#e8f8f0,#d1f1e0);color:#0d7a4b;border:1px solid #b5e7ce;} 
        .alert-banner.error {background:linear-gradient(135deg,#feecec,#fcd9d9);color:#b12525;border:1px solid #f3b6b6;} 
        form.inline-action {display:inline;} 
        form.inline-action button {background:none;border:none;color:inherit;cursor:pointer;padding:0;margin:0 4px;} 
        form.inline-action button:hover {color:#0d6efd;} 
        form.inline-action button.btn-danger {color:#c0392b;} 
    </style>
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
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <main class="dashboard-main">
            <?php if ($deleteStatus !== null): ?>
                <div class="alert-banner <?= $deleteStatus === '1' ? 'success' : 'error' ?>">
                    <i class="fa-solid <?= $deleteStatus === '1' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                    <?= $deleteStatus === '1'
                        ? 'Usuario eliminado correctamente.'
                        : ($deleteError === 'fk'
                            ? 'No se pudo eliminar el usuario porque está relacionado con otros registros.'
                            : 'No se pudo eliminar el usuario. Inténtalo de nuevo.') ?>
                </div>
            <?php endif; ?>

            <div class="usuarios-header-row2">
                <h1>Gestión de Usuarios</h1>
                <a class="btn-main" href="usuarios_create.php"><i class="fa fa-plus"></i> Nuevo Usuario</a>
            </div>

            <section class="usuarios-card">
                <div class="card-title">
                    <div>
                        <h2>Usuarios Activos</h2>
                        <span class="card-subtitle">Gestiona las cuentas que tienen acceso</span>
                    </div>
                </div>
                <div class="usuarios-table-responsive">
                    <table class="usuarios-flat-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios_activos as $u): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                    <td><?= htmlspecialchars($u['nombre_completo']) ?></td>
                                    <td><span class="role-badge role-<?= strtolower($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                                    <td><span class="status-badge active">Activo</span></td>
                                    <td>
                                        <a class="btn-table" title="Editar" href="usuarios_edit.php?id=<?= (int) $u['id'] ?>"><i class="fa fa-pen"></i></a>
                                        <form class="inline-action" method="post" action="usuarios_setactive.php">
                                            <input type="hidden" name="csrf" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <input type="hidden" name="active" value="0">
                                            <button type="submit" title="Desactivar"><i class="fa fa-user-slash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($usuarios_activos)): ?>
                                <tr><td colspan="5" style="text-align:center;">Sin usuarios activos.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="usuarios-card">
                <div class="card-title">
                    <div>
                        <h2>Usuarios inactivos</h2>
                        <span class="card-subtitle">Cuentas deshabilitadas</span>
                    </div>
                </div>
                <div class="usuarios-table-responsive">
                    <table class="usuarios-flat-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios_inactivos as $u): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                    <td><?= htmlspecialchars($u['nombre_completo']) ?></td>
                                    <td><span class="role-badge role-<?= strtolower($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                                    <td><span class="status-badge inactive">Inactivo</span></td>
                                    <td>
                                        <form class="inline-action" method="post" action="usuarios_setactive.php">
                                            <input type="hidden" name="csrf" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <input type="hidden" name="active" value="1">
                                            <button type="submit" title="Activar"><i class="fa fa-user-check"></i></button>
                                        </form>
                                        <form class="inline-action" method="post" action="usuarios_delete.php" data-confirm="¿Eliminar usuario?">
                                            <input type="hidden" name="csrf" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button type="submit" class="btn-danger" title="Eliminar"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($usuarios_inactivos)): ?>
                                <tr><td colspan="5" style="text-align:center;">Sin usuarios inactivos.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
