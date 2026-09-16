<?php
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/Producto.php';

class Prestamo
{
    // Listar préstamos pendientes de devolución
    public static function pendientes()
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT pr.*, p.nombre AS producto, p.codigo AS codigo_producto, u.nombre_completo AS empleado
                FROM prestamos pr
                LEFT JOIN productos p ON pr.producto_id = p.id
                LEFT JOIN usuarios u ON pr.empleado_id = u.id
                WHERE pr.estado = 'Prestado'
                ORDER BY pr.fecha_prestamo DESC";
        return $db->query($sql)->fetchAll();
    }

}
