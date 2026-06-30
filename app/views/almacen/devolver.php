<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Almacen']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Devolución de Herramienta | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/prestamo-devolver.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="devolver-main">
    <div class="devolver-title">
        <i class="fa-solid fa-undo"></i>
        Registrar Devolución de Herramienta
    </div>
    <?php if (!empty($msg)): ?>
        <div class="devolver-msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($prestamo): ?>
        <div class="devolver-info">
            <div>
                <label>Empleado:</label> <?= htmlspecialchars($prestamo['empleado']) ?>
            </div>
            <div>
                <label>Herramienta:</label> <?= htmlspecialchars($prestamo['producto']) ?>
            </div>
            <div>
                <label>Fecha Préstamo:</label> <?= htmlspecialchars($prestamo['fecha_prestamo']) ?>
            </div>
            <div>
                <label>Fecha Estimada Devolución:</label> <?= htmlspecialchars($prestamo['fecha_estimada_devolucion']) ?>
            </div>
            <div>
                <label>Observaciones al prestar:</label>
                <span class="devolver-observ"><?= htmlspecialchars($prestamo['observaciones']) ?></span>
            </div>
        </div>
        <form method="post" class="devolver-form">
            <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">
            <label for="estado_devolucion">Estado al devolver:</label>
            <select name="estado_devolucion" id="estado_devolucion" class="devolver-select" required>
                <option value="Bueno">Bueno</option>
                <option value="Dañado">Dañado</option>
                <option value="Perdido">Perdido</option>
            </select>
            <label for="observaciones">Observaciones al devolver (opcional):</label>
            <input type="text" name="observaciones" id="observaciones" class="devolver-input">
            <div class="devolver-actions">
                <button type="submit" class="btn-devolver">
                    <i class="fa fa-undo"></i> Registrar devolución
                </button>
                <a href="prestamos_pendientes.php" class="btn-volver">
                    <i class="fa fa-arrow-left"></i> Volver a pendientes
                </a>
            </div>
        </form>
    <?php else: ?>
        <p>No se encontró el préstamo o ya fue devuelto.</p>
        <a href="prestamos_pendientes.php" class="btn-volver">
            <i class="fa fa-arrow-left"></i> Volver a pendientes
        </a>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
