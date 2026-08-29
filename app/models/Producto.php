<?php
require_once __DIR__ . '/../helpers/Database.php';

class Producto
{
    private const ESTADOS = ['Nuevo', 'Usado', 'Dañado', 'En reparación'];
    private const TIPOS = ['Consumible', 'Herramienta', 'Equipo'];

    public static function all($filtros = [])
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT p.*,
                       c.nombre AS categoria,
                       a.nombre AS almacen,
                       um.nombre AS unidad_medida_nombre,
                       um.apodo AS unidad_abreviacion
                FROM inventario p
                LEFT JOIN catalogo_categorias_inventario c ON p.categoria_id = c.id
                LEFT JOIN almacenes a ON p.almacen_id = a.id
                LEFT JOIN catalogo_unidades_medida um ON p.unidad_medida_id = um.id
                WHERE 1=1";
        $params = [];

        if (!empty($filtros['buscar'])) {
            $buscar = '%' . trim($filtros['buscar']) . '%';
            $sql .= " AND (p.nombre LIKE ? OR p.codigo LIKE ? OR p.codigo_barras LIKE ? OR p.descripcion LIKE ? OR p.tags LIKE ?)";
            array_push($params, $buscar, $buscar, $buscar, $buscar, $buscar);
        }

        if (!empty($filtros['nombre'])) {
            $sql .= " AND p.nombre LIKE ?";
            $params[] = '%' . trim($filtros['nombre']) . '%';
        }

        if (!empty($filtros['codigo'])) {
            $sql .= " AND p.codigo LIKE ?";
            $params[] = '%' . trim($filtros['codigo']) . '%';
        }

        if (!empty($filtros['codigo_barras'])) {
            $sql .= " AND p.codigo_barras = ?";
            $params[] = trim($filtros['codigo_barras']);
        }

        if (!empty($filtros['tipo']) && in_array($filtros['tipo'], self::TIPOS, true)) {
            $sql .= " AND p.tipo = ?";
            $params[] = $filtros['tipo'];
        }

        if (!empty($filtros['categoria_id'])) {
            $sql .= " AND p.categoria_id = ?";
            $params[] = (int) $filtros['categoria_id'];
        }

        if (!empty($filtros['almacen_id'])) {
            $sql .= " AND p.almacen_id = ?";
            $params[] = (int) $filtros['almacen_id'];
        }

        if (!empty($filtros['estado']) && in_array($filtros['estado'], self::ESTADOS, true)) {
            $sql .= " AND p.estado = ?";
            $params[] = $filtros['estado'];
        }

        if (!empty($filtros['activo_id'])) {
            $sql .= " AND p.activo_id = ?";
            $params[] = (int) $filtros['activo_id'];
        }

        if (!empty($filtros['stock_flag'])) {
            switch ($filtros['stock_flag']) {
                case 'bajo':
                    $sql .= " AND p.stock_actual < p.stock_minimo";
                    break;
                case 'sin':
                    $sql .= " AND p.stock_actual <= 0";
                    break;
                case 'suficiente':
                    $sql .= " AND p.stock_actual >= p.stock_minimo";
                    break;
            }
        }

        if (!empty($filtros['unidad_medida_id'])) {
            $sql .= " AND p.unidad_medida_id = ?";
            $params[] = (int) $filtros['unidad_medida_id'];
        }

        if (!empty($filtros['tags'])) {
            $sql .= " AND p.tags LIKE ?";
            $params[] = '%' . trim($filtros['tags']) . '%';
        }

        if (!empty($filtros['fecha_desde'])) {
            $dt = DateTime::createFromFormat('Y-m-d', $filtros['fecha_desde']);
            if ($dt) {
                $sql .= " AND DATE(p.created_at) >= ?";
                $params[] = $dt->format('Y-m-d');
            }
        }

        if (!empty($filtros['fecha_hasta'])) {
            $dt = DateTime::createFromFormat('Y-m-d', $filtros['fecha_hasta']);
            if ($dt) {
                $sql .= " AND DATE(p.created_at) <= ?";
                $params[] = $dt->format('Y-m-d');
            }
        }

        if (!empty($filtros['valor_min']) && is_numeric($filtros['valor_min'])) {
            $sql .= " AND (p.costo_compra * p.stock_actual) >= ?";
            $params[] = (float) $filtros['valor_min'];
        }

