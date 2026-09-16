<?php
require_once __DIR__ . '/../models/Almacen.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/MovimientoInventario.php';
require_once __DIR__ . '/../models/SolicitudMaterial.php';
require_once __DIR__ . '/../helpers/ActivityLogger.php';

class AlmacenController
{
    private $menu_items;

    public function obtenerDashboardAlmacen(): void{
        Session::requireLogin(['Administrador', 'Almacen']);

        $role   = $_SESSION['role'] ?? '';
        $nombre = $_SESSION['nombre'] ?? '';
        $userId = (int) ($_SESSION['id'] ?? 0);

       $_SESSION['menu_items'] = [
            ['slug' => 'solicitudes_material', 'label' => 'Solicitudes de Material', 'icon' => 'fa-solid fa-file-signature', 'role' => 'Todos'],
            ['slug' => 'registrar_entrada', 'label' => 'Entrada de Productos', 'icon' => 'fa-solid fa-boxes-stacked', 'role' => 'Todos'],
            ['slug' => 'construccion', 'label' => 'Préstamos de Herramientas','icon' => 'fa-solid fa-tools', 'role' => 'Todos'],
            ['slug' => 'construccion', 'label' => 'Cajas de Herramientas', 'icon' => 'fa-solid fa-toolbox', 'role' => 'Todos'],
            ['slug' => 'registrar_salida', 'label' => 'Baja de Productos', 'icon' => 'fa-solid fa-trash-arrow-up', 'role' => 'Todos'],
            ['slug' => 'construccion', 'label' => 'Reabastecimiento', 'icon' => 'fa-solid fa-truck-loading', 'role' => 'Todos'],
            ['slug' => 'construccion', 'label' => 'Etiquetas', 'icon' => 'fa-solid fa-tags', 'role' => 'Todos'],
            //['slug' => 'reportes_inventario', 'label' => 'Reportes de Inventario', 'icon' => 'fa-solid fa-chart-pie', 'role' => 'Administrador'],
            ['slug' => 'inventario', 'label' => 'Ir a Inventario', 'icon' => 'fa-solid fa-warehouse', 'role' => 'Todos'],
            ['slug' => 'logout', 'label' => 'Cerrar Sesión', 'icon' => 'fa-solid fa-arrow-right-from-bracket', 'role' => 'Todos']
        ];

        $db = Database::getInstance()->getConnection();

        $datos = [
            'nombre'      => $nombre,
            'role'        => $role,
            'last_update' => date('d/m/Y, h:i:s a'),
            'alertas'     => [],
        ];

        $datos = array_merge($datos, $this->datosAlmacen($db));

        include __DIR__ . '/../views/almacen/dashboard_almacen.php';
    }

