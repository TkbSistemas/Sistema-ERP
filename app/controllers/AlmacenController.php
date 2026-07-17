<?php
require_once __DIR__ . '/../models/Almacen.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/SolicitudMaterial.php';

class AlmacenController
{
    private $menu_items;

    public function obtenerDashboardAlmacen(): void{
        Session::requireLogin(['Administrador', 'Almacen']);

        $role   = $_SESSION['role'] ?? '';
        $nombre = $_SESSION['nombre'] ?? '';
        $userId = (int) ($_SESSION['user_id'] ?? 0);

       $_SESSION['menu_items'] = [
            ['slug' => 'solicitudes_material', 'label' => 'Solicitudes de Material', 'icon' => 'fa-solid fa-file-signature', 'role' => 'Todos'],
            ['slug' => 'registrar_entrada', 'label' => 'Entrada de Productos', 'icon' => 'fa-solid fa-boxes-stacked', 'role' => 'Todos'],
            ['slug' => 'prestamos_herramientas', 'label' => 'Préstamos de Herramientas','icon' => 'fa-solid fa-tools', 'role' => 'Todos'],
            ['slug' => 'construccion', 'label' => 'Cajas de Herramientas', 'icon' => 'fa-solid fa-toolbox', 'role' => 'Todos'],
            ['slug' => 'registrar_salida', 'label' => 'Baja de Productos', 'icon' => 'fa-solid fa-trash-arrow-up', 'role' => 'Todos'],
            ['slug' => 'construccion', 'label' => 'Reabastecimiento', 'icon' => 'fa-solid fa-truck-loading', 'role' => 'Todos'],
            ['slug' => 'construccion', 'label' => 'Etiquetas', 'icon' => 'fa-solid fa-tags', 'role' => 'Todos'],
            //['slug' => 'reportes_inventario', 'label' => 'Reportes de Inventario', 'icon' => 'fa-solid fa-chart-pie', 'role' => 'Administrador'],
            ['slug' => 'inventario', 'label' => 'Ir a Inventario', 'icon' => 'fa-solid fa-warehouse', 'role' => 'Todos'],
            ['slug' => 'logout', 'label' => 'Cerrar Sesión', 'icon' => 'fa-solid fa-arrow-right-from-bracket', 'role' => 'Todos']
        ];

        $db = Database::getInstance()->getConnection();

        $datos = [
            'nombre'      => $nombre,
            'role'        => $role,
            'last_update' => date('d/m/Y, h:i:s a'),
            'alertas'     => [],
        ];

        $datos = array_merge($datos, $this->datosAlmacen($db));

        include __DIR__ . '/../views/almacen/dashboard_almacen.php';
    }

    public function viewRegistrarEntradaRapida(){
        $productos = Producto::All();
        $almacenes = Almacen::all();
        Session::requireLogin(['Administrador', 'Almacen']);
        
        include __DIR__ . '/../views/almacen/entrada_rapida.php';
    }
    
    public function viewRegistrarEntrada(){
        $productos = Producto::All();
        $almacenes = Almacen::all();
        Session::requireLogin(['Administrador', 'Almacen']);
        
        include __DIR__ . '/../views/almacen/registrar_entrada.php';
    }

    public function registrarEntrada(){
        $_SESSION['alerta'] = [
            'tipo' => 'warning',
            'titulo' => 'Folio Inválido',
            'mensaje' => 'No se Encontró Compra Asociada.'
        ];
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    public function create(): void
    {
        Session::requireLogin(['Administrador', 'Almacen']);
        $usuarios = Usuario::all();
        $error    = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $error = 'Token CSRF inválido.';
            } else {
                Almacen::create($_POST);
                header('Location: almacenes.php?success=1');
                exit();
            }
        }

