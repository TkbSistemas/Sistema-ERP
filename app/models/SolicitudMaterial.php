<?php
require_once __DIR__ . '/../helpers/Database.php';

class SolicitudMaterial {
    // Crear solicitud con múltiples productos y extras
    public static function create($data, $detalles) {
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        $sql = "INSERT INTO solicitudes_material (
            usuario_id, tipo, tipo_solicitud, comentario, observacion, extras, estado, fecha_solicitud
        ) VALUES (?, ?, ?, ?, ?, ?, 'pendiente', NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['usuario_id'],
            $data['tipo'],
            $data['tipo_solicitud'],
            $data['comentario'],
            $data['observacion'],
            isset($data['extras']) && !empty($data['extras']) ? json_encode($data['extras']) : null
        ]);
        $solicitud_id = $db->lastInsertId();

        $updateProducto = $db->prepare("UPDATE productos SET last_requested_by_user_id = ?, last_request_date = NOW() WHERE id = ?");
        foreach ($detalles as $detalle) {
            $sql_det = "INSERT INTO detalle_solicitud (solicitud_id, producto_id, cantidad, observacion) VALUES (?, ?, ?, ?)";
            $stmt_det = $db->prepare($sql_det);
            $stmt_det->execute([
                $solicitud_id,
                $detalle['producto_id'],
                $detalle['cantidad'],
                isset($detalle['observacion']) ? $detalle['observacion'] : null
            ]);
            if (!empty($detalle['producto_id'])) {
                $updateProducto->execute([
                    $data['usuario_id'],
                    $detalle['producto_id'],
                ]);
            }
        }
        $db->commit();
        return $solicitud_id;
    }

    public static function historialPorUsuario($usuario_id, $fecha_inicio = null, $fecha_fin = null) {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT s.*, 
            (SELECT COUNT(*) FROM detalle_solicitud d WHERE d.solicitud_id = s.id) as total_productos
            FROM solicitudes_material s
            WHERE s.usuario_id = ?";
        $params = [$usuario_id];

        if ($fecha_inicio) {
            $sql .= " AND DATE(s.fecha_solicitud) >= ?";
            $params[] = $fecha_inicio;
        }
        if ($fecha_fin) {
            $sql .= " AND DATE(s.fecha_solicitud) <= ?";
            $params[] = $fecha_fin;
        }

        $sql .= " ORDER BY s.fecha_solicitud DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function detalles($solicitud_id) {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT d.*, p.nombre AS producto
                FROM detalle_solicitud d
                LEFT JOIN productos p ON d.producto_id = p.id
                WHERE d.solicitud_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$solicitud_id]);
        return $stmt->fetchAll();
    }

    public static function find($id, $usuario_id = null) {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM solicitudes_material WHERE id=?";
        $params = [$id];
        if ($usuario_id) {
            $sql .= " AND usuario_id=?";
            $params[] = $usuario_id;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    // Solicitudes pendientes por aprobar para admin/almacén (todas o filtradas por estado)
    public static function listarPendientes($estados = ['pendiente']) {
        $db = Database::getInstance()->getConnection();
        $placeholders = implode(',', array_fill(0, count($estados), '?'));
        $sql = "SELECT s.*, u.nombre_completo AS usuario 
                FROM solicitudes_material s 
                LEFT JOIN usuarios u ON s.usuario_id = u.id
                WHERE s.estado IN ($placeholders)
                ORDER BY s.fecha_solicitud DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($estados);
        return $stmt->fetchAll();
    }

    // Cambia estado (aprobada, rechazada, entregada, cancelada), guarda usuario y observación de respuesta
    public static function actualizarEstado($id, $nuevoEstado, $usuarioId, $observacion = null) {
        $db = Database::getInstance()->getConnection();
        $col = '';
        switch ($nuevoEstado) {
            case 'aprobada':
            case 'rechazada':
                $col = 'usuario_aprueba_id';
                break;
            case 'entregada':
                $col = 'usuario_entrega_id';
                break;
            default:
                $col = null;
        }
        $sql = "UPDATE solicitudes_material SET estado=?, " . ($col ? "$col=?," : "") . " fecha_respuesta=NOW(), observaciones_respuesta=? WHERE id=?";
        $params = $col ? [$nuevoEstado, $usuarioId, $observacion, $id] : [$nuevoEstado, $observacion, $id];
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
}
