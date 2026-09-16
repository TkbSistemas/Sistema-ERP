<?php
require_once __DIR__ . '/../helpers/Database.php';

class Almacen {
    public static function all() {
        $db = Database::getInstance()->getConnection();
        return $db->query("SELECT a.*, u.nombre AS responsable FROM almacenes a LEFT JOIN usuarios u ON a.responsable_id = u.id")->fetchAll();
    }
}
