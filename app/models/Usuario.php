<?php
require_once __DIR__ . '/../helpers/Database.php';

class Usuario {
    public static function findByUsername($username) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public static function upgradePasswordHash($hash) { 
        // Verifica si el hash necesita ser actualizado al algoritmo actual
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
}
