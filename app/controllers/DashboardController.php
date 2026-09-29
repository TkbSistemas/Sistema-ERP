<?php
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/ActivityLogger.php';

class DashboardController
{
    public function obtenerDashboardAdmin(): void
    {
        Session::requireLogin(['Administrador']);

        $role   = $_SESSION['role'] ?? '';
        $nombre = $_SESSION['nombre'] ?? '';

        $_SESSION['menu_items'] = [
            ['slug' => 'menu_admin', 'label' => 'Menú Principal', 'icon' => 'fa-solid fa-grip', 'role' => 'Todos'],
            ['slug' => 'dashboard_inventario', 'label' => 'Ir a Inventario', 'icon' => 'fa-solid fa-boxes-stacked', 'role' => 'Todos'],
            ['slug' => 'dashboard_almacen', 'label' => 'Ir a Almacén', 'icon' => 'fa-solid fa-warehouse', 'role' => 'Todos'],
            ['slug' => 'dashboard_compras', 'label' => 'Ir a Compras', 'icon' => 'fa-solid fa-cart-shopping', 'role' => 'Todos'],
            ['slug' => 'dashboard_empleado', 'label' => 'Ir a Empleados', 'icon' => 'fa-solid fa-id-badge', 'role' => 'Todos'],
            ['slug' => 'proyectos', 'label' => 'Ir a Proyectos', 'icon' => 'fa-solid fa-diagram-project', 'role' => 'Todos'],
            ['slug' => 'logout', 'label' => 'Cerrar Sesión', 'icon' => 'fa-solid fa-arrow-right-from-bracket', 'role' => 'Todos'],
        ];

        $db = Database::getInstance()->getConnection();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!Session::checkCsrf((string) ($_POST['csrf'] ?? ''))) {
                    throw new RuntimeException('La Sesión Expiró. Recarga la Página e Intenta Nuevamente.');
                }

