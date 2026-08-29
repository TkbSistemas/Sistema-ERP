<?php
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/ActivityLogger.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/MovimientoInventario.php';

class ReporteController
{
    public function index(): void
    {
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

        $role          = $_SESSION['role'] ?? 'Compras';
        $mostrarCostos = $role === 'Administrador';

        $fechaFin    = $this->parseDate($_GET['to'] ?? date('Y-m-d'));
        $fechaInicio = $this->parseDate($_GET['from'] ?? date('Y-m-01'));
        if ($fechaInicio > $fechaFin) {
            $tmp         = $fechaInicio;
            $fechaInicio = $fechaFin;
            $fechaFin    = $tmp;
        }

        $movTipo = $_GET['mov_tipo'] ?? '';
        $movTipo = in_array($movTipo, ['Entrada', 'Salida', 'Transferencia'], true) ? $movTipo : '';

        $db            = Database::getInstance()->getConnection();
        $proveedores   = $db->query("SELECT id, nombre, rfc FROM proveedores ORDER BY nombre ASC")->fetchAll();
        $almacenes     = $db->query("SELECT id, nombre FROM almacenes ORDER BY nombre ASC")->fetchAll();
        $categorias    = $db->query("SELECT id, nombre FROM catalogo_categorias_inventario ORDER BY nombre ASC")->fetchAll();
        $tiposProducto = Producto::tiposDisponibles();

        $proveedorFiltro = isset($_GET['proveedor_id']) ? (int) $_GET['proveedor_id'] : 0;
        if ($proveedorFiltro <= 0) {
            $proveedorFiltro = null;
        }

        $invAlmacenId = isset($_GET['inv_almacen_id']) ? (int) $_GET['inv_almacen_id'] : 0;
        if ($invAlmacenId <= 0) {
            $invAlmacenId = null;
        }

        $invCategoriaId = isset($_GET['inv_categoria_id']) ? (int) $_GET['inv_categoria_id'] : 0;
        if ($invCategoriaId <= 0) {
            $invCategoriaId = null;
        }

        $movAlmacenId = isset($_GET['mov_almacen_id']) ? (int) $_GET['mov_almacen_id'] : 0;
        if ($movAlmacenId <= 0) {
            $movAlmacenId = null;
        }

        $topTipo = $_GET['top_tipo'] ?? '';
        if (! in_array($topTipo, $tiposProducto, true)) {
            $topTipo = '';
        }

        $topAlmacenId = isset($_GET['top_almacen_id']) ? (int) $_GET['top_almacen_id'] : 0;
        if ($topAlmacenId <= 0) {
            $topAlmacenId = null;
        }

        $inventarioResumen   = $this->resumenInventario();
        $inventarioBajo      = $this->reporteInventarioBajo($invAlmacenId, $invCategoriaId);
        $valorPorAlmacen     = $mostrarCostos ? $this->reporteValorPorAlmacen() : [];
        $movimientos         = $this->reporteMovimientos($fechaInicio, $fechaFin, $movTipo, $movAlmacenId);
        $prestamosAbiertos   = $this->reportePrestamosActivos($fechaInicio, $fechaFin);
        $prestamosVencidos   = $this->reportePrestamosVencidos();
        $topSalidas          = $this->reporteTopSalidas($fechaInicio, $fechaFin, $topTipo, $topAlmacenId);
        $productosPorTipo    = $this->reporteProductosPorTipo();
        $comprasPorProveedor = $mostrarCostos
            ? $this->reporteComprasPorProveedor($fechaInicio, $fechaFin, $proveedorFiltro)
            : ['rows' => [], 'resumen' => ['total' => 0.0, 'proveedores' => []]];
        $comprasListado = $comprasPorProveedor['rows'];
        $comprasResumen = $comprasPorProveedor['resumen'];

        $datasets = [
            'inventario_bajo'        => $inventarioBajo,
            'movimientos'            => $movimientos,
            'prestamos_abiertos'     => $prestamosAbiertos,
            'prestamos_vencidos'     => $prestamosVencidos,
            'top_salidas'            => $topSalidas,
            'productos_consumibles'  => $productosPorTipo['consumibles'],
            'productos_herramientas' => $productosPorTipo['herramientas'],
            'productos_equipos'      => $productosPorTipo['equipos'],
            'productos_todos'        => $productosPorTipo['todos'],
        ];

        if ($mostrarCostos) {
            $datasets['valor_almacen']     = $valorPorAlmacen;
            $datasets['compras_proveedor'] = $comprasListado;
        }

        if (isset($_GET['export'])) {
            $section    = $_GET['section'] ?? '';
            $exportType = $_GET['export'];
            if ($exportType === 'csv') {
                $this->exportCsv($section, $datasets, $mostrarCostos, $fechaInicio, $fechaFin);
            } elseif ($exportType === 'pdf') {
                $this->exportPdf($section, $datasets, $mostrarCostos, $fechaInicio, $fechaFin);
            } else {
                header('HTTP/1.1 400 Bad Request');
                echo 'Formato de exportacion no soportado.';
            }
            return;
        }

        $filters = [
            'from'             => $fechaInicio,
            'to'               => $fechaFin,
            'mov_tipo'         => $movTipo,
            'mov_almacen_id'   => $movAlmacenId ? (string) $movAlmacenId : '',
            'inv_almacen_id'   => $invAlmacenId ? (string) $invAlmacenId : '',
            'inv_categoria_id' => $invCategoriaId ? (string) $invCategoriaId : '',
            'top_tipo'         => $topTipo,
            'top_almacen_id'   => $topAlmacenId ? (string) $topAlmacenId : '',
            'proveedor_id'     => $proveedorFiltro ? (string) $proveedorFiltro : '',
        ];

        include __DIR__ . '/../views/inventario/reportes/index.php';
    }

