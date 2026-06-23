<?php
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/ActivityLogger.php';
require_once __DIR__ . '/../helpers/BarcodeGenerator.php';

class ProductoController
{
    public function index()
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

        include __DIR__ . '/../views/inventario/productos/gestion_productos.php';
    }

    public function create()
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

        include __DIR__ . '/../views/inventario/productos/create.php';
    }

    public function edit($id)
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

                $existente = Producto::findByCodigo($data['codigo']);
                if ($existente && (int) $existente['id'] !== (int) $id) {
                    $errors[] = 'Ya existe otro producto con ese codigo.';
                }

                if ($data['codigo_barras'] === '') {
                    $data['codigo_barras'] = $this->generarCodigoBarras($data['codigo']);
                }

                $nuevaImagen = $this->handleImagenUpload($_FILES['imagen_url'] ?? null, $errors, $producto['imagen_url'] ?? null);
                if ($nuevaImagen === false) {
                    $errors[] = 'No fue posible procesar la imagen adjunta.';
                } elseif (is_string($nuevaImagen)) {
                    $data['imagen_url'] = $nuevaImagen;
                } else {
                    $data['imagen_url'] = $producto['imagen_url'];
                }

                if (empty($errors)) {
                    $data['last_requested_by_user_id'] = $producto['last_requested_by_user_id'] ?? null;
                    $data['last_request_date']         = $producto['last_request_date'] ?? null;

                    Producto::update($id, $data);
                    ActivityLogger::log('producto_actualizado', 'Se actualizo el producto ' . $data['nombre'], [
                        'codigo' => $data['codigo'],
                    ]);
                    header('Location: productos.php?success=2');
                    exit();
                }
            }
        }

        $error = empty($errors) ? '' : implode(PHP_EOL, $errors);

        include __DIR__ . '/../views/inventario/productos/edit.php';
    }
    public function view($id)
    {
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);
        $producto = Producto::find($id);
        if (! $producto) {
            die('Producto no encontrado.');
        }
        include __DIR__ . '/../views/inventario/productos/view.php';
    }

    public function etiqueta($id)
    {
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);
        $producto = Producto::find($id);
        if (! $producto) {
            die('Producto no encontrado.');
        }

        $db        = Database::getInstance()->getConnection();
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
                $error = 'Token CSRF invalido.';
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
                    $error = 'No fue posible generar el PDF de etiquetas.';
                }
            }
        }

        include __DIR__ . '/../views/inventario/productos/etiqueta.php';
    }

    public function buscarCodigoBarras()
    {
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);
        $codigo = '';
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $error = 'Token CSRF invalido.';
            } else {
                $codigo = trim($_POST['codigo_barras'] ?? '');
                if ($codigo === '') {
                    $error = 'Ingresa un codigo de barras.';
                } else {
                    $producto = Producto::findByCodigoBarras($codigo);
                    if ($producto) {
                        header('Location: productos_view.php?id=' . $producto['id'] . '&from=barcode');
                        exit();
                    }
                    header('Location: productos.php?codigo_barras=' . urlencode($codigo));
                    exit();
                }
            }
        } elseif (! empty($_GET['codigo'])) {
            $codigo   = trim((string) $_GET['codigo']);
            $producto = Producto::findByCodigoBarras($codigo);
            if ($producto) {
                header('Location: productos_view.php?id=' . $producto['id'] . '&from=barcode');
                exit();
            }
            $error = 'No se encontro producto con ese codigo de barras.';
        }

        include __DIR__ . '/../views/inventario/productos/buscar_codigo.php';
    }

    public function delete($id)
    {
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ! Session::checkCsrf($_POST['csrf'] ?? '')) {
            header('Location: productos.php?deleted=0&error=csrf');
            exit();
        }

        $producto = Producto::find($id);
        $eliminado = Producto::delete($id);

        if (! $eliminado) {
            ActivityLogger::log('producto_eliminado_error', 'No se pudo eliminar el producto porque tiene registros relacionados.', [
                'producto_id' => (int) $id,
                'codigo'      => $producto['codigo'] ?? null,
            ]);
            header('Location: productos.php?deleted=0&error=relaciones');
            exit();
        }

        if ($producto) {
            ActivityLogger::log('producto_eliminado', 'Se elimino el producto ' . ($producto['nombre'] ?? ''), [
                'codigo' => $producto['codigo'] ?? null,
            ]);
        } else {
            ActivityLogger::log('producto_eliminado', 'Se elimino un producto', ['producto_id' => $id]);
        }

        header('Location: productos.php?deleted=1');
        exit();
    }

    public function setActive($id, $active)
    {
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ! Session::checkCsrf($_POST['csrf'] ?? '')) {
            header('Location: productos.php?error=csrf');
            exit();
        }

        Producto::setActive($id, (int) $active);
        ActivityLogger::log('producto_estado', 'Se cambio la disponibilidad del producto', [
            'producto_id' => (int) $id,
            'activo'      => (bool) $active,
        ]);
        header('Location: productos.php');
        exit();
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
            'costo_compra',
            'precio_venta',
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

    public function import(): void
    {
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: productos.php');
            return;
        }

        if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
            $_SESSION['productos_import'] = [
                'processed' => 0,
                'success'   => 0,
                'skipped'   => 0,
                'errors'    => ['Token CSRF invalido.'],
            ];
            header('Location: productos.php');
            return;
        }

        if (empty($_FILES['archivo']['tmp_name']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['productos_import'] = [
                'processed' => 0,
                'success'   => 0,
                'skipped'   => 0,
                'errors'    => ['Debes seleccionar un archivo CSV valido.'],
            ];
            header('Location: productos.php');
            return;
        }

        $handle = fopen($_FILES['archivo']['tmp_name'], 'r');
        if (! $handle) {
            $_SESSION['productos_import'] = [
                'processed' => 0,
                'success'   => 0,
                'skipped'   => 0,
                'errors'    => ['No fue posible leer el archivo.'],
            ];
            header('Location: productos.php');
            return;
        }

        $columns = fgetcsv($handle);
        if (! $columns) {
            fclose($handle);
            $_SESSION['productos_import'] = [
                'processed' => 0,
                'success'   => 0,
                'skipped'   => 0,
                'errors'    => ['El archivo esta vacio.'],
            ];
            header('Location: productos.php');
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

        ActivityLogger::log('productos_import', 'Importacion de productos finalizada', [
            'exitosos'   => $result['success'],
            'procesados' => $result['processed'],
            'omitidos'   => $result['skipped'],
        ]);

        $_SESSION['productos_import'] = $result;
        header('Location: productos.php');
        exit();
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

    private function collectProductoData(array $input, array &$errors, ?int $productoId = null): array
    {
        $data = $this->defaultProductoData();

        $data['codigo'] = strtoupper(trim($input['codigo'] ?? ''));
        if ($data['codigo'] === '') {
            $errors[] = 'El codigo interno es obligatorio.';
        } elseif (mb_strlen($data['codigo']) > 50) {
            $errors[] = 'El codigo no debe exceder 50 caracteres.';
        } elseif (! preg_match('/^[A-Z0-9][A-Z0-9_.-]*$/', $data['codigo'])) {
            $errors[] = 'El codigo solo puede contener letras, numeros, guion (-), guion bajo (_) o punto (.) sin espacios.';
        }

        $data['codigo_barras'] = strtoupper(trim($input['codigo_barras'] ?? ''));
        if ($data['codigo_barras'] !== '') {
            if (mb_strlen($data['codigo_barras']) > 64) {
                $errors[] = 'El codigo de barras no debe exceder 64 caracteres.';
            } elseif (! preg_match('/^[A-Z0-9\\-_.]+$/', $data['codigo_barras'])) {
                $errors[] = 'El codigo de barras solo puede contener letras, numeros y -_. (sin espacios).';
            } elseif (Producto::codigoBarrasExiste($data['codigo_barras'], $productoId)) {
                $errors[] = 'Ya existe un producto con ese codigo de barras.';
            }
        }

        $data['nombre'] = trim($input['nombre'] ?? '');
        if ($data['nombre'] === '') {
            $errors[] = 'El nombre del producto es obligatorio.';
        } elseif (mb_strlen($data['nombre']) > 150) {
            $errors[] = 'El nombre no debe exceder 150 caracteres.';
        }

        $data['descripcion'] = trim($input['descripcion'] ?? '');
        if (mb_strlen($data['descripcion']) > 1000) {
            $errors[] = 'La descripcion no debe exceder 1000 caracteres.';
        }

        $data['proveedor_id']     = $this->toNullableInt($input['proveedor_id'] ?? null);
        $data['categoria_id']     = $this->toNullableInt($input['categoria_id'] ?? null);
        $data['unidad_medida_id'] = $this->toNullableInt($input['unidad_medida_id'] ?? null);
        $data['almacen_id']       = $this->toNullableInt($input['almacen_id'] ?? null);
        if (empty($data['almacen_id'])) {
            $errors[] = 'Debes seleccionar un almacen asignado.';
        }
        $data['ubicacion_fisica'] = trim($input['ubicacion_fisica'] ?? '');
        if (mb_strlen($data['ubicacion_fisica']) > 150) {
            $errors[] = 'La ubicacion fisica no debe exceder 150 caracteres.';
        }

        $data['clase_categoria']           = trim($input['clase_categoria'] ?? '');
        $data['marca']                     = trim($input['marca'] ?? '');
        $data['color']                     = trim($input['color'] ?? '');
        $data['forma']                     = trim($input['forma'] ?? '');
        $data['especificaciones_tecnicas'] = trim($input['especificaciones_tecnicas'] ?? '');
        $data['origen']                    = trim($input['origen'] ?? '');
        $data['tags']                      = trim($input['tags'] ?? '');

        $data['peso']        = $this->normalizeDecimal($input['peso'] ?? null);
        $data['ancho']       = $this->normalizeDecimal($input['ancho'] ?? null);
        $data['alto']        = $this->normalizeDecimal($input['alto'] ?? null);
        $data['profundidad'] = $this->normalizeDecimal($input['profundidad'] ?? null);

        $data['stock_actual'] = $this->normalizeDecimal($input['stock_actual'] ?? 0);
        if ($data['stock_actual'] === null || $data['stock_actual'] < 0) {
            $errors[] = 'El stock actual debe ser un numero mayor o igual a cero.';
        }

        $data['stock_minimo'] = $this->normalizeDecimal($input['stock_minimo'] ?? 0);
        if ($data['stock_minimo'] === null || $data['stock_minimo'] < 0) {
            $errors[] = 'El stock minimo debe ser un numero mayor o igual a cero.';
        }

        $data['costo_compra'] = $this->normalizeDecimal($input['costo_compra'] ?? 0);
        if ($data['costo_compra'] === null || $data['costo_compra'] < 0) {
            $errors[] = 'El costo de compra debe ser un numero mayor o igual a cero.';
        }

        $data['precio_venta'] = $this->normalizeDecimal($input['precio_venta'] ?? 0);
        if ($data['precio_venta'] === null || $data['precio_venta'] < 0) {
            $errors[] = 'El precio de venta debe ser un numero mayor o igual a cero.';
        }

        $data['tipo'] = $input['tipo'] ?? 'Consumible';
        if (! in_array($data['tipo'], Producto::tiposDisponibles(), true)) {
            $errors[] = 'El tipo seleccionado no es valido.';
        }

        $data['estado'] = $input['estado'] ?? 'Nuevo';
        if (! in_array($data['estado'], Producto::estadosDisponibles(), true)) {
            $errors[] = 'El estado seleccionado no es valido.';
        }

        return $data;
    }

    private function buildEtiquetasPdf(array $labels): string
    {
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
}
