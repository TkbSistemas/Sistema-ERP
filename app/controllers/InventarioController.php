<?php
    require_once __DIR__ . '/../models/MovimientoInventario.php';
    require_once __DIR__ . '/../models/Producto.php';
    require_once __DIR__ . '/../models/Almacen.php';
    require_once __DIR__ . '/../helpers/Session.php';
    require_once __DIR__ . '/../helpers/Database.php';
    require_once __DIR__ . '/../helpers/ActivityLogger.php';
    require_once __DIR__ . '/../helpers/BarcodeGenerator.php';

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
                        $_SESSION['alerta'] = [
                            'tipo' => 'error',
                            'titulo' => 'Error de Registro',
                            'mensaje' => 'Falló al Guardar .',
                        ];
                        $error = 'No fue posible registrar la entrada. Revisa los datos e intenta nuevamente.';
                    }
                }
            }

            $movimientosRecientes = MovimientoInventario::ultimos('Entrada', 6);

            
            $_SESSION['alerta'] = [
                'tipo' => 'success',
                'titulo' => 'Registro Creado',
                'mensaje' => 'Entrada Registrada Exitosamente.',
            ];

            include __DIR__ . '/../views/inventario/registrar_entrada.php';
        }

        private function normalizarLineasEntrada(array $post): array
        {
            $lineas = [];

            if (! empty($post['lineas_producto_id']) && is_array($post['lineas_producto_id'])) {
                $productos     = $post['lineas_producto_id'];
                $almacenes     = $post['lineas_almacen_id'] ?? [];
                $cantidades    = $post['lineas_cantidad'] ?? [];
                $observaciones = $post['lineas_observaciones'] ?? [];
                $folios        = $post['lineas_folio'] ?? [];

                foreach ($productos as $indice => $productoId) {
                    $linea = [
                        'producto_id'   => trim((string) $productoId),
                        'almacen_id'    => trim((string) ($almacenes[$indice] ?? '')),
                        'cantidad'      => trim((string) ($cantidades[$indice] ?? '')),
                        'observaciones' => trim((string) ($observaciones[$indice] ?? '')),
                        'folio'          => trim((string) ($folios[$indice] ?? '')),
                    ];

                    if ($linea['producto_id'] === '' && $linea['almacen_id'] === '' && $linea['cantidad'] === '' && $linea['observaciones'] === '' && $linea['folio'] === '') {
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
                    'folio'        => trim((string) ($post['folio'] ?? ''))
                ];

                if ($linea['producto_id'] !== '' || $linea['almacen_id'] !== '' || $linea['cantidad'] !== '') {
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
                ['slug' => 'dashboard_inventario', 'label' => 'Inventario General', 'icon' => 'fa-solid fa-outdent', 'role' => 'Todos'],
                ['slug' => 'catalogo_productos', 'label' => 'Catálogo de Productos', 'icon' => 'fa-solid fa-clipboard-list', 'role' => 'Todos'],
                ['slug' => 'rotacion_inventario', 'label' => 'Rotación de Inventario', 'icon' => 'fa-solid fa-arrows-rotate', 'role' => 'Todos'],
                ['slug' => 'reportes_inventario', 'label' => 'Reportes de Inventario', 'icon' => 'fa-solid fa-chart-pie', 'role' => 'Administrador'],
                ['slug' => '', 'label' => 'Auditar Inventario', 'icon' => 'fa-solid fa-house-circle-exclamation', 'role' => 'Todos'],
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
            $categorias  = $db->query("SELECT id, nombre FROM catalogo_categorias_inventario ORDER BY nombre ASC")->fetchAll();
            $almacenes   = $db->query("SELECT id, nombre FROM almacenes ORDER BY nombre ASC")->fetchAll();
            //$proveedores = $db->query("SELECT id, nombre FROM proveedores ORDER BY nombre ASC")->fetchAll();
            $unidades    = $db->query("SELECT id, nombre, apodo FROM catalogo_unidades_medida ORDER BY nombre ASC")->fetchAll();

            $tiposProducto   = Producto::tiposDisponibles();
            $estadosProducto = Producto::estadosDisponibles();

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
            'estatus'           => $_GET['estatus'] ?? '',
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
        $categorias      = $db->query('SELECT id, nombre FROM catalogo_categorias_inventario ORDER BY nombre ASC')->fetchAll();
        $almacenes       = $db->query('SELECT id, nombre FROM almacenes ORDER BY nombre ASC')->fetchAll();
        $unidades        = $db->query('SELECT id, nombre, apodo FROM catalogo_unidades_medida ORDER BY nombre ASC')->fetchAll();
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
        $categorias      = $db->query('SELECT id, nombre FROM catalogo_categorias_inventario ORDER BY nombre ASC')->fetchAll();
        $almacenes       = $db->query('SELECT id, nombre FROM almacenes ORDER BY nombre ASC')->fetchAll();
        $unidades        = $db->query('SELECT id, nombre, apodo, sistema FROM catalogo_unidades_medida ORDER BY nombre ASC')->fetchAll();
        $tiposProducto   = Producto::tiposDisponibles();

        $errors = [];
        $data   = [];
        $values  = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $errors[] = 'Token CSRF Invalido.';
                $mensaje_error = 'TOKEN CSRF NO VALIDO.';
            } else {
                $data = $this->collectProductoData($_POST, $errors);
                $values = $data;

                $codigoFabricante = $data['codigo_fabricante'] ?? '';
                $codigoBarras     = $data['codigos_barras'] ?? '';
                $numSerie         = $data['num_serie'] ?? '';

                if (empty($codigoFabricante) && empty($codigoBarras)) {
                    $errors[] = 'Debes proporcionar al Menos uno de los Siguientes Identificadores: Código del Fabricante o Código de Barras.';
                }

                if (!empty($data['codigo_fabricante']) && Producto::findByCodigo($data['codigo_fabricante'])) {
                    $errors[] = 'Ya Existe un Producto con Ese Código de Fabricante.';
                }

                if (!empty($data['codigos_barras']) && Producto::findByCodigoBarras($data['codigos_barras'])) {
                    $errors[] = 'Ya Existe un Producto con Ese Código de Barras.';
                }

                $tasaCambioUsd = 18;

                $precioMxnRaw = $input['precio_unitario'] ?? '';
                $precioUsdRaw = $input['precio_unitario_usd'] ?? '';

                $hasMxn = ($precioMxnRaw !== '' && is_numeric($precioMxnRaw));
                $hasUsd = ($precioUsdRaw !== '' && is_numeric($precioUsdRaw));

                $precioFinalMxn = 0.00;

                if ($hasMxn) {
                    $precioFinalMxn = (float) $precioMxnRaw;
                } elseif ($hasUsd) {
                    $precioFinalMxn = (float) $precioUsdRaw * $tasaCambioUsd;
                }

                $precioFinalMxn = max(0.00, $precioFinalMxn);

                $nuevaImagen = $this->handleImagenUpload($_FILES['imagen_url'] ?? null, $errors);
                if ($nuevaImagen === false) {
                    $errors[] = 'No Fue Posible Procesar la Imagen Adjunta.';
                    $mensaje_error = 'No Fue Posible Procesar la Imagen Adjunta.';
                } elseif (is_string($nuevaImagen)) {
                    $data['imagen_url'] = $nuevaImagen;
                }

                if (empty($errors)) {
                    $payload = $data;
                    Producto::create($payload);
                    $_SESSION['alerta'] = [
                        'tipo' => 'success',
                        'titulo' => 'Éxito al Crear',
                        'mensaje' => 'Producto Añadido al Catálogo',
                    ];
                    header('Location: producto_nuevo');
                    exit();
                }
            }
            if (! empty($errors)) {
                $error = implode(PHP_EOL, $errors);
                $_SESSION['alerta'] = [
                    'tipo' => 'error',
                    'titulo' => 'Error en el Registro',
                    'mensaje' => $mensaje_error,
                ];
            }
        }
        include __DIR__ . '/../views/inventario/crear_producto.php';
    }

    private function generarCodigoBarras(string $codigoBase = '', ?int $ignorarId = null): string
    {
        $base = strtoupper(preg_replace('/[^A-Z0-9]/', '', $codigoBase));
        if ($base === '') {
            $base = 'PRD';
        }
        $base  = substr($base, 0, 8);
        $fecha = date('ymd');

        for ($intentos = 0; $intentos < 8; $intentos++) {
            try {
                $random = strtoupper(bin2hex(random_bytes(3)));
            } catch (\Throwable $e) {
                $random = strtoupper(str_pad(dechex(random_int(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT));
            }

            $candidate = $base . '-' . $fecha . '-' . $random;
            if (! Producto::codigoBarrasExiste($candidate, $ignorarId)) {
                return $candidate;
            }
        }

        do {
            try {
                $random = strtoupper(bin2hex(random_bytes(4)));
            } catch (\Throwable $e) {
                $random = strtoupper(str_pad(dechex(random_int(0, 0xFFFFFFFF)), 8, '0', STR_PAD_LEFT));
            }
            $candidate = $base . '-' . $fecha . '-' . $random;
        } while (Producto::codigoBarrasExiste($candidate, $ignorarId));

        return $candidate;
    }

    public static function generarSku()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT MAX(id) AS max_id FROM inventario");
        $row = $stmt->fetch();
        $nextId = (int) ($row['max_id'] ?? 0) + 1; //Genera algo como TAKAB-000001, TAKAB-000002, etc.
        return 'TAKAB-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }


    private function collectProductoData(array $input, array &$errors, ?int $productoId = null): array
    {
        $data['codigo_fabricante'] = strtoupper(trim($input['codigo_fabricante'] ?? ''));
        if (mb_strlen($data['codigo_fabricante']) > 50) {
            $errors[] = 'El Código es Demasiado Largo.';
        } elseif (! preg_match('/^[A-Z0-9][A-Z0-9_.-]*$/', $data['codigo_fabricante'])) {
            $errors[] = 'El Código Solo Puede Contener Letras, Números, Guion (-), Guion Bajo (_) o Punto (.) Sin Espacios.';
        }

        $data['sku'] = self::generarSku();

        $data['codigo_barras'] = strtoupper(trim($input['codigo_barras'] ?? ''));
        if ($data['codigo_barras'] !== '') {
            if (mb_strlen($data['codigo_barras']) > 64) {
                $errors[] = 'El Código de Barras es Demasiado Largo.';
            } elseif (! preg_match('/^[A-Z0-9\\-_.]+$/', $data['codigo_barras'])) {
                $errors[] = 'El Código de Barras Solo Puede Contener Letras, Números, Guión (-), Guion Bajo (_) o Punto (.) Sin Espacios.';
            } elseif (Producto::codigoBarrasExiste($data['codigo_barras'], $productoId)) {
                $errors[] = 'Ya Existe un Producto con Ese Código de Barras.';
            }
        }

        $data['nombre'] = trim($input['nombre'] ?? '');
        if ($data['nombre'] === '') {
            $errors[] = 'El Nombre Del Producto es Obligatorio.';
        } elseif (mb_strlen($data['nombre']) > 100) {
            $errors[] = 'El Nombre es Demasiado Largo.';
        }

        $data['descripcion'] = trim($input['descripcion'] ?? '');
        if (mb_strlen($data['descripcion']) > 255) {
            $errors[] = 'La Descripción es Demasiado Larga.';
        }

        $data['categoria_id']     = $this->toNullableInt($input['categoria_id'] ?? null);
        $data['unidad_medida_id'] = $this->toNullableInt($input['unidad_medida_id'] ?? null);
        $data['almacen_id']       = $this->toNullableInt($input['almacen_id'] ?? null);
        if (empty($data['almacen_id'])) {
            $errors[] = 'Debes Seleccionar un Almacén Asignado.';
        }
        $data['ubicacion_fisica'] = trim($input['ubicacion_fisica'] ?? '');
        if (mb_strlen($data['ubicacion_fisica']) > 150) {
            $errors[] = 'La Ubicación Física es Demasiado Larga.';
        }

        $data['marca']                     = trim($input['marca'] ?? '');
        $data['modelo']                     = trim($input['modelo'] ?? '');
        $data['color']                     = trim($input['color'] ?? '');
        $data['pais_origen']                    = trim($input['pais_origen'] ?? '');
        $data['tags']                      = trim($input['tags'] ?? '');


        $data['stock_minimo'] = $this->normalizeDecimal($input['stock_minimo'] ?? 0);
        if ($data['stock_minimo'] === null || $data['stock_minimo'] < 0) {
            $errors[] = 'El Stock Mínimo Debe Ser un Número Mayor o Igual a Cero.';
        }

        $data['precio_unitario'] = $this->normalizeDecimal($input['precio_unitario'] ?? 0);
        if ($data['precio_unitario'] === null || $data['precio_unitario'] < 0) {
            $errors[] = 'El precio Unitario Debe Ser un Número Mayor o Igual a Cero.';
        }

        $data['precio_venta'] = $this->normalizeDecimal($input['precio_venta'] ?? 0);
        if ($data['precio_venta'] === null || $data['precio_venta'] < 0) {
            $errors[] = 'El Precio de Venta Debe Ser un Número Mayor o Igual a Cero.';
        }

        $data['tipo'] = $input['tipo'] ?? 'Consumible';
        if (! in_array($data['tipo'], Producto::tiposDisponibles(), true)) {
            $errors[] = 'El tipo Seleccionado no es Valido.';
        }

        return $data;
    }

    private function filaVacia(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function normalizeDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        $normalized = str_replace(',', '.', (string) $value);
        return is_numeric($normalized) ? round((float) $normalized, 2) : null;
    }

    private function handleImagenUpload(?array $file, array &$errors, ?string $existingPath = null)
    {
        if (! $file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $existingPath;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors[] = 'Error al cargar la imagen (codigo ' . ($file['error'] ?? 'desconocido') . ').';
            return false;
        }

        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            $errors[] = 'La imagen no debe superar los 5 MB.';
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];

        if (! $mime || ! isset($allowed[$mime])) {
            $errors[] = 'El formato de imagen no es valido.';
            return false;
        }

        // Guardamos en carpeta persistente de uploads (evita sobrescribir assets estaticos)
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/productos/';
        if (! is_dir($uploadDir) && ! mkdir($uploadDir, 0775, true) && ! is_dir($uploadDir)) {
            $errors[] = 'No fue posible preparar el directorio de imagenes.';
            return false;
        }

        $filename    = uniqid('prod_', true) . '.' . $allowed[$mime];
        $destination = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if (! move_uploaded_file($file['tmp_name'], $destination)) {
            $errors[] = 'No fue posible guardar la imagen subida.';
            return false;
        }

        // Guardamos ruta relativa para servir desde /public
        return 'uploads/productos/' . $filename;
    }

    private function toNullableInt($value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        return (int) $value ?: null;
    }

    private function toNullableFloat($value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $normalized = str_replace(',', '.', (string) $value);
        return is_numeric($normalized) ? (float) $normalized : null;
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
            'precio_unitario'           => 0.0,
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

    public function downloadTemplate(): void
    {
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

        $columns = [
            'codigo',
            'codigo_barras',
            'nombre',
            'descripcion',
            'tipo',
            'estado',
            'categoria_id',
            'proveedor_id',
            'almacen_id',
            'ubicacion_fisica',
            'unidad_medida_id',
            'stock_actual',
            'stock_minimo',
            'precio_unitario',
            'precio_beneficio',
            'peso',
            'ancho',
            'alto',
            'profundidad',
            'marca',
            'color',
            'forma',
            'origen',
            'tags',
        ];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=plantilla_productos_' . date('Ymd_His') . '.csv');

        $output = fopen('php://output', 'w');
        fputs($output, chr(239) . chr(187) . chr(191));
        fputcsv($output, $columns);
        fclose($output);
        ActivityLogger::log('productos_template', 'Descarga de plantilla de productos');
    }

    public function importar(): void{
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: catalogo_productos');
            return;
        }

        if (!Session::checkCsrf($_POST['csrf'] ?? '')) {
            $_SESSION['productos_import'] = [
                'processed' => 0,
                'success'   => 0,
                'skipped'   => 0,
                'errors'    => ['Token CSRF Invalido.'],
            ];
            header('Location: catalogo_productos');
            return;
        }

        if (empty($_FILES['archivo']['tmp_name']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['productos_import'] = [
                'processed' => 0,
                'success'   => 0,
                'skipped'   => 0,
                'errors'    => ['Se Debe Seleccionar un Archivo CSV Válido.'],
            ];
            header('Location: catalogo_productos');
            return;
        }

        $handle = fopen($_FILES['archivo']['tmp_name'], 'r');
        if (! $handle) {
            $_SESSION['productos_import'] = [
                'processed' => 0,
                'success'   => 0,
                'skipped'   => 0,
                'errors'    => ['Imposible Leer el Archivo.'],
            ];
            header('Location: catalogo_productos');
            return;
        }

        $columns = fgetcsv($handle);
        if (! $columns) {
            fclose($handle);
            $_SESSION['productos_import'] = [
                'processed' => 0,
                'success'   => 0,
                'skipped'   => 0,
                'errors'    => ['Archivo Vacío.'],
            ];
            header('Location: catalogo_productos');
            return;
        }

        $columns = array_map('trim', $columns);
        $map     = array_flip($columns);

        $result = [
            'processed' => 0,
            'success'   => 0,
            'skipped'   => 0,
            'errors'    => [],
        ];

        $tiposValidos   = Producto::tiposDisponibles();
        $estadosValidos = Producto::estadosDisponibles();

        while (($row = fgetcsv($handle)) !== false) {
            $result['processed']++;
            $lineNumber = $result['processed'] + 1;

            $rowAssoc = [];
            foreach ($map as $col => $index) {
                $rowAssoc[$col] = $row[$index] ?? null;
            }

            if ($this->filaVacia($rowAssoc)) {
                $result['skipped']++;
                continue;
            }

            $codigo = strtoupper(trim($rowAssoc['codigo'] ?? ''));
            if ($codigo === '') {
                $result['errors'][] = "Fila {$lineNumber}: el campo 'codigo' es obligatorio.";
                continue;
            }

            if (Producto::findByCodigo($codigo)) {
                $result['errors'][] = "Fila {$lineNumber}: el codigo ya existe.";
                continue;
            }

            $nombre = trim($rowAssoc['nombre'] ?? '');
            if ($nombre === '') {
                $result['errors'][] = "Fila {$lineNumber}: el campo 'nombre' es obligatorio.";
                continue;
            }

            $tipo = ucfirst(strtolower(trim($rowAssoc['tipo'] ?? '')));
            if (! in_array($tipo, $tiposValidos, true)) {
                $result['errors'][] = "Fila {$lineNumber}: el tipo '{$rowAssoc['tipo']}' no es valido. Valores permitidos: " . implode(', ', $tiposValidos) . '.';
                continue;
            }

            $estado = ucfirst(strtolower(trim($rowAssoc['estado'] ?? '')));
            if ($estado === '') {
                $estado = 'Nuevo';
            }
            if (! in_array($estado, $estadosValidos, true)) {
                $result['errors'][] = "Fila {$lineNumber}: el estado '{$rowAssoc['estado']}' no es valido. Valores permitidos: " . implode(', ', $estadosValidos) . '.';
                continue;
            }

            $almacenId = (int) ($rowAssoc['almacen_id'] ?? 0);
            if ($almacenId <= 0) {
                $result['errors'][] = "Fila {$lineNumber}: el campo 'almacen_id' debe ser un numero valido.";
                continue;
            }

            $stockActual = (float) str_replace(',', '.', $rowAssoc['stock_actual'] ?? 0);
            $stockMinimo = (float) str_replace(',', '.', $rowAssoc['stock_minimo'] ?? 0);
            if ($stockActual < 0 || $stockMinimo < 0) {
                $result['errors'][] = "Fila {$lineNumber}: el stock no puede ser negativo.";
                continue;
            }

            $costoCompra = (float) str_replace(',', '.', $rowAssoc['costo_compra'] ?? 0);
            $precioVenta = (float) str_replace(',', '.', $rowAssoc['precio_venta'] ?? 0);

            $payload = [
                'codigo'                    => $codigo,
                'codigo_barras'             => trim($rowAssoc['codigo_barras'] ?? ''),
                'nombre'                    => $nombre,
                'descripcion'               => trim($rowAssoc['descripcion'] ?? ''),
                'proveedor_id'              => $this->toNullableInt($rowAssoc['proveedor_id'] ?? null),
                'categoria_id'              => $this->toNullableInt($rowAssoc['categoria_id'] ?? null),
                'peso'                      => $this->toNullableFloat($rowAssoc['peso'] ?? null),
                'ancho'                     => $this->toNullableFloat($rowAssoc['ancho'] ?? null),
                'alto'                      => $this->toNullableFloat($rowAssoc['alto'] ?? null),
                'profundidad'               => $this->toNullableFloat($rowAssoc['profundidad'] ?? null),
                'unidad_medida_id'          => $this->toNullableInt($rowAssoc['unidad_medida_id'] ?? null),
                'clase_categoria'           => trim($rowAssoc['clase_categoria'] ?? ''),
                'marca'                     => trim($rowAssoc['marca'] ?? ''),
                'color'                     => trim($rowAssoc['color'] ?? ''),
                'forma'                     => trim($rowAssoc['forma'] ?? ''),
                'especificaciones_tecnicas' => trim($rowAssoc['especificaciones_tecnicas'] ?? ''),
                'origen'                    => trim($rowAssoc['origen'] ?? ''),
                'costo_compra'              => $costoCompra,
                'precio_venta'              => $precioVenta,
                'stock_minimo'              => $stockMinimo,
                'stock_actual'              => $stockActual,
                'almacen_id'                => $almacenId,
                'ubicacion_fisica'          => trim($rowAssoc['ubicacion_fisica'] ?? ''),
                'estado'                    => $estado,
                'tipo'                      => $tipo,
                'imagen_url'                => null,
                'last_requested_by_user_id' => null,
                'last_request_date'         => null,
                'tags'                      => trim($rowAssoc['tags'] ?? ''),
                'activo_id'                 => 1,
            ];

            if ($payload['codigo_barras'] === '') {
                $payload['codigo_barras'] = $this->generarCodigoBarras($payload['codigo']);
            } elseif (Producto::codigoBarrasExiste($payload['codigo_barras'])) {
                $result['errors'][] = "Fila {$lineNumber}: el codigo de barras ya existe.";
                continue;
            }

            try {
                Producto::create($payload);
                $result['success']++;
            } catch (\Throwable $e) {
                $result['errors'][] = "Fila {$lineNumber}: error al registrar el producto ({$e->getMessage()}).";
            }
        }

        fclose($handle);

        ActivityLogger::log('productos_import', 'Importación de Cátalogo Finalizada', [
            'exitosos'   => $result['success'],
            'procesados' => $result['processed'],
            'omitidos'   => $result['skipped'],
        ]);

        $_SESSION['productos_import'] = $result;
        header('Location: producto_nuevo');
        exit();
    }

    public function ver_producto($id){
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);
        
        $id = (int) $id;
        $producto = Producto::find($id);
        if (! $producto) {
            $_SESSION['alerta'] = [
                'tipo'    => 'error',
                'titulo'  => 'No Encontrado',
                'mensaje' => 'El Producto Solicitado No Existe o Fue Eliminado.'
            ];
            header('Location: inventario');
            exit;
        }
        include __DIR__ . '/../views/inventario/ver_producto.php';
    }

    public function eliminarProducto($id, $active)
    {
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ! Session::checkCsrf($_POST['csrf'] ?? '')) {
            header('Location: catalogo_productos?error=csrf');
            exit();
        }

        Producto::setActive($id, (int) $active);
        ActivityLogger::log('producto_estado', 'Se Eliminó el Producto', [
            'producto_id' => (int) $id,
            'activo'      => (bool) $active,
        ]);
        header('Location: catalogo_productos');
        exit();
    }

    public function editarProducto($id)
    {
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

        $producto = Producto::find($id);
        if (! $producto) {
            die('Producto no encontrado.');
        }

        $db              = Database::getInstance()->getConnection();
        $categorias      = $db->query('SELECT id, nombre FROM categorias ORDER BY nombre ASC')->fetchAll();
        $proveedores     = $db->query('SELECT id, nombre FROM proveedores ORDER BY nombre ASC')->fetchAll();
        $almacenes       = $db->query('SELECT id, nombre FROM almacenes ORDER BY nombre ASC')->fetchAll();
        $unidades        = $db->query('SELECT id, nombre, abreviacion FROM unidades_medida ORDER BY nombre ASC')->fetchAll();
        $estadosProducto = Producto::estadosDisponibles();
        $tiposProducto   = Producto::tiposDisponibles();

        $errors = [];
        $data   = array_merge($this->defaultProductoData(), $producto);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $errors[] = 'Token CSRF invalido.';
            } else {
                $data = $this->collectProductoData($_POST, $errors, (int) $id);

                $existente = Producto::findByCodigo($data['codigo_fabricante'] ?? '');
                if ($existente && (int) $existente['id'] !== (int) $id) {
                    $errors[] = 'Ya Existe Otro Producto con ese Código de Fabricante.';
                }

                $existente = Producto::findByCodigoBarras($data['codigos_barras'] ?? '');
                if ($existente && (int) $existente['id'] !== (int) $id) { // Verificamos si el producto existente no es el mismo que estamos editando
                    $errors[] = 'Ya Existe Otro Producto con ese Código de Barras.';
                }

                $nuevaImagen = $this->handleImagenUpload($_FILES['imagen_url'] ?? null, $errors, $producto['imagen_url'] ?? null);
                if ($nuevaImagen === false) {
                    $errors[] = 'No Fue Posible Procesar la Imagen Adjunta.';
                } elseif (is_string($nuevaImagen)) {
                    $data['imagen_url'] = $nuevaImagen;
                } else {
                    $data['imagen_url'] = $producto['imagen_url'];
                }

                if (empty($errors)) {
                    Producto::update($id, $data);
                    ActivityLogger::log('producto_actualizado', 'Se actualizo el producto ' . $data['nombre'], [
                        'codigo' => $data['codigo'],
                    ]);
                    $_SESSION['alerta'] = [
                        'tipo' => 'success',
                        'titulo' => 'Éxito al Editar',
                        'mensaje' => 'Producto Editado en el Catálogo',
                    ];
                    header('Location: catalogo_productos');
                    exit();
                }
            }
        }
        $error = empty($errors) ? '' : implode(PHP_EOL, $errors);
        include __DIR__ . '/../views/inventario/editar_producto.php';
    }

    public function obtenerEtiqueta($id){
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);
        $producto = Producto::find($id);
        if (! $producto) {
            die('Producto No Encontrado.');
        }

        $db = Database::getInstance()->getConnection();
        $almacenes = $db->query('SELECT id, nombre FROM almacenes ORDER BY nombre ASC')->fetchAll();

        if (empty($producto['codigo_barras'])) {
            $nuevoCodigo = $this->generarCodigoBarras($producto['codigo'] ?? '', (int) $id);
            Producto::actualizarCodigoBarras((int) $id, $nuevoCodigo);
            $producto['codigo_barras'] = $nuevoCodigo;
        }

        $unidadSugerida = $producto['unidad_abreviacion'] ?? $producto['unidad_medida_nombre'] ?? '';
        $error          = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $error = 'Token CSRF Invalido.';
            } else {
                $lote           = trim($_POST['lote'] ?? '');
                $almacenId      = (int) ($_POST['almacen_id'] ?? 0);
                $cantidad       = max(1, min(50, (int) ($_POST['cantidad'] ?? 1)));
                $unidadEtiqueta = trim($_POST['unidad_etiqueta'] ?? $unidadSugerida);

                $almacenNombre = $producto['almacen'] ?? '';
                foreach ($almacenes as $almacen) {
                    if ((int) $almacen['id'] === $almacenId) {
                        $almacenNombre = $almacen['nombre'];
                        break;
                    }
                }

                $labels = [];
                for ($i = 0; $i < $cantidad; $i++) {
                    $labels[] = [
                        'nombre'        => $producto['nombre'],
                        'codigo'        => $producto['codigo'],
                        'codigo_barras' => $producto['codigo_barras'],
                        'almacen'       => $almacenNombre !== '' ? $almacenNombre : 'N/D',
                        'lote'          => $lote !== '' ? $lote : 'N/D',
                        'unidad'        => $unidadEtiqueta !== '' ? $unidadEtiqueta : 'N/D',
                    ];
                }

                try {
                    $pdf = $this->buildEtiquetasPdf($labels);
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: inline; filename=etiquetas_producto_' . preg_replace('/[^A-Za-z0-9_-]/', '', $producto['codigo'] ?? 'producto') . '.pdf');
                    echo $pdf;
                    return;
                } catch (\Throwable $e) {
                    $error = 'No fue posible generar el PDF de Etiquetas.';
                }
            }
        }

        include __DIR__ . '/../views/inventario/etiqueta_producto.php';
    }

    private function buildEtiquetasPdf(array $labels): string{
        $pageWidth            = 226.0;
        $pageHeight           = 170.0;
        $objects              = [];
        $objects[1]           = '<< /Type /Catalog /Pages 2 0 R >>';
        $fontObjNum           = 3;
        $objects[$fontObjNum] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $pageRefs             = [];

        if (empty($labels)) {
            $labels[] = [
                'nombre'        => 'Etiqueta',
                'codigo'        => '',
                'codigo_barras' => '',
                'almacen'       => '',
                'lote'          => '',
                'unidad'        => '',
            ];
        }

        foreach ($labels as $label) {
            $content                 = $this->renderEtiquetaContent($label, $pageWidth, $pageHeight);
            $contentObjNum           = count($objects) + 1;
            $objects[$contentObjNum] = $this->wrapStream($content);
            $pageObjNum              = $contentObjNum + 1;
            $objects[$pageObjNum]    = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 %d 0 R >> >> /Contents %d 0 R >>', $pageWidth, $pageHeight, $fontObjNum, $contentObjNum);
            $pageRefs[]              = $pageObjNum . ' 0 R';
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageRefs) . '] /Count ' . count($pageRefs) . ' >>';

        $pdf = "%PDF-1.4
";
        $offsets     = [];
        $objectCount = count($objects);

        for ($i = 1; $i <= $objectCount; $i++) {
            $offsets[$i] = strlen($pdf);
            $pdf .= $i . " 0 obj
" . $objects[$i] . "
endobj
";
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref
0 " . ($objectCount + 1) . "
";
        $pdf .= "0000000000 65535 f
";
        for ($i = 1; $i <= $objectCount; $i++) {
            $pdf .= sprintf("%010d 00000 n
", $offsets[$i]);
        }
        $pdf .= "trailer << /Size " . ($objectCount + 1) . " /Root 1 0 R >>
";
        $pdf .= "startxref
" . $xrefPosition . "
%%EOF";

        return $pdf;
    }

    private function renderEtiquetaContent(array $label, float $pageWidth, float $pageHeight): string
    {
        $nombre       = $label['nombre'] ?? '';
        $codigo       = $label['codigo'] ?? '';
        $codigoBarras = $label['codigo_barras'] ?? '';
        $almacen      = $label['almacen'] ?? '';
        $lote         = $label['lote'] ?? '';
        $unidad       = $label['unidad'] ?? '';

        $lines   = [];
        $lines[] = 'BT';
        $lines[] = '/F1 12 Tf';
        $lines[] = sprintf('1 0 0 1 %.2f %.2f Tm (%s) Tj', 20.0, $pageHeight - 30.0, $this->escapePdfText($nombre));
        $lines[] = sprintf('1 0 0 1 %.2f %.2f Tm (Codigo: %s) Tj', 20.0, $pageHeight - 48.0, $this->escapePdfText($codigo));
        $lines[] = sprintf('1 0 0 1 %.2f %.2f Tm (Lote: %s) Tj', 20.0, $pageHeight - 66.0, $this->escapePdfText($lote));
        $lines[] = sprintf('1 0 0 1 %.2f %.2f Tm (Almacen: %s) Tj', 20.0, $pageHeight - 84.0, $this->escapePdfText($almacen));
        $lines[] = sprintf('1 0 0 1 %.2f %.2f Tm (Unidad: %s) Tj', 20.0, $pageHeight - 102.0, $this->escapePdfText($unidad));
        $lines[] = 'ET';

        try {
            $pattern = BarcodeGenerator::code39Pattern($codigoBarras);
        } catch (\Throwable $e) {
            $pattern = [];
        }

        if (! empty($pattern)) {
            $lines[] = '0 0 0 rg';
            $lines[] = rtrim($this->barcodeRectangles($pattern, 20.0, 48.0, 1.2, 38.0));
            $lines[] = 'BT';
            $lines[] = '/F1 10 Tf';
            $lines[] = sprintf('1 0 0 1 %.2f %.2f Tm (%s) Tj', 20.0, 42.0, $this->escapePdfText($codigoBarras));
            $lines[] = 'ET';
        } else {
            $lines[] = 'BT';
            $lines[] = '/F1 10 Tf';
            $lines[] = sprintf('1 0 0 1 %.2f %.2f Tm (Codigo barras: %s) Tj', 20.0, 42.0, $this->escapePdfText($codigoBarras !== '' ? $codigoBarras : 'N/D'));
            $lines[] = 'ET';
        }

        return implode("\n", $lines) . "\n";
    }

    private function barcodeRectangles(array $pattern, float $x, float $y, float $moduleWidth, float $height): string
    {
        $cursor   = $x;
        $segments = '';
        foreach ($pattern as $segment) {
            [$type, $units] = $segment;
            $width          = $units * $moduleWidth;
            if ($type === 'bar') {
                $segments .= sprintf('%.2f %.2f %.2f %.2f re f\n', $cursor, $y, $width, $height);
            }
            $cursor += $width;
        }
        return $segments;
    }

    private function wrapStream(string $content): string
    {
        $length = strlen($content);
        return "<< /Length {$length} >>\nstream\n{$content}endstream";
    }

    private function escapePdfText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('(', '\(', $text);
        $text = str_replace(')', '\)', $text);
        return $text;
    }

    //======================================================================================================================================================

    public function obtenerRotacion(): void {
    Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

    $db    = Database::getInstance()->getConnection();
    $desde = $this->parseDate($_GET['from'] ?? date('Y-m-01'));
    $hasta = $this->parseDate($_GET['to'] ?? date('Y-m-d'));
    if ($desde > $hasta) {
        [$desde, $hasta] = [$hasta, $desde];
    }
    $tipoFiltro    = $_GET['tipo'] ?? '';
    $almacenId     = $_GET['almacen_id'] ?? '';
    $clasifFiltro  = $_GET['clasificacion'] ?? ''; // 🔍 Nuevo parámetro de filtro

    // 1. Construcción y ejecución de la consulta base
    $sql = "SELECT p.id,
                   p.codigo,
                   p.nombre,
                   p.tipo,
                   p.stock_actual,
                   p.stock_minimo,
                   a.nombre AS almacen,
                   SUM(CASE WHEN m.tipo = 'Salida' THEN m.cantidad ELSE 0 END) AS total_salidas,
                   SUM(CASE WHEN m.tipo = 'Entrada' THEN m.cantidad ELSE 0 END) AS total_entradas,
                   MAX(m.fecha) AS ultimo_movimiento
            FROM productos p
            LEFT JOIN almacenes a ON p.almacen_id = a.id
            LEFT JOIN movimientos_inventario m
                   ON m.producto_id = p.id
                  AND DATE(m.fecha) BETWEEN ? AND ?";
    $params = [$desde, $hasta];

    $where = [];
    if ($tipoFiltro !== '' && in_array($tipoFiltro, Producto::tiposDisponibles(), true)) {
        $where[]  = 'p.tipo = ?';
        $params[] = $tipoFiltro;
    }
    if ($almacenId !== '') {
        $where[]  = 'p.almacen_id = ?';
        $params[] = (int) $almacenId;
    }
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' GROUP BY p.id ORDER BY total_salidas DESC, p.nombre ASC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $filas = $stmt->fetchAll() ?: [];

    // 2. Procesamiento completo de la matemática de rotación
    $todasLasRotaciones = [];
    foreach ($filas as $fila) {
        $salidas       = (float) ($fila['total_salidas'] ?? 0);
        $entradas      = (float) ($fila['total_entradas'] ?? 0);
        $stockActual   = (float) ($fila['stock_actual'] ?? 0);
        $stockPromedio = max(1.0, ($stockActual + max($entradas, 0)) / 2);
        $indice        = $salidas > 0 ? $salidas / $stockPromedio : 0.0;
        
        $clasificacion = match (true) {
            $salidas <= 0 => 'Sin movimiento',
            $indice >= 2  => 'Alta',
            $indice >= 1  => 'Media',
            default       => 'Baja',
        };

        $diasSinMovimiento = null;
        if (! empty($fila['ultimo_movimiento'])) {
            $ultimo            = new DateTime($fila['ultimo_movimiento']);
            $fin               = new DateTime($hasta);
            $diasSinMovimiento = $ultimo->diff($fin)->days;
        }

        $item = array_merge($fila, [
            'indice'              => $indice,
            'clasificacion'       => $clasificacion,
            'dias_sin_movimiento' => $diasSinMovimiento,
            'salidas'             => $salidas,
            'entradas'            => $entradas,
        ]);

        // 🔍 Aplicamos el filtro dinámico de clasificación si fue enviado
        if ($clasifFiltro !== '') {
            if (strtolower($item['clasificacion']) === strtolower($clasifFiltro)) {
                $todasLasRotaciones[] = $item;
            }
        } else {
            $todasLasRotaciones[] = $item;
        }
    }

    // --- LOGICA DE PAGINACIÓN COMPLETA (Fijado en 15 por página) ---
    $productosPorPagina = 15;
    $totalRegistros     = count($todasLasRotaciones);
    $totalPaginas       = max(1, ceil($totalRegistros / $productosPorPagina));
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) { $page = 1; }
    if ($page > $totalPaginas) { $page = $totalPaginas; }
    
    $offset = ($page - 1) * $productosPorPagina;

    // Números de control informativos para el footer de la tabla
    $desdeRegistro = $totalRegistros > 0 ? $offset + 1 : 0;
    $hastaRegistro = min($offset + $productosPorPagina, $totalRegistros);

    // Ajustamos la variable $rotacion final extrayendo solo el fragmento de 15 registros
    if (isset($_GET['export'])) {
        $rotacion = $todasLasRotaciones; // Las exportaciones conservan el reporte completo filtrado
    } else {
        $rotacion = array_slice($todasLasRotaciones, $offset, $productosPorPagina);
    }
    // ---------------------------------------------------------------

    if (isset($_GET['export'])) {
        $filename = 'rotacion_inventario_' . date('Ymd_His');
        if ($_GET['export'] === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename . '.csv');
            $out = fopen('php://output', 'w');
            fputs($out, chr(239) . chr(187) . chr(191));
            fputcsv($out, ['Codigo', 'Producto', 'Tipo', 'almacen', 'Stock actual', 'Salidas', 'Entradas', 'Indice', 'Clasificacion', 'Ultimo movimiento']);
            foreach ($rotacion as $row) {
                fputcsv($out, [
                    $row['codigo'],
                    $row['nombre'],
                    $row['tipo'],
                    $row['almacen'],
                    number_format((float) $row['stock_actual'], 2, '.', ''),
                    number_format((float) $row['salidas'], 2, '.', ''),
                    number_format((float) $row['entradas'], 2, '.', ''),
                    number_format($row['indice'], 2, '.', ''),
                    $row['clasificacion'],
                    $row['ultimo_movimiento'] ? date('d/m/Y H:i', strtotime($row['ultimo_movimiento'])) : '-',
                ]);
            }
            fclose($out);
            ActivityLogger::log('rotacion_export', 'Exportacion CSV de rotacion de inventario', [
                'tipo'       => $tipoFiltro ?: null,
                'almacen_id' => $almacenId ?: null,
                'desde'      => $desde,
                'hasta'      => $hasta,
            ]);
        } elseif ($_GET['export'] === 'pdf') {
            $lines   = ['Rotacion de inventario', "Periodo: {$desde} al {$hasta}", ''];
            $lines[] = 'Codigo | Producto | Salidas | Indice | Clasificacion';
            $lines[] = str_repeat('-', 80);
            foreach ($rotacion as $row) {
                $lines[] = sprintf(
                    '%s | %s | %0.2f | %0.2f | %s',
                    $row['codigo'],
                    mb_strimwidth($row['nombre'], 0, 30, '...'),
                    $row['salidas'],
                    $row['indice'],
                    $row['clasificacion']
                );
            }
            $pdf = $this->buildPdfDocument($lines);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename=' . $filename . '.pdf');
            echo $pdf;
            ActivityLogger::log('rotacion_export', 'Exportacion PDF de rotacion de inventario', [
                'tipo'       => $tipoFiltro ?: null,
                'almacen_id' => $almacenId ?: null,
                'desde'      => $desde,
                'hasta'      => $hasta,
            ]);
        }
        return;
    }

    // Helper útil para conservar filtros al cambiar de página en el HTML
    $buildQuery = function($newParams) {
        return '?' . http_build_query(array_merge($_GET, $newParams));
    };

    // Renombramos las variables informativas para que cuadren con tu HTML original
    $desde = $desdeRegistro;
    $hasta = $hastaRegistro;

    $almacenes        = $db->query('SELECT id, nombre FROM almacenes ORDER BY nombre ASC')->fetchAll();
    $tiposDisponibles = Producto::tiposDisponibles();
    include __DIR__ . '/../views/inventario/rotacion_inventario.php';
}

    private function parseDate(string $value): string
    {
        $date = DateTime::createFromFormat('Y-m-d', substr($value, 0, 10));
        return $date ? $date->format('Y-m-d') : date('Y-m-d');
    }

    private function buildPdfDocument(array $lines): string
    {
        if (empty($lines)) {
            $lines = ['Reporte sin informacion'];
        }

        $maxLinesPerPage = 42;
        $pagesContent    = array_chunk($lines, $maxLinesPerPage);

        $objects              = [];
        $objects[1]           = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2]           = ''; // placeholder for /Pages
        $fontObjNum           = 3;
        $objects[$fontObjNum] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pageRefs = [];
        foreach ($pagesContent as $chunk) {
            $contentStream           = $this->createPdfContentStream($chunk);
            $contentObjNum           = count($objects) + 1;
            $objects[$contentObjNum] = $contentStream;

            $pageObjNum           = $contentObjNum + 1;
            $objects[$pageObjNum] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 ' . $fontObjNum . ' 0 R >> >> /Contents ' . $contentObjNum . ' 0 R >>';
            $pageRefs[]           = $pageObjNum . ' 0 R';
        }

        if (empty($pageRefs)) {
            // Garantizar al menos una pagina vacia
            $contentStream           = $this->createPdfContentStream(['(Sin contenido)']);
            $contentObjNum           = count($objects) + 1;
            $objects[$contentObjNum] = $contentStream;
            $pageObjNum              = $contentObjNum + 1;
            $objects[$pageObjNum]    = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 ' . $fontObjNum . ' 0 R >> >> /Contents ' . $contentObjNum . ' 0 R >>';
            $pageRefs[]              = $pageObjNum . ' 0 R';
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageRefs) . '] /Count ' . count($pageRefs) . ' >>';

        $pdf         = "%PDF-1.4\n";
        $offsets     = [];
        $objectCount = count($objects);

        for ($i = 1; $i <= $objectCount; $i++) {
            $offsets[$i] = strlen($pdf);
            $pdf .= $i . " 0 obj\n" . $objects[$i] . "\nendobj\n";
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 " . ($objectCount + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $objectCount; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer << /Size " . ($objectCount + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPosition . "\n%%EOF";

        return $pdf;
    }
    
    private function createPdfContentStream(array $lines): string
    {
        $leading = 14;
        $startY  = 792 - 72;
        $content = "BT\n/F1 11 Tf\n{$leading} TL\n72 {$startY} Td\n";

        $total = count($lines);
        foreach ($lines as $index => $line) {
            $content .= '(' . $this->escapePdfText($line) . ") Tj\n";
            if ($index < $total - 1) {
                $content .= "T*\n";
            }
        }

        $content .= "ET\n";
        $length = strlen($content);

        return "<< /Length {$length} >>\nstream\n{$content}\nendstream";
    }

}
