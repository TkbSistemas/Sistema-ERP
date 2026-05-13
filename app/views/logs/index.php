<?php
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';

$buildQuery = function(array $overrides = []) {
    $params = array_merge($_GET, $overrides);
    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        }
    }
    return $params ? '?' . http_build_query($params) : '?';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitácora de Actividad | TAKAB</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/reportes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .logs-main { padding: 32px 32px 48px; }
        .logs-header { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; margin-bottom:26px; }
        .logs-header h1 { margin:0; font-size:2rem; color:#12305f; }
        .logs-table { width:100%; border-collapse:collapse; min-width:960px; }
        .logs-table th, .logs-table td { padding:11px 12px; border-bottom:1px solid #edf0f6; text-align:left; font-size:0.95rem; color:#1a2c51; }
        .logs-table th { background:#f3f6fc; text-transform:uppercase; letter-spacing:.4px; color:#5a6a94; font-size:0.88rem; }
        .badge-accion { display:inline-block; padding:3px 10px; border-radius:999px; background:#eef4ff; color:#2a4fa3; font-weight:600; font-size:0.82rem; }
        .logs-pagination { margin-top:18px; display:flex; gap:12px; align-items:center; }
        .logs-pagination a { color:#1f4ea1; text-decoration:none; font-weight:600; }
        .logs-filter-card { background:#fff; border-radius:16px; padding:24px 26px; border:1px solid #e4e8f3; box-shadow:0 2px 18px rgba(23,44,87,0.05); margin-bottom:24px; }
        .logs-filter-form { display:grid; gap:18px; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); }
        .logs-filter-field { display:flex; flex-direction:column; gap:8px; }
        .logs-filter-field label { font-weight:600; color:#3a4a7a; }
        .logs-filter-field input, .logs-filter-field select { padding:10px 12px; border-radius:9px; border:1px solid #d6dbea; background:#fafbff; color:#1a2c51; }
        .logs-filter-actions { display:flex; gap:10px; align-items:center; }
        @media (max-width:768px) {
            .logs-main { padding:24px 18px 36px; }
            .logs-header { flex-direction:column; align-items:flex-start; }
        }
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
                <a href="usuarios.php"><i class="fa-solid fa-users-cog"></i> Gestión de Usuarios</a>
                <a href="logs.php" class="active"><i class="fa-solid fa-clipboard-list"></i> Bitácora del sistema</a>
            <?php endif; ?>
            <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </nav>
    </aside>
    <div class="content-area">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>

        <main class="dashboard-main logs-main">
            <div class="logs-header">
                <div>
                    <h1>Bitácora de actividad</h1>
                    <p class="reportes-desc">Consulta los eventos más recientes realizados por los usuarios de la plataforma.</p>
                </div>
            </div>

            <section class="logs-filter-card">
                <form method="get" class="logs-filter-form">
                    <div class="logs-filter-field">
                        <label for="usuario_id">Usuario</label>
                        <select id="usuario_id" name="usuario_id">
                            <option value="">Todos</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?= $usuario['id'] ?>" <?= $filtros['usuario_id'] == $usuario['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($usuario['nombre_completo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="logs-filter-field">
                        <label for="accion">Acción</label>
                        <input type="text" id="accion" name="accion" placeholder="Buscar por acción (ej. producto_creado)" value="<?= htmlspecialchars($filtros['accion']) ?>">
                    </div>
                    <div class="logs-filter-field">
                        <label for="desde">Desde</label>
                        <input type="date" id="desde" name="desde" value="<?= htmlspecialchars($filtros['desde']) ?>">
                    </div>
                    <div class="logs-filter-field">
                        <label for="hasta">Hasta</label>
                        <input type="date" id="hasta" name="hasta" value="<?= htmlspecialchars($filtros['hasta']) ?>">
                    </div>
                    <div class="logs-filter-actions">
                        <button type="submit" class="btn-main"><i class="fa fa-filter"></i> Filtrar</button>
                        <a class="btn-ghost" href="logs.php"><i class="fa fa-eraser"></i> Limpiar</a>
                    </div>
                </form>
            </section>

            <section class="reportes-section">
                <div class="reportes-table-wrapper">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Acción</th>
                                <th>Descripción</th>
                                <th>Usuario</th>
                                <th>IP</th>
                                <th>Agente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:24px; color:#7d8bb0;">Sin registros.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                                        <td><span class="badge-accion"><?= htmlspecialchars($log['accion']) ?></span></td>
                                        <td><?= htmlspecialchars($log['descripcion'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($log['usuario_nombre'] ?? $log['username'] ?? 'Sistema') ?></td>
                                        <td><?= htmlspecialchars($log['ip'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($log['user_agent'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalPaginas > 1): ?>
                    <div class="logs-pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="<?= $buildQuery(['page' => $pagina - 1]) ?>">&laquo; Anterior</a>
                        <?php endif; ?>
                        <span>Página <?= $pagina ?> de <?= $totalPaginas ?></span>
                        <?php if ($pagina < $totalPaginas): ?>
                            <a href="<?= $buildQuery(['page' => $pagina + 1]) ?>">Siguiente &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