    private function parseDate(string $value): string
    {
        $date = DateTime::createFromFormat('Y-m-d', substr($value, 0, 10));
        return $date ? $date->format('Y-m-d') : date('Y-m-d');
    }

    private function resumenInventario(): array
    {
        $db  = Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(*) AS total,
                       SUM(CASE WHEN COALESCE(si.stock_total, 0) < p.stock_minimo THEN 1 ELSE 0 END) AS stock_bajo,
                       SUM(CASE WHEN COALESCE(si.stock_total, 0) <= 0 THEN 1 ELSE 0 END) AS sin_stock,
                       SUM(CASE WHEN p.tipo = 'Consumible' THEN 1 ELSE 0 END) AS consumibles,
                       SUM(CASE WHEN p.tipo = 'Herramienta' THEN 1 ELSE 0 END) AS herramientas,
                       SUM(CASE WHEN p.activo = 1 THEN 1 ELSE 0 END) AS activos,
                       SUM(CASE WHEN p.activo <> 1 THEN 1 ELSE 0 END) AS inactivos,
                       SUM(COALESCE(si.stock_total, 0) * p.precio_unitario) AS valor_total
                FROM inventario p
                LEFT JOIN (SELECT producto_id, SUM(stock) AS stock_total FROM stock_almacen GROUP BY producto_id) si ON si.producto_id = p.id";
        $data = $db->query($sql)->fetch();

        $prestamosPendientes = $db->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'Prestado'")->fetchColumn();
        $prestamosVencidos   = $db->query("SELECT COUNT(*)
                                         FROM prestamos
                                         WHERE estado = 'Prestado'
                                           AND fecha_estimada_devolucion IS NOT NULL
                                           AND fecha_estimada_devolucion < NOW()")->fetchColumn();

        return [
            'total'                => (int) ($data['total'] ?? 0),
            'stock_bajo'           => (int) ($data['stock_bajo'] ?? 0),
            'sin_stock'            => (int) ($data['sin_stock'] ?? 0),
            'consumibles'          => (int) ($data['consumibles'] ?? 0),
            'herramientas'         => (int) ($data['herramientas'] ?? 0),
            'activos'              => (int) ($data['activos'] ?? 0),
            'inactivos'            => (int) ($data['inactivos'] ?? 0),
            'valor_total'          => (float) ($data['valor_total'] ?? 0),
            'prestamos_pendientes' => (int) $prestamosPendientes,
            'prestamos_vencidos'   => (int) $prestamosVencidos,
        ];
    }

    private function reporteInventarioBajo(?int $almacenId = null, ?int $categoriaId = null): array
    {
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT p.id,
                       p.codigo_fabricante AS codigo,
                       p.nombre,
                       p.tipo,
                       p.stock_minimo,
                       c.nombre AS categoria,
                       a.nombre AS almacen_producto,
                       um.apodo AS unidad,
                       SUM(sa.stock) AS stock_total,
                       COUNT(DISTINCT sa.almacen_id) AS almacenes_distintos";

        $params = [];
        if ($almacenId !== null) {
            $sql .= ",
                       SUM(CASE WHEN sa.almacen_id = ? THEN sa.stock ELSE 0 END) AS stock_filtrado";
            $params[] = $almacenId;
        }

        $sql .= " FROM inventario p
                  LEFT JOIN catalogo_categorias_inventario c ON p.categoria_id = c.id
                  LEFT JOIN almacenes a ON p.almacen_id = a.id
                  LEFT JOIN catalogo_unidades_medida um ON p.unidad_medida_id = um.id
                  LEFT JOIN stock_almacen sa ON sa.producto_id = p.id";

        $where       = [];
        $whereParams = [];
        if ($categoriaId !== null) {
            $where[]       = 'p.categoria_id = ?';
            $whereParams[] = $categoriaId;
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY p.id
                  ORDER BY p.nombre ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge($params, $whereParams));
        $rows = $stmt->fetchAll() ?: [];

        $almacenNombre = null;
        if ($almacenId !== null) {
            $stmtAlm = $db->prepare('SELECT nombre FROM almacenes WHERE id = ?');
            $stmtAlm->execute([$almacenId]);
            $almacenNombre = $stmtAlm->fetchColumn() ?: null;
        }

        $resultado = [];
        foreach ($rows as $row) {
            $stockTotal     = $row['stock_total'] !== null ? (float) $row['stock_total'] : null;
            $stockFiltrado  = $almacenId !== null ? (float) ($row['stock_filtrado'] ?? 0) : null;
            $stockCalculado = $almacenId !== null
                ? $stockFiltrado
                : (float) ($stockTotal ?? 0);

            $stockMinimo = (float) ($row['stock_minimo'] ?? 0);
            if ($stockCalculado < $stockMinimo) {
                $almacenesDistintos = (int) ($row['almacenes_distintos'] ?? 0);
                $almacenEtiqueta    = $almacenId !== null
                    ? ($almacenNombre ?? 'Almac?n seleccionado')
                    : ($row['almacen_producto'] ?: ($almacenesDistintos > 1 ? 'M?ltiples almacenes' : 'Sin asignar'));

                $resultado[] = [
                    'codigo'       => $row['codigo'],
                    'nombre'       => $row['nombre'],
                    'tipo'         => $row['tipo'],
                    'categoria'    => $row['categoria'],
                    'almacen'      => $almacenEtiqueta,
                    'stock_actual' => $stockCalculado,
                    'stock_minimo' => $stockMinimo,
                    'unidad'       => $row['unidad'] ?? '',
                ];
            }
        }

        usort($resultado, static function ($a, $b) {
            return $a['stock_actual'] <=> $b['stock_actual'];
        });

        return $resultado;
    }

    private function reporteValorPorAlmacen(): array
    {
        $db = Database::getInstance()->getConnection();
        // Agregar por el stock físico registrado en cada almacén.
        $sql = "SELECT a.nombre AS almacen,
                       COUNT(DISTINCT sa.producto_id) AS productos,
                       COALESCE(SUM(sa.stock), 0) AS unidades,
                       COALESCE(SUM(sa.stock * p.precio_unitario), 0) AS valor_total
                FROM almacenes a
                LEFT JOIN stock_almacen sa ON sa.almacen_id = a.id
                LEFT JOIN inventario p ON p.id = sa.producto_id
                GROUP BY a.id
                ORDER BY valor_total DESC, a.nombre ASC";
        return $db->query($sql)->fetchAll();
    }

    private function reporteMovimientos(string $desde, string $hasta, string $tipo = '', ?int $almacenId = null): array
    {
        $db  = Database::getInstance()->getConnection();
        $sql = "SELECT m.fecha,
                       m.tipo,
                       m.cantidad,
                       m.observaciones,
                       p.codigo_fabricante AS codigo,
                       p.nombre AS producto,
                       ao.nombre AS almacen_origen,
                       ad.nombre AS almacen_destino,
                       u.nombre_completo AS usuario
                FROM movimientos_inventario m
                LEFT JOIN inventario p ON m.producto_id = p.id
                LEFT JOIN almacenes ao ON m.almacen_origen_id = ao.id
                LEFT JOIN almacenes ad ON m.almacen_destino_id = ad.id
                LEFT JOIN usuarios u ON m.usuario_id = u.id
                WHERE DATE(m.fecha) BETWEEN ? AND ?";
        $params = [$desde, $hasta];
        if ($tipo !== '') {
            $sql .= " AND m.tipo = ?";
            $params[] = $tipo;
        }
        if ($almacenId !== null) {
            $sql .= " AND (m.almacen_origen_id = ? OR m.almacen_destino_id = ?)";
            $params[] = $almacenId;
            $params[] = $almacenId;
        }
        $sql .= " ORDER BY m.fecha DESC LIMIT 200";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function reportePrestamosActivos(string $desde, string $hasta): array
    {
        $db  = Database::getInstance()->getConnection();
        $sql = "SELECT pr.id,
                       pr.fecha_prestamo,
                       pr.fecha_estimada_devolucion,
                       p.nombre AS producto,
                       p.codigo_fabricante AS codigo,
                       u.nombre_completo AS empleado,
                       pr.observaciones,
                       pr.estado
                FROM prestamos pr
                LEFT JOIN inventario p ON pr.producto_id = p.id
                LEFT JOIN usuarios u ON pr.empleado_id = u.id
                WHERE pr.estado = 'Prestado'
                  AND DATE(pr.fecha_prestamo) BETWEEN ? AND ?
                ORDER BY pr.fecha_prestamo DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll();
    }

    private function reportePrestamosVencidos(): array
    {
        $db  = Database::getInstance()->getConnection();
        $sql = "SELECT pr.id,
                       pr.fecha_prestamo,
                       pr.fecha_estimada_devolucion,
                       TIMESTAMPDIFF(DAY, pr.fecha_estimada_devolucion, NOW()) AS dias_vencidos,
                       p.nombre AS producto,
                       p.codigo_fabricante AS codigo,
                       u.nombre_completo AS empleado,
                       pr.observaciones
                FROM prestamos pr
                LEFT JOIN inventario p ON pr.producto_id = p.id
                LEFT JOIN usuarios u ON pr.empleado_id = u.id
                WHERE pr.estado = 'Prestado'
                  AND pr.fecha_estimada_devolucion IS NOT NULL
                  AND pr.fecha_estimada_devolucion < NOW()
                ORDER BY pr.fecha_estimada_devolucion ASC";
        return $db->query($sql)->fetchAll();
    }

    private function reporteTopSalidas(string $desde, string $hasta, string $tipo = '', ?int $almacenId = null): array
    {
        $db  = Database::getInstance()->getConnection();
        $sql = "SELECT p.codigo_fabricante AS codigo,
                       p.nombre,
                       p.tipo,
                       SUM(m.cantidad) AS total_salidas,
                       SUM(m.cantidad * p.precio_unitario) AS costo_estimado,
                       COUNT(DISTINCT m.almacen_origen_id) AS almacenes_distintos,
                       MAX(ao.nombre) AS almacen_referencia
                FROM movimientos_inventario m
                INNER JOIN inventario p ON m.producto_id = p.id
                LEFT JOIN almacenes ao ON m.almacen_origen_id = ao.id
                WHERE m.tipo = 'Salida'
                  AND DATE(m.fecha) BETWEEN ? AND ?";
        $params = [$desde, $hasta];
        if ($tipo !== '' && in_array($tipo, Producto::tiposDisponibles(), true)) {
            $sql .= " AND p.tipo = ?";
            $params[] = $tipo;
        }
        if ($almacenId !== null) {
            $sql .= " AND m.almacen_origen_id = ?";
            $params[] = $almacenId;
        }
        $sql .= " GROUP BY p.id
                  ORDER BY total_salidas DESC
                  LIMIT 10";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        $almacenNombre = null;
        if ($almacenId !== null) {
            $stmtAlm = $db->prepare('SELECT nombre FROM almacenes WHERE id = ?');
            $stmtAlm->execute([$almacenId]);
            $almacenNombre = $stmtAlm->fetchColumn() ?: null;
        }

        return array_map(function (array $row) use ($almacenId, $almacenNombre) {
            $almacenLabel = $almacenId !== null
                ? ($almacenNombre ?? 'Almac?n seleccionado')
                : (($row['almacenes_distintos'] ?? 0) > 1
                    ? 'Varios almacenes'
                    : ($row['almacen_referencia'] ?? '-'));

            return [
                'codigo'         => $row['codigo'],
                'nombre'         => $row['nombre'],
                'tipo'           => $row['tipo'],
                'total_salidas'  => (float) ($row['total_salidas'] ?? 0),
                'costo_estimado' => (float) ($row['costo_estimado'] ?? 0),
                'almacen'        => $almacenLabel,
            ];
        }, $rows);
    }

    private function reporteComprasPorProveedor(string $desde, string $hasta, ?int $proveedorId = null): array
    {
        $db  = Database::getInstance()->getConnection();
        $sql = "SELECT oc.id,
                       oc.fecha,
                       oc.estado,
                       oc.total,
                       oc.rfc AS orden_rfc,
                       oc.numero_factura,
                       pr.nombre AS proveedor,
                       pr.rfc AS proveedor_rfc,
                       al.nombre AS almacen_destino,
                       u.nombre_completo AS creado_por,
                       COALESCE(SUM(do.cantidad), 0) AS total_items,
                       COALESCE(SUM(do.cantidad * do.precio_unitario), 0) AS subtotal
                FROM ordenes_compra oc
                LEFT JOIN proveedores pr ON oc.proveedor_id = pr.id
                LEFT JOIN almacenes al ON oc.almacen_destino_id = al.id
                LEFT JOIN usuarios u ON oc.usuario_id = u.id
                LEFT JOIN detalle_ordenes do ON do.orden_id = oc.id
                WHERE DATE(oc.fecha) BETWEEN ? AND ?";
        $params = [$desde, $hasta];
        if ($proveedorId !== null) {
            $sql .= " AND oc.proveedor_id = ?";
            $params[] = $proveedorId;
        }
        $sql .= " GROUP BY oc.id ORDER BY oc.fecha DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rowsRaw = $stmt->fetchAll() ?: [];

        $rows               = [];
        $resumenProveedores = [];
        $totalPeriodo       = 0.0;

        foreach ($rowsRaw as $row) {
            $subtotal = (float) ($row['subtotal'] ?? 0);
            $importe  = $row['total'] !== null ? (float) $row['total'] : $subtotal;
            if ($importe <= 0 && $subtotal > 0) {
                $importe = $subtotal;
            }

            $rows[] = [
                'orden_id'        => (int) ($row['id'] ?? 0),
                'fecha'           => $row['fecha'],
                'proveedor'       => $row['proveedor'] ?? 'Sin proveedor',
                'proveedor_rfc'   => $row['proveedor_rfc'] ?? '',
                'orden_rfc'       => $row['orden_rfc'] ?? '',
                'numero_factura'  => $row['numero_factura'] ?? '',
                'estado'          => $row['estado'] ?? '-',
                'almacen'         => $row['almacen_destino'] ?? '-',
                'total_items'     => (float) ($row['total_items'] ?? 0),
                'importe_detalle' => round($subtotal, 2),
                'importe_total'   => round($importe, 2),
                'creado_por'      => $row['creado_por'] ?? '-',
            ];

            $claveProveedor = $row['proveedor'] ?? 'Sin proveedor';
            if (! isset($resumenProveedores[$claveProveedor])) {
                $resumenProveedores[$claveProveedor] = [
                    'proveedor' => $claveProveedor,
                    'rfc'       => $row['proveedor_rfc'] ?? '',
                    'ordenes'   => 0,
                    'importe'   => 0.0,
                ];
            }
            $resumenProveedores[$claveProveedor]['ordenes']++;
            $resumenProveedores[$claveProveedor]['importe'] += $importe;
            $totalPeriodo += $importe;
        }

        usort($rows, static function ($a, $b) {
            return strcmp($b['fecha'], $a['fecha']);
        });

        $proveedoresResumen = array_values($resumenProveedores);
        usort($proveedoresResumen, static function ($a, $b) {
            return $b['importe'] <=> $a['importe'];
        });

        return [
            'rows'    => $rows,
            'resumen' => [
                'total'       => round($totalPeriodo, 2),
                'proveedores' => array_map(static function ($item) {
                    $item['importe'] = round($item['importe'], 2);
                    return $item;
                }, $proveedoresResumen),
            ],
        ];
    }

    private function reporteProductosPorTipo(): array
    {
        $db  = Database::getInstance()->getConnection();
        $sql = "SELECT p.codigo_fabricante AS codigo,
                       p.nombre,
                       p.tipo,
                       p.marca,
                       c.nombre AS categoria,
                       a.nombre AS almacen,
                       sa.ubicacion_fisica,
                       COALESCE(si.stock_total, 0) AS stock_actual,
                       p.stock_minimo,
                       um.apodo AS unidad
                FROM inventario p
                LEFT JOIN (SELECT producto_id, SUM(stock) AS stock_total FROM stock_almacen GROUP BY producto_id) si ON si.producto_id = p.id
                LEFT JOIN catalogo_categorias_inventario c ON p.categoria_id = c.id
                LEFT JOIN almacenes a ON p.almacen_id = a.id
                LEFT JOIN stock_almacen sa ON sa.producto_id = p.id AND sa.almacen_id = p.almacen_id
                LEFT JOIN catalogo_unidades_medida um ON p.unidad_medida_id = um.id
                ORDER BY p.tipo ASC, p.nombre ASC";
        $filas = $db->query($sql)->fetchAll();

        $resultado = [
            'consumibles'  => [],
            'herramientas' => [],
            'equipos'      => [],
            'todos'        => [],
        ];

        foreach ($filas as $fila) {
            $tipo = strtolower($fila['tipo'] ?? '');
            $resultado['todos'][] = $fila;
            if ($tipo === 'consumible') {
                $resultado['consumibles'][] = $fila;
            } elseif ($tipo === 'herramienta') {
                $resultado['herramientas'][] = $fila;
            } elseif ($tipo === 'equipo') {
                $resultado['equipos'][] = $fila;
            }
        }

        return $resultado;
    }

    private function exportCsv(string $section, array $datasets, bool $mostrarCostos, string $desde, string $hasta): void
    {
        if (! isset($datasets[$section])) {
            header('HTTP/1.1 400 Bad Request');
            echo 'Reporte no disponible.';
            return;
        }

        $config = $this->datasetConfig($section, $mostrarCostos);
        if ($config === null) {
            header('HTTP/1.1 400 Bad Request');
            echo 'Reporte no disponible.';
            return;
        }

        if (($config['requiresCost'] ?? false) && ! $mostrarCostos) {
            header('HTTP/1.1 403 Forbidden');
            echo 'No tienes permisos para exportar este reporte.';
            return;
        }

        $rows         = $datasets[$section];
        $filenameBase = $config['filename'] ?? ('reporte_' . $section);
        $filename     = $filenameBase . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        fputs($output, chr(239) . chr(187) . chr(191));

        $columns = $config['columns'];
        fputcsv($output, array_column($columns, 'label'));

        if (empty($rows)) {
            fputcsv($output, array_fill(0, count($columns), ''));
            fclose($output);
            return;
        }

        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $col) {
                $value  = $col['value'];
                $line[] = (string) $value($row);
            }
            fputcsv($output, $line);
        }

        fclose($output);
        ActivityLogger::log('reporte_export', 'Exportacion CSV de ' . $section, [
            'section' => $section,
            'desde'   => $desde,
            'hasta'   => $hasta,
        ]);
    }