        if (!empty($filtros['valor_max']) && is_numeric($filtros['valor_max'])) {
            $sql .= " AND (p.costo_compra * p.stock_actual) <= ?";
            $params[] = (float) $filtros['valor_max'];
        }

        $sql .= " ORDER BY p.nombre ASC";

        if (isset($filtros['limit']) && is_numeric($filtros['limit'])) {
            $limit = max(1, (int) $filtros['limit']);
            $sql .= " LIMIT {$limit}";
            if (isset($filtros['offset']) && is_numeric($filtros['offset'])) {
                $offset = max(0, (int) $filtros['offset']);
                $sql .= " OFFSET {$offset}";
            }
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function create($data)
    {
        $data['codigo_fabricante'] = strtoupper(trim((string) ($data['codigo_fabricante'] ?? '')));
        $data['codigos_barras'] = isset($data['codigos_barras']) && $data['codigos_barras'] !== ''
            ? strtoupper(trim((string) $data['codigos_barras']))
            : null;
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO inventario (
            sku, codigo_fabricante, num_serie, codigo_sat, codigos_barras, nombre, descripcion, tipo, categoria_id, 
            marca, modelo, unidad_medida_id, precio_unitario, precio_iva, precio_beneficio, pais_origen, stock_minimo, stock_actual, 
            color, almacen_id, ubicacion_fisica, estado, imagen_url, tags
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([ //Regresa true o false dependiendo si se pudo ejecutar la consulta
            $data['sku'], $data['codigo_fabricante'], $data['num_serie'], $data['codigo_sat'], $data['codigos_barras'], $data['nombre'], $data['descripcion'], $data['tipo'], $data['categoria_id'],
            $data['marca'], $data['modelo'], $data['unidad_medida_id'], $data['precio_unitario'], $data['precio_unitario']*1.16, $data['precio_unitario']*1.508, $data['pais_origen'], $data['stock_minimo'], $data['stock_actual'],
            $data['color'], $data['almacen_id'], $data['ubicacion_fisica'], $data['estado'], $data['imagen_url'], $data['tags']
        ]);
    }

    public static function find($id){
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT p.*,
                                    c.nombre AS categoria,
                                    a.nombre AS almacen,
                                    um.nombre AS unidad_medida_nombre,
                                    um.apodo AS unidad_apodo,
                                    fs.folio AS folio_solicitud
                            FROM inventario p
                            LEFT JOIN catalogo_categorias_inventario c ON p.categoria_id = c.id
                            LEFT JOIN almacenes a ON p.almacen_id = a.id
                            LEFT JOIN catalogo_unidades_medida um ON p.unidad_medida_id = um.id
                            LEFT JOIN solicitudes_material fs ON p.last_request_id = fs.folio
                            WHERE p.id = ?");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ?: null;
    }

    private static function ultimaSolicitudPorProducto(\PDO $db, int $productoId): ?array
    {
        $sql = "SELECT s.usuario_id, s.fecha_solicitud, u.nombre_completo
                FROM detalle_solicitud d
                INNER JOIN solicitudes_material s ON s.id = d.solicitud_id
                LEFT JOIN usuarios u ON s.usuario_id = u.id
                WHERE d.producto_id = ?
                ORDER BY s.fecha_solicitud DESC
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$productoId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByCodigo($codigo)
    {
        $codigo = strtoupper(trim((string) $codigo));
        if ($codigo === '') {
            return false;
        }
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM inventario WHERE codigo_fabricante = ?");
        $stmt->execute([$codigo]);
        return $stmt->fetch();
    }

    public static function findByCodigoBarras(string $codigoBarras)
    {
        $codigoBarras = strtoupper(trim($codigoBarras));
        if ($codigoBarras === '') {
            return false;
        }
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM inventario WHERE codigo_barras = ?");
        $stmt->execute([$codigoBarras]);
        return $stmt->fetch();
    }

    public static function codigoBarrasExiste(string $codigoBarras, ?int $exceptId = null): bool
    {
        $codigoBarras = strtoupper(trim($codigoBarras));
        if ($codigoBarras === '') {
            return false;
        }
        $db = Database::getInstance()->getConnection();
        if ($exceptId) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM inventario WHERE codigo_barras = ? AND id <> ?");
            $stmt->execute([$codigoBarras, $exceptId]);
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM inventario WHERE codigo_barras = ?");
            $stmt->execute([$codigoBarras]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function actualizarCodigoBarras(int $id, string $codigo): bool
    {
        $codigo = strtoupper(trim($codigo));
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE inventario SET codigo_barras = ? WHERE id = ?");
        return $stmt->execute([$codigo, $id]);
    }

    public static function existsCodigoExcept($codigo, $id)
    {
        $db = Database::getInstance()->getConnection();
        $codigo = strtoupper(trim((string) $codigo));
        if ($codigo === '') {
            return false;
        }
        $stmt = $db->prepare("SELECT * FROM inventario WHERE codigo = ? AND id != ?");
        $stmt->execute([$codigo, $id]);
        return $stmt->fetch();
    }

    public static function update($id, $data)
    {
        $db = Database::getInstance()->getConnection();
        $sql = "UPDATE inventario SET
            codigo=?, codigo_barras=?, nombre=?, descripcion=?, proveedor_id=?, categoria_id=?,
            peso=?, ancho=?, alto=?, profundidad=?, unidad_medida_id=?, clase_categoria=?,
            marca=?, color=?, forma=?, especificaciones_tecnicas=?, origen=?,
            costo_compra=?, precio_venta=?, stock_minimo=?, stock_actual=?, almacen_id=?,
            ubicacion_fisica=?, estado=?, tipo=?, imagen_url=?, last_requested_by_user_id=?, last_request_date=?, tags=?
            WHERE id=?";
        $stmt = $db->prepare($sql);
        $data['codigo'] = strtoupper(trim((string) ($data['codigo'] ?? '')));
        $data['codigo_barras'] = isset($data['codigo_barras']) && $data['codigo_barras'] !== ''
            ? strtoupper(trim((string) $data['codigo_barras']))
            : null;
        return $stmt->execute([
            $data['codigo'], $data['codigo_barras'], $data['nombre'], $data['descripcion'], $data['proveedor_id'], $data['categoria_id'],
            $data['peso'], $data['ancho'], $data['alto'], $data['profundidad'], $data['unidad_medida_id'],
            $data['clase_categoria'], $data['marca'], $data['color'], $data['forma'], $data['especificaciones_tecnicas'],
            $data['origen'], $data['costo_compra'], $data['precio_venta'], $data['stock_minimo'], $data['stock_actual'],
            $data['almacen_id'], $data['ubicacion_fisica'], $data['estado'], $data['tipo'], $data['imagen_url'],
            $data['last_requested_by_user_id'], $data['last_request_date'], $data['tags'], $id
        ]);
    }

    public static function delete($id)
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            self::deleteRelatedRecords($db, (int) $id);

            $stmt = $db->prepare("DELETE FROM inventario WHERE id=?");
            $stmt->execute([$id]);

            $db->commit();
            return true;
        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    private static function deleteRelatedRecords(\PDO $db, int $productoId): void
    {
        $relations = [
            'detalle_ordenes'        => 'producto_id',
            'detalle_solicitud'      => 'producto_id',
            'movimientos_inventario' => 'producto_id',
            'prestamos'              => 'producto_id',
            'solicitudes'            => 'producto_id',
            'stock_almacen'          => 'producto_id',
        ];

        foreach ($relations as $table => $column) {
            $stmt = $db->prepare("DELETE FROM {$table} WHERE {$column} = ?");
            $stmt->execute([$productoId]);
        }
    }

    public static function setActive($id, $active)
    {
        $db = Database::getInstance()->getConnection();
        $estado = $active ? 1 : 2;
        $stmt = $db->prepare("UPDATE inventario SET activo_id=? WHERE id=?");
        return $stmt->execute([$estado, $id]);
    }

    private static bool $stockTableChecked = false;

    public static function ensureStockTableReady(): void
    {
        $db = Database::getInstance()->getConnection();
        self::ensureStockTable($db);
    }

    private static function ensureStockTable(\PDO $db): void
    {
        if (self::$stockTableChecked) return;
        $sql = "CREATE TABLE IF NOT EXISTS stock_almacen (
                    producto_id INT NOT NULL,
                    almacen_id INT NOT NULL,
                    stock DECIMAL(10,2) NOT NULL DEFAULT 0,
                    PRIMARY KEY (producto_id, almacen_id),
                    KEY idx_stock_almacen_prod (producto_id),
                    KEY idx_stock_almacen_alm (almacen_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $db->exec($sql);
        self::$stockTableChecked = true;
    }

    public static function sumarStock($id, $cantidad, ?int $almacenId = null)
    {
        $db = Database::getInstance()->getConnection();
        // Stock global
        $stmt = $db->prepare("UPDATE inventario SET stock_actual = stock_actual + ? WHERE id = ?");
        $ok = $stmt->execute([$cantidad, $id]);
        if (!$ok) return false;

        // Stock por almacén
        if ($almacenId) {
            $up = $db->prepare("INSERT INTO stock_almacen (producto_id, almacen_id, stock)
                                VALUES (?, ?, ?)
                                ON DUPLICATE KEY UPDATE stock = stock + VALUES(stock)");
            return $up->execute([(int)$id, (int)$almacenId, (float)$cantidad]);
        }
        return true;
    }

    public static function restarStock($id, $cantidad, ?int $almacenId = null){
        $db = Database::getInstance()->getConnection();
        $cantidadFloat = (float) $cantidad;

        if ($almacenId) {
            $stockAlmacen = (float) self::stockEnAlmacen($id, $almacenId);

            if ($cantidadFloat > $stockAlmacen) {
                return false;
            }
        } else {
            $stmtCheck = $db->prepare("SELECT stock_actual FROM inventario WHERE id = ?");
            $stmtCheck->execute([$id]);
            $stockGlobal = (float) $stmtCheck->fetchColumn();

            if ($cantidadFloat > $stockGlobal) {
                return false;
            }
        }

        $stmt = $db->prepare("UPDATE inventario SET stock_actual = GREATEST(stock_actual - ?, 0) WHERE id = ?");
        $ok = $stmt->execute([$cantidadFloat, $id]);
        if (!$ok) return false;

        if ($almacenId) {
            $nuevo = max(0.0, $stockAlmacen - $cantidadFloat);
            $up = $db->prepare("INSERT INTO stock_almacen (producto_id, almacen_id, stock)
                                VALUES (?, ?, ?)
                                ON DUPLICATE KEY UPDATE stock = VALUES(stock)");
            return $up->execute([(int)$id, (int)$almacenId, $nuevo]);
        }

        return true;
    }

    public static function stockEnAlmacen(int $productoId, int $almacenId): float
    {
        $db = Database::getInstance()->getConnection();
        self::ensureStockTable($db);
        $stmt = $db->prepare("SELECT stock FROM stock_almacen WHERE producto_id = ? AND almacen_id = ?");
        $stmt->execute([$productoId, $almacenId]);
        $row = $stmt->fetch();
        return (float)($row['stock'] ?? 0);
    }

    public static function moverStock(int $productoId, int $origenId, int $destinoId, float $cantidad): bool
    {
        if ($cantidad <= 0) return false;
        $db = Database::getInstance()->getConnection();
        self::ensureStockTable($db);
        $db->beginTransaction();
        try {
            $disp = self::stockEnAlmacen($productoId, $origenId);
            if ($cantidad > $disp) {
                $db->rollBack();
                return false;
            }
            // Restar en origen (no dejar negativo)
            self::restarStock($productoId, $cantidad, $origenId);
            // Sumar en destino
            self::sumarStock($productoId, $cantidad, $destinoId);
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }

    public static function actualizarAlmacen(int $id, int $almacenId): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE inventario SET almacen_id = ? WHERE id = ?");
        return $stmt->execute([$almacenId, $id]);
    }

    public static function allInventario($filtros = [])
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT p.*,
                       c.nombre AS categoria,
                       u.abreviacion AS unidad,
                       (p.costo_compra * p.stock_actual) AS valor_total,
                       (SELECT MAX(created_at) FROM movimientos_inventario m WHERE m.producto_id = p.id) AS ultimo_movimiento,
                       epa.nombre AS estado_activo
                FROM inventario p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                LEFT JOIN unidades_medida u ON p.unidad_medida_id = u.id
                LEFT JOIN estados_producto_activo epa ON p.activo_id = epa.id
                WHERE 1=1";
        $params = [];
        if (!empty($filtros['q'])) {
            $sql .= " AND (p.nombre LIKE ? OR p.codigo LIKE ? OR p.codigo_barras LIKE ?)";
            $params[] = '%' . $filtros['q'] . '%';
            $params[] = '%' . $filtros['q'] . '%';
            $params[] = '%' . $filtros['q'] . '%';
        }
        if (!empty($filtros['categoria'])) {
            $sql .= " AND c.nombre = ?";
            $params[] = $filtros['categoria'];
        }
        $sql .= " ORDER BY p.nombre ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }


    public static function inventarioListado(array $filtros, ?int $limit = null, int $offset = 0): array
    {
        $db = Database::getInstance()->getConnection();

        if (!empty($filtros['q']) && empty($filtros['buscar'])) {
            $filtros['buscar'] = $filtros['q'];
        }
        if (!empty($filtros['categoria']) && empty($filtros['categoria_id'])) {
            $filtros['categoria_nombre'] = $filtros['categoria'];
        }

        $condiciones = [];
        $params = [];

        if (!empty($filtros['buscar'])) {
            $buscar = '%' . trim($filtros['buscar']) . '%';
            $condiciones[] = '(p.nombre LIKE ? OR p.codigo LIKE ? OR p.codigo_barras LIKE ? OR IFNULL(p.descripcion, "") LIKE ? OR IFNULL(p.tags, "") LIKE ? OR IFNULL(pr.nombre, "") LIKE ?)';
            array_push($params, $buscar, $buscar, $buscar, $buscar, $buscar, $buscar);
        }

        if (!empty($filtros['nombre'])) {
            $condiciones[] = 'p.nombre LIKE ?';
            $params[] = '%' . trim($filtros['nombre']) . '%';
        }

        if (!empty($filtros['codigo'])) {
            $condiciones[] = 'p.codigo LIKE ?';
            $params[] = '%' . trim($filtros['codigo']) . '%';
        }

        if (!empty($filtros['codigo_barras'])) {
            $condiciones[] = 'p.codigo_barras = ?';
            $params[] = trim($filtros['codigo_barras']);
        }

        if (!empty($filtros['categoria_id'])) {
            $condiciones[] = 'p.categoria_id = ?';
            $params[] = (int) $filtros['categoria_id'];
        } elseif (!empty($filtros['categoria_nombre'])) {
            $condiciones[] = 'c.nombre = ?';
            $params[] = trim($filtros['categoria_nombre']);
        }

        if (!empty($filtros['almacen_id'])) {
            $condiciones[] = 'p.almacen_id = ?';
            $params[] = (int) $filtros['almacen_id'];
        }

        if (!empty($filtros['proveedor_id'])) {
            $condiciones[] = 'p.proveedor_id = ?';
            $params[] = (int) $filtros['proveedor_id'];
        }

        if (!empty($filtros['tipo']) && in_array($filtros['tipo'], self::TIPOS, true)) {
            $condiciones[] = 'p.tipo = ?';
            $params[] = $filtros['tipo'];
        }

        if (!empty($filtros['estado']) && in_array($filtros['estado'], self::ESTADOS, true)) {
            $condiciones[] = 'p.estado = ?';
            $params[] = $filtros['estado'];
        }

        $condiciones[] = 'p.activo = 1';

        if (!empty($filtros['unidad_medida_id'])) {
            $condiciones[] = 'p.unidad_medida_id = ?';
            $params[] = (int) $filtros['unidad_medida_id'];
        }

        if (!empty($filtros['stock_flag'])) {
            switch ($filtros['stock_flag']) {
                case 'bajo':
                    $condiciones[] = 'p.stock_actual < p.stock_minimo';
                    break;
                case 'sin':
                    $condiciones[] = 'p.stock_actual <= 0';
                    break;
                case 'suficiente':
                    $condiciones[] = 'p.stock_actual >= p.stock_minimo';
                    break;
            }
        }

        if (!empty($filtros['tags'])) {
            $condiciones[] = 'IFNULL(p.tags, "") LIKE ?';
            $params[] = '%' . trim($filtros['tags']) . '%';
        }

        if (!empty($filtros['valor_min']) && is_numeric($filtros['valor_min'])) {
            $condiciones[] = '(p.costo_compra * p.stock_actual) >= ?';
            $params[] = (float) $filtros['valor_min'];
        }

        if (!empty($filtros['valor_max']) && is_numeric($filtros['valor_max'])) {
            $condiciones[] = '(p.costo_compra * p.stock_actual) <= ?';
            $params[] = (float) $filtros['valor_max'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $fechaDesde = DateTime::createFromFormat('Y-m-d', $filtros['fecha_desde']);
            if ($fechaDesde) {
                $condiciones[] = 'DATE(p.created_at) >= ?';
                $params[] = $fechaDesde->format('Y-m-d');
            }
        }

        if (!empty($filtros['fecha_hasta'])) {
            $fechaHasta = DateTime::createFromFormat('Y-m-d', $filtros['fecha_hasta']);
            if ($fechaHasta) {
                $condiciones[] = 'DATE(p.created_at) <= ?';
                $params[] = $fechaHasta->format('Y-m-d');
            }
        }

        $joins = " LEFT JOIN catalogo_categorias_inventario c ON p.categoria_id = c.id"
               . " LEFT JOIN almacenes a ON p.almacen_id = a.id"
               . " LEFT JOIN catalogo_unidades_medida um ON p.unidad_medida_id = um.id";

        $whereSql = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';

        $totalesSql = "SELECT COUNT(*) AS total,"
                    . " SUM(p.precio_unitario * p.stock_actual) AS valor_total,"
                    . " SUM(CASE WHEN p.stock_actual < p.stock_minimo THEN 1 ELSE 0 END) AS stock_bajo,"
                    . " SUM(CASE WHEN p.stock_actual <= 0 THEN 1 ELSE 0 END) AS sin_stock,"
                    . " SUM(CASE WHEN p.tipo = 'Consumible' THEN 1 ELSE 0 END) AS consumibles,"
                    . " SUM(CASE WHEN p.tipo = 'Herramienta' THEN 1 ELSE 0 END) AS herramientas,"
                    . " SUM(CASE WHEN p.activo = 1 THEN 1 ELSE 0 END) AS activos,"
                    . " SUM(CASE WHEN p.activo <> 1 THEN 1 ELSE 0 END) AS inactivos"
                    . " FROM inventario p" . $joins . $whereSql;

        $stmtTotales = $db->prepare($totalesSql);
        $stmtTotales->execute($params);
        $totales = $stmtTotales->fetch() ?: [];

        $selectSql = "SELECT p.id, p.codigo_fabricante, p.nombre, p.descripcion, p.tipo, p.estado, p.stock_actual, p.stock_minimo,"
                    . " p.precio_unitario, p.precio_beneficio, p.almacen_id, p.activo, p.created_at,"
                    . " c.nombre AS categoria, a.nombre AS almacen, um.nombre AS unidad_medida_nombre,"
                    . " um.apodo AS unidad_abreviacion, p.tags, p.imagen_url, p.marca,"
                    . " (p.precio_unitario * p.stock_actual) AS valor_total,"
                    . " (SELECT MAX(m.created_at) FROM movimientos_inventario m WHERE m.producto_id = p.id) AS ultimo_movimiento"
                    . " FROM inventario p" . $joins . $whereSql . " ORDER BY p.nombre ASC";

        if ($limit !== null) {
            $limit = max(1, (int) $limit);
            $offset = max(0, (int) $offset);
            $selectSql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        }

        $stmt = $db->prepare($selectSql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return [
            'items' => $items,
            'total' => (int) ($totales['total'] ?? 0),
            'stats' => [
                'valor_total' => (float) ($totales['valor_total'] ?? 0),
                'stock_bajo' => (int) ($totales['stock_bajo'] ?? 0),
                'sin_stock' => (int) ($totales['sin_stock'] ?? 0),
                'consumibles' => (int) ($totales['consumibles'] ?? 0),
                'herramientas' => (int) ($totales['herramientas'] ?? 0),
                'activos' => (int) ($totales['activos'] ?? 0),
                'inactivos' => (int) ($totales['inactivos'] ?? 0),
            ],
        ];
    }

    public static function categorias()
    {
        $db = Database::getInstance()->getConnection();
        $cats = $db->query("SELECT nombre FROM categorias ORDER BY nombre ASC")->fetchAll(PDO::FETCH_COLUMN);
        return $cats ?: [];
    }

    public static function estadosDisponibles(): array
    {
        return self::ESTADOS;
    }

    public static function tiposDisponibles(): array
    {
        return self::TIPOS;
    }

    
}
