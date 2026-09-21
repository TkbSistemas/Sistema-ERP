<?php
require_once __DIR__ . '/../helpers/Database.php';

class Usuario {
    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        return $usuario ?: null;
    }

    public static function findByUsername($username) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public static function updatePassword(int $id, string $password): bool
    {
        if ($id <= 0 || $password === '') {
            return false;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false) {
            throw new RuntimeException('No Fue Posible Proteger la Nueva Contraseña.');
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
        $stmt->execute([$hash, $id]);
        return $stmt->rowCount() === 1;
    }

    public static function cambiarPassword(
        int $id,
        string $passwordActual,
        string $passwordNueva,
        string $confirmacion
    ): void {
        $usuario = self::findById($id);
        if ($usuario === null) {
            throw new RuntimeException('La Cuenta de Usuario ya no Está Disponible.');
        }

        $hashActual = (string) ($usuario['password'] ?? '');
        $actualValida = $passwordActual !== '' && (
            password_verify($passwordActual, $hashActual)
            || hash_equals($hashActual, $passwordActual)
        );
        if (!$actualValida) {
            throw new InvalidArgumentException('La Contraseña Actual no es Correcta.');
        }
        if (strlen($passwordNueva) < 8 || strlen($passwordNueva) > 72) {
            throw new InvalidArgumentException('La Nueva Contraseña Debe Tener entre 8 y 72 Caracteres.');
        }
        if ($passwordNueva !== $confirmacion) {
            throw new InvalidArgumentException('La Confirmación no Coincide con la Nueva Contraseña.');
        }
        if (password_verify($passwordNueva, $hashActual) || hash_equals($hashActual, $passwordNueva)) {
            throw new InvalidArgumentException('La Nueva Contraseña Debe ser Diferente de la Actual.');
        }
        if (!self::updatePassword($id, $passwordNueva)) {
            throw new RuntimeException('No Fue Posible Guardar la Nueva Contraseña.');
        }
    }

    public static function upgradePasswordHash(int $id, string $password): bool
    {
        return self::updatePassword($id, $password);
    }
}
