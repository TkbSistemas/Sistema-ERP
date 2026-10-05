<?php
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/ActivityLogger.php';
require_once __DIR__ . '/../models/OrdenCompra.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Almacen.php';
require_once __DIR__ . '/../models/Proyecto.php';
require_once __DIR__ . '/../models/Proveedor.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/FacturaCompra.php';

class ComprasController{

    public function cancelarOrdenCompra(): void
    {
        Session::requireLogin(['Administrador', 'Compras']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        try {
            if (!Session::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
                throw new RuntimeException('La Sesión Expiró. Recarga la Página.');
            }
            $id = (int) ($_POST['orden_id'] ?? 0);
            OrdenCompra::cancelar($id);
            ActivityLogger::registrarCambioEstado('compras', 'orden_compra', $id, 'Cancelada', 'Orden de Compra Cancelada');
            $_SESSION['alerta'] = ['tipo' => 'success', 'titulo' => 'Orden Cancelada', 'mensaje' => 'La Orden se Canceló Correctamente.'];
        } catch (PDOException $e) {
            error_log('Error al cancelar orden: ' . $e->getMessage());
            $_SESSION['alerta'] = ['tipo' => 'error', 'titulo' => 'Error al Cancelar', 'mensaje' => 'No Fue Posible Cancelar la Orden.'];
        } catch (RuntimeException $e) {
            $_SESSION['alerta'] = ['tipo' => 'warning', 'titulo' => 'Orden no Cancelada', 'mensaje' => $e->getMessage()];
        }
        header('Location: ' . Session::url('ordenes_compra'));
        exit;
    }

    public function obtenerDashboardCompras(): void{
        Session::requireLogin(['Administrador', 'Compras']);

        $role   = $_SESSION['role'] ?? '';
        $nombre = $_SESSION['nombre'] ?? '';
       $_SESSION['menu_items'] = [
            ['slug' => 'construccion', 'label' => 'Cotizaciones', 'icon' => 'fa-solid fa-file-contract', 'role' => 'Todos'],
            ['slug' => 'ordenes_compra', 'label' => 'Órdenes de Compra', 'icon' => 'fa-solid fa-list-check', 'role' => 'Todos'],
            ['slug' => 'facturas_compras', 'label' => 'Facturas de Compra', 'icon' => 'fa-solid fa-file-invoice-dollar', 'role' => 'Todos'],
            ['slug' => 'catalogo_productos', 'label' => 'Catálogo de Productos', 'icon' => 'fa-solid fa-clipboard-list', 'role' => 'Todos'],
            ['slug' => 'proveedores', 'label' => 'Catálogo de Proveedores', 'icon' => 'fa-solid fa-building-user', 'role' => 'Todos'],
            ['slug' => 'configuracion_compras', 'label' => 'Configuración', 'icon' => 'fa-solid fa-gear', 'role' => 'Todos'],
            ['slug' => 'logout', 'label' => 'Cerrar Sesión', 'icon' => 'fa-solid fa-arrow-right-from-bracket', 'role' => 'Todos']
        ];

        $ordenes = OrdenCompra::all();
        $ordenesFacturables = FacturaCompra::ordenesElegibles();

        $ordenesPendientes = array_values(array_filter(
            $ordenes,
            static fn(array $orden): bool => ($orden['estatus'] ?? '') === 'Pendiente'
        ));
        $ordenesPorProcesar = array_values(array_filter(
            $ordenes,
            static fn(array $orden): bool => in_array(($orden['estatus'] ?? ''), ['Aprobada', 'Parcial'], true)
        ));
        $ordenesSinFactura = array_values(array_filter(
            $ordenesFacturables,
            static fn(array $orden): bool => empty($orden['factura_id'])
        ));

        $ultimasOrdenes = array_slice($ordenes, 0, 6);

        $datos = [
            'nombre'      => $nombre,
            'role'        => $role,
            'last_update' => date('d/m/Y, h:i:s a'),
            'ordenes_pendientes' => count($ordenesPendientes),
            'ordenes_por_procesar' => count($ordenesPorProcesar),
            'ordenes_sin_factura' => count($ordenesSinFactura),
            'ultimas_ordenes' => $ultimasOrdenes,
        ];


        include __DIR__ . '/../views/compras/dashboard_compras.php';
    }

    public function configuracionCompras(): void
    {
        Session::requireLogin(['Administrador', 'Compras']);

        $role = $_SESSION['role'] ?? '';
        $nombre = $_SESSION['nombre'] ?? '';
        $usuarioId = (int) ($_SESSION['user_id'] ?? 0);
        $error = '';

        if (Usuario::findById($usuarioId) === null) {
            Session::logout();
            header('Location: ' . Session::url('login'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!Session::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
                    throw new RuntimeException('La Sesión Expiró. Recarga la Página e Intenta Nuevamente.');
                }

                Usuario::cambiarPassword(
                    $usuarioId,
                    (string) ($_POST['password_actual'] ?? ''),
                    (string) ($_POST['password_nueva'] ?? ''),
                    (string) ($_POST['password_confirmacion'] ?? '')
                );
                ActivityLogger::registrarActualizacion('compras', 'usuario', $usuarioId, 'Contraseña de Usuario Actualizada');
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Contraseña Actualizada',
                    'mensaje' => 'Tu Contraseña se Cambió Correctamente.',
                ];
                header('Location: ' . Session::url('configuracion_compras'));
                exit;
            } catch (InvalidArgumentException | RuntimeException $e) {
                $error = $e->getMessage();
            } catch (Throwable $e) {
                error_log('Error al cambiar contraseña de compras: ' . $e->getMessage());
                $error = 'No Fue Posible Actualizar la Contraseña. Intenta Nuevamente.';
            }
        }

        include __DIR__ . '/../views/compras/configuracion_compras.php';
    }


