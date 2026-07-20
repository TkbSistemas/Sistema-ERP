<?php
require_once __DIR__ . '/../helpers/Database.php';

class Usuario {
    public static function findByUsername($username) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public static function findById($id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function all() {
        $db = Database::getInstance()->getConnection();
        return $db->query("SELECT * FROM usuarios")->fetchAll();
    }

    public static function create($data) {
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO usuarios (username, password, nombre, role) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            $data['username'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['nombre'],
            $data['role'],
        ]);
    }

    public static function update($id, $data) {
        $db = Database::getInstance()->getConnection();
        if (!empty($data['password'])) {
            $sql = "UPDATE usuarios SET nombre=?, username=?, role=?, activo=?, password=? WHERE id=?";
            $params = [
                $data['nombre'],
                $data['username'],
                $data['role'],
                isset($data['baja']) ? 1 : 0, // Convertir a 1 o 0 según el valor de 'baja'
                password_hash($data['password'], PASSWORD_DEFAULT),
                $id
            ];
        } else {
            $sql = "UPDATE usuarios SET nombre=?, username=?, role=?, activo=? WHERE id=?";
            $params = [
                $data['nombre'],
                $data['username'],
                $data['role'],
                isset($data['baja']) ? 1 : 0,
                $id
            ];
        }
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool{
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE usuarios SET baja=1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function setActive($id, $active) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE usuarios SET activo=? WHERE id=?");
        return $stmt->execute([$active ? 1 : 0, $id]);
    }

    public static function upgradePasswordHash($hash) { 
        // Verifica si el hash necesita ser actualizado al algoritmo actual
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
}
