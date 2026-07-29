<?php
require_once __DIR__ . '/../../helpers/Session.php';
Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

$role = $_SESSION['role'] ?? 'Administrador';
$nombre = $_SESSION['nombre'] ?? '';
$selectedProducto = $_POST['producto_id'] ?? '';
$selectedAlmacen = $_POST['almacen_id'] ?? '';
$cantidadIngresada = $_POST['cantidad'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';
$entradaItems = $entradaItems ?? [];
$error = $error ?? '';
$categoriasFiltro = [];
foreach ($productos as $producto) {
    if (!empty($producto['categoria'])) {
        $categoriasFiltro[$producto['categoria']] = true;
    }
}
ksort($categoriasFiltro);
$breadcrumbs = [
    ['label' => 'Registrar entrada'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ENTRADA DE INVENTARIO | TAKAB</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/productos.css">
    <link rel="stylesheet" href="assets/css/inventario_form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="./assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body>
<?php $seccion_activa = 'registrar_entrada'; ?>

                
<div class="main-layout">
    <button type="button" id="toggleSidebar" class="btn-toggle-sidebar" aria-label="Toggle Menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    <div class="content-area">
        <?php 
            require_once __DIR__ . '/../../helpers/Navigation.php';

            $role = Navigation::normalizeRole($role ?? ($_SESSION['role'] ?? ''));
            ?>
            <header class="top-header">
                <div class="top-header-left">
                </div>
                <div class="top-header-user">
                    <span><?= htmlspecialchars($nombre ?: 'Usuario') ?> (<?= htmlspecialchars($role) ?>)</span>
                    <i class="fa-solid fa-user-circle"></i>
                    <a href="dashboard_almacen" class="logout-btn" title="Ir al Dashboard"><i class="fa-solid fa-home"></i></a>
                    <a href="logout" class="logout-btn" title="Cerrar Sesión"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>    
                </div>
            </header>

        <?php if (isset($_SESSION['alerta'])): ?>
        <script>
            Swal.fire({
                icon: '<?php echo $_SESSION['alerta']['tipo']; ?>',
                title: '<?php echo $_SESSION['alerta']['titulo']; ?>',
                text: <?php echo json_encode($_SESSION['alerta']['mensaje']); ?>,
                confirmButtonColor: '#3085d6'
            });
        </script>
        <?php unset($_SESSION['alerta']); ?>
    <?php endif; ?>

        <main class="dashboard-main inventario-form-main">
            <div class="inventario-form-header">
                <div>
                    <h1><i class="fa fa-arrow-down"></i> REGISTRO DE ENTRADA DE INVENTARIO</h1>
                    <p class="form-desc">Captura Recepción de Productos que Cuenten con Orden de Compra Asociada.</p>
                </div>
                <a class="btn-main" href="entrada_rapida"><i class="fa-solid fa-truck-fast"></i> Entrada Rápida</a>
            </div>

            <?php if (!empty($msg)): ?>
                <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="inventario-form-grid">
                <section class="inventario-form-card">
                    <h2><i class="fa fa-clipboard"></i> Detalles de la Entrada</h2>
                    <form action="" method="post" enctype="application/x-www-form-urlencoded" autocomplete="off" class="inventario-entry-form" id="inventario-entry-form">
                        <input type="hidden" name="csrf" value="<?= Session::csrfToken() ?>">

                        <div class="form-field-line" style="margin-bottom:14px;">
                            <label for="folio_entrada">Folio de la Entrada:</label>
                            <input type="text" id="folio_entrada" name="folio_entrada" placeholder="Folio Para Cargar Lista de Productos">
                            <button type="submit" class="btn-main"> <i class="fa-solid fa-search"></i> Buscar</button>
                        </div>
                    </form>

                        <div style="margin-top:8px; font-size:0.9rem; color:#5c6a96;">
                            Productos Visibles: <span id="filtro_resultados">0</span>
                        </div>

                        <?php
                        $folioIngresado = !empty($_GET['folio']) || !empty($_POST['folio']);
                        $tieneProductos = !empty($productosRecibidos) && is_array($productosRecibidos);

                        if (!$folioIngresado || !$tieneProductos): 
                        ?>
                            <div class="placeholder-vacio">
                                <strong>Sin Datos de Recepción:</strong> 
                                <?php echo !$folioIngresado ? 'Por favor, Ingrese un Folio Válido.' : 'El Folio no Contiene Productos Asignados para Recepción.'; ?>
                            </div>
                        <?php else: ?>
                            <table class="tabla-recepcion" id="tablaRecepcion">
                            <thead>
                                <tr>
                                    <th class="col-no">No.</th>
                                    <th class="col-nombre">Nombre / Descripción</th>
                                    <th class="col-barras">Código de Barras</th>
                                    <th class="col-serie">Número de Serie</th>
                                    <th class="col-marca">Marca</th>
                                    <th class="col-cantidad">Cantidad Esperada</th>
                                    <th class="col-cant-real">Cantidad Real</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $index = 1;
                            foreach ($productosRecibidos as $producto): ?>
                                <tr id="fila-<?php echo $producto['id']; ?>" data-barcode="<?php echo htmlspecialchars($producto['codigo_barras'] ?? ''); ?>">
                                    <td class="col-no"><?php echo $index; ?></td>
                                    <td class="col-nombre"><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td class="col-barras"><?php echo htmlspecialchars($producto['codigo_barras'] ?? 'N/A'); ?></td>
                                    <td class="col-serie"><?php echo htmlspecialchars($producto['serie'] ?? 'N/A'); ?></td>
                                    <td class="col-marca"><?php echo htmlspecialchars($producto['marca'] ?? 'N/A'); ?></td>
                                    <td class="col-cantidad"><?php echo htmlspecialchars($producto['cantidad']); ?></td>
                                    <td class="col-cant-real">
                                        <input type="number" 
                                            id="input-real-<?php echo $producto['id']; ?>"
                                            name="productos_registro[<?php echo $producto['id']; ?>][cantidad_real]" 
                                            class="input-cantidad-real" 
                                            min="0" 
                                            value="<?php echo htmlspecialchars($producto['cantidad_real'] ?? ''); ?>" />
                                    </td>
                                </tr>
                            <?php 
                            $index++;
                            endforeach; ?>
                        </tbody>
                    </table>
                    <div class="entry-batch-actions">
                        <button type="button" class="btn-ghost" id="limpiar-captura"><i class="fa fa-eraser"></i> Cancelar Captura</button>
                        <button type="submit" class="btn-main" id="registrar-entrada"><i class="fa fa-save"></i> Registrar Entrada</button>
                    </div>
                <?php endif; ?>
                        <section class="entry-items-panel">
                            <div class="inventario-empty entry-items-empty" id="entrada-items-empty">
                                <p><i class="fa fa-box-open"></i>   Una Vez Confirmada la Entrada Aumentará el Stock.</p>
                            </div>
                        </section>

                        <div id="entrada-items-inputs"></div>
                </section>

                <section class="inventario-form-card form-summary">
                    <h2><i class="fa fa-circle-info"></i> Resumen de la Entrada</h2>
                    <div class="summary-placeholder" id="summary-placeholder">
                        <i class="fa fa-box"></i>
                        <p>Ingresa un Folio para Comprobar el Resúmen de la Entrada.</p>
                    </div>
                    <div class="summary-content" id="summary-content" hidden>
                        <div class="summary-item">
                            <span class="label">Número de Folio:</span>
                            <span class="value" id="summary-nombre">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Total Productos:</span>
                            <span class="value" id="summary-stock">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Almacén Destino:</span>
                            <span class="value" id="summary-almacen">-</span>
                        </div>
                    </div>
                </section>
            </div>

            <section class="inventario-form-card inventario-recents">
                <div class="recents-header">
                    <h2><i class="fa fa-clock"></i> Últimas Entradas Registradas</h2>
                    <span class="recents-sub">Ayuda a Verificar Duplicados o Confirmar Capturas Recientes</span>
                </div>
                <?php if (empty($movimientosRecientes)): ?>
                    <div class="inventario-empty">
                        <p><i class="fa fa-inbox"></i> Aún no se Registran Entradas de Inventario.</p>
                    </div>
                <?php else: ?>
                    <div class="recents-table-wrapper">
                        <table class="recents-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Almacén</th>
                                    <th>Registro</th>
                                    <th>Notas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimientosRecientes as $mov): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($mov['fecha'])) ?></td>
                                        <td><?= htmlspecialchars($mov['producto'] ?? '-') ?> <span class="mono">(<?= htmlspecialchars($mov['codigo_producto'] ?? '-') ?>)</span></td>
                                        <td><?= rtrim(rtrim(number_format((float) ($mov['cantidad'] ?? 0), 2), '0'), '.') ?></td>
                                        <td><?= htmlspecialchars($mov['almacen_destino'] ?? $mov['almacen_origen'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($mov['usuario'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($mov['observaciones'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>


<script>
    let scannerActivo = true;
    let bufferCodigo = "";
    let ultimoKeyTime = Date.now();

    document.addEventListener('keydown', function(e) {
        if (!scannerActivo) return;

            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }

            const limiteTiempoMilisegundos = 50; 
            const tiempoActual = Date.now();
            const diferenciaTiempo = tiempoActual - ultimoKeyTime;
            ultimoKeyTime = tiempoActual;

            if (diferenciaTiempo > limiteTiempoMilisegundos) {
                bufferCodigo = "";
            }

            if (e.key.length === 1) {
                bufferCodigo += e.key;
            }

            if (e.key === 'Enter') {
                if (bufferCodigo.trim().length > 0) {
                    procesarCodigoEscaneado(bufferCodigo.trim());
                }
                bufferCodigo = ""; 
                e.preventDefault();
            }
    });


        function procesarCodigoEscaneado(codigo) {
            const filaProducto = document.querySelector(`tr[data-barcode="${codigo}"]`);

            if (filaProducto) {
                const idProducto = filaProducto.id.replace('fila-', '');
                const nombreProducto = filaProducto.querySelector('.col-nombre').innerText;
                const inputCantidad = document.getElementById(`input-real-${idProducto}`);

                const estadoPrevioScanner = scannerActivo;
                scannerActivo = false;

                Swal.fire({
                    title: 'Producto Detectado',
                    html: `Se escaneó: <strong>${codigo}</strong><br><br>${nombreProducto}<br><br>Ingrese la Cantidad Recibida:`,
                    input: 'number',
                    inputValue: inputCantidad.value || 1,
                    inputAttributes: {
                        min: 0,
                        step: 1
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Guardar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0070C0',
                    inputValidator: (value) => {
                        if (!value || value < 0) {
                            return 'Por Favor Ingrese una Cantidad Válida Igual o Mayor a 0';
                        }
                    }
                }).then((result) => {
                    scannerActivo = estadoPrevioScanner;

                    if (result.isConfirmed) {
                        inputCantidad.value = result.value;
                        
                        filaProducto.style.backgroundColor = '#e8f5e9';
                        setTimeout(() => {
                            filaProducto.style.backgroundColor = '';
                        }, 1500);
                    }
                });

            } else {
                const estadoPrevioScanner = scannerActivo;
                scannerActivo = false;

                Swal.fire({
                    icon: 'error',
                    title: 'Producto Desconocido',
                    text: `El Código [${codigo}] no se Encontro en esta Entrada.`,
                    confirmButtonColor: '#c62828'
                }).then(() => {
                    scannerActivo = estadoPrevioScanner;
                });
            }
        }

    document.addEventListener('DOMContentLoaded', function () {
        const formularioBusqueda = document.getElementById('inventario-entry-form');
        const inputFolio = document.getElementById('folio_entrada');

        if (formularioBusqueda && inputFolio) {
            formularioBusqueda.addEventListener('submit', function (e) {
                const valorFolio = inputFolio.value.trim();
                if (valorFolio === '') {
                    e.preventDefault(); 
                    
                    inputFolio.focus(); 
                    return false;
                }
            });
        }
    });


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
                            icon.className = 'fa-solid fa-bars'; 
                        } else {
                            icon.className = 'fa-solid fa-xmark'; 
                        }
                    }
                });
            }
        });
</script>
</body>
</html>