                $accion = (string) ($_POST['accion'] ?? '');
                if ($accion === 'crear_usuario') {
                    $nombreUsuario = trim((string) ($_POST['nombre'] ?? ''));
                    $username = strtolower(trim((string) ($_POST['username'] ?? '')));
                    $password = (string) ($_POST['password'] ?? '');
                    $confirmacion = (string) ($_POST['password_confirmacion'] ?? '');
                    $rolUsuario = trim((string) ($_POST['role'] ?? ''));
                    $rolesPermitidos = ['Administrador', 'Almacen', 'Empleado', 'Compras', 'Proyectos'];

                    if (mb_strlen($nombreUsuario) < 2 || mb_strlen($nombreUsuario) > 100) {
                        throw new InvalidArgumentException('El Nombre Debe Tener entre 2 y 100 Caracteres.');
                    }
                    if (!preg_match('/^[a-z0-9._-]{4,25}$/', $username)) {
                        throw new InvalidArgumentException('El Usuario Debe Tener entre 4 y 25 Caracteres y Solo Puede Usar Letras, Números, Punto, Guion o Guion Bajo.');
                    }
                    if (!in_array($rolUsuario, $rolesPermitidos, true)) {
                        throw new InvalidArgumentException('Selecciona un Rol de Acceso Válido.');
                    }
                    if (strlen($password) < 8 || strlen($password) > 72) {
                        throw new InvalidArgumentException('La Contraseña Debe Tener entre 8 y 72 Caracteres.');
                    }
                    if ($password !== $confirmacion) {
                        throw new InvalidArgumentException('La Confirmación de la Contraseña no Coincide.');
                    }

                    $existe = $db->prepare('SELECT COUNT(*) FROM usuarios WHERE username = ?');
                    $existe->execute([$username]);
                    if ((int) $existe->fetchColumn() > 0) {
                        throw new RuntimeException('El Nombre de Usuario ya Está Registrado.');
                    }

                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    if ($hash === false) {
                        throw new RuntimeException('No Fue Posible Proteger la Contraseña.');
                    }
                    $insertar = $db->prepare(
                        'INSERT INTO usuarios (username, password, nombre, role, baja, activo) VALUES (?, ?, ?, ?, 0, 1)'
                    );
                    $insertar->execute([$username, $hash, $nombreUsuario, $rolUsuario]);
                    $usuarioNuevoId = (int) $db->lastInsertId();

                    ActivityLogger::registrarAlta('administracion', 'usuario', $usuarioNuevoId, 'Usuario de Acceso Registrado', [
                        'username' => $username,
                        'rol' => $rolUsuario,
                    ]);
                    $_SESSION['alerta'] = [
                        'tipo' => 'success',
                        'titulo' => 'Usuario Registrado',
                        'mensaje' => 'La Cuenta de Acceso se Creó Correctamente.',
                    ];
                } elseif ($accion === 'crear_proyecto') {
                    $codigo = strtoupper(trim((string) ($_POST['codigo'] ?? '')));
                    $nombreProyecto = trim((string) ($_POST['nombre_proyecto'] ?? ''));

                    if (!preg_match('/^[A-Z0-9._-]{2,50}$/', $codigo)) {
                        throw new InvalidArgumentException('El Código Debe Tener entre 2 y 50 Caracteres y Solo Puede Usar Letras, Números, Punto, Guion o Guion Bajo.');
                    }
                    if (mb_strlen($nombreProyecto) < 2 || mb_strlen($nombreProyecto) > 255) {
                        throw new InvalidArgumentException('El Nombre del Proyecto Debe Tener entre 2 y 255 Caracteres.');
                    }

                    $existe = $db->prepare('SELECT COUNT(*) FROM proyectos WHERE UPPER(codigo) = ?');
                    $existe->execute([$codigo]);
                    if ((int) $existe->fetchColumn() > 0) {
                        throw new RuntimeException('Ya Existe un Proyecto con Ese Código.');
                    }

                    $insertar = $db->prepare('INSERT INTO proyectos (codigo, nombre, cliente_id) VALUES (?, ?, NULL)');
                    $insertar->execute([$codigo, $nombreProyecto]);
                    $proyectoId = (int) $db->lastInsertId();

                    ActivityLogger::registrarAlta('administracion', 'proyecto', $proyectoId, 'Proyecto Registrado', [
                        'codigo' => $codigo,
                        'nombre' => $nombreProyecto,
                    ]);
                    $_SESSION['alerta'] = [
                        'tipo' => 'success',
                        'titulo' => 'Proyecto Registrado',
                        'mensaje' => 'El Proyecto se Creó Correctamente.',
                    ];
                } else {
                    throw new InvalidArgumentException('La Operación Solicitada no es Válida.');
                }
            } catch (InvalidArgumentException | RuntimeException $e) {
                $_SESSION['alerta'] = [
                    'tipo' => 'warning',
                    'titulo' => 'No Fue Posible Guardar el Registro',
                    'mensaje' => $e->getMessage(),
                ];
            } catch (Throwable $e) {
                error_log('Error en acción rápida administrativa: ' . $e->getMessage());
                $_SESSION['alerta'] = [
                    'tipo' => 'error',
                    'titulo' => 'No Fue Posible Guardar el Registro',
                    'mensaje' => 'Ocurrió un Error al Procesar la Información. Intenta Nuevamente.',
                ];
            }

            header('Location: ' . Session::url('dashboard_admin'));
            exit;
        }

        $datos = [
            'nombre'      => $nombre,
            'role'        => $role,
            'last_update' => date('d/m/Y, h:i:s a'),
            'alertas'     => [],
        ];

        $datos = array_merge($datos, $this->datosAdministrador($db));

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
        $resumen = $db->query(
            "SELECT
                (SELECT COUNT(*) FROM inventario WHERE activo = '1') AS productos_activos,
                (SELECT COUNT(*)
                   FROM inventario i
                   LEFT JOIN (
                       SELECT producto_id, SUM(stock) AS stock_total
                       FROM stock_almacen
                       GROUP BY producto_id
                   ) sa ON sa.producto_id = i.id
                  WHERE i.activo = '1'
                    AND COALESCE(sa.stock_total, 0) < COALESCE(i.stock_minimo, 0)) AS productos_stock_bajo,
                (SELECT COALESCE(SUM(COALESCE(sa.stock_total, 0) * COALESCE(i.precio_unitario, 0)), 0)
                   FROM inventario i
                   LEFT JOIN (
                       SELECT producto_id, SUM(stock) AS stock_total
                       FROM stock_almacen
                       GROUP BY producto_id
                   ) sa ON sa.producto_id = i.id
                  WHERE i.activo = '1') AS valor_inventario,
                (SELECT COUNT(*) FROM solicitudes_material WHERE activo = 1 AND estatus = 'Pendiente') AS solicitudes_pendientes,
                (SELECT COUNT(*) FROM solicitudes_material WHERE activo = 1 AND estatus = 'Aprobada') AS solicitudes_por_entregar,
                (SELECT COUNT(*) FROM ordenes_compra WHERE estatus = 'Pendiente') AS ordenes_pendientes,
                (SELECT COUNT(*) FROM ordenes_compra WHERE estatus IN ('Aprobada', 'Parcial')) AS ordenes_por_procesar,
                (SELECT COUNT(*)
                   FROM ordenes_compra oc
                   LEFT JOIN facturas_compras fc ON fc.orden_id = oc.id
                  WHERE oc.estatus IN ('Parcial', 'Completa', 'Incompleta', 'Recibida')
                    AND fc.id IS NULL) AS ordenes_sin_factura,
                (SELECT COUNT(*) FROM catalogo_proveedores WHERE activo = 1) AS proveedores_activos,
                (SELECT COUNT(*) FROM usuarios WHERE activo = 1 AND baja = 0) AS usuarios_activos,
                (SELECT COUNT(*) FROM almacenes WHERE activo = 1) AS almacenes_activos,
                (SELECT COUNT(*) FROM proyectos) AS proyectos_registrados"
        )->fetch(PDO::FETCH_ASSOC) ?: [];

        $actividad = $db->query(
            "SELECT l.accion, l.descripcion, l.created_at, COALESCE(u.nombre, 'Sistema') AS usuario
             FROM logs_actividad l
             LEFT JOIN usuarios u ON u.id = l.usuario_id
             ORDER BY l.created_at DESC, l.id DESC
             LIMIT 7"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($actividad as &$registro) {
            $descripcion = trim((string) ($registro['descripcion'] ?? 'Actividad Registrada'));
            $posicionContexto = strpos($descripcion, ' {');
            if ($posicionContexto !== false) {
                $descripcion = substr($descripcion, 0, $posicionContexto);
            }
            $registro['descripcion_limpia'] = $descripcion !== '' ? $descripcion : 'Actividad Registrada';
        }
        unset($registro);

        return [
            'productosActivos' => (int) ($resumen['productos_activos'] ?? 0),
            'stockBajo' => (int) ($resumen['productos_stock_bajo'] ?? 0),
            'valorTotalInventario' => (float) ($resumen['valor_inventario'] ?? 0),
            'solicitudesPendientes' => (int) ($resumen['solicitudes_pendientes'] ?? 0),
            'solicitudesPorEntregar' => (int) ($resumen['solicitudes_por_entregar'] ?? 0),
            'ordenesPendientes' => (int) ($resumen['ordenes_pendientes'] ?? 0),
            'ordenesPorProcesar' => (int) ($resumen['ordenes_por_procesar'] ?? 0),
            'ordenesSinFactura' => (int) ($resumen['ordenes_sin_factura'] ?? 0),
            'proveedoresActivos' => (int) ($resumen['proveedores_activos'] ?? 0),
            'usuariosActivos' => (int) ($resumen['usuarios_activos'] ?? 0),
            'almacenesActivos' => (int) ($resumen['almacenes_activos'] ?? 0),
            'proyectosRegistrados' => (int) ($resumen['proyectos_registrados'] ?? 0),
            'actividadReciente' => $actividad,
        ];
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
