<?php
require_once __DIR__ . '/../helpers/Database.php';

class MovimientoInventario {

    public static function registrar(array $data): bool{
    try {
        $db = Database::getInstance()->getConnection();
        
        $sql = "INSERT INTO movimientos_inventario 
                (producto_id, tipo, cantidad, responsable_id, almacen_id, observaciones, folio_solicitud)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
                
        $stmt = $db->prepare($sql);
        
        return $stmt->execute([
            $data['producto_id']     ?? null,
            $data['tipo']            ?? 'Salida',
            $data['cantidad']        ?? 0,
            $data['responsable_id']  ?? null,
            $data['almacen_id']      ?? null,
            $data['observaciones']   ?? null,
            $data['folio_solicitud'] ?? null
        ]);
    } catch (\PDOException $e) {
        return false;
    }
}

    public static function movimientos($filtros = []) {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT m.*, p.nombre AS producto, a.nombre AS almacen_origen, ad.nombre AS almacen_destino, u.nombre_completo AS responsable
                FROM movimientos_inventario m
                LEFT JOIN productos p ON m.producto_id = p.id
                LEFT JOIN almacenes a ON m.almacen_entrada_id = a.id
                LEFT JOIN usuarios u ON m.responsable_id = u.id
                WHERE 1=1";
        $params = [];
        if (!empty($filtros['tipo'])) {
            $sql .= " AND m.tipo = ?";
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['producto_id'])) {
            $sql .= " AND m.producto_id = ?";
            $params[] = $filtros['producto_id'];
        }
        $sql .= " ORDER BY m.fecha DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }


    public static function ultimos($tipo = null, $limit = 5)
    {
        $db = Database::getInstance()->getConnection();
        $limit = max(1, (int) $limit);

        $sql = "SELECT m.*, p.nombre AS producto, p.codigo_fabricante AS codigo_producto, u.nombre AS usuario,"
             . " a.nombre AS almacen_origen"
             . " FROM movimientos_inventario m"
             . " LEFT JOIN inventario p ON m.producto_id = p.id"
             . " LEFT JOIN usuarios u ON m.responsable_id = u.id"
             . " LEFT JOIN almacenes a ON m.almacen_id = a.id"
             . " WHERE 1=1";

        $params = [];
        if ($tipo) {
            $sql .= " AND m.tipo = ?";
            $params[] = $tipo;
        }

        $sql .= ' ORDER BY m.created_at DESC LIMIT ' . $limit;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

}