    public function obtenerOrdenesCompra(): void {
        Session::requireLogin(['Administrador', 'Compras']);

        $nombre = $_SESSION['nombre'] ?? '';
        $role   = $_SESSION['role'] ?? '';
        $tabActiva = ($_GET['tab'] ?? 'pendientes') === 'historial' ? 'historial' : 'pendientes';
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $porPagina = 8;

        $todasLasOrdenes = OrdenCompra::all();
        $ordenesPendientes = array_values(array_filter(
            $todasLasOrdenes,
            static fn(array $orden): bool => ($orden['estatus'] ?? '') === 'Pendiente'
        ));
        $ordenesPorGestionar = array_values(array_filter(
            $todasLasOrdenes,
            static fn(array $orden): bool => in_array(
                ($orden['estatus'] ?? ''),
                ['Pendiente', 'Aprobada', 'Parcial'],
                true
            )
        ));
        $ordenesPorProcesar = array_values(array_filter(
            $todasLasOrdenes,
            static fn(array $orden): bool => in_array(($orden['estatus'] ?? ''), ['Aprobada', 'Parcial'], true)
        ));
        $mesActual = date('Y-m');
        $ordenesEsteMes = array_values(array_filter(
            $todasLasOrdenes,
            static fn(array $orden): bool => str_starts_with((string) ($orden['fecha_compra'] ?? ''), $mesActual)
        ));

        $ordenesTab = $tabActiva === 'pendientes' ? $ordenesPorGestionar : $todasLasOrdenes;
        $totalRegistros = count($ordenesTab);
        $totalPaginas = max(1, (int) ceil($totalRegistros / $porPagina));
        $pagina = min($pagina, $totalPaginas);
        $offset = ($pagina - 1) * $porPagina;
        $ordenesCompra = array_slice($ordenesTab, $offset, $porPagina);

        $pagination = [
            'pagina' => $pagina,
            'total_paginas' => $totalPaginas,
            'por_pagina' => $porPagina,
            'total' => $totalRegistros,
            'desde' => $totalRegistros > 0 ? $offset + 1 : 0,
            'hasta' => min($offset + $porPagina, $totalRegistros),
        ];

        $datos = [
            'nombre' => $nombre,
            'role' => $role,
            'last_update' => date('d/m/Y, h:i:s a'),
            'numOrdenesPendientes' => count($ordenesPendientes),
            'numOrdenesPorProcesar' => count($ordenesPorProcesar),
            'numOrdenesEsteMes' => count($ordenesEsteMes),
        ];

        include __DIR__ . '/../views/compras/ordenes_compra.php';
    }

