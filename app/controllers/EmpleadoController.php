<?php
require_once __DIR__ . '/../models/Almacen.php';

class EmpleadoController{

    public function obtenerDashboardEmpleado(): void{
        Session::requireLogin(['Administrador', 'Empleado']);

        $role   = $_SESSION['role'] ?? '';
        $nombre = $_SESSION['nombre'] ?? '';
        $userId = (int) ($_SESSION['user_id'] ?? 0);

       $_SESSION['menu_items'] = [
            ['slug' => '', 'label' => 'Solicitar Material', 'icon' => 'fa-solid fa-file-signature', 'role' => 'Todos'],
            ['slug' => '', 'label' => 'Mis Solicitudes', 'icon' => 'fa-solid fa-file-signature', 'role' => 'Todos'],
            ['slug' => 'logout', 'label' => 'Cerrar Sesión', 'icon' => 'fa-solid fa-arrow-right-from-bracket', 'role' => 'Todos']
        ];

        $db = Database::getInstance()->getConnection();

        $datos = [
            'nombre'      => $nombre,
            'role'        => $role,
            'last_update' => date('d/m/Y, h:i:s a')
        ];

        include __DIR__ . '/../views/empleado/dashboard_empleado.php';
    }

}