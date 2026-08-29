<?php
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../helpers/Session.php';

class PrestamoController
{
    // Listar prestamos pendientes de devolucion
    public function pendientes()
    {
        Session::requireLogin(['Administrador', 'Almacen']);
        $prestamos = Prestamo::pendientes();
        $porPagina = 10;
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $total = count($prestamos);
        $total_paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min($pagina, $total_paginas);
        $prestamos = array_slice($prestamos, ($pagina - 1) * $porPagina, $porPagina);
        include __DIR__ . '/../views/inventario/prestamos/pendientes.php';
    }

    // Registrar devolucion (formulario)
    public function devolver($id)
    {
        Session::requireLogin(['Administrador', 'Almacen']);
        $prestamo = Prestamo::find($id);
        $msg      = '';
        if (! $prestamo || $prestamo['estado'] !== 'Prestado') {
            $msg = 'Este prestamo no esta pendiente de devolucion o no existe.';
            include __DIR__ . '/../views/inventario/prestamos/devolver.php';
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $msg = 'Token CSRF invalido.';
            } else {
                $estado_devolucion = $_POST['estado_devolucion'] ?? '';
                $observaciones     = trim($_POST['observaciones'] ?? '');
                Prestamo::devolver($id, $estado_devolucion, $observaciones);
                $msg = 'Devolucion registrada correctamente.';
                // Recargar prestamo actualizado
                $prestamo = Prestamo::find($id);
            }
        }
        include __DIR__ . '/../views/inventario/prestamos/devolver.php';
    }

    // Historial completo
    public function historial()
    {
        Session::requireLogin(['Administrador', 'Almacen']);

        $busqueda  = trim($_GET['q'] ?? '');
        $estado    = trim($_GET['estado'] ?? '');
        $desde     = trim($_GET['desde'] ?? '');
        $hasta     = trim($_GET['hasta'] ?? '');
        $page      = max(1, (int) ($_GET['page'] ?? 1));
        $porPagina = 10;

        $filtros = [
            'estado' => $estado,
            'desde'  => $desde,
            'hasta'  => $hasta,
        ];

        $prestamos  = Prestamo::historialPaginado($busqueda, $page, $porPagina, $filtros);
        $total      = Prestamo::totalHistorial($busqueda, $filtros);
        $totalPages = (int) ceil(($total ?: 0) / $porPagina);

        include __DIR__ . '/../views/inventario/prestamos/historial.php';
    }
}
