<?php
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Database.php';

class DashboardController
{
    public function obtenerDashboardAdmin(): void
    {
        Session::requireLogin();

        $role   = $_SESSION['role'] ?? '';
        $nombre = $_SESSION['nombre'] ?? '';
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $db = Database::getInstance()->getConnection();

        $datos = [
            'nombre'      => $nombre,
            'role'        => $role,
            'last_update' => date('d/m/Y, h:i:s a'),
            'alertas'     => [],
        ];

        switch ($role) {
            case 'Administrador':
                $datos = array_merge($datos, $this->datosAdministrador($db));
                break;
            case 'Almacen':
                $datos = array_merge($datos, $this->datosAlmacen($db));
                break;
            case 'Empleado':
                $datos = array_merge($datos, $this->datosEmpleado($db, $userId));
                break;
            default:
                $datos = array_merge($datos, $this->datosGenerales($db));
                break;
        }

        include __DIR__ . '/../views/administrador/dashboard_admin.php';
    }

    private function datosGenerales($db): array
    {
        $totalProductos        = (int) $db->query('SELECT COUNT(*) FROM inventario')->fetchColumn();
        $stockBajo             = (int) $db->query('SELECT COUNT(*) FROM inventario p LEFT JOIN (SELECT producto_id, SUM(stock) AS stock_total FROM stock_almacen GROUP BY producto_id) si ON si.producto_id = p.id WHERE COALESCE(si.stock_total, 0) < p.stock_minimo')->fetchColumn();
        $valorTotal            = (float) $db->query('SELECT COALESCE(SUM(COALESCE(si.stock_total, 0) * p.precio_unitario), 0) FROM inventario p LEFT JOIN (SELECT producto_id, SUM(stock) AS stock_total FROM stock_almacen GROUP BY producto_id) si ON si.producto_id = p.id')->fetchColumn();
        $herramientasPrestadas = (int) $db->query("SELECT COUNT(*) FROM solicitudes_herramienta WHERE estatus = 'Activa'")->fetchColumn();
        $prestamosVencidos     = (int) $db->query("SELECT COUNT(*) FROM solicitudes_herramienta WHERE estatus = 'Activa' AND fecha_fin IS NOT NULL AND fecha_fin < NOW() AND fecha_devolucion IS NULL")->fetchColumn();

        return [
            'totalProductos'        => $totalProductos,
            'stockBajo'             => $stockBajo,
            'valorTotalInventario'  => $valorTotal,
            'herramientasPrestadas' => $herramientasPrestadas,
            'alertas'               => array_merge($this->alertasInventario($db), $this->alertasPrestamosVencidos($db)),
            'prestamosVencidos'     => $prestamosVencidos,
        ];
    }

    private function datosAdministrador($db): array
    {
        $datos = $this->datosGenerales($db);

        $solicitudesPendientes = (int) $db->query("SELECT COUNT(*) FROM solicitudes_material WHERE estatus = 'Pendiente'")->fetchColumn();
        $solicitudesAprobadas  = (int) $db->query("SELECT COUNT(*) FROM solicitudes_material WHERE estatus = 'Aprobada'")->fetchColumn();

        $datos['solicitudesPendientes'] = $solicitudesPendientes;
        $datos['solicitudesAprobadas']  = $solicitudesAprobadas;
        $datos['ultimaActualizacion']   = $this->ultimasActualizaciones($db);

        return $datos;
    }

    private function datosAlmacen($db): array{
        $datos = $this->datosGenerales($db);

        $productosAlmacen        = (int) $db->query('SELECT COUNT(*) FROM inventario')->fetchColumn();
        $solicitudesPorGestionar = (int) $db->query("SELECT COUNT(*) FROM solicitudes_material WHERE estado IN ('pendiente','aprobada')")->fetchColumn();

        $datos['productosAlmacen']   = $productosAlmacen;
        $datos['solicitudesAlmacen'] = $solicitudesPorGestionar;
        $datos['ultimosMovimientos'] = $this->expuestosMovimientos($db);

        return $datos;
    }

