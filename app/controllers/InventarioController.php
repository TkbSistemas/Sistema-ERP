<?php
    require_once __DIR__ . '/../models/MovimientoInventario.php';
    require_once __DIR__ . '/../models/Producto.php';
    require_once __DIR__ . '/../models/Almacen.php';
    require_once __DIR__ . '/../helpers/Session.php';
    require_once __DIR__ . '/../helpers/ActivityLogger.php';
    require_once __DIR__ . '/../helpers/Database.php';

    class InventarioController
    {
        public function obtenerVistaInventario()
        {
            include __DIR__ . '/../views/inventario/main/actual.php';
        }

        public function crearEntrada(){
            Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

            $productos     = Producto::all();
            $almacenes     = Almacen::all();
            $msg           = '';
            $error         = '';
            $entradaItems  = [];

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $entradaItems = $this->normalizarLineasEntrada($_POST);

                if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                    $error = 'Token CSRF invalido.';
                } elseif (empty($entradaItems)) {
                    $error = 'Agrega al menos un producto a la captura de entrada.';
                } else {
                    $db = Database::getInstance()->getConnection();

                    try {
                        $db->beginTransaction();
    
                        foreach ($entradaItems as $indice => $linea) {
                            $productoId = (int) ($linea['producto_id'] ?? 0);
                            $almacenId  = (int) ($linea['almacen_id'] ?? 0);
                            $cantidad   = isset($linea['cantidad']) ? (float) $linea['cantidad'] : 0;

                            if ($productoId <= 0 || $almacenId <= 0 || $cantidad <= 0) {
                                throw new RuntimeException('La linea ' . ($indice + 1) . ' es invalida.');
                            }

                            $data = [
                                'producto_id'        => $productoId,
                                'tipo'               => 'Entrada',
                                'cantidad'           => $cantidad,
                                'usuario_id'         => $_SESSION['user_id'],
                                'almacen_destino_id' => $almacenId,
                                'observaciones'      => trim((string) ($linea['observaciones'] ?? '')),
                            ];

                            if (! MovimientoInventario::registrar($data)) {
                                throw new RuntimeException('No fue posible registrar la linea ' . ($indice + 1) . '.');
                            }

                            Producto::sumarStock($productoId, $cantidad, $almacenId);
                            ActivityLogger::log('inventario_entrada', 'Entrada de inventario registrada', [
                                'producto_id' => $productoId,
                                'almacen_id'  => $almacenId,
                                'cantidad'    => $cantidad,
                                'linea'       => $indice + 1,
                            ]);
                        }

                        $db->commit();

                        $totalLineas = count($entradaItems);
                        $msg = $totalLineas === 1
                            ? 'Entrada registrada correctamente.'
                            : 'Se registraron ' . $totalLineas . ' productos en la entrada correctamente.';

                        $entradaItems = [];
                        $_POST = [];
                    } catch (\Throwable $e) {
                        if ($db->inTransaction()) {
                            $db->rollBack();
                        }
                        $error = 'No fue posible registrar la entrada. Revisa los datos e intenta nuevamente.';
                    }
                }
            }

            $movimientosRecientes = MovimientoInventario::ultimos('Entrada', 6);

            include __DIR__ . '/../views/inventario/main/entrada.php';
        }

        private function normalizarLineasEntrada(array $post): array
        {
            $lineas = [];

            if (! empty($post['lineas_producto_id']) && is_array($post['lineas_producto_id'])) {
                $productos     = $post['lineas_producto_id'];
                $almacenes     = $post['lineas_almacen_id'] ?? [];
                $cantidades    = $post['lineas_cantidad'] ?? [];
                $observaciones = $post['lineas_observaciones'] ?? [];

                foreach ($productos as $indice => $productoId) {
                    $linea = [
                        'producto_id'   => trim((string) $productoId),
                        'almacen_id'    => trim((string) ($almacenes[$indice] ?? '')),
                        'cantidad'      => trim((string) ($cantidades[$indice] ?? '')),
                        'observaciones' => trim((string) ($observaciones[$indice] ?? '')),
                    ];

                    if ($linea['producto_id'] === '' && $linea['almacen_id'] === '' && $linea['cantidad'] === '' && $linea['observaciones'] === '') {
                        continue;
                    }

                    $lineas[] = $linea;
                }
            } else {
                $linea = [
                    'producto_id'   => trim((string) ($post['producto_id'] ?? '')),
                    'almacen_id'    => trim((string) ($post['almacen_id'] ?? '')),
                    'cantidad'      => trim((string) ($post['cantidad'] ?? '')),
                    'observaciones' => trim((string) ($post['observaciones'] ?? '')),
                ];

                if ($linea['producto_id'] !== '' || $linea['almacen_id'] !== '' || $linea['cantidad'] !== '' || $linea['observaciones'] !== '') {
                    $lineas[] = $linea;
                }
            }

            return $lineas;
        }

        public function crearSalida()
        {
            Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

            $productos    = Producto::all();
            $almacenes    = Almacen::all();
            $msg          = '';
            $error        = '';
            $salidaItems  = [];

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $salidaItems = $this->normalizarLineasSalida($_POST);

                if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                    $error = 'Token CSRF invalido.';
                } elseif (empty($salidaItems)) {
                    $error = 'Agrega al menos un producto a la captura de salida.';
                } else {
                    $db = Database::getInstance()->getConnection();
                    $consumoAcumulado = [];

                    try {
                        $db->beginTransaction();

                        foreach ($salidaItems as $indice => $linea) {
                            $productoId = (int) ($linea['producto_id'] ?? 0);
                            $almacenId  = (int) ($linea['almacen_id'] ?? 0);
                            $cantidad   = isset($linea['cantidad']) ? (float) $linea['cantidad'] : 0;

                            if ($productoId <= 0 || $almacenId <= 0 || $cantidad <= 0) {
                                throw new RuntimeException('La linea ' . ($indice + 1) . ' es invalida.');
                            }

                            $clave = $productoId . ':' . $almacenId;
                            $consumoPrevio = (float) ($consumoAcumulado[$clave] ?? 0);
                            $disponible = Producto::stockEnAlmacen($productoId, $almacenId) - $consumoPrevio;

                            if ($cantidad > $disponible) {
                                throw new RuntimeException('La linea ' . ($indice + 1) . ' supera el stock disponible en el almacen seleccionado.');
                            }

                            $data = [
                                'producto_id'       => $productoId,
                                'tipo'              => 'Salida',
                                'cantidad'          => $cantidad,
                                'usuario_id'        => $_SESSION['user_id'],
                                'almacen_origen_id' => $almacenId,
                                'observaciones'     => trim((string) ($linea['observaciones'] ?? '')),
                            ];

                            if (! MovimientoInventario::registrar($data)) {
                                throw new RuntimeException('No fue posible registrar la linea ' . ($indice + 1) . '.');
                            }

                            Producto::restarStock($productoId, $cantidad, $almacenId);
                            $consumoAcumulado[$clave] = $consumoPrevio + $cantidad;

                            ActivityLogger::log('inventario_salida', 'Salida de inventario registrada', [
                                'producto_id' => $productoId,
                                'almacen_id'  => $almacenId,
                                'cantidad'    => $cantidad,
                                'linea'       => $indice + 1,
                            ]);
                        }

                        $db->commit();

                        $totalLineas = count($salidaItems);
                        $msg = $totalLineas === 1
                            ? 'Salida registrada correctamente.'
                            : 'Se registraron ' . $totalLineas . ' productos en la salida correctamente.';

                        $salidaItems = [];
                        $_POST = [];
                    } catch (\Throwable $e) {
                        if ($db->inTransaction()) {
                            $db->rollBack();
                        }
                        $error = $e->getMessage() ?: 'No fue posible registrar la salida. Revisa los datos e intenta nuevamente.';
                    }
                }
            }

            $movimientosRecientes = MovimientoInventario::ultimos('Salida', 6);

            include __DIR__ . '/../views/inventario/main/salida.php';
        }

        private function normalizarLineasSalida(array $post): array
        {
            $lineas = [];

            if (! empty($post['lineas_producto_id']) && is_array($post['lineas_producto_id'])) {
                $productos     = $post['lineas_producto_id'];
                $almacenes     = $post['lineas_almacen_id'] ?? [];
                $cantidades    = $post['lineas_cantidad'] ?? [];
                $observaciones = $post['lineas_observaciones'] ?? [];

                foreach ($productos as $indice => $productoId) {
                    $linea = [
                        'producto_id'   => trim((string) $productoId),
                        'almacen_id'    => trim((string) ($almacenes[$indice] ?? '')),
                        'cantidad'      => trim((string) ($cantidades[$indice] ?? '')),
                        'observaciones' => trim((string) ($observaciones[$indice] ?? '')),
                    ];

                    if ($linea['producto_id'] === '' && $linea['almacen_id'] === '' && $linea['cantidad'] === '' && $linea['observaciones'] === '') {
                        continue;
                    }

                    $lineas[] = $linea;
                }
            } else {
                $linea = [
                    'producto_id'   => trim((string) ($post['producto_id'] ?? '')),
                    'almacen_id'    => trim((string) ($post['almacen_id'] ?? '')),
                    'cantidad'      => trim((string) ($post['cantidad'] ?? '')),
                    'observaciones' => trim((string) ($post['observaciones'] ?? '')),
                ];

                if ($linea['producto_id'] !== '' || $linea['almacen_id'] !== '' || $linea['cantidad'] !== '' || $linea['observaciones'] !== '') {
                    $lineas[] = $linea;
                }
            }

            return $lineas;
        }

        public function transferencia()
        {
            Session::requireLogin(["Administrador", "Almacen", "Compras"]);

            $productos = Producto::all();
            $almacenes = Almacen::all();
            $msg       = '';
            $error     = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                    $error = 'Token CSRF invalido.';
                } else {
                    $productoId    = isset($_POST['producto_id']) ? (int) $_POST['producto_id'] : 0;
                    $origenId      = isset($_POST['almacen_origen_id']) ? (int) $_POST['almacen_origen_id'] : 0;
                    $destinoId     = isset($_POST['almacen_destino_id']) ? (int) $_POST['almacen_destino_id'] : 0;
                    $cantidad      = isset($_POST['cantidad']) ? (float) $_POST['cantidad'] : 0;
                    $observaciones = trim($_POST['observaciones'] ?? '');

                    $producto = $productoId ? Producto::find($productoId) : null;

                    if ($productoId <= 0 || $origenId <= 0 || $destinoId <= 0 || ! $producto) {
                        $error = "Selecciona un producto y almacenes validos.";
                    } elseif ($origenId === $destinoId) {
                        $error = "El almacen de origen y destino deben ser diferentes.";
                    } elseif ($cantidad <= 0) {
                        $error = "Indica una cantidad mayor a cero.";
                    } else {
                        $disponible = Producto::stockEnAlmacen($productoId, $origenId);
                        if ($cantidad > $disponible) {
                            $error = "La cantidad supera el inventario disponible en el almacen de origen.";
                        } else {
                            $data = [
                                'producto_id'        => $productoId,
                                'tipo'               => 'Transferencia',
                                'cantidad'           => $cantidad,
                                'usuario_id'         => $_SESSION['user_id'],
                                'almacen_origen_id'  => $origenId,
                                'almacen_destino_id' => $destinoId,
                                'observaciones'      => $observaciones,
                            ];

                            if (MovimientoInventario::registrar($data) && Producto::moverStock($productoId, $origenId, $destinoId, $cantidad)) {
                                $restante = Producto::stockEnAlmacen($productoId, $origenId);
                                if ((int) ($producto['almacen_id'] ?? 0) === $origenId && $restante <= 0.0) {
                                    Producto::actualizarAlmacen($productoId, $destinoId);
                                }
                                $msg = "Transferencia registrada correctamente.";
                                ActivityLogger::log('inventario_transferencia', 'Transferencia entre almacenes', [
                                    'producto_id' => $productoId,
                                    'origen'      => $origenId,
                                    'destino'     => $destinoId,
                                    'cantidad'    => $cantidad,
                                ]);
                            } else {
                                $error = "No fue posible registrar la transferencia. Intenta nuevamente.";
                            }
                        }
                    }
                }
            }

            $movimientosRecientes = MovimientoInventario::ultimos('Transferencia', 6);

            include __DIR__ . '/../views/inventario/transferencia.php';
        }

        public function actual()
        {
            Session::requireLogin();

            $_SESSION['menu_items'] = [
                ['slug' => 'catalogo_productos', 'label' => 'Catálogo de Productos', 'icon' => 'fa-solid fa-clipboard-list', 'role' => 'Todos'],
                ['slug' => '', 'label' => 'Rotación de Inventario', 'icon' => 'fa-solid fa-arrows-rotate', 'role' => 'Todos'],
                ['slug' => 'reportes_inventario', 'label' => 'Reportes de Inventario', 'icon' => 'fa-solid fa-chart-pie', 'role' => 'Administrador'],
                ['slug' => 'dashboard_almacen', 'label' => 'Ir a Almacén', 'icon' => 'fa-solid fa-grip', 'role' => 'Almacen'],
                ['slug' => '', 'label' => 'Ir a Dashboard', 'icon' => 'fa-solid fa-grip', 'role' => 'Administrador'],
                ['slug' => 'logout', 'label' => 'Cerrar Sesión', 'icon' => 'fa-solid fa-arrow-right-from-bracket', 'role' => 'Todos']
            ];

            $role = $_SESSION['role'] ?? 'Empleado';

            $filtros = [
                'buscar'           => trim($_GET['buscar'] ?? ($_GET['q'] ?? '')),
                'categoria_id'     => $_GET['categoria_id'] ?? '',
                'almacen_id'       => $_GET['almacen_id'] ?? '',
                'proveedor_id'     => $_GET['proveedor_id'] ?? '',
                'tipo'             => $_GET['tipo'] ?? '',
                'estado'           => $_GET['estado'] ?? '',
                'activo_id'        => $_GET['activo_id'] ?? '',
                'stock_flag'       => $_GET['stock_flag'] ?? '',
                'valor_min'        => $_GET['valor_min'] ?? '',
                'valor_max'        => $_GET['valor_max'] ?? '',
                'fecha_desde'      => $_GET['fecha_desde'] ?? '',
                'fecha_hasta'      => $_GET['fecha_hasta'] ?? '',
                'unidad_medida_id' => $_GET['unidad_medida_id'] ?? '',
                'codigo_barras'    => trim($_GET['codigo_barras'] ?? ''),
            ];

            if (! empty($_GET['cat']) && empty($filtros['categoria_id'])) {
                $filtros['categoria'] = $_GET['cat'];
            }

            $page           = max(1, (int) ($_GET['page'] ?? 1));
            $perPageOptions = [10, 15, 25, 50, 100];
            $perPage        = (int) ($_GET['per_page'] ?? 15);
            if (! in_array($perPage, $perPageOptions, true)) {
                $perPage = 15;
            }
            $offset = ($page - 1) * $perPage;

            $resultado      = Producto::inventarioListado($filtros, $perPage, $offset);
            $productos      = $resultado['items'];
            $stats          = $resultado['stats'];
            $totalRegistros = $resultado['total'];
            $totalPaginas   = max(1, (int) ceil($totalRegistros / $perPage));

            if ($page > $totalPaginas) {
                $page      = $totalPaginas;
                $offset    = ($page - 1) * $perPage;
                $resultado = Producto::inventarioListado($filtros, $perPage, $offset);
                $productos = $resultado['items'];
                $stats     = $resultado['stats'];
            }

            $db          = Database::getInstance()->getConnection();
            $categorias  = $db->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC")->fetchAll();
            $almacenes   = $db->query("SELECT id, nombre FROM almacenes ORDER BY nombre ASC")->fetchAll();
            $proveedores = $db->query("SELECT id, nombre FROM proveedores ORDER BY nombre ASC")->fetchAll();
            $unidades    = $db->query("SELECT id, nombre, abreviacion FROM unidades_medida ORDER BY nombre ASC")->fetchAll();

            $tiposProducto   = Producto::tiposDisponibles();
            $estadosProducto = Producto::estadosDisponibles();
            $estadosActivos  = Producto::estadosActivos();

            $hayFiltros = false;
            foreach ($filtros as $valor) {
                if ($valor !== '' && $valor !== null) {
                    $hayFiltros = true;
                    break;
                }
            }

            include __DIR__ . '/../views/inventario/dashboard_inventario.php';
        }

    public function obtenerCatalogo()
    {
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

        $filtros = [
            'buscar'           => trim($_GET['buscar'] ?? ''),
            'nombre'           => trim($_GET['nombre'] ?? ''),
            'codigo'           => trim($_GET['codigo'] ?? ''),
            'tipo'             => $_GET['tipo'] ?? '',
            'categoria_id'     => $_GET['categoria_id'] ?? '',
            'almacen_id'       => $_GET['almacen_id'] ?? '',
            'proveedor_id'     => $_GET['proveedor_id'] ?? '',
            'estado'           => $_GET['estado'] ?? '',
            'activo_id'        => $_GET['activo_id'] ?? '',
            'stock_flag'       => $_GET['stock_flag'] ?? '',
            'unidad_medida_id' => $_GET['unidad_medida_id'] ?? '',
            'codigo_barras'    => trim($_GET['codigo_barras'] ?? ''),
            'tags'             => trim($_GET['tags'] ?? ''),
            'fecha_desde'      => $_GET['fecha_desde'] ?? '',
            'fecha_hasta'      => $_GET['fecha_hasta'] ?? '',
            'valor_min'        => $_GET['valor_min'] ?? '',
            'valor_max'        => $_GET['valor_max'] ?? '',
        ];

        $page           = max(1, (int) ($_GET['page'] ?? 1));
        $perPageOptions = [10, 15, 25, 50, 100];
        $perPage        = (int) ($_GET['per_page'] ?? 15);
        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 15;
        }
        $offset = ($page - 1) * $perPage;

        $resultado      = Producto::inventarioListado($filtros, $perPage, $offset);
        $productos      = $resultado['items'];
        $stats          = $resultado['stats'];
        $totalRegistros = $resultado['total'];
        $stats['total'] = $totalRegistros;
        $totalPaginas   = max(1, (int) ceil($totalRegistros / $perPage));

        if ($page > $totalPaginas) {
            $page      = $totalPaginas;
            $offset    = ($page - 1) * $perPage;
            $resultado = Producto::inventarioListado($filtros, $perPage, $offset);
            $productos = $resultado['items'];
            $stats     = $resultado['stats'];
            $stats['total'] = $resultado['total'];
        }

        $db              = Database::getInstance()->getConnection();
        $categorias      = $db->query('SELECT id, nombre FROM categorias ORDER BY nombre ASC')->fetchAll();
        $almacenes       = $db->query('SELECT id, nombre FROM almacenes ORDER BY nombre ASC')->fetchAll();
        $proveedores     = $db->query('SELECT id, nombre FROM proveedores ORDER BY nombre ASC')->fetchAll();
        $unidades        = $db->query('SELECT id, nombre, abreviacion FROM unidades_medida ORDER BY nombre ASC')->fetchAll();
        $estadosActivos  = Producto::estadosActivos();
        $estadosProducto = Producto::estadosDisponibles();
        $tiposProducto   = Producto::tiposDisponibles();

        $hayFiltros = false;
        foreach ($filtros as $valor) {
            if ($valor !== '' && $valor !== null) {
                $hayFiltros = true;
                break;
            }
        }

        $alerta = [
            'success' => $_GET['success'] ?? null,
            'deleted' => $_GET['deleted'] ?? null,
        ];

        $importAlert = $_SESSION['productos_import'] ?? null;
        if (isset($_SESSION['productos_import'])) {
            unset($_SESSION['productos_import']);
        }

        include __DIR__ . '/../views/inventario/catalogo_productos.php';
    }

    public function crearProducto()
    {
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

        $db              = Database::getInstance()->getConnection();
        $categorias      = $db->query('SELECT id, nombre FROM categorias ORDER BY nombre ASC')->fetchAll();
        $proveedores     = $db->query('SELECT id, nombre FROM proveedores ORDER BY nombre ASC')->fetchAll();
        $almacenes       = $db->query('SELECT id, nombre FROM almacenes ORDER BY nombre ASC')->fetchAll();
        $unidades        = $db->query('SELECT id, nombre, abreviacion FROM unidades_medida ORDER BY nombre ASC')->fetchAll();
        $estadosProducto = Producto::estadosDisponibles();
        $tiposProducto   = Producto::tiposDisponibles();

        $errors = [];
        $data   = $this->defaultProductoData();
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $errors[] = 'Token CSRF invalido.';
            } else {
                $data = $this->collectProductoData($_POST, $errors);

                if (Producto::findByCodigo($data['codigo'])) {
                    $errors[] = 'Ya existe un producto con ese codigo.';
                }

                if ($data['codigo_barras'] === '') {
                    $data['codigo_barras'] = $this->generarCodigoBarras($data['codigo']);
                }

                $nuevaImagen = $this->handleImagenUpload($_FILES['imagen_url'] ?? null, $errors);
                if ($nuevaImagen === false) {
                    $errors[] = 'No fue posible procesar la imagen adjunta.';
                } elseif (is_string($nuevaImagen)) {
                    $data['imagen_url'] = $nuevaImagen;
                }

                if (empty($errors)) {
                    $payload                              = $data;
                    $payload['last_requested_by_user_id'] = null;
                    $payload['last_request_date']         = null;

                    Producto::create($payload);
                    ActivityLogger::log('producto_creado', 'Se registro el producto ' . $payload['nombre'], [
                        'codigo' => $payload['codigo'],
                    ]);
                    header('Location: productos.php?success=1');
                    exit();
                }
            }
        }

        if (! empty($errors)) {
            $error = implode(PHP_EOL, $errors);
        }

        include __DIR__ . '/../views/inventario/crear_producto.php';
    }

    private function defaultProductoData(): array
    {
        return [
            'codigo'                    => '',
            'codigo_barras'             => '',
            'nombre'                    => '',
            'descripcion'               => '',
            'proveedor_id'              => null,
            'categoria_id'              => null,
            'peso'                      => null,
            'ancho'                     => null,
            'alto'                      => null,
            'profundidad'               => null,
            'unidad_medida_id'          => null,
            'clase_categoria'           => '',
            'marca'                     => '',
            'color'                     => '',
            'forma'                     => '',
            'especificaciones_tecnicas' => '',
            'origen'                    => '',
            'costo_compra'              => 0.0,
            'precio_venta'              => 0.0,
            'stock_minimo'              => 0.0,
            'stock_actual'              => 0.0,
            'almacen_id'                => null,
            'ubicacion_fisica'          => '',
            'estado'                    => 'Nuevo',
            'tipo'                      => 'Consumible',
            'imagen_url'                => null,
            'tags'                      => '',
            'activo_id'                 => 1,
        ];
    }
    
}