    private function exportPdf(string $section, array $datasets, bool $mostrarCostos, string $desde, string $hasta): void
    {
        if (! isset($datasets[$section])) {
            header('HTTP/1.1 400 Bad Request');
            echo 'Reporte no disponible.';
            return;
        }

        $config = $this->datasetConfig($section, $mostrarCostos);
        if ($config === null) {
            header('HTTP/1.1 400 Bad Request');
            echo 'Reporte no disponible.';
            return;
        }

        if (($config['requiresCost'] ?? false) && ! $mostrarCostos) {
            header('HTTP/1.1 403 Forbidden');
            echo 'No tienes permisos para exportar este reporte.';
            return;
        }

        $rows     = $datasets[$section];
        $columns  = $config['columns'];
        $title    = $config['title'] ?? 'Reporte';
        $subtitle = $config['subtitle'] ?? null;
        if ($subtitle instanceof \Closure) {
            $subtitle = $subtitle($desde, $hasta);
        }

        $lines   = [];
        $lines[] = $title;
        if ($subtitle) {
            $lines[] = $subtitle;
        } elseif (in_array($section, ['movimientos', 'prestamos_abiertos', 'prestamos_vencidos', 'top_salidas'], true)) {
            $lines[] = 'Periodo: ' . $desde . ' al ' . $hasta;
        }
        $lines[]      = '';
        $headerLabels = array_column($columns, 'label');
        $lines[]      = implode(' | ', $headerLabels);
        $lines[]      = str_repeat('-', min(110, strlen($lines[count($lines) - 1])));

        if (empty($rows)) {
            $lines[] = 'Sin registros para este reporte.';
        } else {
            foreach ($rows as $row) {
                $lineParts = [];
                foreach ($columns as $col) {
                    $value       = $col['value'];
                    $lineParts[] = (string) $value($row);
                }
                $lines[] = implode(' | ', $lineParts);
            }
        }

        $pdf          = $this->buildPdfDocument($lines);
        $filenameBase = $config['filename'] ?? ('reporte_' . $section);
        $filename     = $filenameBase . '_' . date('Ymd_His') . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=' . $filename);
        echo $pdf;
        ActivityLogger::log('reporte_export', 'Exportacion PDF de ' . $section, [
            'section' => $section,
            'desde'   => $desde,
            'hasta'   => $hasta,
        ]);
    }