    private function datosEmpleado($db, int $userId): array{
        $solicitudesEnviadas  = (int) $db->query("SELECT COUNT(*) FROM solicitudes_material WHERE usuario_id = {$userId}")->fetchColumn();
        $pendientesAprobacion = (int) $db->query("SELECT COUNT(*) FROM solicitudes_material WHERE usuario_id = {$userId} AND estado = 'pendiente'")->fetchColumn();
        $entregadas           = (int) $db->query("SELECT COUNT(*) FROM solicitudes_material WHERE usuario_id = {$userId} AND estado = 'entregada'")->fetchColumn();

        $alertas = $db->prepare("SELECT comentario, estado, DATE_FORMAT(fecha_solicitud, '%d/%m/%Y') AS fecha
                                  FROM solicitudes_material
                                  WHERE usuario_id = ?
                                  ORDER BY fecha_solicitud DESC
                                  LIMIT 5");
        $alertas->execute([$userId]);

        return [
            'solicitudesMias'   => $solicitudesEnviadas,
            'pendientesAprobar' => $pendientesAprobacion,
            'entregadas'        => $entregadas,
            'alertas'           => $alertas->fetchAll() ?: [],
        ];
    }

    private function alertasInventario($db): array
    {
        $stmt = $db->query("SELECT p.nombre, COALESCE(si.stock_total, 0) AS stock_disponible, p.stock_minimo, DATE_FORMAT(p.created_at, '%d/%m/%Y') AS fecha
                             FROM inventario p
                             LEFT JOIN (SELECT producto_id, SUM(stock) AS stock_total FROM stock_almacen GROUP BY producto_id) si ON si.producto_id = p.id
                             WHERE COALESCE(si.stock_total, 0) < p.stock_minimo
                             ORDER BY COALESCE(si.stock_total, 0) ASC
                             LIMIT 5");
        $productos = $stmt->fetchAll();

        $alertas = [];
        foreach ($productos as $p) {
            $alertas[] = [
                $p['nombre'] . ' por debajo del stock mínimo',
                $p['fecha'],
                'alta',
            ];
        }
        return $alertas;
    }

    private function alertasPrestamosVencidos($db): array
    {
        $stmt = $db->query("SELECT p.nombre AS producto, pr.fecha_fin AS fecha_estimada_devolucion, u.nombre AS empleado
                             FROM solicitudes_herramienta pr
                             LEFT JOIN solicitudes_herramienta_detalles d ON d.solicitud_id = pr.id
                             LEFT JOIN inventario p ON d.producto_id = p.id
                             LEFT JOIN usuarios u ON pr.solicitante_id = u.id
                             WHERE pr.estatus = 'Activa'
                               AND pr.fecha_fin IS NOT NULL
                               AND pr.fecha_fin < NOW()
                               AND pr.fecha_devolucion IS NULL
                             ORDER BY pr.fecha_fin ASC
                             LIMIT 5");
        $rows    = $stmt->fetchAll() ?: [];
        $alertas = [];
        foreach ($rows as $r) {
            $fecha     = date('d/m/Y', strtotime($r['fecha_estimada_devolucion']));
            $alertas[] = [
                'Préstamo vencido: ' . ($r['producto'] ?? 'Herramienta') . ' (' . ($r['empleado'] ?? 'Empleado') . ')',
                $fecha,
                'alta',
            ];
        }
        return $alertas;
    }

    private function ultimasActualizaciones($db): array
    {
        $stmt = $db->query("SELECT p.nombre,
                                    m.tipo,
                                    m.created_at AS fecha,
                                    m.cantidad,
                                    a.nombre AS almacen
                             FROM movimientos_inventario m
                             LEFT JOIN inventario p ON m.producto_id = p.id
                             LEFT JOIN almacenes a ON m.almacen_id = a.id
                             ORDER BY m.created_at DESC
                             LIMIT 5");
        return $stmt->fetchAll();
    }

    private function expuestosMovimientos($db): array
    {
        $stmt = $db->query("SELECT p.nombre,
                                    p.codigo,
                                    m.tipo,
                                    m.cantidad,
                                    m.fecha,
                                    COALESCE(a.nombre, ad.nombre) AS almacen
                             FROM movimientos_inventario m
                             LEFT JOIN inventario p ON m.producto_id = p.id
                             LEFT JOIN almacenes a ON m.almacen_origen_id = a.id
                             LEFT JOIN almacenes ad ON m.almacen_destino_id = ad.id
                             ORDER BY m.fecha DESC
                             LIMIT 7");
        return $stmt->fetchAll();
    }
}
