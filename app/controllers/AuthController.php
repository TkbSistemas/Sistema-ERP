<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helpers/Session.php';

Session::start(); //Se ejecuta al inicio para asegurar que la sesión esté disponible en toda la aplicación

class AuthController
{
    public function login() {
        //Si ya hay sesión activa y no es un intento de login, redirigir de inmediato
        if (Session::user() !== null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $next = $this->sanitizeNext($_GET['next'] ?? null);
            $this->redirectUser(Session::user(), $next);
        }

        $allowedModules = ['nomina', 'rh', 'gestion_usuarios', 'contabilidad', 'bancos', 'compras', 'inventario', 'ventas', 'proyectos', 'clientes'];
        $module = $_GET['module'] ?? $_POST['module'] ?? null;
        if ($module !== null && !in_array($module, $allowedModules, true)) {
            $module = null;
        }

        $next = $this->sanitizeNext($_GET['next'] ?? $_POST['next'] ?? null);

        //Manejo del intento de login (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $user = Usuario::findByUsername($username);

            if ($user && $this->credentialsMatch($user, $password)) {
                Session::regen();
                Session::setUser($user);

                //Usamos el nuevo método centralizado
                $this->redirectUser($user, $next, $module);
            }

            //Configuración de alerta en caso de error
            $_SESSION['alerta'] = [
                'tipo' => 'error',
                'titulo' => 'Error de Inicio de Sesión',
                'mensaje' => 'Usuario o contraseña Incorrectos. Por favor, inténtalo de nuevo.',
            ];
        }

        include __DIR__ . '/../views/auth/login.php';
    }

    private function redirectUser($user, $next = null, $module = null) {
        // Redirección explícita (?next=...)
        if ($next) {
            header('Location: ' . Session::url($next));
            exit();
        }

        // Prioridad 3: Mapeo de Roles a Dashboards
        $rol = $user['role'] ?? null;
        
        $roleDashboards = [
            'Administrador' => 'dashboard_admin',
            'Almacen'       => 'dashboard_almacen',
            'RH'            => 'dashboard_rh',
            'Compras'       => 'dashboard_compras',
            'Licitaciones'  => 'dashboard_licitaciones',
            'Campo'         => 'dashboard_campo',
            'Inventario'    => 'dashboard_inventario'
        ];

        if ($rol && isset($roleDashboards[$rol])) {
            header('Location: ' . Session::url($roleDashboards[$rol]));
            exit();
        }

        // Redirección por defecto si no cumple ninguna de las anteriores
        header('Location: ' . Session::url('index'));
        exit();
    }

    private function routeByModule(string $module): string{
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

    private function sanitizeNext($next): ?string{
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

    private function credentialsMatch(array $user, string $password): bool{
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

    public function logout(){
        Session::logout();
        include __DIR__ . '/../views/inicio_sesion/inicio.php';
        exit();
    }

    public function index(){
        include __DIR__ . '/../views/inicio_sesion/inicio.php';
    }

    public function obtenerDashboardAdmin(){
        include __DIR__ . '/../views/administrador/dashboard_admin.php';
    }

    public function forgotPassword(){
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