    public function facturasCompras(): void
    {
        Session::requireLogin(['Administrador', 'Compras']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!Session::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
                    throw new RuntimeException('La Sesión Expiró. Recarga la Página e Intenta Nuevamente.');
                }

                $ordenId = (int) ($_POST['orden_id'] ?? 0);
                $proyectoId = (int) ($_POST['proyecto_id'] ?? 0);
                $folioFiscal = strtoupper(trim((string) ($_POST['folio_fiscal'] ?? '')));
                $monto = filter_var($_POST['monto_total'] ?? null, FILTER_VALIDATE_FLOAT);
                $fechaEmision = trim((string) ($_POST['fecha_emision'] ?? ''));

                if ($ordenId <= 0) {
                    throw new InvalidArgumentException('Selecciona una Orden de Compra Válida.');
                }
                if ($proyectoId <= 0) {
                    throw new InvalidArgumentException('Selecciona el Proyecto al que se Asignará la Compra.');
                }
                if ($folioFiscal === '' || mb_strlen($folioFiscal) > 100) {
                    throw new InvalidArgumentException('Captura un Folio Fiscal Válido de Hasta 100 Caracteres.');
                }
                if ($monto === false || $monto <= 0 || $monto > 999999999999.99) {
                    throw new InvalidArgumentException('El Monto Total de la Factura Debe ser Mayor a Cero.');
                }
                $fechaValida = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaEmision);
                if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fechaEmision) {
                    throw new InvalidArgumentException('Selecciona una Fecha de Emisión Válida.');
                }
                if ($fechaValida > new DateTimeImmutable('today')) {
                    throw new InvalidArgumentException('La Fecha de Emisión no Puede ser Posterior al Día de Hoy.');
                }

                $resultado = FacturaCompra::guardarParaOrden(
                    $ordenId,
                    $proyectoId,
                    $folioFiscal,
                    round((float) $monto, 2),
                    $fechaEmision
                );

                $descripcion = $resultado['es_nueva'] ? 'Factura de Compra Registrada' : 'Factura de Compra Actualizada';
                if ($resultado['es_nueva']) {
                    ActivityLogger::registrarAlta('compras', 'factura_compra', $resultado['id'], $descripcion, [
                        'orden_id' => $ordenId,
                        'folio_orden' => $resultado['folio_orden'],
                        'folio_fiscal' => $folioFiscal,
                        'proyecto_id' => $proyectoId,
                    ]);
                } else {
                    ActivityLogger::registrarActualizacion('compras', 'factura_compra', $resultado['id'], $descripcion, [
                        'orden_id' => $ordenId,
                        'folio_orden' => $resultado['folio_orden'],
                        'folio_fiscal' => $folioFiscal,
                        'proyecto_id' => $proyectoId,
                    ]);
                }

                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => $resultado['es_nueva'] ? 'Factura Asignada' : 'Factura Actualizada',
                    'mensaje' => 'La Factura de la Orden ' . $resultado['folio_orden'] . ' se Guardó Correctamente.',
                ];
            } catch (InvalidArgumentException | RuntimeException $e) {
                $_SESSION['alerta'] = [
                    'tipo' => 'warning',
                    'titulo' => 'No Fue Posible Guardar la Factura',
                    'mensaje' => $e->getMessage(),
                ];
            } catch (Throwable $e) {
                error_log('Error al guardar factura de compra: ' . $e->getMessage());
                $_SESSION['alerta'] = [
                    'tipo' => 'error',
                    'titulo' => 'No Fue Posible Guardar la Factura',
                    'mensaje' => 'Ocurrió un Error al Procesar la Información. Intenta Nuevamente.',
                ];
            }

            header('Location: ' . Session::url('facturas_compras'));
            exit;
        }

        $nombre = $_SESSION['nombre'] ?? '';
        $role = $_SESSION['role'] ?? '';
        $ordenesFacturables = FacturaCompra::ordenesElegibles();
        $proyectos = Proyecto::all();

        include __DIR__ . '/../views/compras/facturas_compra.php';
    }

    public function facturaHistorica(): void
    {
        Session::requireLogin(['Administrador', 'Compras']);

        $nombre = $_SESSION['nombre'] ?? '';
        $role = $_SESSION['role'] ?? '';
        $productos = array_values(array_filter(
            Producto::all(),
            static fn(array $producto): bool => !array_key_exists('activo', $producto)
                || (int) $producto['activo'] === 1
        ));
        $proyectos = Proyecto::all();
        $proveedores = Proveedor::all();
        $almacenes = $this->almacenesActivos();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!Session::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
                    throw new RuntimeException('La Sesión Expiró. Recarga la Página e Intenta Nuevamente.');
                }

                $responsableId = (int) ($_SESSION['user_id'] ?? 0);
                if ($responsableId <= 0) {
                    throw new RuntimeException('No Fue Posible Identificar al Usuario Responsable.');
                }

                $proveedorId = $this->validarProveedorActivo((int) ($_POST['proveedor_id'] ?? 0));
                $almacenId = $this->validarAlmacenActivo((int) ($_POST['almacen_id'] ?? 0), $almacenes);
                $proyectoId = (int) ($_POST['proyecto_id'] ?? 0);
                $proyectosValidos = array_column($proyectos, null, 'id');
                if ($proyectoId <= 0 || !isset($proyectosValidos[$proyectoId])) {
                    throw new InvalidArgumentException('Selecciona un Proyecto Válido.');
                }

                $folioFiscal = strtoupper(trim((string) ($_POST['folio_fiscal'] ?? '')));
                if ($folioFiscal === '' || mb_strlen($folioFiscal) > 100) {
                    throw new InvalidArgumentException('Captura un Folio Fiscal Válido de Hasta 100 Caracteres.');
                }

                $fechaEmision = trim((string) ($_POST['fecha_emision'] ?? ''));
                $fechaValida = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaEmision);
                if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fechaEmision) {
                    throw new InvalidArgumentException('Selecciona una Fecha de Emisión Válida.');
                }
                if ($fechaValida > new DateTimeImmutable('today')) {
                    throw new InvalidArgumentException('La Fecha de Emisión no Puede ser Posterior al Día de Hoy.');
                }

                $materialesEntrada = json_decode((string) ($_POST['materiales'] ?? ''), true);
                if (!is_array($materialesEntrada) || $materialesEntrada === []) {
                    throw new InvalidArgumentException('Agrega al Menos un Material a la Factura.');
                }
                if (count($materialesEntrada) > 200) {
                    throw new InvalidArgumentException('La Factura no Puede Contener más de 200 Partidas.');
                }

                $productosValidos = [];
                foreach ($productos as $producto) {
                    $productosValidos[(int) ($producto['id'] ?? 0)] = $producto;
                }

                $materiales = [];
                $productosUsados = [];
                $montoTotal = 0.0;
                foreach ($materialesEntrada as $indice => $material) {
                    $productoId = (int) ($material['producto_id'] ?? 0);
                    $cantidad = filter_var($material['cantidad'] ?? null, FILTER_VALIDATE_FLOAT);
                    $precio = filter_var($material['precio_unitario'] ?? null, FILTER_VALIDATE_FLOAT);

                    if ($productoId <= 0 || !isset($productosValidos[$productoId])) {
                        throw new InvalidArgumentException('El Producto de la Partida ' . ($indice + 1) . ' no es Válido o Está Inactivo.');
                    }
                    if (isset($productosUsados[$productoId])) {
                        throw new InvalidArgumentException('El Producto de la Partida ' . ($indice + 1) . ' ya fue Agregado.');
                    }
                    if ($cantidad === false || $cantidad <= 0 || $cantidad > 99999999.99) {
                        throw new InvalidArgumentException('La Cantidad de la Partida ' . ($indice + 1) . ' Debe ser Mayor a Cero.');
                    }
                    if ($precio === false || $precio <= 0 || $precio > 999999999999.99) {
                        throw new InvalidArgumentException('El Precio Unitario de la Partida ' . ($indice + 1) . ' Debe ser Mayor a Cero.');
                    }

                    $productosUsados[$productoId] = true;
                    $materiales[] = [
                        'producto_id' => $productoId,
                        'cantidad' => round((float) $cantidad, 2),
                        'precio_unitario' => round((float) $precio, 2),
                    ];
                    $montoTotal += round((float) $cantidad, 2) * round((float) $precio, 2);
                }
                if ($montoTotal > 999999999999.99) {
                    throw new InvalidArgumentException('El Monto Total de la Factura Excede el Límite Permitido.');
                }

                $resultado = FacturaCompra::registrarHistorica([
                    'proveedor_id' => $proveedorId,
                    'proyecto_id' => $proyectoId,
                    'almacen_id' => $almacenId,
                    'responsable_id' => $responsableId,
                    'folio_fiscal' => $folioFiscal,
                    'fecha_emision' => $fechaEmision,
                ], $materiales);

                ActivityLogger::registrarAlta(
                    'compras',
                    'factura_compra_historica',
                    $resultado['factura_id'],
                    'Factura Histórica Registrada con Entrada de Almacén',
                    [
                        'folio_fiscal' => $resultado['folio_fiscal'],
                        'orden_id' => $resultado['orden_id'],
                        'folio_orden' => $resultado['folio_orden'],
                        'recepcion_id' => $resultado['recepcion_id'],
                        'folio_recepcion' => $resultado['folio_recepcion'],
                        'proveedor_id' => $proveedorId,
                        'proyecto_id' => $proyectoId,
                        'almacen_id' => $almacenId,
                        'partidas' => $resultado['partidas'],
                        'total' => $resultado['total'],
                    ]
                );

                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Factura Histórica Registrada',
                    'mensaje' => 'La Factura ' . $folioFiscal . ' Generó la Entrada ' . $resultado['folio_recepcion']
                        . ' y Actualizó el Inventario Correctamente.',
                ];
                header('Location: ' . Session::url('facturas_compras'));
                exit;
            } catch (InvalidArgumentException | RuntimeException $e) {
                $error = $e->getMessage();
            } catch (Throwable $e) {
                error_log('Error al registrar factura histórica: ' . $e->getMessage());
                $error = 'No Fue Posible Registrar la Factura Histórica. Revisa los Datos e Intenta Nuevamente.';
            }
        }

        include __DIR__ . '/../views/compras/factura_historica.php';
    }

    public function verOrdenCompra(int $id): void
    {
        Session::requireLogin(['Administrador', 'Compras']);

        $orden = OrdenCompra::find($id);
        if ($orden === null) {
            http_response_code(404);
            echo 'Orden de Compra no Encontrada.';
            return;
        }

        $fechaImpresion = new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City'));
        ActivityLogger::registrarAccion('compras', 'impresion_orden_compra', 'Formato de Orden de Compra Generado', [
            'orden_id' => $id,
            'folio' => $orden['folio'] ?? null,
            'total_estimado' => $orden['total_estimado'] ?? 0,
        ]);

        include __DIR__ . '/../templates/orden_compra.php';
    }

    public function crearOrdenCompra(): void
    {
        Session::requireLogin(['Administrador', 'Compras']);

        $nombre = $_SESSION['nombre'] ?? '';
        $role = $_SESSION['role'] ?? '';
        $productos = Producto::All();
        $proyectos = Proyecto::all();
        $proveedores = Proveedor::all();
        $almacenes = $this->almacenesActivos();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!Session::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
                    throw new RuntimeException('La Sesión Expiró. Recarga la Página e Intenta Nuevamente.');
                }

                $proveedorId = $this->validarProveedorActivo((int) ($_POST['proveedor_id'] ?? 0));
                $proyectoId = null;
                if (trim((string) ($_POST['proyecto_id'] ?? '')) !== '') {
                    $proyectoId = (int) $_POST['proyecto_id'];
                    $proyectosValidos = array_column($proyectos, null, 'id');
                    if ($proyectoId <= 0 || !isset($proyectosValidos[$proyectoId])) {
                        throw new InvalidArgumentException('El Proyecto Seleccionado no es Válido.');
                    }
                }

                $fechaCompra = trim((string) ($_POST['fecha_compra'] ?? ''));
                $fechaValida = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaCompra);
                if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fechaCompra) {
                    throw new InvalidArgumentException('Selecciona una Fecha Válida para la Orden.');
                }

                $metodoEntrega = trim((string) ($_POST['metodo_entrega'] ?? ''));
                if (!in_array($metodoEntrega, ['Reparto', 'Recolección', 'Por Confirmar'], true)) {
                    throw new InvalidArgumentException('Selecciona un Método de Entrega Válido.');
                }
                $almacenId = $this->validarAlmacenActivo((int) ($_POST['almacen_id'] ?? 0), $almacenes);

                $materiales = json_decode((string) ($_POST['material'] ?? ''), true);
                if (!is_array($materiales) || $materiales === []) {
                    throw new InvalidArgumentException('Agrega al Menos un Material a la Orden.');
                }
                if (count($materiales) > 100) {
                    throw new InvalidArgumentException('La Orden no Puede Contener más de 100 Partidas.');
                }

                $productosValidos = [];
                foreach ($productos as $producto) {
                    $productosValidos[(int) ($producto['id'] ?? 0)] = $producto;
                }

                $detalles = [];
                foreach ($materiales as $indice => $material) {
                    $productoId = (int) ($material['producto_id'] ?? 0);
                    $cantidad = filter_var($material['cantidad'] ?? null, FILTER_VALIDATE_FLOAT);
                    $precioUnitario = filter_var($material['precio_unitario'] ?? null, FILTER_VALIDATE_FLOAT);

                    if ($productoId <= 0 || !isset($productosValidos[$productoId])) {
                        throw new InvalidArgumentException('El Producto de la Partida ' . ($indice + 1) . ' no es Válido.');
                    }
                    if ($cantidad === false || $cantidad <= 0) {
                        throw new InvalidArgumentException('La Cantidad de la Partida ' . ($indice + 1) . ' Debe ser Mayor a Cero.');
                    }
                    if ($precioUnitario === false || $precioUnitario <= 0) {
                        throw new InvalidArgumentException('El Precio Unitario de la Partida ' . ($indice + 1) . ' Debe ser Mayor a Cero.');
                    }

                    $detalles[] = [
                        'producto_id' => $productoId,
                        'cantidad' => round((float) $cantidad, 2),
                        'precio_unitario' => round((float) $precioUnitario, 2),
                    ];
                }

                $resultado = OrdenCompra::crearOrden([
                    'proyecto_id' => $proyectoId,
                    'proveedor_id' => $proveedorId,
                    'fecha_compra' => $fechaCompra,
                    'metodo_entrega' => $metodoEntrega,
                    'almacen_id' => $almacenId,
                    'created_by' => (int) ($_SESSION['user_id'] ?? 0),
                ], $detalles);

                ActivityLogger::registrarAlta('compras', 'orden_compra', $resultado['id'], 'Orden de Compra Registrada', [
                    'folio' => $resultado['folio'],
                    'proveedor_id' => $proveedorId,
                    'proyecto_id' => $proyectoId,
                    'almacen_id' => $almacenId,
                    'partidas' => count($detalles),
                ]);
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Orden Registrada',
                    'mensaje' => 'La Orden de Compra ' . $resultado['folio'] . ' se Registró Correctamente.',
                ];
                header('Location: ' . Session::url('ordenes_compra'));
                exit;
            } catch (InvalidArgumentException | RuntimeException $e) {
                $error = $e->getMessage();
            } catch (Throwable $e) {
                error_log('Error al crear orden de compra: ' . $e->getMessage());
                $error = 'No Fue Posible Registrar la Orden. Revisa los Datos e Intenta Nuevamente.';
            }
        }

        $msg = '';
        include __DIR__ . '/../views/compras/crear_orden.php';
    }

    public function aprobarOrdenCompra(): void
    {
        Session::requireLogin(['Administrador', 'Compras']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . Session::url('ordenes_compra'));
            exit;
        }

        try {
            if (!Session::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
                throw new RuntimeException('La Sesión Expiró. Recarga la Página e Intenta Nuevamente.');
            }

            $ordenId = (int) ($_POST['orden_id'] ?? 0);
            if ($ordenId <= 0) {
                throw new InvalidArgumentException('La Orden de Compra no es Válida.');
            }

            $orden = OrdenCompra::aprobar($ordenId);
            ActivityLogger::registrarCambioEstado(
                'compras',
                'orden_compra',
                $ordenId,
                'Aprobada',
                'Orden de Compra Aprobada',
                [
                    'folio' => $orden['folio'] ?? null,
                    'estatus_anterior' => 'Pendiente',
                ]
            );

            $_SESSION['alerta'] = [
                'tipo' => 'success',
                'titulo' => 'Orden Aprobada',
                'mensaje' => 'La Orden ' . ($orden['folio'] ?? '') . ' ya Puede Procesarse.',
            ];
        } catch (InvalidArgumentException | RuntimeException $e) {
            $_SESSION['alerta'] = [
                'tipo' => 'warning',
                'titulo' => 'No Fue Posible Aprobar la Orden',
                'mensaje' => $e->getMessage(),
            ];
        } catch (Throwable $e) {
            error_log('Error al aprobar orden de compra: ' . $e->getMessage());
            $_SESSION['alerta'] = [
                'tipo' => 'error',
                'titulo' => 'No Fue Posible Aprobar la Orden',
                'mensaje' => 'Ocurrió un Error al Actualizar la Orden. Intenta Nuevamente.',
            ];
        }

        header('Location: ' . Session::url('ordenes_compra'));
        exit;
    }


    public function procesarCompra(): void
    {
        Session::requireLogin(['Administrador', 'Compras']);

        $nombre = $_SESSION['nombre'] ?? '';
        $role = $_SESSION['role'] ?? '';
        $almacenes = $this->almacenesActivos();
        $ordenId = (int) ($_POST['orden_id'] ?? $_GET['id'] ?? 0);
        $orden = OrdenCompra::find($ordenId);

        if ($orden === null) {
            http_response_code(404);
            echo 'Orden de Compra no Encontrada.';
            return;
        }

        if (!in_array((string) ($orden['estatus'] ?? ''), ['Aprobada', 'Parcial'], true)) {
            $_SESSION['alerta'] = [
                'tipo' => 'warning',
                'titulo' => 'Orden no Disponible',
                'mensaje' => 'Solo las Órdenes Aprobadas o Parciales Pueden Procesarse.',
            ];
            header('Location: ' . Session::url('ordenes_compra'));
            exit;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!Session::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
                    throw new RuntimeException('La Sesión Expiró. Recarga la Página e Intenta Nuevamente.');
                }

                $metodoEntrega = trim((string) ($_POST['metodo_entrega'] ?? ''));
                if (!in_array($metodoEntrega, ['Reparto', 'Recolección', 'Por Confirmar'], true)) {
                    throw new InvalidArgumentException('Selecciona una Forma de Entrega Válida.');
                }
                $almacenId = $this->validarAlmacenActivo((int) ($_POST['almacen_id'] ?? 0), $almacenes);

                $entrada = $_POST['detalles'] ?? null;
                if (!is_array($entrada)) {
                    throw new InvalidArgumentException('No se Recibieron los Materiales de la Orden.');
                }

                $detallesConfirmados = [];
                foreach ($orden['detalles'] as $indice => $detalle) {
                    $detalleId = (int) ($detalle['id'] ?? 0);
                    $valores = $entrada[$detalleId] ?? null;
                    if (!is_array($valores)) {
                        throw new InvalidArgumentException('Faltan los Datos de la Partida ' . ($indice + 1) . '.');
                    }

                    $cantidad = filter_var($valores['cantidad_confirmada'] ?? null, FILTER_VALIDATE_FLOAT);
                    $precio = filter_var($valores['precio_confirmado'] ?? null, FILTER_VALIDATE_FLOAT);
                    $cantidadSolicitada = (float) ($detalle['cantidad_solicitada'] ?? 0);

                    if ($cantidad === false || $cantidad < 0 || $cantidad > $cantidadSolicitada) {
                        throw new InvalidArgumentException(
                            'La Cantidad Confirmada de la Partida ' . ($indice + 1)
                            . ' Debe Estar entre Cero y ' . $cantidadSolicitada . '.'
                        );
                    }
                    if ($precio === false || $precio <= 0) {
                        throw new InvalidArgumentException(
                            'El Precio Confirmado de la Partida ' . ($indice + 1) . ' Debe ser Mayor a Cero.'
                        );
                    }

                    $detallesConfirmados[] = [
                        'id' => $detalleId,
                        'cantidad_confirmada' => round((float) $cantidad, 2),
                        'precio_confirmado' => round((float) $precio, 2),
                    ];
                }

                $estatus = OrdenCompra::confirmarCompra(
                    $ordenId,
                    $detallesConfirmados,
                    $metodoEntrega,
                    $almacenId
                );
                ActivityLogger::registrarActualizacion(
                    'compras',
                    'orden_compra',
                    $ordenId,
                    'Recepción de Orden de Compra Confirmada',
                    [
                        'folio' => $orden['folio'] ?? null,
                        'estatus' => $estatus,
                        'metodo_entrega' => $metodoEntrega,
                        'almacen_id' => $almacenId,
                        'partidas' => count($detallesConfirmados),
                    ]
                );

                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Compra Procesada',
                    'mensaje' => 'La Orden ' . ($orden['folio'] ?? '') . ' quedó con Estatus ' . $estatus . '.',
                ];
                header('Location: ' . Session::url('ordenes_compra'));
                exit;
            } catch (InvalidArgumentException | RuntimeException $e) {
                $error = $e->getMessage();
            } catch (Throwable $e) {
                error_log('Error al procesar orden de compra: ' . $e->getMessage());
                $error = 'No Fue Posible Procesar la Compra. Revisa los Datos e Intenta Nuevamente.';
            }
        }

        include __DIR__ . '/../views/compras/procesar_compra.php';
    }

    public function obtenerProveedores(): void {
        Session::requireLogin(['Administrador', 'Compras']);

        $nombre = $_SESSION['nombre'] ?? '';
        $role   = $_SESSION['role'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarGestionProveedor($role);
        }

        $filtros = [
            'buscar' => trim((string) ($_GET['buscar'] ?? '')),
            'categoria' => trim((string) ($_GET['categoria'] ?? '')),
            'partner_activo' => isset($_GET['partner_activo']) && $_GET['partner_activo'] === '1',
        ];

        $perPageOptions = [10, 25, 50];
        $perPage = (int) ($_GET['per_page'] ?? 10);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 10;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        $resultado = Proveedor::filtrar($filtros, $perPage, $offset);
        $totalRegistros = $resultado['total'];
        $totalPaginas = max(1, (int) ceil($totalRegistros / $perPage));

        if ($page > $totalPaginas) {
            $page = $totalPaginas;
            $offset = ($page - 1) * $perPage;
            $resultado = Proveedor::filtrar($filtros, $perPage, $offset);
        }

        $proveedores = $resultado['proveedores'];
        $categorias = Proveedor::categorias();
        $categoriasFormulario = Proveedor::categoriasPermitidas();
        $hayFiltros = $filtros['buscar'] !== ''
            || $filtros['categoria'] !== ''
            || $filtros['partner_activo'];
        $total = $totalRegistros;

        $datos = [
            'nombre' => $nombre,
            'role' => $role,
            'last_update' => date('d/m/Y, h:i:s a')
        ];

        include __DIR__ . '/../views/compras/proveedores.php';
    }

    private function procesarGestionProveedor(string $role): void
    {
        if ($role !== 'Administrador') {
            $_SESSION['alerta'] = [
                'tipo' => 'error',
                'titulo' => 'Acceso Denegado',
                'mensaje' => 'Solo un Administrador Puede Modificar Proveedores y Agentes.',
            ];
            header('Location: ' . Session::url('proveedores'));
            exit;
        }

        if (!Session::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
            $_SESSION['alerta'] = [
                'tipo' => 'error',
                'titulo' => 'Sesión Expirada',
                'mensaje' => 'Recarga la Página e Intenta Nuevamente.',
            ];
            header('Location: ' . Session::url('proveedores'));
            exit;
        }

        try {
            $accion = (string) ($_POST['accion'] ?? '');
            $titulo = 'Operación Completada';
            $mensaje = '';

            switch ($accion) {
                case 'crear_proveedor':
                    $datosProveedor = $this->validarDatosProveedor($_POST);
                    Proveedor::create($datosProveedor);
                    $proveedorId = (int) Database::getInstance()->getConnection()->lastInsertId();
                    ActivityLogger::registrarAlta('compras', 'proveedor', $proveedorId ?: null, 'Proveedor Registrado', [
                        'nombre' => $datosProveedor['nombre'],
                        'categoria' => $datosProveedor['categoria'],
                    ]);
                    $titulo = 'Proveedor Registrado';
                    $mensaje = 'El Proveedor se Registró Correctamente.';
                    break;

                case 'editar_proveedor':
                    $proveedorId = $this->validarProveedorActivo((int) ($_POST['proveedor_id'] ?? 0));
                    $datosProveedor = $this->validarDatosProveedor($_POST);
                    Proveedor::update($proveedorId, $datosProveedor);
                    ActivityLogger::registrarActualizacion('compras', 'proveedor', $proveedorId, 'Proveedor Actualizado', [
                        'nombre' => $datosProveedor['nombre'],
                        'categoria' => $datosProveedor['categoria'],
                    ]);
                    $titulo = 'Proveedor Actualizado';
                    $mensaje = 'Los Datos del Proveedor se Actualizaron Correctamente.';
                    break;

                case 'agregar_agente':
                    $proveedorId = $this->validarProveedorActivo((int) ($_POST['proveedor_id'] ?? 0));
                    $datosAgente = $this->validarDatosAgente($_POST);
                    Proveedor::crearAgente($proveedorId, $datosAgente);
                    $agenteId = (int) Database::getInstance()->getConnection()->lastInsertId();
                    ActivityLogger::registrarAlta('compras', 'agente_proveedor', $agenteId ?: null, 'Agente Añadido al Proveedor', [
                        'proveedor_id' => $proveedorId,
                        'nombre' => $datosAgente['nombre'],
                    ]);
                    $titulo = 'Agente Registrado';
                    $mensaje = 'El Agente se Añadió Correctamente al Proveedor.';
                    break;

                case 'editar_agente':
                    $proveedorId = $this->validarProveedorActivo((int) ($_POST['proveedor_id'] ?? 0));
                    $agenteId = (int) ($_POST['agente_id'] ?? 0);
                    if ($agenteId <= 0 || !Proveedor::buscarAgente($agenteId, $proveedorId)) {
                        throw new RuntimeException('El Agente Seleccionado no Existe o no Pertenece al Proveedor.');
                    }
                    $datosAgente = $this->validarDatosAgente($_POST);
                    Proveedor::actualizarAgente($agenteId, $proveedorId, $datosAgente);
                    ActivityLogger::registrarActualizacion('compras', 'agente_proveedor', $agenteId, 'Agente de Proveedor Actualizado', [
                        'proveedor_id' => $proveedorId,
                        'nombre' => $datosAgente['nombre'],
                    ]);
                    $titulo = 'Agente Actualizado';
                    $mensaje = 'Los Datos del Agente se Actualizaron Correctamente.';
                    break;

                case 'eliminar_agente':
                    $proveedorId = $this->validarProveedorActivo((int) ($_POST['proveedor_id'] ?? 0));
                    $agenteId = (int) ($_POST['agente_id'] ?? 0);
                    if ($agenteId <= 0 || !Proveedor::buscarAgente($agenteId, $proveedorId)) {
                        throw new RuntimeException('El Agente Seleccionado no Existe o no Pertenece al Proveedor.');
                    }
                    if (!Proveedor::eliminarAgente($agenteId, $proveedorId)) {
                        throw new RuntimeException('El Agente ya no se Encuentra Activo.');
                    }
                    ActivityLogger::registrarBaja('compras', 'agente_proveedor', $agenteId, 'Agente Retirado del Proveedor', [
                        'proveedor_id' => $proveedorId,
                    ]);
                    $titulo = 'Agente Eliminado';
                    $mensaje = 'El Agente se Retiró del Proveedor.';
                    break;

                case 'eliminar_proveedor':
                    $proveedorId = $this->validarProveedorActivo((int) ($_POST['proveedor_id'] ?? 0));
                    Proveedor::delete($proveedorId);
                    ActivityLogger::registrarBaja('compras', 'proveedor', $proveedorId, 'Proveedor Retirado del Catálogo');
                    $titulo = 'Proveedor Eliminado';
                    $mensaje = 'El Proveedor se Retiró del Catálogo.';
                    break;

                default:
                    throw new InvalidArgumentException('La Operación Solicitada no es Válida.');
            }

            $_SESSION['alerta'] = [
                'tipo' => 'success',
                'titulo' => $titulo,
                'mensaje' => $mensaje,
            ];
        } catch (InvalidArgumentException | RuntimeException $e) {
            $_SESSION['alerta'] = [
                'tipo' => 'error',
                'titulo' => 'No Fue Posible Guardar los Cambios',
                'mensaje' => $e->getMessage(),
            ];
        } catch (Throwable $e) {
            error_log('Error al gestionar proveedor o agente: ' . $e->getMessage());
            $_SESSION['alerta'] = [
                'tipo' => 'error',
                'titulo' => 'Error al Guardar',
                'mensaje' => 'No Fue Posible Completar la Operación. Intenta Nuevamente.',
            ];
        }

        header('Location: ' . Session::url('proveedores'));
        exit;
    }

    private function validarProveedorActivo(int $proveedorId): int
    {
        $proveedor = $proveedorId > 0 ? Proveedor::find($proveedorId) : null;
        if (!$proveedor || (int) ($proveedor['activo'] ?? 0) !== 1) {
            throw new RuntimeException('El Proveedor Seleccionado no Existe o ya no Está Activo.');
        }

        return $proveedorId;
    }

    private function almacenesActivos(): array
    {
        return array_values(array_filter(
            Almacen::all(),
            static fn(array $almacen): bool => (int) ($almacen['activo'] ?? 0) === 1
        ));
    }

    private function validarAlmacenActivo(int $almacenId, array $almacenes): int
    {
        foreach ($almacenes as $almacen) {
            if ((int) ($almacen['id'] ?? 0) === $almacenId) {
                return $almacenId;
            }
        }

        throw new RuntimeException('El Almacén Destino no Existe o ya no Está Activo.');
    }

    private function validarDatosProveedor(array $entrada): array
    {
        $datos = [
            'nombre' => trim((string) ($entrada['nombre'] ?? '')),
            'categoria' => trim((string) ($entrada['categoria'] ?? '')),
            'razon_social' => trim((string) ($entrada['razon_social'] ?? '')),
            'rfc' => strtoupper(trim((string) ($entrada['rfc'] ?? ''))),
            'telefono' => trim((string) ($entrada['telefono'] ?? '')),
            'correo' => trim((string) ($entrada['correo'] ?? '')),
            'ubicacion_fisica' => trim((string) ($entrada['ubicacion_fisica'] ?? '')),
            'url_tienda' => trim((string) ($entrada['url_tienda'] ?? '')),
            'partner_activo' => isset($entrada['partner_activo']) && (string) $entrada['partner_activo'] === '1' ? 1 : 0,
        ];
        $errores = [];

        if ($datos['nombre'] === '') {
            $errores[] = 'El Nombre del Proveedor es Obligatorio.';
        }
        $limites = [
            'nombre' => 100,
            'razon_social' => 100,
            'rfc' => 50,
            'telefono' => 100,
            'correo' => 100,
            'ubicacion_fisica' => 255,
            'url_tienda' => 255,
        ];
        foreach ($limites as $campo => $limite) {
            if (mb_strlen($datos[$campo]) > $limite) {
                $errores[] = 'Uno de los Campos Supera la Longitud Permitida.';
                break;
            }
        }
        if ($datos['categoria'] !== '' && !in_array($datos['categoria'], Proveedor::categoriasPermitidas(), true)) {
            $errores[] = 'La Categoría Seleccionada no es Válida.';
        }
        if ($datos['correo'] !== '' && !filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El Correo Electrónico no es Válido.';
        }
        if ($datos['url_tienda'] !== '') {
            $esUrlValida = filter_var($datos['url_tienda'], FILTER_VALIDATE_URL)
                && in_array(strtolower((string) parse_url($datos['url_tienda'], PHP_URL_SCHEME)), ['http', 'https'], true);
            if (!$esUrlValida) {
                $errores[] = 'El Sitio Web Debe ser una URL HTTP o HTTPS Válida.';
            }
        }

        if ($errores) {
            throw new InvalidArgumentException(implode(' ', $errores));
        }

        return $datos;
    }

    private function validarDatosAgente(array $entrada): array
    {
        $datos = [
            'nombre' => trim((string) ($entrada['nombre'] ?? '')),
            'correo' => trim((string) ($entrada['correo'] ?? '')),
            'telefono' => trim((string) ($entrada['telefono'] ?? '')),
        ];
        $errores = [];

        if ($datos['nombre'] === '') {
            $errores[] = 'El Nombre del Agente es Obligatorio.';
        } elseif (mb_strlen($datos['nombre']) > 100) {
            $errores[] = 'El Nombre del Agente no Puede Superar 100 Caracteres.';
        }
        if ($datos['correo'] !== '' && (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL) || mb_strlen($datos['correo']) > 100)) {
            $errores[] = 'El Correo Electrónico del Agente no es Válido.';
        }
        if (mb_strlen($datos['telefono']) > 100) {
            $errores[] = 'El Teléfono del Agente no Puede Superar 100 Caracteres.';
        }

        if ($errores) {
            throw new InvalidArgumentException(implode(' ', $errores));
        }

        return $datos;
    }

    public function eliminarProveedor(int $id): void {
        Session::requireLogin(['Administrador', 'Compras']);
        try {
            $resultado = Proveedor::delete($id);
            if ($resultado) {
                ActivityLogger::registrarBaja('compras', 'proveedor', $id, 'Proveedor Retirado del Catálogo');
                echo json_encode(['success' => true]);
                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'titulo' => 'Proveedor Eliminado',
                    'mensaje' => 'El Proveedor se Eliminó Correctamente.',
                ];
            } else {
                $_SESSION['alerta'] = [
                    'tipo' => 'error',
                    'titulo' => 'Proveedor No Encontrado',
                    'mensaje' => 'El Proveedor no fue encontrado.',
                ];
                http_response_code(404);
                echo json_encode(['error' => 'Proveedor no encontrado.']);
            }
        } catch (Throwable $e) {
            $_SESSION['alerta'] = [
                    'tipo' => 'error',
                    'titulo' => 'Fallo al Eliminar Proveedor',
                    'mensaje' => 'Ocurrió un error al eliminar el proveedor.',
            ];
            error_log('Error al eliminar proveedor: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Ocurrió un error al procesar la solicitud.']);
        }
        // Redirigir a la página de proveedores después de la eliminación
        header('Location: ' . Session::url('proveedores'));
        exit;
    }

}
