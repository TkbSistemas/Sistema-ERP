<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helpers/Session.php';

Session::start(); //Se ejecuta al inicio para asegurar que la sesión esté disponible en toda la aplicación

class AdminController
{
    public function obtenerMenuAdmin() {
        // Verificar si el usuario tiene el rol de Administrador
        $user = Session::user();
        if (!$user || $user['role'] !== 'Administrador') {
            header('Location: ' . Session::url('index'));
            exit();
        }

        // Aquí puedes agregar la lógica para obtener los datos necesarios para el menú del administrador
        $datos = [
            'nombre' => $user['nombre'] ?? '',
            'role' => $user['role'] ?? '',
            // Agrega más datos según sea necesario
        ];

        include __DIR__ . '/../views/administrador/menu_admin.php';
    }
}