    public function rotacion(): void
    {
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

        $db    = Database::getInstance()->getConnection();
        $desde = $this->parseDate($_GET['from'] ?? date('Y-m-01'));
        $hasta = $this->parseDate($_GET['to'] ?? date('Y-m-d'));
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        $tipoFiltro = $_GET['tipo'] ?? '';
        $almacenId  = $_GET['almacen_id'] ?? '';
        $almacenSeleccionado = $almacenId !== '' ? (int) $almacenId : null;
        $stockJoin = $almacenSeleccionado
            ? " INNER JOIN (SELECT producto_id, almacen_id, stock AS stock_total FROM stock_almacen WHERE almacen_id = {$almacenSeleccionado}) si ON si.producto_id = p.id"
            : ' LEFT JOIN (SELECT producto_id, SUM(stock) AS stock_total FROM stock_almacen GROUP BY producto_id) si ON si.producto_id = p.id';
        $almacenJoin = $almacenSeleccionado
            ? ' LEFT JOIN almacenes a ON a.id = si.almacen_id'
            : ' LEFT JOIN almacenes a ON p.almacen_id = a.id';

        $sql = "SELECT p.id,
                       p.codigo_fabricante AS codigo,
                       p.nombre,
                       p.tipo,
                       COALESCE(si.stock_total, 0) AS stock_actual,
                       p.stock_minimo,
                       a.nombre AS almacen,
                       SUM(CASE WHEN m.tipo = 'Salida' THEN m.cantidad ELSE 0 END) AS total_salidas,
                       SUM(CASE WHEN m.tipo = 'Entrada' THEN m.cantidad ELSE 0 END) AS total_entradas,
                       MAX(m.fecha) AS ultimo_movimiento
                FROM inventario p
                {$stockJoin}
                {$almacenJoin}
                LEFT JOIN movimientos_inventario m
                       ON m.producto_id = p.id
                      AND DATE(m.fecha) BETWEEN ? AND ?";
        $params = [$desde, $hasta];

        $where = [];
        if ($tipoFiltro !== '' && in_array($tipoFiltro, Producto::tiposDisponibles(), true)) {
            $where[]  = 'p.tipo = ?';
            $params[] = $tipoFiltro;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY p.id ORDER BY total_salidas DESC, p.nombre ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $filas = $stmt->fetchAll() ?: [];

        $rotacion = [];
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

            $rotacion[] = array_merge($fila, [
                'indice'              => $indice,
                'clasificacion'       => $clasificacion,
                'dias_sin_movimiento' => $diasSinMovimiento,
                'salidas'             => $salidas,
                'entradas'            => $entradas,
            ]);
        }

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

        $almacenes        = $db->query('SELECT id, nombre FROM almacenes ORDER BY nombre ASC')->fetchAll();
        $tiposDisponibles = Producto::tiposDisponibles();
        include __DIR__ . '/../views/inventario/reportes/rotacion.php';
    }

