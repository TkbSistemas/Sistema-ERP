<?php
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/ActivityLogger.php';
require_once __DIR__ . '/../models/OrdenCompra.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Almacen.php';
require_once __DIR__ . '/../models/Proyecto.php';
require_once __DIR__ . '/../models/Proveedor.php';

class ComprasController{

    public function obtenerDashboardCompras(): void{
        Session::requireLogin(['Administrador', 'Compras']);

        $role   = $_SESSION['role'] ?? '';
        $nombre = $_SESSION['nombre'] ?? '';
        $userId = (int) ($_SESSION['user_id'] ?? 0);

       $_SESSION['menu_items'] = [
            ['slug' => 'construccion', 'label' => 'Cotizaciones', 'icon' => 'fa-solid fa-file-contract', 'role' => 'Todos'],
            ['slug' => 'ordenes_compra', 'label' => 'Órdenes de Compra', 'icon' => 'fa-solid fa-list-check', 'role' => 'Todos'],
            ['slug' => 'construccion', 'label' => 'Facturas de Compra', 'icon' => 'fa-solid fa-file-invoice-dollar', 'role' => 'Todos'],
            ['slug' => 'catalogo_productos', 'label' => 'Catálogo de Productos', 'icon' => 'fa-solid fa-clipboard-list', 'role' => 'Todos'],
            ['slug' => 'proveedores', 'label' => 'Catálogo de Proveedores', 'icon' => 'fa-solid fa-building-user', 'role' => 'Todos'],
            ['slug' => 'logout', 'label' => 'Cerrar Sesión', 'icon' => 'fa-solid fa-arrow-right-from-bracket', 'role' => 'Todos']
        ];

        $db = Database::getInstance()->getConnection();

        $datos = [
            'nombre'      => $nombre,
            'role'        => $role,
            'last_update' => date('d/m/Y, h:i:s a'),
            'alertas'     => [],
        ];


        include __DIR__ . '/../views/compras/dashboard_compras.php';
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
        $ordenesEnEntrega = array_values(array_filter(
            $todasLasOrdenes,
            static fn(array $orden): bool => in_array(($orden['estatus'] ?? ''), ['Aprobada', 'Parcial'], true)
        ));
        $mesActual = date('Y-m');
        $ordenesEsteMes = array_values(array_filter(
            $todasLasOrdenes,
            static fn(array $orden): bool => str_starts_with((string) ($orden['fecha_compra'] ?? ''), $mesActual)
        ));

        $ordenesTab = $tabActiva === 'pendientes' ? $ordenesPendientes : $todasLasOrdenes;
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
            'numOrdenesEnEntrega' => count($ordenesEnEntrega),
            'numOrdenesEsteMes' => count($ordenesEsteMes),
        ];

        include __DIR__ . '/../views/compras/ordenes_compra.php';
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
                    'created_by' => (int) ($_SESSION['user_id'] ?? 0),
                ], $detalles);

                ActivityLogger::registrarAlta('compras', 'orden_compra', $resultado['id'], 'Orden de Compra Registrada', [
                    'folio' => $resultado['folio'],
                    'proveedor_id' => $proveedorId,
                    'proyecto_id' => $proyectoId,
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


    public function procesarCompra(): void
    {
        Session::requireLogin(['Administrador', 'Compras']);

        $nombre = $_SESSION['nombre'] ?? '';
        $role = $_SESSION['role'] ?? '';
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

                $estatus = OrdenCompra::confirmarCompra($ordenId, $detallesConfirmados);
                ActivityLogger::registrarActualizacion(
                    'compras',
                    'orden_compra',
                    $ordenId,
                    'Recepción de Orden de Compra Confirmada',
                    [
                        'folio' => $orden['folio'] ?? null,
                        'estatus' => $estatus,
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
