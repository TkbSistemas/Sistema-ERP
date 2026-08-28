<?php
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/ActivityLogger.php';
require_once __DIR__ . '/../models/OrdenCompra.php';

class ComprasController
{

    public function obtenerDashboardCompras(): void{
        Session::requireLogin(['Administrador', 'Compras']);

        $role   = $_SESSION['role'] ?? '';
        $nombre = $_SESSION['nombre'] ?? '';
        $userId = (int) ($_SESSION['user_id'] ?? 0);

       $_SESSION['menu_items'] = [
            ['slug' => 'construccion', 'label' => 'Cotizaciones', 'icon' => 'fa-solid fa-file-invoice-dollar', 'role' => 'Todos'],
            ['slug' => 'construccion', 'label' => 'Ordenes de Compra', 'icon' => 'fa-solid fa-list-check', 'role' => 'Todos'],
            ['slug' => 'catalogo_productos', 'label' => 'Catálogo de Productos', 'icon' => 'fa-solid fa-clipboard-list', 'role' => 'Todos'],
            ['slug' => 'construccion', 'label' => 'Catálogo de Proveedores', 'icon' => 'fa-solid fa-building-user', 'role' => 'Todos'],
            ['slug' => 'logout', 'label' => 'Cerrar Sesión', 'icon' => 'fa-solid fa-arrow-right-from-bracket', 'role' => 'Todos']
        ];

        $db = Database::getInstance()->getConnection();

        $datos = [
            'nombre'      => $nombre,
            'role'        => $role,
            'last_update' => date('d/m/Y, h:i:s a'),
            'alertas'     => [],
        ];


        include __DIR__ . '/../views/compras/dashboard_compras.php';
    }
    public function historial(): void
    {
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

        $filtros = [
            'proveedor_id' => $_GET['proveedor_id'] ?? '',
            'desde'        => $_GET['desde'] ?? date('Y-m-01'),
            'hasta'        => $_GET['hasta'] ?? date('Y-m-d'),
        ];

        if ($filtros['desde'] > $filtros['hasta']) {
            [$filtros['desde'], $filtros['hasta']] = [$filtros['hasta'], $filtros['desde']];
        }

        $db          = Database::getInstance()->getConnection();
        $proveedores = $db->query("SELECT id, nombre FROM proveedores ORDER BY nombre ASC")->fetchAll();

        $historial = OrdenCompra::historial($filtros);

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            ActivityLogger::log('compras_export', 'Descarga de historial de compras', [
                'proveedor_id' => $filtros['proveedor_id'] ?: null,
                'desde'        => $filtros['desde'],
                'hasta'        => $filtros['hasta'],
            ]);
            $filename = 'compras_proveedor_' . date('Ymd_His') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);
            $out = fopen('php://output', 'w');
            fputs($out, chr(239) . chr(187) . chr(191));
            fputcsv($out, ['Orden', 'Fecha', 'Proveedor', 'Estado', 'Productos', 'Importe detalle', 'Importe total']);
            foreach ($historial['ordenes'] as $orden) {
                fputcsv($out, [
                    $orden['id'],
                    $orden['fecha'],
                    $orden['proveedor'],
                    $orden['estado'],
                    number_format((float) ($orden['total_items'] ?? 0), 2, '.', ''),
                    number_format((float) ($orden['subtotal'] ?? 0), 2, '.', ''),
                    number_format((float) ($orden['total'] ?? $orden['subtotal'] ?? 0), 2, '.', ''),
                ]);
            }
            fclose($out);
            return;
        }

        include __DIR__ . '/../views/compras/historial.php';
    }
}