    /**
     * @return array{columns: array<int, array{label:string, value:\Closure}>, title?:string, subtitle?:string|\Closure, filename?:string, requiresCost?:bool}|null
     */
    private function datasetConfig(string $section, bool $mostrarCostos): ?array
    {
        $formatNumber = fn($value, $decimals = 2) => number_format((float) $value, $decimals, '.', '');

        switch ($section) {
            case 'inventario_bajo':
                return [
                    'title'    => 'Inventario por debajo del stock minimo',
                    'filename' => 'inventario_bajo',
                    'columns'  => [
                        ['label' => 'Codigo', 'value' => fn($row) => $row['codigo']],
                        ['label' => 'Producto', 'value' => fn($row) => $row['nombre']],
                        ['label' => 'Tipo', 'value' => fn($row) => $row['tipo']],
                        ['label' => 'Categoria', 'value' => fn($row) => $row['categoria']],
                        ['label' => 'Almacen', 'value' => fn($row) => $row['almacen']],
                        ['label' => 'Stock actual', 'value' => fn($row) => $formatNumber($row['stock_actual'])],
                        ['label' => 'Stock minimo', 'value' => fn($row) => $formatNumber($row['stock_minimo'])],
                        ['label' => 'Unidad', 'value' => fn($row) => $row['unidad']],
                    ],
                ];
            case 'valor_almacen':
                return [
                    'title'        => 'Valor del inventario por almacen',
                    'filename'     => 'valor_inventario_almacen',
                    'requiresCost' => true,
                    'columns'      => [
                        ['label' => 'Almacen', 'value' => fn($row) => $row['almacen']],
                        ['label' => 'Productos', 'value' => fn($row) => $row['productos']],
                        ['label' => 'Unidades', 'value' => fn($row) => $formatNumber($row['unidades'])],
                        ['label' => 'Valor total (MXN)', 'value' => fn($row) => $formatNumber($row['valor_total'])],
                    ],
                ];
            case 'movimientos':
                return [
                    'title'    => 'Movimientos de inventario',
                    'filename' => 'movimientos_inventario',
                    'columns'  => [
                        ['label' => 'Fecha', 'value' => fn($row) => $row['fecha']],
                        ['label' => 'Tipo', 'value' => fn($row) => $row['tipo']],
                        ['label' => 'Codigo', 'value' => fn($row) => $row['codigo']],
                        ['label' => 'Producto', 'value' => fn($row) => $row['producto']],
                        ['label' => 'Cantidad', 'value' => fn($row) => $formatNumber($row['cantidad'])],
                        ['label' => 'Almacen origen', 'value' => fn($row) => $row['almacen_origen'] ?? '-'],
                        ['label' => 'Almacen destino', 'value' => fn($row) => $row['almacen_destino'] ?? '-'],
                        ['label' => 'Usuario', 'value' => fn($row) => $row['usuario'] ?? '-'],
                        ['label' => 'Observaciones', 'value' => fn($row) => $row['observaciones'] ?? '-'],
                    ],
                ];
            case 'prestamos_abiertos':
                return [
                    'title'    => 'Herramientas prestadas',
                    'filename' => 'prestamos_herramientas',
                    'columns'  => [
                        ['label' => 'ID', 'value' => fn($row) => $row['id']],
                        ['label' => 'Fecha prestamo', 'value' => fn($row) => $row['fecha_prestamo']],
                        ['label' => 'Fecha estimada', 'value' => fn($row) => $row['fecha_estimada_devolucion'] ?? '-'],
                        ['label' => 'Producto', 'value' => fn($row) => $row['producto']],
                        ['label' => 'Codigo', 'value' => fn($row) => $row['codigo']],
                        ['label' => 'Empleado', 'value' => fn($row) => $row['empleado']],
                        ['label' => 'Observaciones', 'value' => fn($row) => $row['observaciones'] ?? '-'],
                        ['label' => 'Estado', 'value' => fn($row) => $row['estado']],
                    ],
                ];
            case 'prestamos_vencidos':
                return [
                    'title'    => 'Prestamos vencidos',
                    'filename' => 'prestamos_vencidos',
                    'columns'  => [
                        ['label' => 'ID', 'value' => fn($row) => $row['id']],
                        ['label' => 'Fecha prestamo', 'value' => fn($row) => $row['fecha_prestamo']],
                        ['label' => 'Fecha estimada', 'value' => fn($row) => $row['fecha_estimada_devolucion'] ?? '-'],
                        ['label' => 'Dias vencidos', 'value' => fn($row) => $row['dias_vencidos']],
                        ['label' => 'Producto', 'value' => fn($row) => $row['producto']],
                        ['label' => 'Codigo', 'value' => fn($row) => $row['codigo']],
                        ['label' => 'Empleado', 'value' => fn($row) => $row['empleado']],
                        ['label' => 'Observaciones', 'value' => fn($row) => $row['observaciones'] ?? '-'],
                    ],
                ];
            case 'top_salidas':
                $config = [
                    'title'    => 'Top de productos mas retirados',
                    'filename' => 'top_salidas',
                    'columns'  => [
                        ['label' => 'Codigo', 'value' => fn($row) => $row['codigo']],
                        ['label' => 'Producto', 'value' => fn($row) => $row['nombre']],
                        ['label' => 'Tipo', 'value' => fn($row) => $row['tipo'] ?? '-'],
                        ['label' => 'Almacen', 'value' => fn($row) => $row['almacen'] ?? '-'],
                        ['label' => 'Cantidad salida', 'value' => fn($row) => $formatNumber($row['total_salidas'])],
                    ],
                ];
                if ($mostrarCostos) {
                    $config['columns'][] = ['label' => 'Costo estimado (MXN)', 'value' => fn($row) => $formatNumber($row['costo_estimado'])];
                }
                return $config;
            case 'compras_proveedor':
                return [
                    'title'        => 'Compras por proveedor',
                    'filename'     => 'compras_proveedor',
                    'requiresCost' => true,
                    'columns'      => [
                        ['label' => 'Orden', 'value' => fn($row) => $row['orden_id']],
                        ['label' => 'Fecha', 'value' => fn($row) => $row['fecha']],
                        ['label' => 'Proveedor', 'value' => fn($row) => $row['proveedor']],
                        ['label' => 'RFC proveedor', 'value' => fn($row) => $row['proveedor_rfc'] ?? ''],
                        ['label' => 'RFC orden', 'value' => fn($row) => $row['orden_rfc'] ?? ''],
                        ['label' => 'Factura', 'value' => fn($row) => $row['numero_factura'] ?? ''],
                        ['label' => 'Estado', 'value' => fn($row) => $row['estado']],
                        ['label' => 'Almacen destino', 'value' => fn($row) => $row['almacen']],
                        ['label' => 'Productos', 'value' => fn($row) => $formatNumber($row['total_items'])],
                        ['label' => 'Importe detalle (MXN)', 'value' => fn($row) => $formatNumber($row['importe_detalle'])],
                        ['label' => 'Importe total (MXN)', 'value' => fn($row) => $formatNumber($row['importe_total'])],
                        ['label' => 'Registrado por', 'value' => fn($row) => $row['creado_por'] ?? '-'],
                    ],
                ];
            case 'productos_consumibles':
                return [
                    'title'    => 'Catalogo de consumibles',
                    'filename' => 'productos_consumibles',
                    'columns'  => [
                        ['label' => 'Codigo', 'value' => fn($row) => $row['codigo']],
                        ['label' => 'Producto', 'value' => fn($row) => $row['nombre']],
                        ['label' => 'Marca', 'value' => fn($row) => $row['marca'] ?? '-'],
                        ['label' => 'Categoria', 'value' => fn($row) => $row['categoria'] ?? '-'],
                        ['label' => 'Almacen', 'value' => fn($row) => $row['almacen'] ?? '-'],
                        ['label' => 'Ubicacion fisica', 'value' => fn($row) => $row['ubicacion_fisica'] ?? '-'],
                        ['label' => 'Stock actual', 'value' => fn($row) => $formatNumber($row['stock_actual'])],
                        ['label' => 'Stock minimo', 'value' => fn($row) => $formatNumber($row['stock_minimo'])],
                        ['label' => 'Unidad', 'value' => fn($row) => $row['unidad'] ?? '-'],
                        ['label' => 'Estado', 'value' => fn($row) => $row['estado'] ?? '-'],
                    ],
                ];
            case 'productos_herramientas':
                return [
                    'title'    => 'Catalogo de herramientas',
                    'filename' => 'productos_herramientas',
                    'columns'  => [
                        ['label' => 'Codigo', 'value' => fn($row) => $row['codigo']],
                        ['label' => 'Producto', 'value' => fn($row) => $row['nombre']],
                        ['label' => 'Marca', 'value' => fn($row) => $row['marca'] ?? '-'],
                        ['label' => 'Categoria', 'value' => fn($row) => $row['categoria'] ?? '-'],
                        ['label' => 'Almacen', 'value' => fn($row) => $row['almacen'] ?? '-'],
                        ['label' => 'Ubicacion fisica', 'value' => fn($row) => $row['ubicacion_fisica'] ?? '-'],
                        ['label' => 'Stock actual', 'value' => fn($row) => $formatNumber($row['stock_actual'])],
                        ['label' => 'Stock minimo', 'value' => fn($row) => $formatNumber($row['stock_minimo'])],
                        ['label' => 'Unidad', 'value' => fn($row) => $row['unidad'] ?? '-'],
                        ['label' => 'Estado', 'value' => fn($row) => $row['estado'] ?? '-'],
                    ],
                ];
            case 'productos_equipos':
                return [
                    'title'    => 'Catalogo de equipos',
                    'filename' => 'productos_equipos',
                    'columns'  => [
                        ['label' => 'Codigo', 'value' => fn($row) => $row['codigo']],
                        ['label' => 'Producto', 'value' => fn($row) => $row['nombre']],
                        ['label' => 'Marca', 'value' => fn($row) => $row['marca'] ?? '-'],
                        ['label' => 'Categoria', 'value' => fn($row) => $row['categoria'] ?? '-'],
                        ['label' => 'Almacen', 'value' => fn($row) => $row['almacen'] ?? '-'],
                        ['label' => 'Ubicacion fisica', 'value' => fn($row) => $row['ubicacion_fisica'] ?? '-'],
                        ['label' => 'Stock actual', 'value' => fn($row) => $formatNumber($row['stock_actual'])],
                        ['label' => 'Stock minimo', 'value' => fn($row) => $formatNumber($row['stock_minimo'])],
                        ['label' => 'Unidad', 'value' => fn($row) => $row['unidad'] ?? '-'],
                        ['label' => 'Estado', 'value' => fn($row) => $row['estado'] ?? '-'],
                    ],
                ];
            case 'productos_todos':
                return [
                    'title'    => 'Catalogo completo de productos',
                    'filename' => 'productos_todos',
                    'columns'  => [
                        ['label' => 'Codigo', 'value' => fn($row) => $row['codigo']],
                        ['label' => 'Producto', 'value' => fn($row) => $row['nombre']],
                        ['label' => 'Marca', 'value' => fn($row) => $row['marca'] ?? '-'],
                        ['label' => 'Categoria', 'value' => fn($row) => $row['categoria'] ?? '-'],
                        ['label' => 'Almacen', 'value' => fn($row) => $row['almacen'] ?? '-'],
                        ['label' => 'Ubicacion fisica', 'value' => fn($row) => $row['ubicacion_fisica'] ?? '-'],
                        ['label' => 'Stock actual', 'value' => fn($row) => $formatNumber($row['stock_actual'])],
                        ['label' => 'Stock minimo', 'value' => fn($row) => $formatNumber($row['stock_minimo'])],
                        ['label' => 'Unidad', 'value' => fn($row) => $row['unidad'] ?? '-'],
                        ['label' => 'Estado', 'value' => fn($row) => $row['estado'] ?? '-'],
                    ],
                ];
            default:
                return null;
        }
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

    private function escapePdfText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);
        return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }
}

