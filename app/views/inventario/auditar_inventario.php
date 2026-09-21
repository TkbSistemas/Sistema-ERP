<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Almacen', 'Inventario']);

$role = $role ?? ($_SESSION['role'] ?? '');
$nombre = $nombre ?? ($_SESSION['nombre'] ?? '');
$productos = is_array($productos ?? null) ? $productos : [];
$almacenes = is_array($almacenes ?? null) ? $almacenes : [];
$categorias = is_array($categorias ?? null) ? $categorias : [];
$tiposProducto = is_array($tiposProducto ?? null) ? $tiposProducto : [];
$filtros = $filtros ?? ['almacen_id' => 0, 'categoria_id' => 0, 'tipo' => ''];
$mostrarListado = (bool) ($mostrarListado ?? false);
$seccion_activa = 'auditar_inventario';

$formatearStock = static function ($valor): string {
    return rtrim(rtrim(number_format((float) $valor, 2, '.', ','), '0'), '.');
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AUDITAR INVENTARIO | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/auditar-inventario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body class="module-inventory-warehouse">
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Mostrar u Ocultar Menú">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>

        <main class="dashboard-main inventory-audit-main">
            <div class="dashboard-header-row">
                <div>
                    <h1 class="page-title-icon"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> AUDITAR INVENTARIO</h1>
                    <span class="dashboard-desc">Compara las Existencias Teóricas con el Conteo Físico del Almacén.</span>
                </div>
                <a class="audit-back-button" href="<?= htmlspecialchars(Session::url('inventario'), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa-solid fa-arrow-left"></i> Volver al Inventario
                </a>
            </div>

            <section class="audit-filter-card">
                <div class="audit-section-heading">
                    <div>
                        <h2><i class="fa-solid fa-filter"></i> Selección del Conteo</h2>
                        <p>Primero Selecciona el Almacén y la Categoría. El Tipo de Producto es Opcional.</p>
                    </div>
                    <span class="audit-step">Paso 1</span>
                </div>

                <form method="get" action="<?= htmlspecialchars(Session::url('auditar_inventario'), ENT_QUOTES, 'UTF-8') ?>" class="audit-filter-form">
                    <label class="audit-filter-field">
                        <span>Almacén <strong>*</strong></span>
                        <span class="audit-select-wrap">
                            <i class="fa-solid fa-warehouse"></i>
                            <select name="almacen_id" required>
                                <option value="">Selecciona un Almacén</option>
                                <?php foreach ($almacenes as $almacen): ?>
                                    <option value="<?= (int) $almacen['id'] ?>" <?= (int) $filtros['almacen_id'] === (int) $almacen['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) $almacen['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </span>
                    </label>

                    <label class="audit-filter-field">
                        <span>Categoría <strong>*</strong></span>
                        <span class="audit-select-wrap">
                            <i class="fa-solid fa-layer-group"></i>
                            <select name="categoria_id" required>
                                <option value="">Selecciona una Categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= (int) $categoria['id'] ?>" <?= (int) $filtros['categoria_id'] === (int) $categoria['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) $categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </span>
                    </label>

                    <label class="audit-filter-field">
                        <span>Tipo de Producto</span>
                        <span class="audit-select-wrap">
                            <i class="fa-solid fa-shapes"></i>
                            <select name="tipo">
                                <option value="">Todos los Tipos</option>
                                <?php foreach ($tiposProducto as $tipo): ?>
                                    <option value="<?= htmlspecialchars((string) $tipo, ENT_QUOTES, 'UTF-8') ?>" <?= $filtros['tipo'] === $tipo ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) $tipo, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </span>
                    </label>

                    <div class="audit-filter-actions">
                        <button type="submit" class="audit-primary-button"><i class="fa-solid fa-list-check"></i> Cargar Productos</button>
                        <?php if ($mostrarListado): ?>
                            <a href="<?= htmlspecialchars(Session::url('auditar_inventario'), ENT_QUOTES, 'UTF-8') ?>" class="audit-secondary-button"><i class="fa-solid fa-eraser"></i> Limpiar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <?php if (!$mostrarListado): ?>
                <section class="audit-empty-state">
                    <span class="audit-empty-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
                    <h2>Selecciona el Inventario que Será Auditado</h2>
                    <p>El Listado Aparecerá al Elegir un Almacén y una Categoría.</p>
                </section>
            <?php else: ?>
                <form id="audit-save-form" method="post" action="<?= htmlspecialchars(Session::url('auditar_inventario'), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="almacen_id" value="<?= (int) $filtros['almacen_id'] ?>">
                    <input type="hidden" name="categoria_id" value="<?= (int) $filtros['categoria_id'] ?>">
                    <input type="hidden" name="tipo" value="<?= htmlspecialchars((string) $filtros['tipo'], ENT_QUOTES, 'UTF-8') ?>">
                <section class="audit-summary-grid" aria-label="Resumen de la Auditoría">
                    <article class="audit-summary-card">
                        <span>Almacén</span>
                        <strong><?= htmlspecialchars((string) ($almacenSeleccionado['nombre'] ?? 'Sin Almacén'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <i class="fa-solid fa-warehouse"></i>
                    </article>
                    <article class="audit-summary-card">
                        <span>Categoría</span>
                        <strong><?= htmlspecialchars((string) ($categoriaSeleccionada['nombre'] ?? 'Sin Categoría'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <i class="fa-solid fa-layer-group"></i>
                    </article>
                    <article class="audit-summary-card">
                        <span>Productos del Listado</span>
                        <strong><?= number_format(count($productos)) ?></strong>
                        <i class="fa-solid fa-cubes"></i>
                    </article>
                    <article class="audit-summary-card audit-summary-progress">
                        <span>Partidas Contadas</span>
                        <strong><span id="audit-counted">0</span> / <?= count($productos) ?></strong>
                        <i class="fa-solid fa-check-double"></i>
                    </article>
                </section>

                <section class="audit-table-card">
                    <div class="audit-section-heading audit-table-heading">
                        <div>
                            <h2><i class="fa-solid fa-clipboard-list"></i> Conteo Físico</h2>
                            <p>Captura el Stock Encontrado Físicamente para Visualizar la Diferencia.</p>
                        </div>
                        <span class="audit-step">Paso 2</span>
                    </div>

                    <?php if ($productos === []): ?>
                        <div class="audit-empty-table">
                            <i class="fa-solid fa-box-open"></i>
                            <p>No se Encontraron Productos con los Filtros Seleccionados.</p>
                        </div>
                    <?php else: ?>
                        <div class="audit-table-wrapper">
                            <table class="audit-table">
                                <thead>
                                    <tr>
                                        <th>Nomenclatura</th>
                                        <th>Nombre</th>
                                        <th>Marca</th>
                                        <th>Modelo</th>
                                        <th>Stock Teórico</th>
                                        <th>Stock Físico</th>
                                        <th>Diferencia</th>
                                        <th>Resultado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($productos as $producto): ?>
                                    <?php
                                    $stockActual = (float) ($producto['stock_actual'] ?? 0);
                                    $unidad = trim((string) ($producto['unidad_abreviacion'] ?? $producto['unidad_medida_nombre'] ?? ''));
                                    ?>
                                    <tr data-audit-row data-theoretical="<?= htmlspecialchars((string) $stockActual, ENT_QUOTES, 'UTF-8') ?>">
                                        <td class="audit-code"><?= htmlspecialchars((string) ($producto['nomenclatura'] ?? 'Sin Registro'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <div class="audit-product-name">
                                                <span class="audit-product-icon"><i class="fa-solid fa-cube"></i></span>
                                                <strong><?= htmlspecialchars((string) ($producto['nombre'] ?? 'Sin Nombre'), ENT_QUOTES, 'UTF-8') ?></strong>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars(trim((string) ($producto['marca'] ?? '')) ?: 'Sin Registro', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(trim((string) ($producto['modelo'] ?? '')) ?: 'Sin Registro', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="audit-theoretical-stock">
                                            <strong><?= htmlspecialchars($formatearStock($stockActual), ENT_QUOTES, 'UTF-8') ?></strong>
                                            <?php if ($unidad !== ''): ?><span><?= htmlspecialchars($unidad, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="audit-physical-control">
                                                <input
                                                    type="number"
                                                    class="audit-physical-input"
                                                    name="stock_fisico[<?= (int) $producto['id'] ?>]"
                                                    value="<?= htmlspecialchars((string) ($_POST['stock_fisico'][(int) $producto['id']] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    min="0"
                                                    step="0.01"
                                                    placeholder="0"
                                                    required
                                                    aria-label="Stock Físico de <?= htmlspecialchars((string) ($producto['nombre'] ?? 'Producto'), ENT_QUOTES, 'UTF-8') ?>"
                                                >
                                                <?php if ($unidad !== ''): ?><span><?= htmlspecialchars($unidad, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="audit-difference">—</td>
                                        <td><span class="audit-result audit-result-pending"><i class="fa-regular fa-clock"></i> Pendiente</span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="audit-footer">
                            <div class="audit-legend">
                                <span><i class="audit-dot audit-dot-match"></i> Coincide</span>
                                <span><i class="audit-dot audit-dot-shortage"></i> Faltante</span>
                                <span><i class="audit-dot audit-dot-surplus"></i> Sobrante</span>
                            </div>
                            <div class="audit-footer-actions">
                                <button type="button" class="audit-secondary-button" id="clear-count"><i class="fa-solid fa-rotate-left"></i> Reiniciar Conteo</button>
                                <button type="submit" class="audit-save-button" id="save-audit" disabled><i class="fa-solid fa-floppy-disk"></i> Guardar Auditoría</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
                </form>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('audit-save-form');
    const rows = [...document.querySelectorAll('[data-audit-row]')];
    const counted = document.getElementById('audit-counted');
    const saveButton = document.getElementById('save-audit');
    let confirmedSubmit = false;
    const formatQuantity = (value) => Number(value).toLocaleString('es-MX', { maximumFractionDigits: 2 });

    const updateAudit = () => {
        let completed = 0;
        rows.forEach((row) => {
            const input = row.querySelector('.audit-physical-input');
            const differenceCell = row.querySelector('.audit-difference');
            const result = row.querySelector('.audit-result');
            const theoretical = Number(row.dataset.theoretical) || 0;
            const hasValue = input.value.trim() !== '';

            result.className = 'audit-result';
            row.classList.remove('audit-row-match', 'audit-row-shortage', 'audit-row-surplus');
            if (!hasValue) {
                differenceCell.textContent = '—';
                result.classList.add('audit-result-pending');
                result.innerHTML = '<i class="fa-regular fa-clock"></i> Pendiente';
                return;
            }

            completed += 1;
            const physical = Math.max(0, Number(input.value) || 0);
            const difference = physical - theoretical;
            differenceCell.textContent = `${difference > 0 ? '+' : ''}${formatQuantity(difference)}`;

            if (Math.abs(difference) < 0.00001) {
                row.classList.add('audit-row-match');
                result.classList.add('audit-result-match');
                result.innerHTML = '<i class="fa-solid fa-check"></i> Coincide';
            } else if (difference < 0) {
                row.classList.add('audit-row-shortage');
                result.classList.add('audit-result-shortage');
                result.innerHTML = '<i class="fa-solid fa-arrow-down"></i> Faltante';
            } else {
                row.classList.add('audit-row-surplus');
                result.classList.add('audit-result-surplus');
                result.innerHTML = '<i class="fa-solid fa-arrow-up"></i> Sobrante';
            }
        });
        if (counted) counted.textContent = String(completed);
        if (saveButton) saveButton.disabled = rows.length === 0 || completed !== rows.length;
    };

    document.querySelectorAll('.audit-physical-input').forEach((input) => {
        input.addEventListener('input', () => {
            if (Number(input.value) < 0) input.value = '0';
            updateAudit();
        });
    });

    document.getElementById('clear-count')?.addEventListener('click', async () => {
        const hasCapturedValues = [...document.querySelectorAll('.audit-physical-input')]
            .some((input) => input.value.trim() !== '');
        if (!hasCapturedValues) return;

        const result = await Swal.fire({
            icon: 'warning',
            title: '¿Reiniciar el Conteo?',
            text: 'Se Borrarán Todas las Cantidades Físicas Capturadas en este Listado.',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sí, Reiniciar',
            cancelButtonText: 'Conservar Conteo',
            reverseButtons: true,
            focusCancel: true
        });
        if (!result.isConfirmed) return;

        document.querySelectorAll('.audit-physical-input').forEach((input) => { input.value = ''; });
        updateAudit();
    });

    form?.addEventListener('submit', async (event) => {
        if (confirmedSubmit || !form.checkValidity()) return;
        event.preventDefault();
        const result = await Swal.fire({
            icon: 'question',
            title: '¿Guardar la Auditoría?',
            text: 'El Stock se Ajustará al Conteo Físico y se Generará el PDF de la Auditoría.',
            showCancelButton: true,
            confirmButtonColor: '#16834a',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sí, Guardar',
            cancelButtonText: 'Revisar'
        });
        if (result.isConfirmed) {
            confirmedSubmit = true;
            if (saveButton) {
                saveButton.disabled = true;
                saveButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const contentType = response.headers.get('Content-Type') || '';
                if (!response.ok || !contentType.includes('application/pdf')) {
                    let mensaje = 'No Fue Posible Guardar la Auditoría.';
                    if (contentType.includes('application/json')) {
                        const data = await response.json();
                        mensaje = data.mensaje || mensaje;
                    }
                    throw new Error(mensaje);
                }

                const pdf = await response.blob();
                const disposition = response.headers.get('Content-Disposition') || '';
                const filenameMatch = disposition.match(/filename="?([^";]+)"?/i);
                const filename = filenameMatch?.[1] || 'auditoria_inventario.pdf';
                const downloadUrl = URL.createObjectURL(pdf);
                const link = document.createElement('a');
                link.href = downloadUrl;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);

                form.reset();
                form.querySelectorAll('.audit-physical-input').forEach((input) => { input.value = ''; });
                updateAudit();
                confirmedSubmit = false;
                if (saveButton) {
                    saveButton.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Auditoría';
                }

                await Swal.fire({
                    icon: 'success',
                    title: 'Auditoría Guardada',
                    text: 'El Conteo se Registró Correctamente y el PDF Fue Descargado.',
                    confirmButtonColor: '#16834a'
                });
            } catch (error) {
                confirmedSubmit = false;
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Auditoría';
                }
                Swal.fire({
                    icon: 'error',
                    title: 'No Fue Posible Guardar la Auditoría',
                    text: error instanceof Error ? error.message : 'Intenta Nuevamente.',
                    confirmButtonColor: '#2563eb'
                });
            }
        }
    });

    const toggleButton = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.main_sidebar');
    const content = document.querySelector('.content-area');
    toggleButton?.addEventListener('click', () => {
        sidebar?.classList.toggle('collapsed');
        content?.classList.toggle('collapsed');
        const icon = toggleButton.querySelector('i');
        if (icon) icon.className = sidebar?.classList.contains('collapsed') ? 'fa-solid fa-bars' : 'fa-solid fa-xmark';
    });

    <?php if ($error !== ''): ?>
    Swal.fire({
        icon: 'error',
        title: 'No Fue Posible Guardar la Auditoría',
        text: <?= json_encode($error, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        confirmButtonColor: '#2563eb'
    });
    <?php endif; ?>
});
</script>
</body>
</html>
