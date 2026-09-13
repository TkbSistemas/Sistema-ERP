<?php
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/ActivityLogger.php';
require_once __DIR__ . '/../models/OrdenCompra.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Almacen.php';
require_once __DIR__ . '/../models/Proyecto.php';
require_once __DIR__ . '/../models/Proveedor.php';

class ComprasController
{

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


     public function crearOrdenCompra(): void {
        Session::requireLogin(['Administrador', 'Compras']);

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $nombre = $_SESSION['nombre'] ?? '';
        $role   = $_SESSION['role'] ?? '';
        $error  = '';
        $msg    = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errores = [];
            if (!Session::checkCsrf($_POST['csrf'] ?? '')) {
                $errores[] = 'La Sesión del Formulario Expiró. Recarga la Página e Intenta Nuevamente.';
            }

            $proyectoId = (int) ($_POST['proyecto_id'] ?? 0);
            if ($proyectoId <= 0) {
                $errores[] = 'Selecciona un Proyecto o Destino.';
            } else {
                $db = Database::getInstance()->getConnection();
                $stmtProyecto = $db->prepare('SELECT COUNT(*) FROM proyectos WHERE id = ?');
                $stmtProyecto->execute([$proyectoId]);
                if ((int) $stmtProyecto->fetchColumn() === 0) {
                    $errores[] = 'El Proyecto o Destino Seleccionado no Existe.';
                }
            }

            $fechaRequerida = trim((string) ($_POST['fecha_compra'] ?? ''));
            $fechaValida = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRequerida) === 1;
            if ($fechaValida) {
                [$anio, $mes, $dia] = array_map('intval', explode('-', $fechaRequerida));
                $fechaValida = checkdate($mes, $dia, $anio);
            }
            if (!$fechaValida) {
                $errores[] = 'Captura una Fecha Requerida Válida.';
            } elseif ($fechaRequerida < date('Y-m-d')) {
                $errores[] = 'La Fecha Requerida no Puede ser Anterior a Hoy.';
            }

            $comentario = trim((string) ($_POST['comentario_general'] ?? $_POST['observacion'] ?? ''));
            if (mb_strlen($comentario) > 255) {
                $errores[] = 'El Motivo o las Indicaciones Generales no Pueden Superar 255 Caracteres.';
            }

            [$detalles, $noRegistrados] = $this->normalizarMaterialesSolicitud(
                (string) ($_POST['material'] ?? ''),
                $errores
            );

            if (!$errores) {
                try {
                    $solicitud = SolicitudMaterial::crearSolicitudCompleta([
                        'solicitante_id' => $userId,
                        'proyecto_id' => $proyectoId,
                        'fecha_requerida' => $fechaRequerida,
                        'comentario_solicitante' => $comentario,
                    ], $detalles, $noRegistrados);

                    ActivityLogger::registrarAlta('compras', 'solicitud_compra', $solicitud['id'] ?? null, 'Solicitud de Compra Registrada', [
                        'folio' => $solicitud['folio'] ?? null,
                        'proyecto_id' => $proyectoId,
                        'productos_catalogo' => count($detalles),
                        'productos_fuera_catalogo' => count($noRegistrados),
                    ]);

                    $_SESSION['alerta'] = [
                        'tipo' => 'success',
                        'titulo' => 'Solicitud Registrada',
                        'mensaje' => 'La Solicitud ' . $solicitud['folio'] . ' se Registró Correctamente.',
                    ];
                    header('Location: ' . Session::url('crear_solicitud'));
                    exit;
                } catch (InvalidArgumentException | RuntimeException $e) {
                    $errores[] = $e->getMessage();
                } catch (Throwable $e) {
                    error_log('Error al crear solicitud de material: ' . $e->getMessage());
                    $errores[] = 'No Fue Posible Registrar la Solicitud. Revisa los Datos e Intenta Nuevamente.';
                }
            }

            $error = implode(' ', $errores);
        }

        $productos            = Producto::all();
        $almacenes            = Almacen::all();
        $proyectos            = Proyecto::all();

        $datos = [
            'nombre' => $nombre,
            'role' => $role,
            'last_update' => date('d/m/Y, h:i:s a')
        ];

        include __DIR__ . '/../views/compras/ordenes_compra.php';
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

    public function historial(): void{
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

        $filtros = [
            'proveedor_id' => $_GET['proveedor_id'] ?? '',
            'desde'        => $_GET['desde'] ?? date('Y-m-01'),
            'hasta'        => $_GET['hasta'] ?? date('Y-m-d'),
        ];

        if ($filtros['desde'] > $filtros['hasta']) {
            [$filtros['desde'], $filtros['hasta']] = [$filtros['hasta'], $filtros['desde']];
        }

        $db          = Database::getInstance()->getConnection();
        $proveedores = $db->query("SELECT id, nombre FROM proveedores ORDER BY nombre ASC")->fetchAll();

        $historial = OrdenCompra::historial($filtros);

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            ActivityLogger::log('compras_export', 'Descarga de historial de compras', [
                'proveedor_id' => $filtros['proveedor_id'] ?: null,
                'desde'        => $filtros['desde'],
                'hasta'        => $filtros['hasta'],
            ]);
            $filename = 'compras_proveedor_' . date('Ymd_His') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);
            $out = fopen('php://output', 'w');
            fputs($out, chr(239) . chr(187) . chr(191));
            fputcsv($out, ['Orden', 'Fecha', 'Proveedor', 'Estado', 'Productos', 'Importe detalle', 'Importe total']);
            foreach ($historial['ordenes'] as $orden) {
                fputcsv($out, [
                    $orden['id'],
                    $orden['fecha'],
                    $orden['proveedor'],
                    $orden['estado'],
                    number_format((float) ($orden['total_items'] ?? 0), 2, '.', ''),
                    number_format((float) ($orden['subtotal'] ?? 0), 2, '.', ''),
                    number_format((float) ($orden['total'] ?? $orden['subtotal'] ?? 0), 2, '.', ''),
                ]);
            }
            fclose($out);
            return;
        }

        include __DIR__ . '/../views/compras/historial.php';
    }
}
