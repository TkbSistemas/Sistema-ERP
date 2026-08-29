<?php
require_once __DIR__ . '/../helpers/Database.php';

class Producto
{
    private const TIPOS = ['Consumible', 'Herramienta', 'Equipo'];

    public static function all($filtros = [])
    {
        $db = Database::getInstance()->getConnection();
        $almacenSeleccionado = !empty($filtros['almacen_id']) ? (int) $filtros['almacen_id'] : null;
        $stockJoin = $almacenSeleccionado
            ? " INNER JOIN (SELECT producto_id, almacen_id, stock AS stock_actual FROM stock_almacen WHERE almacen_id = {$almacenSeleccionado}) si ON si.producto_id = p.id"
            : ' LEFT JOIN (SELECT producto_id, SUM(stock) AS stock_actual FROM stock_almacen GROUP BY producto_id) si ON si.producto_id = p.id';
        $almacenJoin = $almacenSeleccionado
            ? ' LEFT JOIN almacenes a ON a.id = si.almacen_id'
            : ' LEFT JOIN almacenes a ON p.almacen_id = a.id';
        $sql = "SELECT p.*, COALESCE(si.stock_actual, 0) AS stock_actual,
                       c.nombre AS categoria,
                       a.nombre AS almacen,
                       um.nombre AS unidad_medida_nombre,
                       um.apodo AS unidad_abreviacion
                FROM inventario p
                {$stockJoin}
                LEFT JOIN catalogo_categorias_inventario c ON p.categoria_id = c.id
                {$almacenJoin}
                LEFT JOIN catalogo_unidades_medida um ON p.unidad_medida_id = um.id
                WHERE 1=1";
        $params = [];

        if (!empty($filtros['buscar'])) {
            $buscar = '%' . trim($filtros['buscar']) . '%';
            $sql .= " AND (p.nombre LIKE ? OR p.nomenclatura LIKE ? OR p.codigo_fabricante LIKE ? OR p.codigos_barras LIKE ? OR p.descripcion LIKE ? OR p.marca LIKE ? OR p.modelo LIKE ?)";
            array_push($params, $buscar, $buscar, $buscar, $buscar, $buscar, $buscar, $buscar);
        }

        if (!empty($filtros['nombre'])) {
            $sql .= " AND p.nombre LIKE ?";
            $params[] = '%' . trim($filtros['nombre']) . '%';
        }

        if (!empty($filtros['codigo'])) {
            $sql .= " AND (p.nomenclatura LIKE ? OR p.codigo_fabricante LIKE ? OR p.codigos_barras LIKE ?)";
            $codigo = '%' . trim($filtros['codigo']) . '%';
            array_push($params, $codigo, $codigo, $codigo);
        }

        if (!empty($filtros['codigo_barras'])) {
            $sql .= " AND p.codigos_barras = ?";
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

        if (!empty($filtros['stock_flag'])) {
            switch ($filtros['stock_flag']) {
                case 'bajo':
                    $sql .= " AND COALESCE(si.stock_actual, 0) < p.stock_minimo";
                    break;
                case 'sin':
                    $sql .= " AND COALESCE(si.stock_actual, 0) <= 0";
                    break;
                case 'suficiente':
                    $sql .= " AND COALESCE(si.stock_actual, 0) >= p.stock_minimo";
                    break;
            }
        }

        if (!empty($filtros['unidad_medida_id'])) {
            $sql .= " AND p.unidad_medida_id = ?";
            $params[] = (int) $filtros['unidad_medida_id'];
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
            $sql .= " AND p.precio_unitario >= ?";
            $params[] = (float) $filtros['valor_min'];
        }

        if (!empty($filtros['valor_max']) && is_numeric($filtros['valor_max'])) {
            $sql .= " AND p.precio_unitario <= ?";
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
        self::ensureStockTable($db);
        $sql = "INSERT INTO inventario (
            sku, codigo_fabricante, num_serie, codigo_sat, codigos_barras, nombre, descripcion, tipo, categoria_id, 
            marca, modelo, unidad_medida_id, precio_unitario, precio_iva, precio_beneficio, pais_origen, stock_minimo,
            color, almacen_id, imagen_url
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $db->beginTransaction();
        try {
        $stmt->execute([ //Regresa true o false dependiendo si se pudo ejecutar la consulta
            $data['sku'], $data['codigo_fabricante'], $data['num_serie'], $data['codigo_sat'], $data['codigos_barras'], $data['nombre'], $data['descripcion'], $data['tipo'], $data['categoria_id'],
            $data['marca'], $data['modelo'], $data['unidad_medida_id'], $data['precio_unitario'], $data['precio_unitario']*1.16, $data['precio_unitario']*1.508, $data['pais_origen'], $data['stock_minimo'],
            $data['color'], $data['almacen_id'], $data['imagen_url'] ?? null
        ]);
        $productoId = (int) $db->lastInsertId();
        $stockInicial = max(0, (float) ($data['stock_inicial'] ?? ($data['stock_actual'] ?? 0)));
        if ($productoId && !empty($data['almacen_id'])) {
            $stmtStock = $db->prepare("INSERT INTO stock_almacen (producto_id, almacen_id, stock, ubicacion_fisica)
                                       VALUES (?, ?, ?, ?)
                                       ON DUPLICATE KEY UPDATE stock = VALUES(stock), ubicacion_fisica = VALUES(ubicacion_fisica)");
            $stmtStock->execute([
                $productoId,
                (int) $data['almacen_id'],
                $stockInicial,
                ($data['ubicacion_fisica'] ?? '') !== '' ? $data['ubicacion_fisica'] : null,
            ]);
        }
        $db->commit();
        return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public static function find($id){
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT p.*, p.codigos_barras AS codigo_barras, COALESCE(si.stock_actual, 0) AS stock_actual,
                                    c.nombre AS categoria,
                                    a.nombre AS almacen,
                                    um.nombre AS unidad_medida_nombre,
                                    um.apodo AS unidad_apodo,
                                    sa.ubicacion_fisica,
                                    fs.folio AS folio_solicitud
                            FROM inventario p
                            LEFT JOIN (SELECT producto_id, SUM(stock) AS stock_actual FROM stock_almacen GROUP BY producto_id) si ON si.producto_id = p.id
                            LEFT JOIN catalogo_categorias_inventario c ON p.categoria_id = c.id
                            LEFT JOIN almacenes a ON p.almacen_id = a.id
                            LEFT JOIN stock_almacen sa ON sa.producto_id = p.id AND sa.almacen_id = p.almacen_id
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
        $stmt = $db->prepare("SELECT * FROM inventario WHERE codigos_barras = ?");
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
            $stmt = $db->prepare("SELECT COUNT(*) FROM inventario WHERE codigos_barras = ? AND id <> ?");
            $stmt->execute([$codigoBarras, $exceptId]);
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM inventario WHERE codigos_barras = ?");
            $stmt->execute([$codigoBarras]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function actualizarCodigoBarras(int $id, string $codigo): bool
    {
        $codigo = strtoupper(trim($codigo));
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE inventario SET codigos_barras = ? WHERE id = ?");
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
        self::ensureStockTable($db);
        $sql = "UPDATE inventario SET
                    codigo_fabricante = ?, codigos_barras = ?, nombre = ?, descripcion = ?, tipo = ?,
                    categoria_id = ?, marca = ?, modelo = ?, unidad_medida_id = ?, precio_unitario = ?,
                    precio_iva = ?, precio_beneficio = ?, pais_origen = ?, stock_minimo = ?, color = ?,
                    almacen_id = ?, imagen_url = ?
                WHERE id = ?";
        $codigoFabricante = strtoupper(trim((string) ($data['codigo_fabricante'] ?? '')));
        $codigoBarras = trim((string) ($data['codigos_barras'] ?? ''));
        $codigoBarras = $codigoBarras !== '' ? strtoupper($codigoBarras) : null;
        $precioUnitario = (float) ($data['precio_unitario'] ?? 0);

        $db->beginTransaction();
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $codigoFabricante,
                $codigoBarras,
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['tipo'],
                $data['categoria_id'],
                $data['marca'],
                $data['modelo'] ?? null,
                $data['unidad_medida_id'],
                $precioUnitario,
                $precioUnitario * 1.16,
                $precioUnitario * 1.508,
                $data['pais_origen'] ?? null,
                (float) ($data['stock_minimo'] ?? 0),
                $data['color'] ?? null,
                $data['almacen_id'],
                $data['imagen_url'] ?? null,
                (int) $id,
            ]);

            $stmtStock = $db->prepare("INSERT INTO stock_almacen (producto_id, almacen_id, stock, ubicacion_fisica)
                                       VALUES (?, ?, 0, ?)
                                       ON DUPLICATE KEY UPDATE ubicacion_fisica = VALUES(ubicacion_fisica)");
            $stmtStock->execute([
                (int) $id,
                (int) $data['almacen_id'],
                ($data['ubicacion_fisica'] ?? '') !== '' ? $data['ubicacion_fisica'] : null,
            ]);
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
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
            'stock_almacen'       => 'producto_id',
        ];

        foreach ($relations as $table => $column) {
            $stmt = $db->prepare("DELETE FROM {$table} WHERE {$column} = ?");
            $stmt->execute([$productoId]);
        }
    }

    public static function setActive($id, $active)
    {
        $db = Database::getInstance()->getConnection();
        $activo = $active ? 1 : 0;
        $stmt = $db->prepare("UPDATE inventario SET activo=? WHERE id=?");
        return $stmt->execute([$activo, $id]);
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
                    ubicacion_fisica VARCHAR(150) NULL,
                    PRIMARY KEY (producto_id, almacen_id),
                    KEY idx_stock_almacen_producto (producto_id),
                    KEY idx_stock_almacen_almacen (almacen_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $db->exec($sql);
        self::$stockTableChecked = true;
    }

    public static function sumarStock($id, $cantidad, ?int $almacenId = null)
    {
        $db = Database::getInstance()->getConnection();
        if ($almacenId) {
            self::ensureStockTable($db);
            $up = $db->prepare("INSERT INTO stock_almacen (producto_id, almacen_id, stock)
                                VALUES (?, ?, ?)
                                ON DUPLICATE KEY UPDATE stock = stock + VALUES(stock)");
            return $up->execute([(int)$id, (int)$almacenId, (float)$cantidad]);
        }
        return false;
    }

    public static function restarStock($id, $cantidad, ?int $almacenId = null){
        $db = Database::getInstance()->getConnection();
        $cantidadFloat = (float) $cantidad;

        if (!$almacenId || $cantidadFloat <= 0) return false;
        self::ensureStockTable($db);
        // La condición evita stock negativo incluso ante solicitudes concurrentes.
        $stmt = $db->prepare("UPDATE stock_almacen
                              SET stock = stock - ?
                              WHERE producto_id = ? AND almacen_id = ? AND stock >= ?");
        $stmt->execute([$cantidadFloat, (int) $id, (int) $almacenId, $cantidadFloat]);
        return $stmt->rowCount() === 1;
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

    public static function stockTotal(int $productoId): float
    {
        $db = Database::getInstance()->getConnection();
        self::ensureStockTable($db);
        $stmt = $db->prepare('SELECT COALESCE(SUM(stock), 0) AS total FROM stock_almacen WHERE producto_id = ?');
        $stmt->execute([$productoId]);
        return (float) ($stmt->fetchColumn() ?: 0);
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
        $almacenSeleccionado = !empty($filtros['almacen_id']) ? (int) $filtros['almacen_id'] : null;
        $stockJoin = $almacenSeleccionado
            ? " INNER JOIN (SELECT producto_id, almacen_id, stock AS stock_actual FROM stock_almacen WHERE almacen_id = {$almacenSeleccionado}) si ON si.producto_id = p.id"
            : ' LEFT JOIN (SELECT producto_id, SUM(stock) AS stock_actual FROM stock_almacen GROUP BY producto_id) si ON si.producto_id = p.id';
        $sql = "SELECT p.*,
                       COALESCE(si.stock_actual, 0) AS stock_actual,
                       c.nombre AS categoria,
                       u.abreviacion AS unidad,
                       (p.costo_compra * COALESCE(si.stock_actual, 0)) AS valor_total,
                       (SELECT MAX(created_at) FROM movimientos_inventario m WHERE m.producto_id = p.id) AS ultimo_movimiento,
                       p.activo
                FROM inventario p
                {$stockJoin}
                LEFT JOIN categorias c ON p.categoria_id = c.id
                LEFT JOIN unidades_medida u ON p.unidad_medida_id = u.id
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
            $condiciones[] = '(p.nombre LIKE ? OR p.nomenclatura LIKE ? OR p.codigo_fabricante LIKE ? OR p.codigos_barras LIKE ? OR IFNULL(p.descripcion, "") LIKE ? OR IFNULL(p.marca, "") LIKE ? OR IFNULL(p.modelo, "") LIKE ?)';
            array_push($params, $buscar, $buscar, $buscar, $buscar, $buscar, $buscar, $buscar);
        }

        if (!empty($filtros['nombre'])) {
            $condiciones[] = 'p.nombre LIKE ?';
            $params[] = '%' . trim($filtros['nombre']) . '%';
        }

        if (!empty($filtros['codigo'])) {
            $codigo = '%' . trim($filtros['codigo']) . '%';
            $condiciones[] = '(p.nomenclatura LIKE ? OR p.codigo_fabricante LIKE ? OR p.codigos_barras LIKE ?)';
            array_push($params, $codigo, $codigo, $codigo);
        }

        if (!empty($filtros['codigo_barras'])) {
            $condiciones[] = 'p.codigos_barras = ?';
            $params[] = trim($filtros['codigo_barras']);
        }

        if (!empty($filtros['categoria_id'])) {
            $condiciones[] = 'p.categoria_id = ?';
            $params[] = (int) $filtros['categoria_id'];
        } elseif (!empty($filtros['categoria_nombre'])) {
            $condiciones[] = 'c.nombre = ?';
            $params[] = trim($filtros['categoria_nombre']);
        }

        if (!empty($filtros['tipo']) && in_array($filtros['tipo'], self::TIPOS, true)) {
            $condiciones[] = 'p.tipo = ?';
            $params[] = $filtros['tipo'];
        }

        if (!empty($filtros['marca'])) {
            $condiciones[] = 'p.marca LIKE ?';
            $params[] = '%' . trim($filtros['marca']) . '%';
        }

        $condiciones[] = 'p.activo = 1';

        if (!empty($filtros['unidad_medida_id'])) {
            $condiciones[] = 'p.unidad_medida_id = ?';
            $params[] = (int) $filtros['unidad_medida_id'];
        }

        if (!empty($filtros['stock_flag'])) {
            switch ($filtros['stock_flag']) {
                case 'bajo':
                    $condiciones[] = 'COALESCE(si.stock_actual, 0) < p.stock_minimo';
                    break;
                case 'sin':
                    $condiciones[] = 'COALESCE(si.stock_actual, 0) <= 0';
                    break;
                case 'suficiente':
                    $condiciones[] = 'COALESCE(si.stock_actual, 0) >= p.stock_minimo';
                    break;
            }
        }

        if (!empty($filtros['valor_min']) && is_numeric($filtros['valor_min'])) {
            $condiciones[] = 'p.precio_unitario >= ?';
            $params[] = (float) $filtros['valor_min'];
        }

        if (!empty($filtros['valor_max']) && is_numeric($filtros['valor_max'])) {
            $condiciones[] = 'p.precio_unitario <= ?';
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

        $almacenSeleccionado = !empty($filtros['almacen_id']) ? (int) $filtros['almacen_id'] : null;
        $stockJoin = $almacenSeleccionado
            ? " INNER JOIN (SELECT producto_id, almacen_id, stock AS stock_actual FROM stock_almacen WHERE almacen_id = {$almacenSeleccionado}) si ON si.producto_id = p.id"
            : ' LEFT JOIN (SELECT producto_id, SUM(stock) AS stock_actual FROM stock_almacen GROUP BY producto_id) si ON si.producto_id = p.id';
        $almacenJoin = $almacenSeleccionado
            ? ' LEFT JOIN almacenes a ON a.id = si.almacen_id'
            : ' LEFT JOIN almacenes a ON p.almacen_id = a.id';
        $joins = $stockJoin
               . " LEFT JOIN catalogo_categorias_inventario c ON p.categoria_id = c.id"
               . $almacenJoin
               . " LEFT JOIN catalogo_unidades_medida um ON p.unidad_medida_id = um.id";

        $whereSql = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';

        $totalesSql = "SELECT COUNT(*) AS total,"
                    . " SUM(p.precio_unitario * COALESCE(si.stock_actual, 0)) AS valor_total,"
                    . " SUM(CASE WHEN COALESCE(si.stock_actual, 0) < p.stock_minimo THEN 1 ELSE 0 END) AS stock_bajo,"
                    . " SUM(CASE WHEN COALESCE(si.stock_actual, 0) <= 0 THEN 1 ELSE 0 END) AS sin_stock,"
                    . " SUM(CASE WHEN p.tipo = 'Consumible' THEN 1 ELSE 0 END) AS consumibles,"
                    . " SUM(CASE WHEN p.tipo = 'Herramienta' THEN 1 ELSE 0 END) AS herramientas,"
                    . " SUM(CASE WHEN p.activo = 1 THEN 1 ELSE 0 END) AS activos,"
                    . " SUM(CASE WHEN p.activo <> 1 THEN 1 ELSE 0 END) AS inactivos"
                    . " FROM inventario p" . $joins . $whereSql;

        $stmtTotales = $db->prepare($totalesSql);
        $stmtTotales->execute($params);
        $totales = $stmtTotales->fetch() ?: [];

        $selectSql = "SELECT p.id, p.nomenclatura, p.nomenclatura AS codigo, p.codigo_fabricante, p.codigos_barras, p.nombre, p.descripcion, p.tipo, COALESCE(si.stock_actual, 0) AS stock_actual, p.stock_minimo,"
                    . " p.precio_unitario, p.precio_beneficio, p.almacen_id, p.activo, p.created_at,"
                    . " c.nombre AS categoria, a.nombre AS almacen, um.nombre AS unidad_medida_nombre,"
                    . " um.apodo AS unidad_abreviacion, p.imagen_url, p.marca, p.modelo,"
                    . " (p.precio_unitario * COALESCE(si.stock_actual, 0)) AS valor_total,"
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

    public static function tiposDisponibles(): array
    {
        return self::TIPOS;
    }

    
}
