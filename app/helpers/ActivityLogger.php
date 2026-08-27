<?php
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Database.php';

class ActivityLogger
{
    private const TABLE = 'logs_actividad';
    private static bool $tableChecked = false;

    public static function log(string $accion, ?string $descripcion = null, array $contexto = []): void
    {
        try {
            $db = Database::getInstance()->getConnection();
            Session::start();
            $usuarioId = $_SESSION['user_id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $detalle = $descripcion;
            if (!empty($contexto)) {
                $detalle .= ($detalle ? ' ' : '') . json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $stmt = $db->prepare(
                'INSERT INTO ' . self::TABLE . ' (usuario_id, accion, descripcion, ip, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $usuarioId,
                substr($accion, 0, 100),
                $detalle,
                $ip,
                $userAgent ? substr($userAgent, 0, 255) : null,
            ]);
        } catch (\Throwable $e) {
            // Silenciar fallos de logging para no interrumpir el flujo principal.
        }
    }

    private static function ensureTable(\PDO $db): void
    {
        if (self::$tableChecked) {
            return;
        }

        $sql = 'CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NULL,
            accion VARCHAR(100) NOT NULL,
            descripcion TEXT NULL,
            ip VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_accion (accion),
            INDEX idx_created_at (created_at),
            INDEX idx_usuario (usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci';

        $db->exec($sql);
        self::$tableChecked = true;
    }
}
