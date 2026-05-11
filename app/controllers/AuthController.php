<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helpers/Session.php';

Session::start();

class AuthController
{
    public function login()
    {
        if (Session::user() !== null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $next = $this->sanitizeNext($_GET['next'] ?? null);
            if ($next) {
                header('Location: ' . Session::url($next));
                exit();
            }
            header('Location: ' . Session::url('menu.php'));
            exit();
        }

        $allowedModules = ['nomina', 'rh', 'gestion_usuarios', 'contabilidad', 'bancos', 'compras', 'inventario', 'ventas', 'proyectos', 'clientes'];
        $module = $_GET['module'] ?? $_POST['module'] ?? null;
        if ($module !== null && !in_array($module, $allowedModules, true)) {
            $module = null;
        }

        // Soportar redireccion post-login cuando una pagina protegida manda ?next=...
        $next = $_GET['next'] ?? $_POST['next'] ?? null;
        $next = $this->sanitizeNext($next);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $user = Usuario::findByUsername($username);

            if ($user && $this->credentialsMatch($user, $password)) {
                Session::regen();
                Session::setUser($user);

                if ($next) {
                    header('Location: ' . Session::url($next));
                    exit();
                }
                if ($module) {
                    $redirect = $this->routeByModule($module);
                    header('Location: ' . Session::url($redirect));
                    exit();
                }
                header('Location: ' . Session::url('menu.php'));
                exit();
            }

            $error = 'Usuario o contrasena incorrectos.';
        }

        include __DIR__ . '/../views/auth/login.php';
    }

    private function routeByModule(string $module): string
    {
        switch ($module) {
            case 'nomina':
                return 'nomina.php';
            case 'rh':
                return 'rh.php';
            case 'gestion_usuarios':
                return 'gestion_usuarios.php';
            case 'contabilidad':
                return 'contabilidad.php';
            case 'bancos':
                return 'bancos.php';
            case 'compras':
                return 'compras.php';
            case 'ventas':
                return 'ventas.php';
            case 'proyectos':
                return 'proyectos.php';
            case 'inventario':
                return 'inventario.php';
            case 'clientes':
                return 'clientes.php';
            default:
                return 'dashboard.php';
        }
    }

    private function sanitizeNext($next): ?string
    {
        if (!$next || !is_string($next)) {
            return null;
        }

        $next = trim($next);

        if (preg_match('/^(https?:)?\/\//i', $next)) {
            return null;
        }

        $parts = explode('?', $next, 2);
        $file = $parts[0] ?? '';
        if (!preg_match('/^[a-zA-Z0-9_-]+\.php$/', $file)) {
            return null;
        }

        $file = basename($file);
        if ($file === '') {
            return null;
        }

        return $file . (isset($parts[1]) ? ('?' . $parts[1]) : '');
    }

    private function credentialsMatch(array $user, string $password): bool
    {
        $storedPassword = (string) ($user['password'] ?? '');
        if ($storedPassword === '' || $password === '') {
            return false;
        }

        if (password_verify($password, $storedPassword)) {
            if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                Usuario::upgradePasswordHash((int) $user['id'], $password);
            }
            return true;
        }

        if (hash_equals($storedPassword, $password)) {
            Usuario::upgradePasswordHash((int) $user['id'], $password);
            return true;
        }

        return false;
    }

    public function logout()
    {
        Session::logout();
        header('Location: ' . Session::url('index.php'));
        exit();
    }

    public function forgotPassword()
    {
        $mensaje = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $user = Usuario::findByUsername($username);
            if ($user) {
                $mensaje = 'Por favor contacta al administrador para restablecer tu contraseña.';
            } else {
                $mensaje = 'Usuario no encontrado.';
            }
        }

        include __DIR__ . '/../views/auth/forgot.php';
    }
}
