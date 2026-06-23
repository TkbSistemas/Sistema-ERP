<?php
class Session {
    public const R_ADMIN = 'Administrador';
    public const R_ALMACEN = 'Almacen';
    public const R_EMPLEADO = 'Empleado';

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(self::cookieName());
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => self::cookiePath(),
                'secure' => self::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }
    }

    public static function regen(): void {
        self::start();
        session_regenerate_id(true);
    }

    public static function setUser(array $u): void {
        self::start();
        $_SESSION['user_id'] = (int) $u['id'];
        $_SESSION['username'] = $u['username'] ?? null;
        $_SESSION['nombre'] = $u['nombre_completo'] ?? $u['nombre'] ?? $u['username'] ?? null;
        $_SESSION['role'] = $u['role'] ?? null;
    }

    public static function user(): ?array {
        self::start();
        if (!isset($_SESSION['user_id'])) { //Comprobar si el usuario ha iniciado sesión
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? null,
            'nombre' => $_SESSION['nombre'] ?? null,
            'role' => $_SESSION['role'] ?? null,
        ];
    }

    public static function requireLogin($roles = null) {
        self::start();
        if (!isset($_SESSION['user_id'])) {
            $file = basename($_SERVER['PHP_SELF'] ?? '');
            $qs = $_SERVER['QUERY_STRING'] ?? '';
            $next = $file . ($qs ? ('?' . $qs) : '');
            header('Location: ' . self::url('login.php?next=' . urlencode($next)));
            exit();
        }

        if ($roles) {
            $role = $_SESSION['role'] ?? '';
            if (is_array($roles)) {
                if (!in_array($role, $roles, true)) {
                    header('Location: dashboard.php?no_access=1');
                    exit();
                }
            } else {
                if ($role !== $roles) {
                    header('Location: dashboard.php?no_access=1');
                    exit();
                }
            }
        }
    }

    public static function logout(): void {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function csrfToken(): string {
        self::start();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return hash_hmac('sha256', $_SESSION['csrf'], 'takab_csrf_key_change_me');
    }

    public static function checkCsrf(string $t): bool {
        return hash_equals(self::csrfToken(), $t);
    }

    public static function url(string $path = ''): string {
        $base = self::cookiePath(); 
        if ($path === '') { 
            return $base;
        }

        if (preg_match('/^(https?:)?\/\//i', $path)) {
            return $path;
        }

        return $base . ltrim($path, '/');
    }

    private static function cookiePath(): string {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';
        $dir = str_replace('\\', '/', dirname($scriptName));
        if ($dir === '/' || $dir === '\\' || $dir === '.') {
            return '/';
        }
        return rtrim($dir, '/') . '/';
    }

    private static function isHttps(): bool {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if (!empty($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443') {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
        }

        return false;
    }

    private static function cookieName(): string {
        return 'TAKABSESSID_' . substr(sha1(__DIR__), 0, 8);
    }
}
