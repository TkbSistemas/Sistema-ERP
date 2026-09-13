<?php
$role = $_SESSION['role'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';


$totalRegistros = $totalRegistros ?? count($proveedores);
$page = $page ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$perPage = $perPage ?? 10;
$perPageOptions = $perPageOptions ?? [10];
$offset = $offset ?? 0;
$desde = $totalRegistros > 0 ? $offset + 1 : 0;
$hasta = $totalRegistros > 0 ? min($offset + $perPage, $totalRegistros) : 0;

$buildQuery = function(array $overrides = []) {
    $params = array_merge($_GET, $overrides);
    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        }
    }
    return $params ? ('?' . http_build_query($params)) : '?';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CATÁLOGO DE PROVEEDORES | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/config.css">
    <link rel="stylesheet" href="assets/css/productos.css">
    <link rel="stylesheet" href="assets/css/inventario.css">
    <link rel="stylesheet" href="assets/css/proveedores.css?v=20260912-3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="./assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body class="module-inventory-warehouse">
<?php $seccion_activa = 'proveedores'; ?>
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Toggle Menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    <div class="content-area">
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>
        <main class="dashboard-main productos-main">
            <?php if (isset($_SESSION['alerta'])): ?>
                <script>
                    Swal.fire({
                        icon: '<?php echo $_SESSION['alerta']['tipo']; ?>',
                        title: '<?php echo $_SESSION['alerta']['titulo']; ?>',
                        text: '<?php echo $_SESSION['alerta']['mensaje']; ?>',
                        confirmButtonColor: '#3085d6'
                    });
                </script>
                <?php 
                    unset($_SESSION['alerta']); 
                ?>
            <?php endif; ?>

            <div class="productos-header">
                <div>
                    <h1 class="page-title-icon"><i class="fa-solid fa-building-user" aria-hidden="true"></i> CATÁLOGO DE PROVEEDORES</h1>
                    <p class="productos-header-desc">La Información de los Principales Proveedores.</p>
                </div>
                <div class="productos-header-actions">
                    <?php if ($role === 'Administrador'): ?>
                        <button type="button" class="btn-main" id="btnAgregarProveedor"><i class="fa fa-plus"></i> Agregar Proveedor</button>
                    <?php endif; ?>
                </div>
            </div>

            <section class="inventario-filters-card">
                <form method="get" class="productos-filters-form">
                    <div class="filter-row">
                        <div class="filter-field">
                            <label for="buscar">Búsqueda Global</label>
                            <div class="filter-input-icon">
                                <i class="fa fa-search"></i>
                                <input type="text" id="buscar" name="buscar" placeholder="Nombre, RFC, correo, teléfono..." value="<?= htmlspecialchars($filtros['buscar']) ?>" style="width: 100% !important;">
                            </div>
                        </div>

                        <div class="filter-field">
                            <label for="categoria">Categoría:</label>
                            <select id="categoria" name="categoria">
                                <option value="">Todas</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= htmlspecialchars($categoria) ?>" <?= $filtros['categoria'] === $categoria ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categoria) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-field partner-filter-field">
                            <span class="partner-filter-label">Tipo de Proveedor</span>
                            <label class="partner-filter-control" for="partner_activo">
                                <input type="checkbox" id="partner_activo" name="partner_activo" value="1" <?= $filtros['partner_activo'] ? 'checked' : '' ?>>
                                <span>Partners Activos</span>
                            </label>
                        </div>

                        <div class="filter-field">
                            <label for="per_page">Registros por Página</label>
                            <select id="per_page" name="per_page">
                                <?php foreach ($perPageOptions as $opcion): ?>
                                    <option value="<?= $opcion ?>" <?= $perPage === $opcion ? 'selected' : '' ?>><?= $opcion ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="inv-filter-row">
                        <div class="inv-filter-actions">
                            <button type="submit" class="btn-main"><i class="fa fa-filter"></i> Aplicar Filtros</button>
                            <?php if ($hayFiltros): ?>
                                <a class="btn-ghost" href="proveedores"><i class="fa fa-eraser"></i> Limpiar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </section>

            <section class="productos-table-card">
                <div class="productos-table-header">
                    <h2><i class="fa-solid fa-cubes"></i> Catálogo (<?= number_format($total) ?>)</h2>
                    <span class="productos-table-sub">Resultados Según Filtros Aplicados</span>
                </div>
                <div class="productos-table-wrapper">
                    <?php if (empty($proveedores)): ?>
                        <div class="productos-empty">
                            <i class="fa fa-inbox"></i>
                            <p>No se Encontraron Proveedores para Listar.</p>
                        </div>
                    <?php else: ?>
                        <table class="productos-table">
                            <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th class="col-actions col-provider-info">Información</th>
                                <th>Agentes</th>
                                <?php if ($role === 'Administrador'): ?>
                                    <th class="col-actions col-provider-actions">Acciones</th>
                                <?php endif; ?>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($proveedores as $proveedor): ?>
                                <?php
                                    $urlTienda = trim((string) ($proveedor['url_tienda'] ?? ''));
                                    $esUrlTiendaValida = filter_var($urlTienda, FILTER_VALIDATE_URL)
                                        && in_array(strtolower((string) parse_url($urlTienda, PHP_URL_SCHEME)), ['http', 'https'], true);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($proveedor['nombre']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($proveedor['categoria'] ?? 'Sin Categoría') ?></td>
                                    
                                    <td class="col-actions col-provider-info">
                                        <div class="acciones-celda acciones-proveedor" aria-label="Información de <?= htmlspecialchars($proveedor['nombre']) ?>">
                                            <?php if ($esUrlTiendaValida): ?>
                                                <a class="btn-table proveedor-info--tienda" title="Abrir Sitio Web" href="<?= htmlspecialchars($urlTienda) ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-cart-shopping"></i></a>
                                            <?php else: ?>
                                                <button type="button" class="btn-table proveedor-info--tienda" title="Sitio Web no Registrado" disabled aria-disabled="true"><i class="fa fa-cart-shopping"></i></button>
                                            <?php endif; ?>
                                            <button type="button" class="btn-table proveedor-info proveedor-info--telefono" title="Ver Teléfono" data-tipo="telefono" data-proveedor="<?= htmlspecialchars($proveedor['nombre']) ?>" data-valor="<?= htmlspecialchars((string) ($proveedor['telefono'] ?? '')) ?>"><i class="fa fa-phone"></i></button>
                                            <button type="button" class="btn-table proveedor-info proveedor-info--correo" title="Ver Correo" data-tipo="correo" data-proveedor="<?= htmlspecialchars($proveedor['nombre']) ?>" data-valor="<?= htmlspecialchars((string) ($proveedor['correo'] ?? '')) ?>"><i class="fa fa-envelope"></i></button>
                                            <button type="button" class="btn-table proveedor-info proveedor-info--ubicacion" title="Ver Ubicación" data-tipo="ubicacion" data-proveedor="<?= htmlspecialchars($proveedor['nombre']) ?>" data-valor="<?= htmlspecialchars((string) ($proveedor['ubicacion_fisica'] ?? '')) ?>"><i class="fa fa-map-marker-alt"></i></button>
                                            <button type="button" class="btn-table proveedor-info proveedor-info--fiscal" title="Ver Datos Fiscales" data-tipo="fiscal" data-proveedor="<?= htmlspecialchars($proveedor['nombre']) ?>" data-rfc="<?= htmlspecialchars((string) ($proveedor['rfc'] ?? '')) ?>" data-razon-social="<?= htmlspecialchars((string) ($proveedor['razon_social'] ?? '')) ?>"><i class="fa fa-info-circle"></i></button>
                                        </div>
                                    </td>

                                    <td>
                                        <?php
                                            $agentes = $proveedor['agentes'] ?? [];
                                            
                                            if (!empty($agentes)) {
                                                echo '<ul class="proveedor-agentes-list">';
                                                foreach ($agentes as $agente) {
                                                    ?>
                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="agente-info"
                                                            data-nombre="<?= htmlspecialchars((string) ($agente['nombre'] ?? 'Agente')) ?>"
                                                            data-correo="<?= htmlspecialchars((string) ($agente['correo'] ?? '')) ?>"
                                                            data-telefono="<?= htmlspecialchars((string) ($agente['telefono'] ?? '')) ?>"
                                                            data-agente-id="<?= (int) ($agente['id'] ?? 0) ?>"
                                                            data-proveedor-id="<?= (int) $proveedor['id'] ?>"
                                                        ><i class="fa-solid fa-user" aria-hidden="true"></i><span><?= htmlspecialchars((string) ($agente['nombre'] ?? 'Agente')) ?></span></button>
                                                    </li>
                                                    <?php
                                                }
                                                echo '</ul>';
                                            } else {
                                                echo '<span class="sin-agentes">Sin Agentes Registrados</span>';
                                            }
                                        ?>
                                    </td>

                                    <?php if ($role === 'Administrador'): ?>
                                        <td class="col-actions col-provider-actions">
                                            <div class="acciones-celda acciones-proveedor" aria-label="Acciones para <?= htmlspecialchars($proveedor['nombre']) ?>">
                                                <button type="button" class="btn-table btn-editar-proveedor" title="Editar Proveedor"
                                                    data-id="<?= (int) $proveedor['id'] ?>"
                                                    data-nombre="<?= htmlspecialchars((string) $proveedor['nombre']) ?>"
                                                    data-categoria="<?= htmlspecialchars((string) ($proveedor['categoria'] ?? '')) ?>"
                                                    data-razon-social="<?= htmlspecialchars((string) ($proveedor['razon_social'] ?? '')) ?>"
                                                    data-rfc="<?= htmlspecialchars((string) ($proveedor['rfc'] ?? '')) ?>"
                                                    data-telefono="<?= htmlspecialchars((string) ($proveedor['telefono'] ?? '')) ?>"
                                                    data-correo="<?= htmlspecialchars((string) ($proveedor['correo'] ?? '')) ?>"
                                                    data-ubicacion="<?= htmlspecialchars((string) ($proveedor['ubicacion_fisica'] ?? '')) ?>"
                                                    data-url="<?= htmlspecialchars((string) ($proveedor['url_tienda'] ?? '')) ?>"
                                                    data-partner="<?= (int) ($proveedor['partner_activo'] ?? 0) ?>"><i class="fa fa-edit"></i></button>
                                                <button type="button" class="btn-table btn-agregar-agente" title="Añadir Agente" data-id="<?= (int) $proveedor['id'] ?>" data-nombre="<?= htmlspecialchars((string) $proveedor['nombre']) ?>"><i class="fa-solid fa-user-plus"></i></button>
                                                <button type="button" class="btn-table btn-danger btn-eliminar-proveedor" title="Eliminar Proveedor" data-id="<?= (int) $proveedor['id'] ?>" data-nombre="<?= htmlspecialchars((string) $proveedor['nombre']) ?>"><i class="fa fa-trash"></i></button>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>

            <div class="productos-pagination">
                <div class="productos-pagination-info">
                    <?= $totalRegistros > 0
                        ? "Mostrando $desde - $hasta de " . number_format($totalRegistros) . " registros"
                        : "Sin registros disponibles" ?>
                </div>
                <div class="productos-pagination-controls">
                    <?php if ($page > 1): ?>
                        <a class="btn-ghost" href="<?= $buildQuery(['page' => $page - 1]) ?>"><i class="fa fa-chevron-left"></i> Anterior</a>
                    <?php endif; ?>
                    <span class="productos-pagination-page">Página <?= number_format($page) ?> de <?= number_format($totalPaginas) ?></span>
                    <?php if ($page < $totalPaginas): ?>
                        <a class="btn-ghost" href="<?= $buildQuery(['page' => $page + 1]) ?>">Siguiente <i class="fa fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById('toggleSidebar');
   const sidebar = document.querySelector('.main_sidebar'); 
    const mainContent = document.querySelector('.content-area');

    if (toggleBtn && sidebar && mainContent) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('collapsed');
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                if (sidebar.classList.contains('collapsed')) {
                    icon.className = 'fa-solid fa-bars'; // Icono normal
                } else {
                    icon.className = 'fa-solid fa-xmark'; // Icono de cerrar
                }
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const esAdministrador = <?= $role === 'Administrador' ? 'true' : 'false' ?>;
    const csrf = <?= json_encode(Session::csrfToken(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const categoriasFormulario = <?= json_encode($categoriasFormulario, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const accionesInfo = {
        telefono: { etiqueta: 'Teléfono', icono: 'fa-phone' },
        correo: { etiqueta: 'Correo Electrónico', icono: 'fa-envelope' },
        ubicacion: { etiqueta: 'Ubicación Física', icono: 'fa-map-marker-alt' },
        fiscal: { etiqueta: 'Datos Fiscales', icono: 'fa-info-circle' }
    };

    function enviarAccion(accion, datos) {
        const formulario = document.createElement('form');
        formulario.method = 'POST';
        formulario.action = 'proveedores';

        Object.entries({ accion, csrf, ...datos }).forEach(([nombre, valor]) => {
            const campo = document.createElement('input');
            campo.type = 'hidden';
            campo.name = nombre;
            campo.value = valor == null ? '' : String(valor);
            formulario.appendChild(campo);
        });

        document.body.appendChild(formulario);
        formulario.submit();
    }

    function validarCampos(contenedor) {
        const invalido = Array.from(contenedor.querySelectorAll('input, select')).find((campo) => !campo.checkValidity());
        if (invalido) {
            Swal.showValidationMessage(invalido.validationMessage);
            invalido.focus();
            return false;
        }
        return true;
    }

    function abrirFormularioProveedor(datos = {}) {
        const esEdicion = Boolean(datos.id);
        const formulario = document.createElement('div');
        formulario.className = 'swal-form-grid proveedor-form-alert';
        formulario.innerHTML = `
            <label class="swal-form-field swal-form-wide">Nombre del Proveedor *<input type="text" data-campo="nombre" maxlength="100" required></label>
            <label class="swal-form-field">Categoría<select data-campo="categoria"><option value="">Sin Categoría</option></select></label>
            <label class="swal-form-field">Razón Social<input type="text" data-campo="razon_social" maxlength="100"></label>
            <label class="swal-form-field">RFC<input type="text" data-campo="rfc" maxlength="50"></label>
            <label class="swal-form-field">Teléfono<input type="text" data-campo="telefono" maxlength="100"></label>
            <label class="swal-form-field">Correo Electrónico<input type="email" data-campo="correo" maxlength="100"></label>
            <label class="swal-form-field swal-form-wide">Ubicación Física<input type="text" data-campo="ubicacion_fisica" maxlength="255"></label>
            <label class="swal-form-field swal-form-wide">Sitio Web<input type="url" data-campo="url_tienda" maxlength="255" placeholder="https://ejemplo.com"></label>
            <label class="swal-form-check swal-form-wide"><input type="checkbox" data-campo="partner_activo" value="1"><span>Partner Activo</span></label>
        `;

        const categoria = formulario.querySelector('[data-campo="categoria"]');
        categoriasFormulario.forEach((nombreCategoria) => {
            const opcion = document.createElement('option');
            opcion.value = nombreCategoria;
            opcion.textContent = nombreCategoria;
            categoria.appendChild(opcion);
        });

        ['nombre', 'categoria', 'razon_social', 'rfc', 'telefono', 'correo', 'ubicacion_fisica', 'url_tienda'].forEach((campo) => {
            formulario.querySelector(`[data-campo="${campo}"]`).value = datos[campo] || '';
        });
        formulario.querySelector('[data-campo="partner_activo"]').checked = String(datos.partner_activo || '0') === '1';

        Swal.fire({
            title: esEdicion ? 'Editar Proveedor' : 'Agregar Proveedor',
            html: formulario,
            width: 760,
            showCancelButton: true,
            confirmButtonColor: '#315bb6',
            cancelButtonColor: '#7286a6',
            confirmButtonText: esEdicion ? 'Guardar Cambios' : 'Registrar Proveedor',
            cancelButtonText: 'Cancelar',
            focusConfirm: false,
            preConfirm: () => {
                if (!validarCampos(formulario)) return false;
                const resultado = {};
                formulario.querySelectorAll('[data-campo]').forEach((campo) => {
                    resultado[campo.dataset.campo] = campo.type === 'checkbox' ? (campo.checked ? '1' : '0') : campo.value.trim();
                });
                return resultado;
            }
        }).then((resultado) => {
            if (resultado.isConfirmed) {
                enviarAccion(esEdicion ? 'editar_proveedor' : 'crear_proveedor', {
                    proveedor_id: datos.id || '',
                    ...resultado.value
                });
            }
        });
    }

    function abrirFormularioAgente(datos) {
        const esEdicion = Boolean(datos.agente_id);
        const formulario = document.createElement('div');
        formulario.className = 'swal-form-grid agente-form-alert';
        formulario.innerHTML = `
            <label class="swal-form-field swal-form-wide">Nombre del Agente *<input type="text" data-campo="nombre" maxlength="100" required></label>
            <label class="swal-form-field">Correo Electrónico<input type="email" data-campo="correo" maxlength="100"></label>
            <label class="swal-form-field">Teléfono<input type="text" data-campo="telefono" maxlength="100"></label>
        `;
        ['nombre', 'correo', 'telefono'].forEach((campo) => {
            formulario.querySelector(`[data-campo="${campo}"]`).value = datos[campo] || '';
        });

        Swal.fire({
            title: esEdicion ? 'Editar Agente' : `Añadir Agente a ${datos.proveedor_nombre}`,
            html: formulario,
            width: 620,
            showCancelButton: true,
            showDenyButton: esEdicion,
            confirmButtonColor: '#315bb6',
            cancelButtonColor: '#7286a6',
            denyButtonColor: '#c93636',
            confirmButtonText: esEdicion ? 'Guardar Cambios' : 'Añadir Agente',
            denyButtonText: 'Eliminar Agente',
            cancelButtonText: 'Cancelar',
            focusConfirm: false,
            preConfirm: () => {
                if (!validarCampos(formulario)) return false;
                const resultado = {};
                formulario.querySelectorAll('[data-campo]').forEach((campo) => {
                    resultado[campo.dataset.campo] = campo.value.trim();
                });
                return resultado;
            }
        }).then((resultado) => {
            if (resultado.isConfirmed) {
                enviarAccion(esEdicion ? 'editar_agente' : 'agregar_agente', {
                    proveedor_id: datos.proveedor_id,
                    agente_id: datos.agente_id || '',
                    ...resultado.value
                });
                return;
            }

            if (resultado.isDenied && esEdicion) {
                Swal.fire({
                    title: '¿Eliminar Agente?',
                    text: `“${datos.nombre}” Será Retirado del Proveedor.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#c93636',
                    cancelButtonColor: '#7286a6',
                    confirmButtonText: 'Sí, Eliminar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((confirmacion) => {
                    if (confirmacion.isConfirmed) {
                        enviarAccion('eliminar_agente', {
                            proveedor_id: datos.proveedor_id,
                            agente_id: datos.agente_id
                        });
                    }
                });
            }
        });
    }

    document.querySelectorAll('.proveedor-info').forEach((boton) => {
        boton.addEventListener('click', function () {
            const tipo = this.dataset.tipo;
            const proveedor = this.dataset.proveedor || 'Proveedor';
            const accion = accionesInfo[tipo] || { etiqueta: 'Información', icono: 'fa-circle-info' };
            const contenido = document.createElement('div');
            contenido.className = 'proveedor-alert-content';
            const icono = document.createElement('div');
            icono.className = 'proveedor-alert-icon';
            icono.innerHTML = `<i class="fa-solid ${accion.icono}" aria-hidden="true"></i>`;
            contenido.appendChild(icono);

            if (tipo === 'fiscal') {
                const detalle = document.createElement('div');
                detalle.className = 'proveedor-alert-detail';
                const razon = document.createElement('p');
                razon.textContent = `Razón Social: ${this.dataset.razonSocial || 'No Registrada'}`;
                const registro = document.createElement('p');
                registro.textContent = `RFC: ${this.dataset.rfc || 'No Registrado'}`;
                detalle.append(razon, registro);
                contenido.appendChild(detalle);
            } else {
                const detalle = document.createElement('p');
                detalle.className = 'proveedor-alert-value';
                detalle.textContent = this.dataset.valor || 'Información no Registrada';
                contenido.appendChild(detalle);
            }

            Swal.fire({
                title: `${accion.etiqueta} de ${proveedor}`,
                html: contenido,
                confirmButtonColor: '#315bb6'
            });
        });
    });

    document.querySelectorAll('.agente-info').forEach((boton) => {
        boton.addEventListener('click', function () {
            const datosAgente = {
                agente_id: this.dataset.agenteId,
                proveedor_id: this.dataset.proveedorId,
                nombre: this.dataset.nombre || 'Agente',
                correo: this.dataset.correo || '',
                telefono: this.dataset.telefono || ''
            };
            const contenido = document.createElement('div');
            contenido.className = 'proveedor-alert-content';
            const icono = document.createElement('div');
            icono.className = 'proveedor-alert-icon';
            icono.innerHTML = '<i class="fa-solid fa-user-tie" aria-hidden="true"></i>';
            const detalle = document.createElement('div');
            detalle.className = 'proveedor-alert-detail';
            const datoCorreo = document.createElement('p');
            datoCorreo.textContent = `Correo Electrónico: ${datosAgente.correo || 'No Registrado'}`;
            const datoTelefono = document.createElement('p');
            datoTelefono.textContent = `Teléfono: ${datosAgente.telefono || 'No Registrado'}`;
            detalle.append(datoCorreo, datoTelefono);
            contenido.append(icono, detalle);

            Swal.fire({
                title: datosAgente.nombre,
                html: contenido,
                showDenyButton: esAdministrador,
                confirmButtonText: 'Cerrar',
                denyButtonText: 'Editar Agente',
                confirmButtonColor: '#315bb6',
                denyButtonColor: '#315bb6'
            }).then((resultado) => {
                if (resultado.isDenied) abrirFormularioAgente(datosAgente);
            });
        });
    });

    document.getElementById('btnAgregarProveedor')?.addEventListener('click', () => abrirFormularioProveedor());

    document.querySelectorAll('.btn-editar-proveedor').forEach((boton) => {
        boton.addEventListener('click', function () {
            abrirFormularioProveedor({
                id: this.dataset.id,
                nombre: this.dataset.nombre,
                categoria: this.dataset.categoria,
                razon_social: this.dataset.razonSocial,
                rfc: this.dataset.rfc,
                telefono: this.dataset.telefono,
                correo: this.dataset.correo,
                ubicacion_fisica: this.dataset.ubicacion,
                url_tienda: this.dataset.url,
                partner_activo: this.dataset.partner
            });
        });
    });

    document.querySelectorAll('.btn-agregar-agente').forEach((boton) => {
        boton.addEventListener('click', function () {
            abrirFormularioAgente({ proveedor_id: this.dataset.id, proveedor_nombre: this.dataset.nombre });
        });
    });

    document.querySelectorAll('.btn-eliminar-proveedor').forEach((boton) => {
        boton.addEventListener('click', function () {
            Swal.fire({
                title: '¿Eliminar Proveedor?',
                text: `“${this.dataset.nombre}” Será Retirado del Catálogo.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#7286a6',
                confirmButtonText: 'Sí, Eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((resultado) => {
                if (resultado.isConfirmed) enviarAccion('eliminar_proveedor', { proveedor_id: this.dataset.id });
            });
        });
    });
});
</script>
</body>
</html>



