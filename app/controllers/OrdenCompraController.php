<?php
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/ActivityLogger.php';
require_once __DIR__ . '/../models/OrdenCompra.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Factura.php';

class OrdenCompraController
{
    public function index(): void
    {
        Session::requireLogin(['Administrador', 'Compras', 'Almacen']);
        $db = Database::getInstance()->getConnection();

        $filtros = [
            'proveedor_id' => $_GET['proveedor_id'] ?? '',
            'estado'       => $_GET['estado'] ?? '',
            'desde'        => $_GET['desde'] ?? '',
            'hasta'        => $_GET['hasta'] ?? '',
        ];

        $historial = OrdenCompra::historial([
            'proveedor_id' => $filtros['proveedor_id'] ?: null,
            'desde'        => $filtros['desde'] ?: null,
            'hasta'        => $filtros['hasta'] ?: null,
        ]);

        if (! empty($filtros['estado'])) {
            $estadoFiltro         = strtolower($filtros['estado']);
            $historial['ordenes'] = array_values(array_filter(
                $historial['ordenes'],
                fn($orden) => strtolower($orden['estado'] ?? '') === $estadoFiltro
            ));
        }

        $proveedores = $db->query("SELECT id, nombre FROM proveedores ORDER BY nombre ASC")->fetchAll();
        $estados     = ['Pendiente', 'Enviada', 'Recibida', 'Cancelada'];

        include __DIR__ . '/../views/ordenes/index.php';
    }

