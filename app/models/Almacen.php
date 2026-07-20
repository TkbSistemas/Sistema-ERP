<?php
require_once __DIR__ . '/../helpers/Database.php';

class Almacen {
    public static function all() {
        $db = Database::getInstance()->getConnection();
        return $db->query("SELECT a.*, u.nombre AS responsable FROM almacenes a LEFT JOIN usuarios u ON a.responsable_id = u.id")->fetchAll();
    }

    public static function find($id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM almacenes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($data) {
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO almacenes (nombre, ubicacion, responsable_id, es_principal) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            $data['nombre'], $data['ubicacion'], $data['responsable_id'] ?: null, $data['es_principal'] ? 1 : 0
        ]);
    }

    public static function update($id, $data) {
        $db = Database::getInstance()->getConnection();
        $sql = "UPDATE almacenes SET nombre=?, ubicacion=?, responsable_id=?, es_principal=? WHERE id=?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            $data['nombre'], $data['ubicacion'], $data['responsable_id'] ?: null, $data['es_principal'] ? 1 : 0, $id
        ]);
    }

    public static function delete($id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM almacenes WHERE id=?");
        return $stmt->execute([$id]);
    }
}
