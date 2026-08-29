<?php
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/Producto.php';

class Prestamo
{
    // Listar préstamos pendientes de devolución
    public static function pendientes()
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT pr.*, p.nombre AS producto, p.codigo AS codigo_producto, u.nombre_completo AS empleado
                FROM prestamos pr
                LEFT JOIN productos p ON pr.producto_id = p.id
                LEFT JOIN usuarios u ON pr.empleado_id = u.id
                WHERE pr.estado = 'Prestado'
                ORDER BY pr.fecha_prestamo DESC";
        return $db->query($sql)->fetchAll();
    }

    // Buscar préstamo específico
    public static function find($id)
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT pr.*, p.nombre AS producto, u.nombre_completo AS empleado
                FROM prestamos pr
                LEFT JOIN productos p ON pr.producto_id = p.id
                LEFT JOIN usuarios u ON pr.empleado_id = u.id
                WHERE pr.id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Registrar devolución
    public static function devolver($id, $estado_devolucion, $observaciones, $fecha = null)
    {
        $db = Database::getInstance()->getConnection();
        $fechaDevolucion = $fecha ?? date('Y-m-d H:i:s');

        $sql = "UPDATE prestamos SET estado = 'Devuelto', estado_devolucion = ?, fecha_devolucion = ?, observaciones = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$estado_devolucion, $fechaDevolucion, $observaciones, $id]);

        $prestamo = self::find($id);
        if ($prestamo && $prestamo['producto_id']) {
            $productoId = (int)$prestamo['producto_id'];
            $prod = Producto::find($productoId);
            $almacenId = (int)($prod['almacen_id'] ?? 0);

            $ed = strtolower((string)$estado_devolucion);
            if ($ed === 'bueno') {
                Producto::sumarStock($productoId, 1, $almacenId ?: null);
            } elseif ($ed === 'dañado' || $ed === 'danado') {
                $db->prepare("UPDATE productos SET estado = 'Dañado' WHERE id = ?")->execute([$productoId]);
            } else {
                // perdido u otro: no reponer stock
            }
        }
        return true;
    }

    // Historial completo
    public static function historial()
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT pr.*, p.nombre AS producto, p.codigo AS codigo_producto, u.nombre_completo AS empleado
                FROM prestamos pr
                LEFT JOIN productos p ON pr.producto_id = p.id
                LEFT JOIN usuarios u ON pr.empleado_id = u.id
                ORDER BY pr.fecha_prestamo DESC";
        return $db->query($sql)->fetchAll();
    }

    public static function crear($data, $cantidad = 1)
    {
        $db = Database::getInstance()->getConnection();
        for ($i = 0; $i < $cantidad; $i++) {
            $sql = "INSERT INTO prestamos
                    (producto_id, empleado_id, autorizado_by_user_id, fecha_prestamo, fecha_estimada_devolucion, estado, observaciones)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['producto_id'],
                $data['empleado_id'],
                $data['autorizado_by_user_id'],
                $data['fecha_prestamo'],
                $data['fecha_estimada_devolucion'],
                $data['estado'],
                $data['observaciones']
            ]);
        }
    }

    public static function historialPaginado($busqueda = '', $pagina = 1, $porPagina = 10, array $filtros = [])
    {
        $db = Database::getInstance()->getConnection();
        $offset = ($pagina - 1) * $porPagina;
        $condiciones = [];
        $params = [];
        if ($busqueda !== '') {
            $condiciones[] = "(p.codigo LIKE ? OR u.nombre_completo LIKE ?)";
            $params[] = "%$busqueda%";
            $params[] = "%$busqueda%";
        }
        if (!empty($filtros['estado'])) {
            $condiciones[] = 'pr.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['empleado_id'])) {
            $condiciones[] = 'pr.empleado_id = ?';
            $params[] = (int)$filtros['empleado_id'];
        }
        if (!empty($filtros['desde'])) {
            $condiciones[] = 'DATE(pr.fecha_prestamo) >= ?';
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $condiciones[] = 'DATE(pr.fecha_prestamo) <= ?';
            $params[] = $filtros['hasta'];
        }
        $where = $condiciones ? ('WHERE ' . implode(' AND ', $condiciones)) : '';
        $sql = "SELECT pr.*, p.nombre AS producto, p.codigo AS codigo_producto, u.nombre_completo AS empleado
                FROM prestamos pr
                LEFT JOIN productos p ON pr.producto_id = p.id
                LEFT JOIN usuarios u ON pr.empleado_id = u.id
                $where
                ORDER BY pr.fecha_prestamo DESC
                LIMIT $porPagina OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function totalHistorial($busqueda = '', array $filtros = [])
    {
        $db = Database::getInstance()->getConnection();
        $condiciones = [];
        $params = [];
        if ($busqueda !== '') {
            $condiciones[] = "(p.codigo LIKE ? OR u.nombre_completo LIKE ?)";
            $params[] = "%$busqueda%";
            $params[] = "%$busqueda%";
        }
        if (!empty($filtros['estado'])) {
            $condiciones[] = 'pr.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['empleado_id'])) {
            $condiciones[] = 'pr.empleado_id = ?';
            $params[] = (int)$filtros['empleado_id'];
        }
        if (!empty($filtros['desde'])) {
            $condiciones[] = 'DATE(pr.fecha_prestamo) >= ?';
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $condiciones[] = 'DATE(pr.fecha_prestamo) <= ?';
            $params[] = $filtros['hasta'];
        }
        $where = $condiciones ? ('WHERE ' . implode(' AND ', $condiciones)) : '';
        $sql = "SELECT COUNT(*) FROM prestamos pr
                LEFT JOIN productos p ON pr.producto_id = p.id
                LEFT JOIN usuarios u ON pr.empleado_id = u.id
                $where";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
?>
