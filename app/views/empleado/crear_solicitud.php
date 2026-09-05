<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Empleado']);

$role = $_SESSION['role'] ?? 'Empleado';
$nombre = $_SESSION['nombre'] ?? '';
$productos = is_array($productos ?? null) ? array_values(array_filter(
    $productos,
    static fn(array $producto): bool => !array_key_exists('activo', $producto) || (string) $producto['activo'] === '1'
)) : [];
$proyectos = is_array($proyectos ?? null) ? $proyectos : [];
$error = $error ?? '';
$msg = $msg ?? '';

$categoriasFiltro = [];
foreach ($productos as $producto) {
    $categoria = trim((string) ($producto['categoria'] ?? ''));
    if ($categoria !== '') {
        $categoriasFiltro[$categoria] = true;
    }
}
ksort($categoriasFiltro, SORT_NATURAL | SORT_FLAG_CASE);

$materialesIniciales = [];
$materialesPost = $_POST['material'] ?? '';
if (is_string($materialesPost) && $materialesPost !== '') {
    $materialesDecodificados = json_decode($materialesPost, true);
    if (is_array($materialesDecodificados)) {
        $materialesIniciales = $materialesDecodificados;
    }
} elseif (!empty($entradaItems) && is_array($entradaItems)) {
    $materialesIniciales = array_values($entradaItems);
}

