<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/ActivityLogger.php';

class UsuarioController
{
    private array $rolesPermitidos = ['Administrador', 'Almacen', 'Empleado', 'Compras'];

    public function index(): void
    {
        Session::requireLogin('Administrador');
        $usuarios = Usuario::all();
        include __DIR__ . '/../views/usuarios/index.php';
    }

    public function create(): void
    {
        Session::requireLogin('Administrador');
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $error = 'Token CSRF inválido.';
            } else {
                $username = trim($_POST['username'] ?? '');
                $nombre   = trim($_POST['nombre_completo'] ?? '');
                $password = $_POST['password'] ?? '';
                $role     = $_POST['role'] ?? '';
                $activo   = isset($_POST['activo']);

                if ($username === '' || $nombre === '' || $password === '' || $role === '') {
                    $error = 'Todos los campos son obligatorios.';
                } elseif (! in_array($role, $this->rolesPermitidos, true)) {
                    $error = 'El rol seleccionado no es válido.';
                } elseif (strlen($password) < 6) {
                    $error = 'La contraseña debe tener al menos 6 caracteres.';
                } elseif (Usuario::findByUsername($username)) {
                    $error = 'Ese nombre de usuario ya está registrado.';
                } else {
                    Usuario::create([
                        'username'        => $username,
                        'password'        => $password,
                        'nombre_completo' => $nombre,
                        'role'            => $role,
                        'activo'          => $activo ? 1 : 0,
                    ]);
                    $usuarioId = (int) Database::getInstance()->getConnection()->lastInsertId();
                    ActivityLogger::registrarAlta('administracion', 'usuario', $usuarioId ?: null, 'Usuario Registrado', [
                        'username' => $username,
                        'rol' => $role,
                        'activo' => $activo,
                    ]);
                    header('Location: usuarios.php?success=1');
                    exit();
                }
            }
        }

        include __DIR__ . '/../views/usuarios/create.php';
    }

    public function edit($id): void
    {
        Session::requireLogin('Administrador');
        $usuario = Usuario::findById($id);
        $error   = '';

        if (! $usuario) {
            die('Usuario no encontrado.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $error = 'Token CSRF inválido.';
            } else {
                $username = trim($_POST['username'] ?? '');
                $nombre   = trim($_POST['nombre_completo'] ?? '');
                $password = $_POST['password'] ?? '';
                $role     = $_POST['role'] ?? '';
                $activo   = isset($_POST['activo']);

                if ($username === '' || $nombre === '' || $role === '') {
                    $error = 'Todos los campos son obligatorios.';
                } elseif (! in_array($role, $this->rolesPermitidos, true)) {
                    $error = 'El rol seleccionado no es válido.';
                } else {
                    Usuario::update($id, [
                        'username'        => $username,
                        'nombre_completo' => $nombre,
                        'password'        => $password,
                        'role'            => $role,
                        'activo'          => $activo ? 1 : 0,
                    ]);
                    ActivityLogger::registrarActualizacion('administracion', 'usuario', $id, 'Usuario Actualizado', [
                        'username' => $username,
                        'rol' => $role,
                        'activo' => $activo,
                        'password_actualizada' => $password !== '',
                    ]);
                    header('Location: usuarios.php?success=2');
                    exit();
                }
            }
        }

        include __DIR__ . '/../views/usuarios/edit.php';
    }

    public function delete($id): void
    {
        Session::requireLogin('Administrador');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ! Session::checkCsrf($_POST['csrf'] ?? '')) {
            header('Location: usuarios.php?deleted=0&error=csrf');
            exit();
        }

        $usuarioId = (int) ($id ?: ($_POST['id'] ?? 0));
        if ($usuarioId <= 0) {
            header('Location: usuarios.php?deleted=0');
            exit();
        }

        try {
            $ok = Usuario::delete($usuarioId);
            if ($ok) {
                ActivityLogger::registrarBaja('administracion', 'usuario', $usuarioId, 'Usuario Dado de Baja');
            }
            header('Location: usuarios.php?' . ($ok ? 'deleted=1' : 'deleted=0'));
        } catch (PDOException $e) {
            header('Location: usuarios.php?deleted=0&error=fk');
        }
        exit();
    }

    public function setActive($id, $active): void
    {
        Session::requireLogin('Administrador');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ! Session::checkCsrf($_POST['csrf'] ?? '')) {
            header('Location: usuarios.php?error=csrf');
            exit();
        }

        Usuario::setActive($id, (int) $active);
        ActivityLogger::registrarCambioEstado(
            'administracion',
            'usuario',
            $id,
            (int) $active === 1 ? 'Activo' : 'Inactivo',
            'Disponibilidad del Usuario Modificada'
        );
        header('Location: usuarios.php');
        exit();
    }
}
