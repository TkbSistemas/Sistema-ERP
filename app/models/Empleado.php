<?php
require_once __DIR__ . '/../helpers/Database.php';

class Empleado{

    public static function obtenerSolicitudesPendientes($userId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM solicitudes_material WHERE solicitante_id = ? AND estatus = 'Pendiente' ORDER BY id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerSolicitudesEnEntrega($userId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM solicitudes_material WHERE solicitante_id = ? AND estatus = 'Aprobada' ORDER BY id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerMisSolicitudes($userId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM solicitudes_material WHERE solicitante_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerSolicitudesEsteMes($userId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM solicitudes_material WHERE solicitante_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    public static function obtenerUltimasSolicitudes($userId, $limit = 5) {
        $db = Database::getInstance()->getConnection();
        // PDO con emulación activada requiere vincular el LIMIT explícitamente como entero
        $stmt = $db->prepare("SELECT sm.*, p.nombre AS proyecto_nombre 
                            FROM solicitudes_material sm 
                            LEFT JOIN proyectos p ON sm.proyecto_id = p.id 
                            WHERE sm.solicitante_id = :solicitante_id 
                            ORDER BY sm.created_at DESC 
                            LIMIT :limit");
        $stmt->bindValue(':solicitante_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}