$alertaSesion = $_SESSION['alerta'] ?? null;
unset($_SESSION['alerta']);
$seccion_activa = 'crear_solicitud';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Solicitar Material | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/productos.css">
    <link rel="stylesheet" href="assets/css/inventario_form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body class="module-inventory-warehouse solicitud-material-page">
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Abrir o Cerrar Menú" aria-expanded="true">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>

        <main class="dashboard-main inventario-form-main">
            <div class="inventario-form-header">
                <div>
                    <h1><i class="fa-solid fa-file-circle-plus"></i> Solicitud de Materiales</h1>
                    <p class="form-desc">Agrega Productos del Catálogo o Materiales que todavía no Estén Registrados.</p>
                </div>
            </div>

            <?php if ($msg !== ''): ?>
                <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="inventario-form-grid">
                <section class="inventario-form-card">
                    <h2><i class="fa-solid fa-clipboard-list"></i> Datos de la Solicitud</h2>
                    <form action="<?= htmlspecialchars(Session::url('crear_solicitud'), ENT_QUOTES, 'UTF-8') ?>"
                          method="post"
                          autocomplete="off"
                          class="inventario-entry-form"
                          id="solicitud-form">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="material" id="material" value="">
                        <input type="hidden" name="comentario" id="comentario" value="">
                        <input type="hidden" name="observacion" id="observacion" value="">

                        <div class="double-field">
                            <div class="form-field">
                                <label for="proyecto_id">Proyecto o Destino *</label>
                                <select id="proyecto_id" name="proyecto_id" required>
                                    <option value="">Selecciona un Proyecto o Destino...</option>
                                    <?php foreach ($proyectos as $proyecto): ?>
                                        <option value="<?= (int) ($proyecto['id'] ?? 0) ?>"
                                            <?= (string) ($_POST['proyecto_id'] ?? '') === (string) ($proyecto['id'] ?? '') ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($proyecto['nombre'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="fecha_entrega">Fecha Requerida *</label>
                                <input type="date" id="fecha_entrega" name="fecha_entrega" min="<?= date('Y-m-d') ?>"
                                       value="<?= htmlspecialchars($_POST['fecha_entrega'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="comentario_general">Motivo o Indicaciones Generales</label>
                            <textarea id="comentario_general" name="comentario_general" rows="3"
                                      maxlength="255"
                                      placeholder="Describe Brevemente para qué se Utilizarán los Materiales."><?= htmlspecialchars($_POST['comentario_general'] ?? '') ?></textarea>
                        </div>

                        <div class="form-field-line">
                            <input type="checkbox" id="toggle_fuera_catalogo">
                            <label for="toggle_fuera_catalogo">El Material no Está en el Catálogo</label>
                        </div>

                        <div id="modo_catalogo">
                            <div class="form-field">
                                <label for="filtro_texto">Buscar en el Catálogo</label>
                                <div class="double-field">
                                    <input type="search" id="filtro_texto" placeholder="Nombre, Nomenclatura, SKU, Fabricante o Serie">
                                    <select id="filtro_tipo" aria-label="Filtrar por Tipo">
                                        <option value="">Todos los Tipos</option>
                                        <option value="Consumible">Consumible</option>
                                        <option value="Herramienta">Herramienta</option>
                                        <option value="Equipo">Equipo</option>
                                    </select>
                                    <select id="filtro_categoria" aria-label="Filtrar por Categoría">
                                        <option value="">Todas las Categorías</option>
                                        <?php foreach (array_keys($categoriasFiltro) as $categoriaNombre): ?>
                                            <option value="<?= htmlspecialchars($categoriaNombre) ?>"><?= htmlspecialchars($categoriaNombre) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <small>Productos Visibles: <span id="filtro_resultados">0</span></small>
                            </div>

                            <div class="form-field">
                                <label for="producto_id">Producto del Catálogo *</label>
                                <select id="producto_id">
                                    <option value="">Selecciona un Producto...</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <?php
                                        $codigo = $producto['nomenclatura'] ?? $producto['codigo_fabricante'] ?? '';
                                        $stockActual = (float) ($producto['stock_actual'] ?? 0);
                                        $unidadProducto = $producto['unidad_medida_nombre'] ?? $producto['unidad_abreviacion'] ?? '';
                                        $textoBusqueda = implode(' ', [
                                            $producto['nombre'] ?? '',
                                            $producto['nomenclatura'] ?? '',
                                            $producto['sku'] ?? '',
                                            $producto['codigo_fabricante'] ?? '',
                                            $producto['num_serie'] ?? '',
                                            $producto['codigos_barras'] ?? '',
                                        ]);
                                        ?>
                                        <option value="<?= (int) ($producto['id'] ?? 0) ?>"
                                                data-nombre="<?= htmlspecialchars($producto['nombre'] ?? '') ?>"
                                                data-busqueda="<?= htmlspecialchars($textoBusqueda) ?>"
                                                data-stock="<?= $stockActual ?>"
                                                data-unidad="<?= htmlspecialchars($unidadProducto) ?>"
                                                data-tipo="<?= htmlspecialchars($producto['tipo'] ?? '') ?>"
                                                data-categoria="<?= htmlspecialchars($producto['categoria'] ?? '') ?>">
                                            <?= htmlspecialchars($producto['nombre'] ?? '') ?><?= $codigo !== '' ? ' — ' . htmlspecialchars($codigo) : '' ?> · Stock: <?= number_format($stockActual, 2) ?> <?= htmlspecialchars($unidadProducto) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div id="modo_manual" hidden>
                            <div class="double-field">
                                <div class="form-field">
                                    <label for="manual_nombre">Nombre del Material *</label>
                                    <input type="text" id="manual_nombre" maxlength="100" placeholder="Ej. Broca de Cobalto 1/2">
                                </div>
                                <div class="form-field">
                                    <label for="manual_marca">Marca o Modelo Sugerido</label>
                                    <input type="text" id="manual_marca" maxlength="100" placeholder="Ej. Truper o DeWalt">
                                </div>
                                <div class="form-field">
                                    <label for="manual_tamano">Dimensiones o Especificación</label>
                                    <input type="text" id="manual_tamano" maxlength="50" placeholder="Ej. 1/2 Pulgada">
                                </div>
                                <div class="form-field">
                                    <label for="manual_unidad">Unidad de Medida *</label>
                                    <select id="manual_unidad">
                                        <option value="Pieza">Pieza</option>
                                        <option value="Metro">Metro</option>
                                        <option value="Litro">Litro</option>
                                        <option value="Kilogramo">Kilogramo</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="double-field">
                            <div class="form-field">
                                <label for="cantidad">Cantidad Necesaria *</label>
                                <input type="number" id="cantidad" min="0.01" step="0.01" placeholder="Ej. 25">
                            </div>
                            <div class="form-field">
                                <label for="observaciones">Observaciones del Material</label>
                                <textarea id="observaciones" rows="2" maxlength="255" placeholder="Características o Indicaciones Específicas."></textarea>
                            </div>
                        </div>

                        <div class="entry-batch-actions">
                            <button type="button" class="btn-secondary" id="agregar-material"><i class="fa fa-plus"></i> Agregar a la Solicitud</button>
                            <button type="button" class="btn-ghost" id="limpiar-captura"><i class="fa fa-eraser"></i> Vaciar Lista</button>
                            <button type="submit" class="btn-main"><i class="fa fa-paper-plane"></i> Enviar Solicitud</button>
                        </div>

                        <section class="entry-items-panel" aria-live="polite">
                            <div class="entry-items-header">
                                <h3><i class="fa fa-list-check"></i> Materiales Solicitados</h3>
                                <span id="items-count">0 Materiales</span>
                            </div>
                            <div class="inventario-empty entry-items-empty" id="items-empty">
                                <i class="fa fa-box-open"></i>
                                <p>Agrega Productos del Catálogo o Materiales Externos.</p>
                            </div>
                            <div class="entry-items-table-wrapper" id="items-wrapper" hidden>
                                <table class="entry-items-table">
                                    <thead><tr><th>Origen</th><th>Material</th><th>Cantidad</th><th>Observaciones</th><th>Acción</th></tr></thead>
                                    <tbody id="items-body"></tbody>
                                </table>
                            </div>
                        </section>

                        <div id="lineas-inputs"></div>
                    </form>
                </section>

                <aside class="inventario-form-card form-summary">
                    <h2><i class="fa fa-circle-info"></i> Resumen de la Solicitud</h2>
                    <div class="summary-placeholder" id="summary-placeholder">
                        <i class="fa fa-box"></i>
                        <p>Agrega un Material para Consultar el Resumen.</p>
                    </div>
                    <div class="summary-content" id="summary-content" hidden>
                        <div class="summary-item"><span class="label">Total</span><span class="value" id="summary-total">0</span></div>
                        <div class="summary-item"><span class="label">Del Catálogo</span><span class="value" id="summary-catalogo">0</span></div>
                        <div class="summary-item"><span class="label">Fuera del Catálogo</span><span class="value" id="summary-externos">0</span></div>
                        <div class="summary-item"><span class="label">Proyecto</span><span class="value" id="summary-proyecto">Sin Seleccionar</span></div>
                        <div class="summary-item"><span class="label">Fecha Requerida</span><span class="value" id="summary-fecha">Sin Seleccionar</span></div>
                    </div>
                </aside>
            </div>
        </main>
    </div>
</div>

<script>
(() => {
    'use strict';

    const form = document.getElementById('solicitud-form');
    const toggleManual = document.getElementById('toggle_fuera_catalogo');
    const modoCatalogo = document.getElementById('modo_catalogo');
    const modoManual = document.getElementById('modo_manual');
    const productoSelect = document.getElementById('producto_id');
    const cantidadInput = document.getElementById('cantidad');
    const observacionesInput = document.getElementById('observaciones');
    const manualNombre = document.getElementById('manual_nombre');
    const manualMarca = document.getElementById('manual_marca');
    const manualTamano = document.getElementById('manual_tamano');
    const manualUnidad = document.getElementById('manual_unidad');
    const proyectoSelect = document.getElementById('proyecto_id');
    const fechaEntrega = document.getElementById('fecha_entrega');
    const comentarioGeneral = document.getElementById('comentario_general');
    const filtroTexto = document.getElementById('filtro_texto');
    const filtroTipo = document.getElementById('filtro_tipo');
    const filtroCategoria = document.getElementById('filtro_categoria');
    const filtroResultados = document.getElementById('filtro_resultados');
    const itemsBody = document.getElementById('items-body');
    const itemsWrapper = document.getElementById('items-wrapper');
    const itemsEmpty = document.getElementById('items-empty');
    const itemsCount = document.getElementById('items-count');
    const lineasInputs = document.getElementById('lineas-inputs');
    const materialInput = document.getElementById('material');
    const comentarioInput = document.getElementById('comentario');
    const observacionInput = document.getElementById('observacion');
    const summaryPlaceholder = document.getElementById('summary-placeholder');
    const summaryContent = document.getElementById('summary-content');

    const materialesIniciales = <?= json_encode($materialesIniciales, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const materiales = Array.isArray(materialesIniciales) ? materialesIniciales.map(normalizarMaterial).filter(Boolean) : [];

    function normalizarMaterial(item) {
        if (!item || typeof item !== 'object') return null;
        const productoId = String(item.producto_id ?? '').trim();
        const nombreMaterial = String(item.producto_nombre ?? item.nombre ?? '').trim();
        const cantidad = Number.parseFloat(item.cantidad ?? 0);
        if ((!productoId && !nombreMaterial) || !Number.isFinite(cantidad) || cantidad <= 0) return null;
        return {
            tipo: String(item.tipo || (productoId ? 'Consumible' : 'Extra')),
            producto_id: productoId || null,
            producto_nombre: nombreMaterial || `Producto #${productoId}`,
            cantidad,
            observacion: String(item.observacion ?? item.observaciones ?? '').trim(),
            unidad: String(item.unidad ?? '').trim(),
            marca_modelo: String(item.marca_modelo ?? '').trim(),
            tamano: String(item.tamano ?? '').trim()
        };
    }

    function avisar(titulo, mensaje) {
        if (window.Swal) {
            Swal.fire({ icon: 'warning', title: titulo, text: mensaje, confirmButtonColor: '#3085d6' });
        } else {
            window.alert(`${titulo}: ${mensaje}`);
        }
    }

    function crearInputOculto(nombreCampo, valor) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = nombreCampo;
        input.value = valor ?? '';
        lineasInputs.appendChild(input);
    }

    function textoCantidad(item) {
        const cantidad = Number(item.cantidad).toLocaleString('es-MX', { maximumFractionDigits: 2 });
        return item.unidad ? `${cantidad} ${item.unidad}` : cantidad;
    }

    function renderMateriales() {
        itemsBody.replaceChildren();
        lineasInputs.replaceChildren();
        itemsCount.textContent = `${materiales.length} Material${materiales.length === 1 ? '' : 'es'}`;
        itemsEmpty.hidden = materiales.length > 0;
        itemsWrapper.hidden = materiales.length === 0;

        materiales.forEach((item, index) => {
            const row = document.createElement('tr');
            [item.producto_id ? 'Catálogo' : 'Fuera del Catálogo', item.producto_nombre, textoCantidad(item), item.observacion || '-'].forEach((valor) => {
                const cell = document.createElement('td');
                cell.textContent = valor;
                row.appendChild(cell);
            });
            const actionCell = document.createElement('td');
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'btn-inline-remove';
            removeButton.dataset.index = String(index);
            removeButton.title = 'Quitar Material';
            removeButton.setAttribute('aria-label', `Quitar ${item.producto_nombre}`);
            removeButton.innerHTML = '<i class="fa fa-trash"></i>';
            actionCell.appendChild(removeButton);
            row.appendChild(actionCell);
            itemsBody.appendChild(row);

            crearInputOculto('lineas_tipo[]', item.tipo);
            crearInputOculto('lineas_producto_id[]', item.producto_id || '');
            crearInputOculto('lineas_producto_nombre[]', item.producto_nombre);
            crearInputOculto('lineas_cantidad[]', item.cantidad);
            crearInputOculto('lineas_observaciones[]', item.observacion);
            crearInputOculto('lineas_unidad[]', item.unidad);
            crearInputOculto('lineas_marca_modelo[]', item.marca_modelo);
            crearInputOculto('lineas_tamano[]', item.tamano);
        });

        materialInput.value = JSON.stringify(materiales);
        actualizarResumen();
    }

    function actualizarResumen() {
        const total = materiales.length;
        const catalogo = materiales.filter((item) => item.producto_id).length;
        summaryPlaceholder.hidden = total > 0;
        summaryContent.hidden = total === 0;
        document.getElementById('summary-total').textContent = String(total);
        document.getElementById('summary-catalogo').textContent = String(catalogo);
        document.getElementById('summary-externos').textContent = String(total - catalogo);
        document.getElementById('summary-proyecto').textContent = proyectoSelect.selectedOptions[0]?.value ? proyectoSelect.selectedOptions[0].textContent.trim() : 'Sin Seleccionar';
        document.getElementById('summary-fecha').textContent = fechaEntrega.value || 'Sin Seleccionar';
    }

    function limpiarBorrador() {
        productoSelect.value = '';
        manualNombre.value = '';
        manualMarca.value = '';
        manualTamano.value = '';
        manualUnidad.value = 'Pieza';
        cantidadInput.value = '';
        observacionesInput.value = '';
    }

    function agregarMaterial() {
        const cantidad = Number.parseFloat(cantidadInput.value);
        if (!Number.isFinite(cantidad) || cantidad <= 0) {
            avisar('Cantidad Inválida', 'Captura una Cantidad Mayor a Cero.');
            cantidadInput.focus();
            return;
        }

        let material;
        if (toggleManual.checked) {
            const nombreMaterial = manualNombre.value.trim();
            if (!nombreMaterial) {
                avisar('Material Incompleto', 'Escribe el Nombre del Material que no Está en el Catálogo.');
                manualNombre.focus();
                return;
            }
            material = {
                tipo: 'Extra', producto_id: null,
                producto_nombre: nombreMaterial,
                cantidad, observacion: observacionesInput.value.trim(), unidad: manualUnidad.value,
                marca_modelo: manualMarca.value.trim(), tamano: manualTamano.value.trim()
            };
        } else {
            const option = productoSelect.selectedOptions[0];
            if (!option?.value) {
                avisar('Producto Incompleto', 'Selecciona un Producto del Catálogo.');
                productoSelect.focus();
                return;
            }
            const stockDisponible = Number.parseFloat(option.dataset.stock || '0');
            if (Number.isFinite(stockDisponible) && cantidad > stockDisponible
                && !window.confirm(`Solicitas ${cantidad} y hay ${stockDisponible} Disponibles. ¿Deseas Continuar?`)) {
                return;
            }
            material = {
                tipo: option.dataset.tipo || 'Consumible', producto_id: option.value,
                producto_nombre: option.dataset.nombre || option.textContent.trim(), cantidad,
                observacion: observacionesInput.value.trim(), unidad: option.dataset.unidad || '',
                marca_modelo: '', tamano: ''
            };
        }

        materiales.push(material);
        limpiarBorrador();
        renderMateriales();
    }

    function aplicarFiltros() {
        const texto = filtroTexto.value.trim().toLocaleLowerCase('es');
        const tipo = filtroTipo.value;
        const categoria = filtroCategoria.value;
        let visibles = 0;
        Array.from(productoSelect.options).forEach((option, index) => {
            if (index === 0) return;
            const busqueda = (option.dataset.busqueda || option.textContent).toLocaleLowerCase('es');
            const coincide = (!texto || busqueda.includes(texto))
                && (!tipo || option.dataset.tipo === tipo)
                && (!categoria || option.dataset.categoria === categoria);
            option.hidden = !coincide;
            option.disabled = !coincide;
            if (coincide) visibles += 1;
        });
        if (productoSelect.selectedOptions[0]?.disabled) productoSelect.value = '';
        filtroResultados.textContent = String(visibles);
    }

    function alternarModo() {
        modoCatalogo.hidden = toggleManual.checked;
        modoManual.hidden = !toggleManual.checked;
        productoSelect.value = '';
        manualNombre.value = '';
    }

    document.getElementById('agregar-material').addEventListener('click', agregarMaterial);
    document.getElementById('limpiar-captura').addEventListener('click', () => {
        materiales.length = 0;
        limpiarBorrador();
        renderMateriales();
    });
    itemsBody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-index]');
        if (!button) return;
        materiales.splice(Number(button.dataset.index), 1);
        renderMateriales();
    });
    toggleManual.addEventListener('change', alternarModo);
    filtroTexto.addEventListener('input', aplicarFiltros);
    filtroTipo.addEventListener('change', aplicarFiltros);
    filtroCategoria.addEventListener('change', aplicarFiltros);
    proyectoSelect.addEventListener('change', actualizarResumen);
    fechaEntrega.addEventListener('change', actualizarResumen);

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;
        if (materiales.length === 0) {
            avisar('Solicitud Vacía', 'Agrega al Menos un Producto o Material Externo.');
            return;
        }
        comentarioInput.value = proyectoSelect.selectedOptions[0]?.textContent.trim() || '';
        observacionInput.value = comentarioGeneral.value.trim();
        materialInput.value = JSON.stringify(materiales);

        const enviar = () => HTMLFormElement.prototype.submit.call(form);
        if (!window.Swal) {
            if (window.confirm('¿Deseas Enviar esta Solicitud de Materiales?')) enviar();
            return;
        }
        Swal.fire({
            title: '¿Enviar Solicitud?', text: `Se Enviarán ${materiales.length} Materiales en su Solicitud.`,
            icon: 'question', showCancelButton: true, confirmButtonColor: '#10b981', cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, Enviar', cancelButtonText: 'Cancelar', reverseButtons: true
        }).then((result) => { if (result.isConfirmed) enviar(); });
    });

    aplicarFiltros();
    alternarModo();
    renderMateriales();

    const alertaSesion = <?= json_encode($alertaSesion, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (alertaSesion && window.Swal) {
        Swal.fire({
            icon: alertaSesion.tipo || 'info', title: alertaSesion.titulo || 'Aviso',
            text: alertaSesion.mensaje || '', confirmButtonColor: '#3085d6'
        });
    }
})();
</script>
<?php include __DIR__ . '/../layouts/scripts.php'; ?>
</body>
</html>
