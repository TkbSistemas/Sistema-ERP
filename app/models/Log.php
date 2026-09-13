<?php
require_once __DIR__ . '/../helpers/Database.php';

class Log
{
    public static function listar(array $filtros = [], int $limit = 50, int $offset = 0): array
    {
        $db = Database::getInstance()->getConnection();
        $where = [];
        $params = [];

        if (!empty($filtros['usuario_id'])) {
            $where[] = 'l.usuario_id = ?';
            $params[] = (int) $filtros['usuario_id'];
        }
        if (!empty($filtros['accion'])) {
            $where[] = 'l.accion LIKE ?';
            $params[] = '%' . trim($filtros['accion']) . '%';
        }
        if (!empty($filtros['desde'])) {
            $where[] = 'DATE(l.created_at) >= ?';
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where[] = 'DATE(l.created_at) <= ?';
            $params[] = $filtros['hasta'];
        }

        $sql = "SELECT l.*, u.nombre AS usuario_nombre, u.username
                FROM logs_actividad l
                LEFT JOIN usuarios u ON l.usuario_id = u.id";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY l.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll() ?: [];

        $countSql = "SELECT COUNT(*) FROM logs_actividad l";
        if ($where) {
            $countSql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmtCount = $db->prepare($countSql);
        $stmtCount->execute(array_slice($params, 0, count($params) - 2));
        $total = (int) $stmtCount->fetchColumn();

        return ['items' => $items, 'total' => $total];
    }
}