    public function obtenerSolicitudesMaterial(){
            Session::requireLogin(['Administrador', 'Almacen']);

            $db = Database::getInstance()->getConnection();
            $datos = [];
            $porPagina = 8;
            $tabActiva = $_GET['tab'] ?? 'pendientes';
            if (!in_array($tabActiva, ['pendientes', 'historial'], true)) {
                $tabActiva = 'pendientes';
            }
            $pagina = max(1, (int) ($_GET['pagina'] ?? 1));

            $solicitudesEsteMes = $db->query("
                SELECT 
                    s.id,
                    s.folio,
                    s.comentario_responsable,
                    s.fecha_respuesta,
                    s.proyecto_id,
                    s.estatus,
                    s.solicitante_id,
                    u.nombre AS nombre_solicitante,
                    p.nombre AS nombre_proyecto,
                    (SELECT COUNT(*) FROM solicitudes_material_detalles d WHERE d.solicitud_id = s.id)
                        + (SELECT COUNT(*) FROM solicitudes_material_noregistrados nr WHERE nr.solicitud_id = s.id) AS total_items,
                    COALESCE((SELECT SUM(d.cantidad) FROM solicitudes_material_detalles d WHERE d.solicitud_id = s.id), 0)
                        + COALESCE((SELECT SUM(nr.cantidad) FROM solicitudes_material_noregistrados nr WHERE nr.solicitud_id = s.id), 0) AS total_cantidad_materiales,
                    CONCAT_WS('<br>',
                        (SELECT GROUP_CONCAT(CONCAT(d.cantidad, 'x ', i.nombre) SEPARATOR '<br>') FROM solicitudes_material_detalles d INNER JOIN inventario i ON i.id = d.producto_id WHERE d.solicitud_id = s.id),
                        (SELECT GROUP_CONCAT(CONCAT(nr.cantidad, 'x ', nr.nombre, ' (Fuera del Catálogo)') SEPARATOR '<br>') FROM solicitudes_material_noregistrados nr WHERE nr.solicitud_id = s.id)
                    ) AS materiales_resumen
                FROM solicitudes_material s
                LEFT JOIN usuarios u 
                    ON s.solicitante_id = u.id
                LEFT JOIN proyectos p
                    ON s.proyecto_id = p.id
                WHERE estatus IN ('Rechazada', 'Entregada')
                AND fecha_solicitud >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00')
                AND fecha_solicitud <= CONCAT(LAST_DAY(NOW()), ' 23:59:59')
                ORDER BY s.fecha_solicitud DESC
            ")->fetchAll();

            $numSolicitudesEsteMes = (int) $db->query("
                SELECT COUNT(*) 
                FROM solicitudes_material 
                WHERE estatus IN ('Rechazada', 'Entregada')
                AND fecha_solicitud >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00')
                AND fecha_solicitud <= CONCAT(LAST_DAY(NOW()), ' 23:59:59')
            ")->fetchColumn();


            $solicitudesPendientes = $db->query("
                SELECT 
                    s.id,
                    s.folio,
                    s.fecha_solicitud,
                    s.proyecto_id,
                    s.estatus,
                    s.solicitante_id,
                    u.nombre AS nombre_solicitante,
                    p.nombre AS nombre_proyecto,
                    (SELECT COUNT(*) FROM solicitudes_material_detalles d WHERE d.solicitud_id = s.id)
                        + (SELECT COUNT(*) FROM solicitudes_material_noregistrados nr WHERE nr.solicitud_id = s.id) AS total_items,
                    COALESCE((SELECT SUM(d.cantidad) FROM solicitudes_material_detalles d WHERE d.solicitud_id = s.id), 0)
                        + COALESCE((SELECT SUM(nr.cantidad) FROM solicitudes_material_noregistrados nr WHERE nr.solicitud_id = s.id), 0) AS total_cantidad_materiales,
                    CONCAT_WS('<br>',
                        (SELECT GROUP_CONCAT(CONCAT(d.cantidad, 'x ', i.nombre) SEPARATOR '<br>') FROM solicitudes_material_detalles d INNER JOIN inventario i ON i.id = d.producto_id WHERE d.solicitud_id = s.id),
                        (SELECT GROUP_CONCAT(CONCAT(nr.cantidad, 'x ', nr.nombre, ' (Fuera del Catálogo)') SEPARATOR '<br>') FROM solicitudes_material_noregistrados nr WHERE nr.solicitud_id = s.id)
                    ) AS materiales_resumen
                FROM solicitudes_material s
                LEFT JOIN usuarios u 
                    ON s.solicitante_id = u.id
                LEFT JOIN proyectos p
                    ON s.proyecto_id = p.id
                WHERE estatus IN ('Pendiente','Aprobada')
                ORDER BY s.fecha_solicitud DESC
            ")->fetchAll(PDO::FETCH_ASSOC);

            $numSolicitudesPendientes = (int) $db->query("
                SELECT COUNT(*) 
                FROM solicitudes_material 
                WHERE estatus IN ('Pendiente', 'Aprobada')
            ")->fetchColumn();

            $totalSeleccionado = $tabActiva === 'historial'
                ? $numSolicitudesEsteMes
                : $numSolicitudesPendientes;
            $total_paginas = max(1, (int) ceil($totalSeleccionado / $porPagina));
            $pagina = min($pagina, $total_paginas);
            $offset = ($pagina - 1) * $porPagina;

            // Ambas consultas se conservan para las pestañas; la lista activa se recorta
            // antes de renderizar para garantizar el máximo de ocho filas visibles.
            if ($tabActiva === 'historial') {
                $solicitudesEsteMes = array_slice($solicitudesEsteMes, $offset, $porPagina);
            } else {
                $solicitudesPendientes = array_slice($solicitudesPendientes, $offset, $porPagina);
            }


            $datos['numSolicitudesEsteMes'] = $numSolicitudesEsteMes;
            $datos['solicitudesEsteMes'] = $solicitudesEsteMes;
            $datos['solicitudesPendientes'] = $solicitudesPendientes;
            $datos['numSolicitudesPendientes'] = $numSolicitudesPendientes;

            $pagination = [
                'pagina' => $pagina,
                'total_paginas' => $total_paginas,
                'por_pagina' => $porPagina,
                'total' => $totalSeleccionado,
                'desde' => $totalSeleccionado > 0 ? $offset + 1 : 0,
                'hasta' => min($offset + $porPagina, $totalSeleccionado),
            ];

            include __DIR__ . '/../views/almacen/solicitudes_material.php';
    }

    public function verSolicitudMaterial($id){
        Session::requireLogin(['Administrador', 'Almacen']);

        $solicitud = SolicitudMaterial::obtenerSolicitudConDetalles($id);
        if (! $solicitud) {
            die('Solicitud no encontrada.');
        }

        include __DIR__ . '/../templates/solicitud_material.php';
    }

    public function procesarSolicitud(): void
    {
        Session::requireLogin(['Administrador', 'Almacen']);

        $id = max(0, (int) ($_GET['id'] ?? 0));
        $solicitud = $id > 0 ? SolicitudMaterial::obtenerSolicitudConDetalles($id) : null;

        if (!$solicitud) {
            $_SESSION['alerta'] = [
                'tipo' => 'error',
                'titulo' => 'Solicitud no Encontrada',
                'mensaje' => 'No Fue Posible Cargar la Solicitud Seleccionada.',
            ];
            header('Location: solicitudes_material');
            exit();
        }

        $role = $_SESSION['role'] ?? '';
        $nombre = $_SESSION['nombre'] ?? '';
        include __DIR__ . '/../views/almacen/procesar_solicitud.php';
    }

    public function aprobarSolicitud(): void {
        Session::requireLogin(['Administrador', 'Almacen']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id         = (int) ($_POST['id'] ?? 0);
            $comentario = trim($_POST['comentario'] ?? '');
            if ($id > 0) {
                $db = Database::getInstance()->getConnection();
                
                $stmt = $db->prepare("
                    UPDATE solicitudes_material 
                    SET estatus = 'Aprobada', 
                        comentario_responsable = ?, 
                        fecha_respuesta = NOW() 
                    WHERE id = ? AND estatus = 'Pendiente'
                ");
                $stmt->execute([$comentario, $id]);

                if ($stmt->rowCount() === 1) {
                    ActivityLogger::registrarCambioEstado(
                        'almacen',
                        'solicitud_material',
                        $id,
                        'Aprobada',
                        'Solicitud de Material Aprobada',
                        ['comentario_registrado' => $comentario !== '']
                    );
                    $_SESSION['alerta'] = [
                        'tipo' => 'success',
                        'titulo' => 'Solicitud Aprobada',
                        'mensaje' => 'Solicitud Aprobada Éxitosamente.'
                    ];
                } else {
                    $_SESSION['alerta'] = [
                        'tipo' => 'error',
                        'titulo' => 'Solicitud no Disponible',
                        'mensaje' => 'La Solicitud no Existe o ya Fue Procesada.'
                    ];
                }
            } else {
                    $_SESSION['alerta'] = [
                    'tipo' => 'error',
                    'titulo' => 'Error',
                    'mensaje' => 'Error al Aprobar la Solicitud.'
                ];
            }
            header('Location: solicitudes_material');
            exit();
        }
    }

    public function rechazarSolicitud(): void {
        Session::requireLogin(['Administrador', 'Almacen']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id         = (int) ($_POST['id'] ?? 0);
            $comentario = trim($_POST['comentario'] ?? '');
            if ($id > 0) {
                $db = Database::getInstance()->getConnection();
                
                $stmt = $db->prepare("
                    UPDATE solicitudes_material 
                    SET estatus = 'Rechazada', 
                        comentario_responsable = ?, 
                        fecha_respuesta = NOW() 
                    WHERE id = ? AND estatus IN ('Pendiente', 'Aprobada')
                ");
                $stmt->execute([$comentario, $id]);

                if ($stmt->rowCount() === 1) {
                    ActivityLogger::registrarCambioEstado(
                        'almacen',
                        'solicitud_material',
                        $id,
                        'Rechazada',
                        'Solicitud de Material Rechazada',
                        ['comentario_registrado' => $comentario !== '']
                    );
                    $_SESSION['alerta'] = [
                        'tipo' => 'success',
                        'titulo' => 'Solicitud Rechazada',
                        'mensaje' => 'Solicitud Rechazada Éxitosamente.'
                    ];
                } else {
                    $_SESSION['alerta'] = [
                        'tipo' => 'error',
                        'titulo' => 'Solicitud no Disponible',
                        'mensaje' => 'La Solicitud no Existe o ya Fue Procesada.'
                    ];
                }
            } else {
                $_SESSION['alerta'] = [
                    'tipo' => 'error',
                    'titulo' => 'Error',
                    'mensaje' => 'Error al Rechazar la Solicitud.'
                ];
            }
            header('Location: solicitudes_material');
            exit();
        }
    }

    public function viewRegistrarEntradaRapida(){
        $productos = Producto::All();
        $almacenes = Almacen::all();
        Session::requireLogin(['Administrador', 'Almacen']);
        
        include __DIR__ . '/../views/almacen/entrada_rapida.php';
    }
    
    public function viewRegistrarEntrada(){
        $productos = Producto::All();
        $almacenes = Almacen::all();
        Session::requireLogin(['Administrador', 'Almacen']);
        
        include __DIR__ . '/../views/almacen/registrar_entrada.php';
    }

    public function registrarEntradaRapida(){
        Session::requireLogin(['Administrador', 'Almacen']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $entradaItems = $this->normalizarLineasEntrada($_POST);

            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $_SESSION['alerta'] = [
                    'tipo' => 'error',
                    'titulo' => 'Seguridad',
                    'mensaje' => 'Token CSRF inválido.'
                ];
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            } elseif (empty($entradaItems)) {
                $_SESSION['alerta'] = [
                    'tipo' => 'warning',
                    'titulo' => 'Captura Vacía',
                    'mensaje' => 'Agrega al Menos un Producto a la Captura de Entrada.'
                ];
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $db = Database::getInstance()->getConnection();

                    try {
                        // La comprobación puede ejecutar DDL, que provoca un commit implícito
                        // en MariaDB/MySQL; por eso debe ocurrir antes de la transacción.
                        Producto::ensureStockTableReady();
                        $db->beginTransaction();
    
                        foreach ($entradaItems as $indice => $linea) {
                            $productoId = (int) ($linea['producto_id'] ?? 0);
                            $almacenId  = (int) ($linea['almacen_id'] ?? 0);
                            $cantidad   = isset($linea['cantidad']) ? (float) $linea['cantidad'] : 0;

                            if ($productoId <= 0 || $almacenId <= 0 || $cantidad <= 0) {
                                throw new RuntimeException('La Línea ' . ($indice + 1) . ' Es Invalida.');
                            }

                            $data = [
                                'producto_id'        => $productoId,
                                'tipo'               => 'Entrada',
                                'cantidad'           => $cantidad,
                                'responsable_id'     => $_SESSION['user_id'] ?? 0,
                                'almacen_id' => $almacenId,
                                'observaciones'        => $linea['observaciones'] ?? null,
                                'folio_solicitud' => $linea['folio'] ?? null
                            ];

                            if (! MovimientoInventario::registrar($data)) {
                                throw new RuntimeException('No Fue Posible Registrar La Línea ' . ($indice + 1) . '.');
                            }

                            if (! Producto::sumarStock($productoId, $cantidad, $almacenId)) {
                                throw new RuntimeException('No Fue Posible Actualizar el Stock en la Línea ' . ($indice + 1) . '.');
                            }
                        }

                        $db->commit();

                        $totalLineas = count($entradaItems);
                        ActivityLogger::registrarAccion(
                            'inventario',
                            'entrada_rapida',
                            'Entrada Rápida de Inventario Registrada',
                            [
                                'lineas' => $totalLineas,
                                'productos' => array_values(array_unique(array_map(
                                    static fn(array $linea): int => (int) ($linea['producto_id'] ?? 0),
                                    $entradaItems
                                ))),
                            ]
                        );
                        $_SESSION['alerta'] = [
                            'tipo' => 'success',
                            'titulo' => 'Entrada Registrada',
                            'mensaje' => $totalLineas === 1 
                                ? 'Entrada registrada Correctamente.' 
                                : 'Se registraron ' . $totalLineas . ' Productos Correctamente.'
                        ];
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    } catch (\Throwable $e) {
                        if ($db->inTransaction()) {
                            $db->rollBack();
                        }
                        $_SESSION['alerta'] = [
                            'tipo' => 'error',
                            'titulo' => 'Error de Registro',
                            'mensaje' => $e->getMessage() ?: 'No fue posible registrar la entrada. Revisa los datos.'
                            //'mensaje' => 'No fue posible registrar la entrada. Revisa los datos.'
                        ];
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    }
                }
            }

            $productos            = Producto::all();
            $almacenes            = Almacen::all();
            $movimientosRecientes = MovimientoInventario::ultimos('Entrada', 6);
            $entradaItems         = [];

            include __DIR__ . '/../views/almacen/entrada_rapida.php';
    }
    
    public function viewRegistrarSalida(){
            Session::requireLogin(['Administrador', 'Almacen']); 
            $limite = 8;
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $productos = Producto::All();
            $almacenes = Almacen::all();
            $movimientosRecientes = MovimientoInventario::ultimos('Salida', 6);
            $solicitudesPendientes = SolicitudMaterial::obtenerSalidasPendientes($page, $limite);
            $solicitudesHistorial  = SolicitudMaterial::obtenerSalidasHistorial($page, $limite);
            $totalPendientes = SolicitudMaterial::contarBajasPendientes();
            $totalHistorial = SolicitudMaterial::contarBajasHistorial();
            $maxPaginas = max(1, (int) ceil(max($totalPendientes, $totalHistorial) / $limite));
            if ($page > $maxPaginas) {
                $page = $maxPaginas;
                $solicitudesPendientes = SolicitudMaterial::obtenerSalidasPendientes($page, $limite);
                $solicitudesHistorial  = SolicitudMaterial::obtenerSalidasHistorial($page, $limite);
            }
            
            include __DIR__ . '/../views/almacen/registrar_salida.php';
    }

    public function crearSolicitudBaja(){
        Session::requireLogin(['Administrador', 'Almacen']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $entradaItems = $this->normalizarLineasEntrada($_POST);
            $user = Session::user();

            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $_SESSION['alerta'] = [
                    'tipo' => 'error',
                    'titulo' => 'Seguridad',
                    'mensaje' => 'Token CSRF inválido.'
                ];
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            } elseif (empty($entradaItems)) {
                $_SESSION['alerta'] = [
                    'tipo' => 'warning',
                    'titulo' => 'Captura Vacía',
                    'mensaje' => 'Agrega al Menos un Producto a la Captura de Entrada.'
                ];
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $db = Database::getInstance()->getConnection();

                    try {
                        $db->beginTransaction();
                        $almacen_id = $_POST['almacen_id'] ?? $_POST['almacen_id_hidden'] ?? null;
                        if ($almacen_id <= 0) {
                            throw new RuntimeException('Debes Seleccionar un Almacén Válido.');
                        }
                        
                        $data = [
                            'folio'        => $this->generarFolioBaja(),
                            'solicitante_id'  => $user['id'],
                            'almacen_id'    => $almacen_id
                        ];

                        $sql = "INSERT INTO solicitudes_bajas (folio, solicitante_id, almacen_id) VALUES (?, ?, ?)";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            $data['folio']   ?? null,
                            $data['solicitante_id']          ?? 0,
                            $data['almacen_id']      ?? 0
                        ]);

                        $solicitudId = $db->lastInsertId();

                        $sqlDetalle = "INSERT INTO solicitudes_bajas_detalles (solicitud_id, producto_id, cantidad, motivos) VALUES (?, ?, ?, ?)";
                        $stmtDetalle = $db->prepare($sqlDetalle);
    
                        foreach ($entradaItems as $indice => $linea) {
                            $productoId = (int) ($linea['producto_id'] ?? 0);
                            $cantidad   = isset($linea['cantidad']) ? (float) $linea['cantidad'] : 0;
                            $motivo     = trim($linea['observaciones'] ?? '');

                            if ($productoId <= 0 || $cantidad <= 0) {
                                throw new RuntimeException('La Línea ' . ($indice + 1) . ' Es Inválida.');
                            }

                            $stmtDetalle->execute([
                                $solicitudId,
                                $productoId,
                                $cantidad,
                                $motivo
                            ]);
                        }

                        $db->commit();

                        ActivityLogger::registrarAlta(
                            'almacen',
                            'solicitud_baja',
                            $solicitudId,
                            'Solicitud de Baja Registrada',
                            [
                                'folio' => $data['folio'],
                                'almacen_id' => (int) $almacen_id,
                                'lineas' => count($entradaItems),
                            ]
                        );

                        $_SESSION['alerta'] = [
                            'tipo' => 'success',
                            'titulo' => 'Solicitud Registrada',
                            'mensaje' => 'En Espera de Aprobación.'
                        ];
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    } catch (\Throwable $e) {
                        if ($db->inTransaction()) {
                            $db->rollBack();
                        }
                        $_SESSION['alerta'] = [
                            'tipo' => 'error',
                            'titulo' => 'Error de Registro',
                            'mensaje' => $e->getMessage() ?: 'Fallo al Crear la Solicitud.'
                        ];
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    }
                }
            }

        $this->viewRegistrarSalida();
    }

    public function generarFolioBaja(): string {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT MAX(id) AS ultimo_id FROM solicitudes_bajas";
        $stmt = $db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $siguienteNumero = ($row && $row['ultimo_id']) ? ((int)$row['ultimo_id'] + 1) : 1;

        $numeroFormateado = str_pad($siguienteNumero, 5, '0', STR_PAD_LEFT);
        $anioActual = date('Y') ; 
        $mesActual = date('m');
        return "TAKAB-SB-{$anioActual}{$mesActual}-{$numeroFormateado}";
    }

    public function verArchivoSalida(){
        Session::requireLogin(['Administrador', 'Almacen']);
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            die('Solicitud no encontrada.');
        }

        $solicitud = SolicitudMaterial::obtenerBajaConDetalles($id);
        if (! $solicitud) {
            die('Solicitud no encontrada.');
        }

        include __DIR__ . '/../templates/baja_material.php';
    }

    public function aprobarSolicitudBaja(){
        Session::requireLogin(['Administrador', 'Almacen']);
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            die('Solicitud No Encontrada.');
        }

        try {
            $db = Database::getInstance()->getConnection();
            // Esta comprobación puede ejecutar DDL. Debe ocurrir antes de abrir
            // la transacción para evitar un commit implícito en MySQL/MariaDB.
            Producto::ensureStockTableReady();
            $db->beginTransaction();

            $stmtSolicitud = $db->prepare("SELECT folio, almacen_id, estatus FROM solicitudes_bajas WHERE id = ? FOR UPDATE");
            $stmtSolicitud->execute([$id]);
            $solicitud = $stmtSolicitud->fetch(PDO::FETCH_ASSOC);

            if (!$solicitud) {
                throw new RuntimeException('La Solicitud de Baja Especificada no Existe.');
            }
            if (($solicitud['estatus'] ?? '') !== 'Pendiente') {
                throw new RuntimeException('La Solicitud de Baja ya Fue Procesada.');
            }
            $folioSolicitud = $solicitud['folio'];

            $almacen_id = $solicitud['almacen_id'];
            if ($almacen_id <= 0) {
                throw new RuntimeException('Debes Seleccionar un Almacén Válido.');
            }
                
                $stmtItems = $db->prepare("SELECT * FROM solicitudes_bajas_detalles WHERE solicitud_id = ?");
                $stmtItems->execute([$id]);
                $salidaItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

                foreach ($salidaItems as $indice => $linea) {
                    $productoId = (int) ($linea['producto_id'] ?? 0);
                    $cantidad   = isset($linea['cantidad']) ? (float) $linea['cantidad'] : 0;

                    if ($productoId <= 0 || $cantidad <= 0) {
                        throw new RuntimeException('La Línea ' . ($indice + 1) . ' Es Invalida.');
                    }

                    if (! Producto::restarStock($productoId, $cantidad, $almacen_id)) {
                        throw new RuntimeException('Stock Insuficiente o Error al Actualizar el Stock del Producto en la Línea ' . ($indice + 1) . '.');
                    }

                    $data = [
                        'producto_id'        => $productoId,
                        'tipo'               => 'Salida',
                        'cantidad'           => $cantidad,
                        'responsable_id'     => $_SESSION['user_id'] ?? 0,
                        'almacen_id'        =>  $almacen_id,
                        'observaciones'   => $linea['motivo'] ?? $linea['motivos'] ?? '', 
                        'folio_solicitud' => $folioSolicitud
                    ];

                    if (! MovimientoInventario::registrar($data)) {
                        throw new RuntimeException('No Fue Posible Registrar La Línea ' . ($indice + 1) . '.');
                    }
                }

                $stmt = $db->prepare("UPDATE solicitudes_bajas SET estatus = 'Aprobada' WHERE id = ? AND estatus = 'Pendiente'");
                $stmt->execute([$id]);
                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException('La Solicitud de Baja ya Fue Procesada.');
                }

                $db->commit();

                ActivityLogger::registrarCambioEstado(
                    'almacen',
                    'solicitud_baja',
                    $id,
                    'Aprobada',
                    'Solicitud de Baja Aprobada y Stock Actualizado',
                    [
                        'folio' => $folioSolicitud,
                        'almacen_id' => (int) $almacen_id,
                        'lineas' => count($salidaItems),
                    ]
                );

                $_SESSION['alerta'] = [
                    'tipo' => 'success',
                    'mensaje' => 'Solicitud de Baja Aprobada Exitosamente. El Stock Ha Sido Actualizado',
                    'titulo' => 'Solicitud Aprobada'
                ];
                header("Location: registrar_salida");
                exit;
            } catch (\Throwable $e) {
                try {
                    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
                        $db->rollBack();
                    }
                } catch (\Throwable $rollbackEx) {}
                $_SESSION['alerta'] = [
                    'tipo' => 'error',
                    'mensaje' => $e->getMessage() ?: 'No fue Posible Registrar la Solicitud. Revisa los datos.',
                    'titulo' => 'Error de Registro',
                    //'mensaje' => 'No fue posible registrar la entrada. Revisa los datos.'
                ];
                header("Location: registrar_salida");
                exit;
            }

        $this->viewRegistrarSalida();
    }

    public function rechazarSolicitudBaja(){
        Session::requireLogin(['Administrador', 'Almacen']);
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            die('Solicitud No Encontrada.');
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE solicitudes_bajas 
            SET estatus = 'Rechazada' 
            WHERE id = ? AND estatus = 'Pendiente'");

        $stmt->execute([$id]);

        if ($stmt->rowCount() === 1) {
            ActivityLogger::registrarCambioEstado(
                'almacen',
                'solicitud_baja',
                $id,
                'Rechazada',
                'Solicitud de Baja Rechazada'
            );
            $_SESSION['alerta'] = [
                'tipo' => 'success',
                'titulo' => 'Solicitud Rechazada',
                'mensaje' => 'Solicitud de Baja Rechazada Exitosamente.'
            ];
        } else {
            $_SESSION['alerta'] = [
                'tipo' => 'error',
                'titulo' => 'Solicitud no Disponible',
                'mensaje' => 'La Solicitud de Baja no Existe o ya Fue Procesada.'
            ];
        }

        header('Location: registrar_salida');
        exit();
    }


    private function datosAlmacen($db): array{
        $datos = $this->datosGenerales($db);

        $productosAlmacen        = (int) $db->query('SELECT COUNT(*) FROM inventario')->fetchColumn();
        $solicitudesPorGestionar = (int) $db->query("SELECT COUNT(*) FROM solicitudes_material WHERE estatus IN ('Pendiente','Aprobada')")->fetchColumn();
        
        $datos['productosAlmacen']   = $productosAlmacen;
        $datos['solicitudesAlmacen'] = $solicitudesPorGestionar;
        $datos['ultimosMovimientos'] = $this->expuestosMovimientos($db);

        return $datos;
    }

     private function datosGenerales($db): array{
        $totalProductos        = (int) $db->query('SELECT COUNT(*) FROM inventario')->fetchColumn();
        $stockBajo             = (int) $db->query('SELECT COUNT(*) FROM inventario p LEFT JOIN (SELECT producto_id, SUM(stock) AS stock_actual FROM stock_almacen GROUP BY producto_id) si ON si.producto_id = p.id WHERE COALESCE(si.stock_actual, 0) < p.stock_minimo')->fetchColumn();
        $valorTotal            = (float) $db->query('SELECT SUM(COALESCE(si.stock_actual, 0) * p.precio_iva) FROM inventario p LEFT JOIN (SELECT producto_id, SUM(stock) AS stock_actual FROM stock_almacen GROUP BY producto_id) si ON si.producto_id = p.id')->fetchColumn();
        $prestamosVencidos     = (int) $db->query("SELECT COUNT(*) FROM solicitudes_herramienta WHERE estatus = 'Activa' AND fecha_fin IS NOT NULL AND fecha_devolucion < NOW()")->fetchColumn();

        return [
            'totalProductos'        => $totalProductos,
            'stockBajo'             => $stockBajo,
            'valorTotalInventario'  => $valorTotal,
            'alertas'               => array_merge($this->alertasInventario($db)),
            'prestamosVencidos'     => $prestamosVencidos,
        ];
    }

    private function alertasInventario($db): array
    {
        $stmt = $db->query("SELECT p.nombre, COALESCE(si.stock_actual, 0) AS stock_actual, p.stock_minimo, DATE_FORMAT(p.created_at, '%d/%m/%Y') AS fecha
                             FROM inventario p
                             LEFT JOIN (SELECT producto_id, SUM(stock) AS stock_actual FROM stock_almacen GROUP BY producto_id) si ON si.producto_id = p.id
                             WHERE COALESCE(si.stock_actual, 0) < p.stock_minimo
                             ORDER BY COALESCE(si.stock_actual, 0) ASC
                             LIMIT 5");
        $productos = $stmt->fetchAll();

        $alertas = [];
        foreach ($productos as $p) {
            $alertas[] = [
                $p['nombre'] . ' por Debajo del Stock Mínimo',
                $p['fecha'],
                'Alta',
            ];
        }
        return $alertas;
    }

    private function expuestosMovimientos($db): array{
        $stmt = $db->query("SELECT p.nombre,
                                    p.nomenclatura,
                                    m.tipo,
                                    m.cantidad,
                                    m.created_at,
                                    a.nombre
                             FROM movimientos_inventario m
                             LEFT JOIN inventario p ON m.producto_id = p.id
                             LEFT JOIN almacenes a ON m.almacen_id = a.id
                             ORDER BY m.created_at DESC
                             LIMIT 8");
        return $stmt->fetchAll();
    }

    // Listar prestamos pendientes de devolucion
    public function pendientes()
    {
        Session::requireLogin(['Administrador', 'Almacen']);
        $prestamos = Prestamo::pendientes();
        $limite = 8;
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $totalRegistros = count($prestamos);
        $total_paginas = max(1, (int) ceil($totalRegistros / $limite));
        $pagina = min($pagina, $total_paginas);
        $prestamos = array_slice($prestamos, ($pagina - 1) * $limite, $limite);
        $pagination = [
            'pagina' => $pagina,
            'total_paginas' => $total_paginas,
            'por_pagina' => $limite,
            'total' => $totalRegistros,
            'desde' => $totalRegistros > 0 ? (($pagina - 1) * $limite) + 1 : 0,
            'hasta' => min($pagina * $limite, $totalRegistros),
        ];
        include __DIR__ . '/../views/almacen/prestamos_herramientas.php';
    }

    public function crearEtiquetas($id)
    {
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);
        $producto = Producto::find($id);
        if (! $producto) {
            die('Producto no encontrado.');
        }

        $db        = Database::getInstance()->getConnection();
        $almacenes = $db->query('SELECT id, nombre FROM almacenes ORDER BY nombre ASC')->fetchAll();

        if (empty($producto['codigo_barras'])) {
            $nuevoCodigo = $this->generarCodigoBarras($producto['codigo'] ?? '', (int) $id);
            Producto::actualizarCodigoBarras((int) $id, $nuevoCodigo);
            $producto['codigo_barras'] = $nuevoCodigo;
        }

        $unidadSugerida = $producto['unidad_abreviacion'] ?? $producto['unidad_medida_nombre'] ?? '';
        $error          = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                $error = 'Token CSRF invalido.';
            } else {
                $lote           = trim($_POST['lote'] ?? '');
                $almacenId      = (int) ($_POST['almacen_id'] ?? 0);
                $cantidad       = max(1, min(50, (int) ($_POST['cantidad'] ?? 1)));
                $unidadEtiqueta = trim($_POST['unidad_etiqueta'] ?? $unidadSugerida);

                $almacenNombre = $producto['almacen'] ?? '';
                foreach ($almacenes as $almacen) {
                    if ((int) $almacen['id'] === $almacenId) {
                        $almacenNombre = $almacen['nombre'];
                        break;
                    }
                }

                $labels = [];
                for ($i = 0; $i < $cantidad; $i++) {
                    $labels[] = [
                        'nombre'        => $producto['nombre'],
                        'codigo'        => $producto['codigo'],
                        'codigo_barras' => $producto['codigo_barras'],
                        'almacen'       => $almacenNombre !== '' ? $almacenNombre : 'N/D',
                        'lote'          => $lote !== '' ? $lote : 'N/D',
                        'unidad'        => $unidadEtiqueta !== '' ? $unidadEtiqueta : 'N/D',
                    ];
                }

                try {
                    $pdf = $this->buildEtiquetasPdf($labels);
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: inline; filename=etiquetas_producto_' . preg_replace('/[^A-Za-z0-9_-]/', '', $producto['codigo'] ?? 'producto') . '.pdf');
                    echo $pdf;
                    return;
                } catch (\Throwable $e) {
                    $error = 'No fue posible generar el PDF de etiquetas.';
                }
            }
        }

        include __DIR__ . '/../views/almacen/etiquetas.php';
    }

    private function buildEtiquetasPdf(array $labels): string
    {
        $pageWidth            = 226.0;
        $pageHeight           = 170.0;
        $objects              = [];
        $objects[1]           = '<< /Type /Catalog /Pages 2 0 R >>';
        $fontObjNum           = 3;
        $objects[$fontObjNum] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $pageRefs             = [];

        if (empty($labels)) {
            $labels[] = [
                'nombre'        => 'Etiqueta',
                'codigo'        => '',
                'codigo_barras' => '',
                'almacen'       => '',
                'lote'          => '',
                'unidad'        => '',
            ];
        }

        foreach ($labels as $label) {
            $content                 = $this->renderEtiquetaContent($label, $pageWidth, $pageHeight);
            $contentObjNum           = count($objects) + 1;
            $objects[$contentObjNum] = $this->wrapStream($content);
            $pageObjNum              = $contentObjNum + 1;
            $objects[$pageObjNum]    = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 %d 0 R >> >> /Contents %d 0 R >>', $pageWidth, $pageHeight, $fontObjNum, $contentObjNum);
            $pageRefs[]              = $pageObjNum . ' 0 R';
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageRefs) . '] /Count ' . count($pageRefs) . ' >>';

        $pdf = "%PDF-1.4
";
        $offsets     = [];
        $objectCount = count($objects);

        for ($i = 1; $i <= $objectCount; $i++) {
            $offsets[$i] = strlen($pdf);
            $pdf .= $i . " 0 obj
" . $objects[$i] . "
endobj
";
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref
0 " . ($objectCount + 1) . "
";
        $pdf .= "0000000000 65535 f
";
        for ($i = 1; $i <= $objectCount; $i++) {
            $pdf .= sprintf("%010d 00000 n
", $offsets[$i]);
        }
        $pdf .= "trailer << /Size " . ($objectCount + 1) . " /Root 1 0 R >>
";
        $pdf .= "startxref
" . $xrefPosition . "
%%EOF";

        return $pdf;
    }

    private function normalizarLineasEntrada(array $post): array
{
    $lineas = [];
    $almacenGeneral = trim((string) ($post['almacen_entrada_id'] ?? $post['almacen_id'] ?? ''));
    $folioGeneral   = trim((string) ($post['folio'] ?? ''));

    if (! empty($post['lineas_producto_id']) && is_array($post['lineas_producto_id'])) {
        $productos     = $post['lineas_producto_id'];
        $almacenes     = $post['lineas_almacen_id'] ?? [];
        $cantidades    = $post['lineas_cantidad'] ?? [];
        $observaciones = $post['lineas_observaciones'] ?? [];
        $folios        = $post['lineas_folio_solicitud'] ?? [];   

        foreach ($productos as $indice => $productoId) {
            $almacenIdItem = trim((string) ($almacenes[$indice] ?? ''));
            if ($almacenIdItem === '') {
                $almacenIdItem = $almacenGeneral;
            }

            $folioItem = trim((string) ($folios[$indice] ?? ''));
            if ($folioItem === '') {
                $folioItem = $folioGeneral;
            }

            $linea = [
                'producto_id'   => trim((string) $productoId),
                'almacen_id'    => $almacenIdItem,
                'cantidad'      => trim((string) ($cantidades[$indice] ?? '')),
                'observaciones' => trim((string) ($observaciones[$indice] ?? '')),
                'folio'         => $folioItem
            ];

            if ($linea['producto_id'] === '' && $linea['almacen_id'] === '' && $linea['cantidad'] === '') {
                continue;
            }

            $lineas[] = $linea;
        }
    } else {
        $linea = [
            'producto_id'   => trim((string) ($post['producto_id'] ?? '')),
            'almacen_id'    => $almacenGeneral,
            'cantidad'      => trim((string) ($post['cantidad'] ?? '')),
            'observaciones' => trim((string) ($post['observaciones'] ?? '')),
            'folio'         => $folioGeneral
        ];

        if ($linea['producto_id'] !== '' && $linea['cantidad'] !== '') {
            $lineas[] = $linea;
        }
    }

    return $lineas;
}

    private function renderEtiquetaContent(array $label, float $pageWidth, float $pageHeight): string
    {
        $nombre       = $label['nombre'] ?? '';
        $codigo       = $label['codigo'] ?? '';
        $codigoBarras = $label['codigo_barras'] ?? '';
        $almacen      = $label['almacen'] ?? '';
        $lote         = $label['lote'] ?? '';
        $unidad       = $label['unidad'] ?? '';

        $lines   = [];
        $lines[] = 'BT';
        $lines[] = '/F1 12 Tf';
        $lines[] = sprintf('1 0 0 1 %.2f %.2f Tm (%s) Tj', 20.0, $pageHeight - 30.0, $this->escapePdfText($nombre));
        $lines[] = sprintf('1 0 0 1 %.2f %.2f Tm (Codigo: %s) Tj', 20.0, $pageHeight - 48.0, $this->escapePdfText($codigo));
        $lines[] = sprintf('1 0 0 1 %.2f %.2f Tm (Lote: %s) Tj', 20.0, $pageHeight - 66.0, $this->escapePdfText($lote));
        $lines[] = sprintf('1 0 0 1 %.2f %.2f Tm (Almacen: %s) Tj', 20.0, $pageHeight - 84.0, $this->escapePdfText($almacen));
        $lines[] = sprintf('1 0 0 1 %.2f %.2f Tm (Unidad: %s) Tj', 20.0, $pageHeight - 102.0, $this->escapePdfText($unidad));
        $lines[] = 'ET';

        try {
            $pattern = BarcodeGenerator::code39Pattern($codigoBarras);
        } catch (\Throwable $e) {
            $pattern = [];
        }

        if (! empty($pattern)) {
            $lines[] = '0 0 0 rg';
            $lines[] = rtrim($this->barcodeRectangles($pattern, 20.0, 48.0, 1.2, 38.0));
            $lines[] = 'BT';
            $lines[] = '/F1 10 Tf';
            $lines[] = sprintf('1 0 0 1 %.2f %.2f Tm (%s) Tj', 20.0, 42.0, $this->escapePdfText($codigoBarras));
            $lines[] = 'ET';
        } else {
            $lines[] = 'BT';
            $lines[] = '/F1 10 Tf';
            $lines[] = sprintf('1 0 0 1 %.2f %.2f Tm (Codigo barras: %s) Tj', 20.0, 42.0, $this->escapePdfText($codigoBarras !== '' ? $codigoBarras : 'N/D'));
            $lines[] = 'ET';
        }

        return implode("\n", $lines) . "\n";
    }

    private function barcodeRectangles(array $pattern, float $x, float $y, float $moduleWidth, float $height): string
    {
        $cursor   = $x;
        $segments = '';
        foreach ($pattern as $segment) {
            [$type, $units] = $segment;
            $width          = $units * $moduleWidth;
            if ($type === 'bar') {
                $segments .= sprintf('%.2f %.2f %.2f %.2f re f\n', $cursor, $y, $width, $height);
            }
            $cursor += $width;
        }
        return $segments;
    }

    private function wrapStream(string $content): string
    {
        $length = strlen($content);
        return "<< /Length {$length} >>\nstream\n{$content}endstream";
    }

    private function escapePdfText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('(', '\(', $text);
        $text = str_replace(')', '\)', $text);
        return $text;
    }
}