        include __DIR__ . '/../views/almacenes/create.php';
    }

    public function edit($id): void
    {
        Session::requireLogin(['Administrador', 'Almacen']);
        $almacen  = Almacen::find($id);
        $usuarios = Usuario::all();
        $error    = '';

        if (! $almacen) {
            die('Almacén no encontrado.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $error = 'Token CSRF inválido.';
            } else {
                Almacen::update($id, $_POST);
                header('Location: almacenes.php?success=2');
                exit();
            }
        }

        include __DIR__ . '/../views/almacenes/edit.php';
    }

    public function delete($id): void
    {
        Session::requireLogin(['Administrador', 'Almacen']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ! Session::checkCsrf($_POST['csrf'] ?? '')) {
            header('Location: almacenes.php?error=csrf');
            exit();
        }

        $almacenId = (int) ($id ?: ($_POST['id'] ?? 0));
        if ($almacenId > 0) {
            Almacen::delete($almacenId);
            header('Location: almacenes.php?deleted=1');
            exit();
        }

        header('Location: almacenes.php?error=not_found');
        exit();
    }


    private function datosAlmacen($db): array{
        $datos = $this->datosGenerales($db);

        $productosAlmacen        = (int) $db->query('SELECT COUNT(*) FROM productos')->fetchColumn();
        $solicitudesPorGestionar = (int) $db->query("SELECT COUNT(*) FROM solicitudes_material WHERE estado IN ('Pendiente','Aprobada')")->fetchColumn();
        
        $datos['productosAlmacen']   = $productosAlmacen;
        $datos['solicitudesAlmacen'] = $solicitudesPorGestionar;
        $datos['ultimosMovimientos'] = $this->expuestosMovimientos($db);

        return $datos;
    }

     private function datosGenerales($db): array{
        $totalProductos        = (int) $db->query('SELECT COUNT(*) FROM productos')->fetchColumn();
        $stockBajo             = (int) $db->query('SELECT COUNT(*) FROM productos WHERE stock_actual < stock_minimo')->fetchColumn();
        $valorTotal            = (float) $db->query('SELECT SUM(stock_actual * costo_compra) FROM productos')->fetchColumn();
        $herramientasPrestadas = (int) $db->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'Prestado'")->fetchColumn();
        $prestamosVencidos     = (int) $db->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'Prestado' AND fecha_estimada_devolucion IS NOT NULL AND fecha_estimada_devolucion < NOW()")->fetchColumn();

        return [
            'totalProductos'        => $totalProductos,
            'stockBajo'             => $stockBajo,
            'valorTotalInventario'  => $valorTotal,
            'herramientasPrestadas' => $herramientasPrestadas,
            'alertas'               => array_merge($this->alertasInventario($db), $this->alertasPrestamosVencidos($db)),
            'prestamosVencidos'     => $prestamosVencidos,
        ];
    }

    private function alertasInventario($db): array
    {
        $stmt = $db->query("SELECT nombre, stock_actual, stock_minimo, DATE_FORMAT(created_at, '%d/%m/%Y') AS fecha
                             FROM productos
                             WHERE stock_actual < stock_minimo
                             ORDER BY stock_actual ASC
                             LIMIT 5");
        $productos = $stmt->fetchAll();

        $alertas = [];
        foreach ($productos as $p) {
            $alertas[] = [
                $p['nombre'] . ' por Debajo del Stock Mínimo',
                $p['fecha'],
                'Alta',
            ];
        }
        return $alertas;
    }

    private function alertasPrestamosVencidos($db): array
    {
        $stmt = $db->query("SELECT p.nombre AS producto, pr.fecha_estimada_devolucion, u.nombre_completo AS empleado
                             FROM prestamos pr
                             LEFT JOIN productos p ON pr.producto_id = p.id
                             LEFT JOIN usuarios u ON pr.empleado_id = u.id
                             WHERE pr.estado = 'Prestado'
                               AND pr.fecha_estimada_devolucion IS NOT NULL
                               AND pr.fecha_estimada_devolucion < NOW()
                             ORDER BY pr.fecha_estimada_devolucion ASC
                             LIMIT 5");
        $rows    = $stmt->fetchAll() ?: [];
        $alertas = [];
        foreach ($rows as $r) {
            $fecha     = date('d/m/Y', strtotime($r['fecha_estimada_devolucion']));
            $alertas[] = [
                'Préstamo vencido: ' . ($r['producto'] ?? 'Herramienta') . ' (' . ($r['empleado'] ?? 'Empleado') . ')',
                $fecha,
                'alta',
            ];
        }
        return $alertas;
    }

    private function ultimasActualizaciones($db): array
    {
        $stmt = $db->query("SELECT p.nombre,
                                    m.tipo,
                                    m.fecha,
                                    m.cantidad,
                                    COALESCE(a.nombre, ad.nombre) AS almacen
                             FROM movimientos_inventario m
                             LEFT JOIN productos p ON m.producto_id = p.id
                             LEFT JOIN almacenes a ON m.almacen_origen_id = a.id
                             LEFT JOIN almacenes ad ON m.almacen_destino_id = ad.id
                             ORDER BY m.fecha DESC
                             LIMIT 5");
        return $stmt->fetchAll();
    }

    private function expuestosMovimientos($db): array
    {
        $stmt = $db->query("SELECT p.nombre,
                                    p.codigo,
                                    m.tipo,
                                    m.cantidad,
                                    m.fecha,
                                    COALESCE(a.nombre, ad.nombre) AS almacen
                             FROM movimientos_inventario m
                             LEFT JOIN productos p ON m.producto_id = p.id
                             LEFT JOIN almacenes a ON m.almacen_origen_id = a.id
                             LEFT JOIN almacenes ad ON m.almacen_destino_id = ad.id
                             ORDER BY m.fecha DESC
                             LIMIT 8");
        return $stmt->fetchAll();
    }

    // Listar prestamos pendientes de devolucion
    public function pendientes()
    {
        Session::requireLogin(['Administrador', 'Almacen']);
        $prestamos = Prestamo::pendientes();
        include __DIR__ . '/../views/almacen/prestamos_herramientas.php';
    }

    public function obtenerSolicitudes()
    {
            Session::requireLogin(['Almacen']);
            
            $filtros = [
                'estado'       => $_GET['estado'] ?? '',
                'fecha_inicio' => $_GET['fecha_inicio'] ?? '',
                'fecha_fin'    => $_GET['fecha_fin'] ?? '',
                'search'       => $_GET['search'] ?? ''
            ];
            
            $solicitudes = SolicitudMaterial::historialPorUsuario($_SESSION['user_id'], $filtros);
            include __DIR__ . '/../views/almacen/solicitudes_material.php';
    }

    public function obtenerSolicitudesMaterial(){
            Session::requireLogin(['Administrador', 'Almacen']);

            $db = Database::getInstance()->getConnection();
            $datos = [];

            $solicitudesEsteMes = $db->query("
                SELECT id, usuario_id, extras, fecha_solicitud
                FROM solicitudes_material 
                WHERE estado IN ('rechazada', 'aprobada', 'entregada')
                AND fecha_solicitud >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00')
                AND fecha_solicitud <= NOW()
            ")->fetchAll();

            $numSolicitudesEsteMes = (int) $db->query("
                SELECT COUNT(*) 
                FROM solicitudes_material 
                WHERE estado IN ('rechazada', 'aprobada', 'entregada')
                AND fecha_solicitud >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00')
                AND fecha_solicitud <= NOW()
            ")->fetchColumn();

            $solicitudesPendientes = $db->query("
                SELECT id,usuario_id, extras, fecha_solicitud
                FROM solicitudes_material
                WHERE estado IN ('pendiente')
            ")->fetchAll();

            $numSolicitudesPendientes = (int) $db->query("
                SELECT COUNT(*) 
                FROM solicitudes_material 
                WHERE estado IN ('pendiente')
            ")->fetchColumn();


            $datos['numSolicitudesEsteMes'] = $numSolicitudesEsteMes;
            $datos['solicitudesEsteMes'] = $solicitudesEsteMes;
            $datos['solicitudesPendientes'] = $solicitudesPendientes;
            $datos['numSolicitudesPendientes'] = $numSolicitudesPendientes;

            include __DIR__ . '/../views/almacen/solicitudes_material.php';
    }

    public function crearSalida(){

        $productos = Producto::All();
        $almacenes = Almacen::all();
        Session::requireLogin(['Administrador', 'Almacen']);
            include __DIR__ . '/../views/almacen/registrar_salida.php';

    }

    public function crearEtiquetas($id)
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

        include __DIR__ . '/../views/almacen/etiquetas.php';
    }

    public function crearEntrada(){
            Session::requireLogin(['Administrador', 'Almacen']);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $entradaItems = $this->normalizarLineasEntrada($_POST);

                if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                    $_SESSION['alerta'] = [
                        'tipo' => 'error',
                        'titulo' => 'Seguridad',
                        'mensaje' => 'Token CSRF inválido.'
                    ];
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                } elseif (empty($entradaItems)) {
                    $_SESSION['alerta'] = [
                        'tipo' => 'warning',
                        'titulo' => 'Captura Vacía',
                        'mensaje' => 'Agrega al menos un producto a la captura de entrada.'
                    ];
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
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
                        $_SESSION['alerta'] = [
                            'tipo' => 'success',
                            'titulo' => 'Registro Creado',
                            'mensaje' => $totalLineas === 1 
                                ? 'Entrada registrada Correctamente.' 
                                : 'Se registraron ' . $totalLineas . ' Productos Correctamente.'
                        ];

            // REDIRECCIÓN DE ÉXITO: Limpia los datos de envío y cambia la petición a GET
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
                    } catch (\Throwable $e) {
                        if ($db->inTransaction()) {
                            $db->rollBack();
                        }
                        $_SESSION['alerta'] = [
                            'tipo' => 'error',
                            'titulo' => 'Error de Registro',
                            'mensaje' => 'No fue posible registrar la entrada. Revisa los datos.'
                        ];
                        
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    }
                }
            }

            $productos            = Producto::all();
            $almacenes            = Almacen::all();
            $movimientosRecientes = MovimientoInventario::ultimos('Entrada', 6);
            $entradaItems         = [];

            include __DIR__ . '/../views/almacen/registrar_entrada.php';
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
}
