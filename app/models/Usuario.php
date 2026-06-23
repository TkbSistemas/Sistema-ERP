<?php
require_once __DIR__ . '/../helpers/Database.php';

class Usuario {
    public static function findByUsername($username) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = ? AND activo = 1");
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
        $sql = "INSERT INTO usuarios (username, password, nombre_completo, role, activo) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            $data['username'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['nombre_completo'],
            $data['role'],
            isset($data['activo']) ? 1 : 0
        ]);
    }

    public static function update($id, $data) {
        $db = Database::getInstance()->getConnection();
        if (!empty($data['password'])) {
            $sql = "UPDATE usuarios SET nombre_completo=?, username=?, role=?, activo=?, password=? WHERE id=?";
            $params = [
                $data['nombre_completo'],
                $data['username'],
                $data['role'],
                isset($data['activo']) ? 1 : 0,
                password_hash($data['password'], PASSWORD_DEFAULT),
                $id
            ];
        } else {
            $sql = "UPDATE usuarios SET nombre_completo=?, username=?, role=?, activo=? WHERE id=?";
            $params = [
                $data['nombre_completo'],
                $data['username'],
                $data['role'],
                isset($data['activo']) ? 1 : 0,
                $id
            ];
        }
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool{
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
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
