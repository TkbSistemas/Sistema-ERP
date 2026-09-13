<?php
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Database.php';

class ActivityLogger
{
    private const TABLE = 'logs_actividad';
    private const MAX_CONTEXT_DEPTH = 4;
    private const REDACTED = '[REDACTADO]';
    private static bool $tableChecked = false;

    /** Registra una acción relevante que no pertenece al ciclo CRUD de una entidad. */
    public static function registrarAccion(string $modulo, string $accion, ?string $descripcion = null, array $contexto = []): void
    {
        self::log(self::normalizarParte($modulo) . '.' . self::normalizarParte($accion), $descripcion, $contexto);
    }

    public static function registrarAlta(string $modulo, string $entidad, $entidadId = null, ?string $descripcion = null, array $contexto = []): void
    {
        self::registrarEntidad($modulo, $entidad, 'alta', $entidadId, $descripcion, $contexto);
    }

    public static function registrarActualizacion(string $modulo, string $entidad, $entidadId = null, ?string $descripcion = null, array $contexto = []): void
    {
        self::registrarEntidad($modulo, $entidad, 'actualizacion', $entidadId, $descripcion, $contexto);
    }

    public static function registrarBaja(string $modulo, string $entidad, $entidadId = null, ?string $descripcion = null, array $contexto = []): void
    {
        self::registrarEntidad($modulo, $entidad, 'baja', $entidadId, $descripcion, $contexto);
    }

    public static function registrarCambioEstado(string $modulo, string $entidad, $entidadId, string $estado, ?string $descripcion = null, array $contexto = []): void
    {
        $contexto['estado_nuevo'] = $estado;
        self::registrarEntidad($modulo, $entidad, 'cambio_estado', $entidadId, $descripcion, $contexto);
    }

    public static function log(string $accion, ?string $descripcion = null, array $contexto = []): void
    {
        try {
            $db = Database::getInstance()->getConnection();
            self::ensureTable($db);

            Session::start();
            $usuarioId = $_SESSION['user_id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $contexto = self::sanitizarContexto($contexto);
            $ruta = trim((string) ($_GET['route'] ?? ''));
            if ($ruta !== '' && !isset($contexto['ruta'])) {
                $contexto['ruta'] = substr($ruta, 0, 100);
            }

            $detalle = trim((string) $descripcion);
            if ($contexto) {
                $json = json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
                if ($json !== false) {
                    $detalle .= ($detalle !== '' ? ' ' : '') . $json;
                }
            }

            $stmt = $db->prepare(
                'INSERT INTO ' . self::TABLE . ' (usuario_id, accion, descripcion, ip, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $usuarioId,
                substr(trim($accion), 0, 100),
                $detalle !== '' ? $detalle : null,
                $ip,
                $userAgent ? substr($userAgent, 0, 255) : null,
            ]);
        } catch (\Throwable $e) {
            // Silenciar fallos de logging para no interrumpir el flujo principal.
        }
    }

    private static function registrarEntidad(string $modulo, string $entidad, string $evento, $entidadId, ?string $descripcion, array $contexto): void
    {
        $entidadNormalizada = self::normalizarParte($entidad);
        if ($entidadId !== null && $entidadId !== '') {
            $contexto[$entidadNormalizada . '_id'] = is_numeric($entidadId) ? (int) $entidadId : (string) $entidadId;
        }

        self::log(
            self::normalizarParte($modulo) . '.' . $entidadNormalizada . '.' . self::normalizarParte($evento),
            $descripcion,
            $contexto
        );
    }

    private static function normalizarParte(string $valor): string
    {
        $valor = trim(mb_strtolower($valor));
        if (function_exists('iconv')) {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);
            if ($ascii !== false) {
                $valor = $ascii;
            }
        }
        $valor = preg_replace('/[^a-z0-9]+/', '_', $valor) ?? '';
        return trim($valor, '_') ?: 'evento';
    }

    private static function sanitizarContexto(array $contexto, int $profundidad = 0): array
    {
        if ($profundidad >= self::MAX_CONTEXT_DEPTH) {
            return ['detalle' => '[CONTEXTO_TRUNCADO]'];
        }

        $resultado = [];
        foreach ($contexto as $clave => $valor) {
            $claveTexto = (string) $clave;
            if (preg_match('/password|contrasena|contraseña|secret|token|csrf|cookie|authorization/i', $claveTexto)) {
                $resultado[$claveTexto] = self::REDACTED;
                continue;
            }
            if (is_array($valor)) {
                $resultado[$claveTexto] = self::sanitizarContexto($valor, $profundidad + 1);
            } elseif (is_scalar($valor) || $valor === null) {
                $resultado[$claveTexto] = is_string($valor) && mb_strlen($valor) > 1000
                    ? mb_substr($valor, 0, 1000) . '...'
                    : $valor;
            } elseif ($valor instanceof \Stringable) {
                $resultado[$claveTexto] = mb_substr((string) $valor, 0, 1000);
            } else {
                $resultado[$claveTexto] = '[' . get_debug_type($valor) . ']';
            }
        }
        return $resultado;
    }

    private static function ensureTable(\PDO $db): void
    {
        if (self::$tableChecked) {
            return;
        }

        try {
            $db->query('SELECT 1 FROM ' . self::TABLE . ' LIMIT 1');
            self::$tableChecked = true;
            return;
        } catch (\Throwable $e) {
            // Instalaciones anteriores pueden no tener aún la tabla de auditoría.
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