    public function crear(): void
    {
        Session::requireLogin(['Administrador', 'Compras', 'Almacen']);
        $db = Database::getInstance()->getConnection();

        $proveedores = $db->query("SELECT id, nombre, rfc FROM proveedores ORDER BY nombre ASC")->fetchAll();
        $almacenes   = $db->query("SELECT id, nombre FROM almacenes ORDER BY nombre ASC")->fetchAll();
        $productos   = $db->query("SELECT id, codigo, nombre, tipo FROM productos ORDER BY nombre ASC")->fetchAll();
        $categorias  = $db->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC")->fetchAll();
        $unidades    = $db->query("SELECT id, nombre, abreviacion FROM unidades_medida ORDER BY nombre ASC")->fetchAll();

        $errors = [];
        $msg    = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $errors[] = 'Token CSRF invalido.';
            } else {
                $cabecera = $this->buildCabeceraFromRequest($_POST, $errors);
                $items    = $this->buildDetallesFromRequest($_POST, $cabecera, $errors);

                if (empty($items)) {
                    $errors[] = 'Debes agregar al menos un producto a la orden.';
                }

                if (empty($errors)) {
                    try {
                        $cabecera['usuario_id'] = $_SESSION['user_id'] ?? null;
                        $ordenId                = OrdenCompra::crear($cabecera, $items);
                        ActivityLogger::log('orden_compra_creada', 'Orden de compra creada', [
                            'orden_id'     => $ordenId,
                            'proveedor_id' => $cabecera['proveedor_id'],
                        ]);
                        header('Location: ordenes_compra_detalle.php?id=' . $ordenId . '&created=1');
                        exit();
                    } catch (\Throwable $e) {
                        $errors[] = 'Error al registrar la orden: ' . $e->getMessage();
                    }
                }
            }
        }

        include __DIR__ . '/../views/ordenes/form.php';
    }

    public function editar(int $id): void
    {
        Session::requireLogin(['Administrador', 'Compras', 'Almacen']);
        $orden = OrdenCompra::find($id);
        if (! $orden) {
            header('Location: ordenes_compra.php?not_found=1');
            exit();
        }

        if (in_array(strtolower($orden['estado']), ['recibida', 'cancelada'], true)) {
            header('Location: ordenes_compra_detalle.php?id=' . $id . '&locked=1');
            exit();
        }

        $db          = Database::getInstance()->getConnection();
        $proveedores = $db->query("SELECT id, nombre, rfc FROM proveedores ORDER BY nombre ASC")->fetchAll();
        $almacenes   = $db->query("SELECT id, nombre FROM almacenes ORDER BY nombre ASC")->fetchAll();
        $productos   = $db->query("SELECT id, codigo, nombre, tipo FROM productos ORDER BY nombre ASC")->fetchAll();
        $categorias  = $db->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC")->fetchAll();
        $unidades    = $db->query("SELECT id, nombre, abreviacion FROM unidades_medida ORDER BY nombre ASC")->fetchAll();

        $errors = [];
        $msg    = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $errors[] = 'Token CSRF invalido.';
            } else {
                $cabecera = $this->buildCabeceraFromRequest($_POST, $errors);
                $items    = $this->buildDetallesFromRequest($_POST, $cabecera, $errors);

                if (empty($items)) {
                    $errors[] = 'Debes agregar al menos un producto a la orden.';
                }

                if (empty($errors)) {
                    try {
                        $cabecera['usuario_id'] = $_SESSION['user_id'] ?? ($orden['usuario_id'] ?? null);
                        OrdenCompra::actualizar($id, $cabecera, $items);
                        ActivityLogger::log('orden_compra_actualizada', 'Orden de compra actualizada', [
                            'orden_id'     => $id,
                            'proveedor_id' => $cabecera['proveedor_id'],
                        ]);
                        header('Location: ordenes_compra_detalle.php?id=' . $id . '&updated=1');
                        exit();
                    } catch (\Throwable $e) {
                        $errors[] = 'No fue posible actualizar la orden: ' . $e->getMessage();
                    }
                }

                $orden             = array_merge($orden, $cabecera);
                $orden['detalles'] = $items;
            }
        }

        include __DIR__ . '/../views/ordenes/form.php';
    }

    public function detalle(int $id): void
    {
        Session::requireLogin(['Administrador', 'Compras', 'Almacen']);
        $orden = OrdenCompra::find($id);
        if (! $orden) {
            header('Location: ordenes_compra.php?not_found=1');
            exit();
        }

        $msg   = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $error = 'Token CSRF invalido.';
            } else {
                $accion = $_POST['accion'] ?? '';
                if ($accion === 'cancelar' && strtolower($orden['estado']) !== 'cancelada') {
                    try {
                        OrdenCompra::actualizarEstado($id, 'Cancelada');
                        ActivityLogger::log('orden_compra_cancelada', 'Orden de compra cancelada', [
                            'orden_id' => $id,
                        ]);
                        header('Location: ordenes_compra_detalle.php?id=' . $id . '&cancelled=1');
                        exit();
                    } catch (\Throwable $e) {
                        $error = 'No fue posible cancelar la orden: ' . $e->getMessage();
                    }
                }

                $orden = OrdenCompra::find($id) ?: $orden;
            }
        }

        $facturasRelacionadas = Factura::porOrden($id);

        include __DIR__ . '/../views/ordenes/detalle.php';
    }

    private function buildCabeceraFromRequest(array $input, array &$errors): array
    {
        $proveedorId    = isset($input['proveedor_id']) ? (int) $input['proveedor_id'] : 0;
        $almacenDestino = isset($input['almacen_destino_id']) ? (int) $input['almacen_destino_id'] : 0;
        $estado         = $input['estado'] ?? 'Pendiente';
        $fecha          = $input['fecha'] ?? date('Y-m-d');
        $numeroFactura  = trim($input['numero_factura'] ?? '');
        $rfc            = trim($input['rfc'] ?? '');

        if ($proveedorId <= 0) {
            $errors[] = 'Debes seleccionar un proveedor.';
        }
        if (! in_array($estado, ['Pendiente', 'Enviada', 'Recibida', 'Cancelada'], true)) {
            $errors[] = 'El estado seleccionado no es valido.';
        }
        if (! preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $fecha)) {
            $errors[] = 'La fecha no tiene un formato valido (YYYY-MM-DD).';
        }
        if ($almacenDestino <= 0) {
            $errors[] = 'Debes seleccionar un almacen destino.';
        }
        if ($numeroFactura !== '' && ! $this->validFactura($numeroFactura)) {
            $errors[] = 'El numero de factura solo puede contener letras, numeros y -/_. (max. 30 caracteres).';
        }
        if ($rfc !== '' && ! $this->validRfc($rfc)) {
            $errors[] = 'El RFC proporcionado no es valido.';
        }

        return [
            'proveedor_id'       => $proveedorId,
            'solicitud_id'       => ! empty($input['solicitud_id']) ? (int) $input['solicitud_id'] : null,
            'rfc'                => $rfc !== '' ? strtoupper($rfc) : null,
            'numero_factura'     => $numeroFactura !== '' ? strtoupper($numeroFactura) : null,
            'fecha'              => $fecha,
            'estado'             => $estado,
            'almacen_destino_id' => $almacenDestino,
        ];
    }

    private function buildDetallesFromRequest(array $post, array $cabecera, array &$errors): array
    {
        $detalles      = [];
        $tipos         = $post['item_tipo'] ?? [];
        $productoIds   = $post['item_producto_id'] ?? [];
        $cantidad      = $post['item_cantidad'] ?? [];
        $costos        = $post['item_costo'] ?? [];
        $preciosVenta  = $post['item_precio_venta'] ?? [];
        $codigos       = $post['item_codigo'] ?? [];
        $nombres       = $post['item_nombre'] ?? [];
        $tiposProducto = $post['item_tipo_producto'] ?? [];
        $unidades      = $post['item_unidad'] ?? [];
        $categorias    = $post['item_categoria'] ?? [];
        $stockMinimo   = $post['item_stock_minimo'] ?? [];

        foreach ($tipos as $index => $tipo) {
            $cant        = isset($cantidad[$index]) ? (float) str_replace(',', '.', $cantidad[$index]) : 0.0;
            $costo       = isset($costos[$index]) ? (float) str_replace(',', '.', $costos[$index]) : 0.0;
            $precioVenta = isset($preciosVenta[$index]) ? (float) str_replace(',', '.', $preciosVenta[$index]) : 0.0;

            if ($cant <= 0) {
                $errors[] = 'La cantidad debe ser mayor a cero (fila ' . ($index + 1) . ').';
                continue;
            }
            if ($costo < 0) {
                $errors[] = 'El costo unitario no puede ser negativo (fila ' . ($index + 1) . ').';
                continue;
            }

            if ($tipo === 'existente') {
                $productoId = isset($productoIds[$index]) ? (int) $productoIds[$index] : 0;
                if ($productoId <= 0) {
                    $errors[] = 'Debes seleccionar un producto existente (fila ' . ($index + 1) . ').';
                    continue;
                }
                $detalles[] = [
                    'producto_id'     => $productoId,
                    'cantidad'        => $cant,
                    'precio_unitario' => $costo,
                ];
            } else {
                $codigo       = trim($codigos[$index] ?? '');
                $nombre       = trim($nombres[$index] ?? '');
                $tipoProducto = trim($tiposProducto[$index] ?? '');
                $unidadId     = isset($unidades[$index]) ? (int) $unidades[$index] : null;
                $categoriaId  = isset($categorias[$index]) ? (int) $categorias[$index] : null;
                $stockMin     = isset($stockMinimo[$index]) ? (float) str_replace(',', '.', $stockMinimo[$index]) : 0.0;

                if ($nombre === '') {
                    $errors[] = 'El nombre del nuevo producto es obligatorio (fila ' . ($index + 1) . ').';
                    continue;
                }
                if ($codigo === '') {
                    $errors[] = 'El codigo del nuevo producto es obligatorio (fila ' . ($index + 1) . ').';
                    continue;
                }
                if (mb_strlen($codigo) > 50) {
                    $errors[] = 'El codigo del nuevo producto excede 50 caracteres (fila ' . ($index + 1) . ').';
                    continue;
                }
                if (mb_strlen($nombre) > 255) {
                    $errors[] = 'El nombre del nuevo producto excede 255 caracteres (fila ' . ($index + 1) . ').';
                    continue;
                }
                if (! in_array($tipoProducto, Producto::tiposDisponibles(), true)) {
                    $errors[] = 'El tipo de producto indicado no es valido (fila ' . ($index + 1) . ').';
                    continue;
                }
                if ($stockMin < 0) {
                    $errors[] = 'El stock minimo no puede ser negativo (fila ' . ($index + 1) . ').';
                    continue;
                }
                if ($precioVenta < 0) {
                    $errors[] = 'El precio de venta no puede ser negativo (fila ' . ($index + 1) . ').';
                    continue;
                }

                try {
                    $productoId = $this->crearProductoDesdeOrden([
                        'codigo'           => $codigo,
                        'nombre'           => $nombre,
                        'tipo'             => $tipoProducto,
                        'unidad_medida_id' => $unidadId,
                        'categoria_id'     => $categoriaId,
                        'costo_compra'     => $costo,
                        'precio_venta'     => $precioVenta,
                        'stock_minimo'     => $stockMin,
                        'almacen_id'       => $cabecera['almacen_destino_id'] ?? null,
                        'proveedor_id'     => $cabecera['proveedor_id'] ?? null,
                    ]);
                } catch (\Throwable $e) {
                    $errors[] = 'No fue posible registrar el nuevo producto (fila ' . ($index + 1) . '): ' . $e->getMessage();
                    continue;
                }

                $detalles[] = [
                    'producto_id'     => $productoId,
                    'cantidad'        => $cant,
                    'precio_unitario' => $costo,
                ];
            }
        }

        return $detalles;
    }

    private function crearProductoDesdeOrden(array $data): int
    {
        $codigo = strtoupper(trim((string) ($data['codigo'] ?? '')));
        if ($codigo === '') {
            throw new InvalidArgumentException('El codigo del producto es obligatorio.');
        }
        $data['codigo'] = $codigo;

        $tiposValidos = Producto::tiposDisponibles();
        $data['tipo'] = $data['tipo'] ?? 'Consumible';
        if (! in_array($data['tipo'], $tiposValidos, true)) {
            $data['tipo'] = 'Consumible';
        }

        if (empty($data['almacen_id'])) {
            throw new InvalidArgumentException('Debes seleccionar el almacén destino para la orden.');
        }

        $existente = Producto::findByCodigo($data['codigo']);
        if ($existente) {
            return (int) $existente['id'];
        }

        $payload = array_merge([
            'descripcion'               => '',
            'peso'                      => null,
            'ancho'                     => null,
            'alto'                      => null,
            'profundidad'               => null,
            'clase_categoria'           => null,
            'marca'                     => null,
            'color'                     => null,
            'forma'                     => null,
            'especificaciones_tecnicas' => null,
            'origen'                    => null,
            'stock_inicial'             => 0,
            'imagen_url'                => null,
            'last_requested_by_user_id' => null,
            'last_request_date'         => null,
        ], $data);

        Producto::create($payload);
        $db = Database::getInstance()->getConnection();
        return (int) $db->lastInsertId();
    }

    private function validRfc(string $rfc): bool
    {
        return (bool) preg_match('/^[A-Z]{3,4}[0-9]{6}[A-Z0-9]{3}$/i', $rfc);
    }

    private function validFactura(string $factura): bool
    {
        return strlen($factura) <= 30 && preg_match('/^[A-Z0-9._-]*$/i', $factura);
    }
}
