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
    <title>UNIDADES DE MEDIDA | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/config.css">
    <link rel="stylesheet" href="/assets/css/config-pages.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="main-layout">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="content-area">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>
        <main class="dashboard-main config-page">
            <div class="main-table-card">
                <div class="config-section-header">
                    <div>
                        <div class="config-section-title">
                            <span class="config-icon gradient-purple"><i class="fa-solid fa-ruler-combined"></i></span>
                            Unidades de Medida
                        </div>
                        <p class="config-section-desc">Define las unidades que se utilizan para registrar existencias.</p>
                    </div>
                </div>
                <div class="config-section-actions" style="margin-bottom:18px;">
                    <a class="btn-secondary-ghost" href="ajustes.php"><i class="fa fa-arrow-left"></i> Ajustes</a>
                    <a class="btn-main" href="unidades_create.php"><i class="fa fa-plus"></i> Agregar unidad</a>
                </div>
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success"><i class="fa fa-check-circle"></i> Operación realizada correctamente.</div>
                <?php endif; ?>
                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-danger"><i class="fa fa-trash"></i> Unidad eliminada.</div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error'] === 'csrf'): ?>
                    <div class="alert alert-danger"><i class="fa fa-triangle-exclamation"></i> No pudimos completar la acción por motivos de seguridad.</div>
                <?php endif; ?>
                <table class="takab-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Abreviación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unidades as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['nombre']) ?></td>
                            <td><?= htmlspecialchars($u['abreviacion']) ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="unidades_edit.php?id=<?= (int) $u['id'] ?>" class="btn-inline btn-edit"><i class="fa fa-pen"></i> Editar</a>
                                        <form method="post" action="unidades_delete.php" class="inline-form" data-confirm="¿Eliminar unidad?">
                                            <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button type="submit" class="btn-inline btn-delete"><i class="fa fa-trash"></i> Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($unidades)): ?>
                        <tr>
                            <td colspan="3" style="text-align:center;">No hay unidades registradas.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
