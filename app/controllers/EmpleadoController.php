<?php
require_once __DIR__ . '/../models/Almacen.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Proyecto.php';
require_once __DIR__ . '/../models/SolicitudMaterial.php';


class EmpleadoController{

    public function obtenerDashboardEmpleado(): void{
        Session::requireLogin(['Administrador', 'Empleado']);

        $role   = $_SESSION['role'] ?? '';
        $nombre = $_SESSION['nombre'] ?? '';
        $userId = (int) ($_SESSION['user_id'] ?? 0);

       $_SESSION['menu_items'] = [
            ['slug' => 'crear_solicitud', 'label' => 'Solicitar Material', 'icon' => 'fa-solid fa-file-signature', 'role' => 'Todos'],
            ['slug' => 'mis_solicitudes', 'label' => 'Mis Solicitudes', 'icon' => 'fa-solid fa-clipboard-list', 'role' => 'Todos'],
            ['slug' => 'logout', 'label' => 'Cerrar Sesión', 'icon' => 'fa-solid fa-arrow-right-from-bracket', 'role' => 'Todos']
        ];

        $db = Database::getInstance()->getConnection();

        $datos = [
            'nombre'      => $nombre,
            'role'        => $role,
            'last_update' => date('d/m/Y, h:i:s a')
        ];

        $datos = array_merge($datos, $this->datosEmpleado($db));

        include __DIR__ . '/../views/empleado/dashboard_empleado.php';
    }

    public function datosEmpleado($db): array {
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $solicitudesPendientes = Empleado::obtenerSolicitudesPendientes($userId);
        $solicitudesEnEntrega  = Empleado::obtenerSolicitudesEnEntrega($userId);
        $misSolicitudes        = Empleado::obtenerMisSolicitudes($userId);
        $ultimasSolicitudes    = Empleado::obtenerUltimasSolicitudes($userId, 5);

        $datos = [
            'numSolicitudesPendientes' => count($solicitudesPendientes),
            'numSolicitudesEnEntrega'  => count($solicitudesEnEntrega),
            'numMisSolicitudes'        => count($misSolicitudes),
            'ultimasSolicitudes'    => $ultimasSolicitudes
        ];

        return $datos;
    }

    public function misSolicitudes(): void {
        Session::requireLogin(['Administrador', 'Empleado']);

        $role   = $_SESSION['role'] ?? '';
        $nombre = $_SESSION['nombre'] ?? '';
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $solicitudesPendientes = Empleado::obtenerSolicitudesPendientes($userId);
        $solicitudesEnEntrega  = Empleado::obtenerSolicitudesEnEntrega($userId);
        $misSolicitudes        = Empleado::obtenerMisSolicitudes($userId);
        $numSolicitudesEsteMes = Empleado::obtenerSolicitudesEsteMes($userId);

        $datos = [
            'nombre' => $nombre,
            'role' => $role,
            'last_update' => date('d/m/Y, h:i:s a'),
            'numSolicitudesPendientes' => count($solicitudesPendientes),
            'numSolicitudesEnEntrega' => count($solicitudesEnEntrega),
            'numMisSolicitudes' => count($misSolicitudes),
            'numSolicitudesEsteMes' => $numSolicitudesEsteMes,
            'solicitudesPendientes' => $solicitudesPendientes,
            'solicitudesEnEntrega' => $solicitudesEnEntrega,
            'misSolicitudes' => $misSolicitudes
        ];

        include __DIR__ . '/../views/empleado/mis_solicitudes.php';
    }

    public function obtenerSolicitudesEmpleado(){
            Session::requireLogin(['Administrador', 'Empleado']);

            $db = Database::getInstance()->getConnection();
            $datos = [];
            $usuarioId = (int) ($_SESSION['user_id'] ?? 0);
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
                    np.nombre AS nombre_proyecto,
                    (SELECT COUNT(*) FROM solicitudes_material_detalles d WHERE d.solicitud_id = s.id)
                        + (SELECT COUNT(*) FROM solicitudes_material_noregistrados nr WHERE nr.solicitud_id = s.id) AS total_items,
                    COALESCE((SELECT SUM(d.cantidad) FROM solicitudes_material_detalles d WHERE d.solicitud_id = s.id), 0)
                        + COALESCE((SELECT SUM(nr.cantidad) FROM solicitudes_material_noregistrados nr WHERE nr.solicitud_id = s.id), 0) AS total_cantidad_materiales,
                    CONCAT_WS('<br>',
                        (SELECT GROUP_CONCAT(CONCAT(d.cantidad, 'x ', i.nombre) SEPARATOR '<br>') FROM solicitudes_material_detalles d INNER JOIN inventario i ON i.id = d.producto_id WHERE d.solicitud_id = s.id),
                        (SELECT GROUP_CONCAT(CONCAT(nr.cantidad, 'x ', nr.nombre, ' (Fuera del Catálogo)') SEPARATOR '<br>') FROM solicitudes_material_noregistrados nr WHERE nr.solicitud_id = s.id)
                    ) AS materiales_resumen
                FROM solicitudes_material s
                LEFT JOIN proyectos np ON s.proyecto_id = np.id
                WHERE s.estatus IN ('Rechazada', 'Entregada')
                AND s.solicitante_id = {$usuarioId}
                AND fecha_solicitud >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00')
                AND fecha_solicitud <= CONCAT(LAST_DAY(NOW()), ' 23:59:59')
                ORDER BY s.fecha_solicitud DESC
            ")->fetchAll();

            $numSolicitudesEsteMes = (int) $db->query("
                SELECT COUNT(*)
                FROM solicitudes_material
                WHERE estatus IN ('Rechazada', 'Entregada')
                AND solicitante_id = {$usuarioId}
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
                    np.nombre AS nombre_proyecto,
                    (SELECT COUNT(*) FROM solicitudes_material_detalles d WHERE d.solicitud_id = s.id)
                        + (SELECT COUNT(*) FROM solicitudes_material_noregistrados nr WHERE nr.solicitud_id = s.id) AS total_items,
                    COALESCE((SELECT SUM(d.cantidad) FROM solicitudes_material_detalles d WHERE d.solicitud_id = s.id), 0)
                        + COALESCE((SELECT SUM(nr.cantidad) FROM solicitudes_material_noregistrados nr WHERE nr.solicitud_id = s.id), 0) AS total_cantidad_materiales,
                    CONCAT_WS('<br>',
                        (SELECT GROUP_CONCAT(CONCAT(d.cantidad, 'x ', i.nombre) SEPARATOR '<br>') FROM solicitudes_material_detalles d INNER JOIN inventario i ON i.id = d.producto_id WHERE d.solicitud_id = s.id),
                        (SELECT GROUP_CONCAT(CONCAT(nr.cantidad, 'x ', nr.nombre, ' (Fuera del Catálogo)') SEPARATOR '<br>') FROM solicitudes_material_noregistrados nr WHERE nr.solicitud_id = s.id)
                    ) AS materiales_resumen
                FROM solicitudes_material s
                LEFT JOIN proyectos np ON s.proyecto_id = np.id
                WHERE s.estatus IN ('Pendiente','Aprobada')
                AND s.solicitante_id = {$usuarioId}
                ORDER BY s.fecha_solicitud DESC
            ")->fetchAll(PDO::FETCH_ASSOC);

            $numSolicitudesPendientes = (int) $db->query("
                SELECT COUNT(*)
                FROM solicitudes_material
                WHERE estatus IN ('Pendiente','Aprobada')
                AND solicitante_id = {$usuarioId}
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

            include __DIR__ . '/../views/empleado/mis_solicitudes.php';
    }

    public function cancelarSolicitudEmpleado(): void {
        Session::requireLogin(['Administrador', 'Empleado']);

        $solicitudId = (int) ($_POST['id'] ?? 0);
        $comentario = trim($_POST['comentario_solicitante'] ?? '');

        if ($solicitudId <= 0 || empty($comentario)) {
            $_SESSION['alerta'] = [
                    'tipo' => 'error',
                    'titulo' => 'Error al Cancelar Solicitud',
                    'mensaje' => 'Solicitud Inválida o Comentario Vacío.'
                ];
            header('Location: mis_solicitudes');
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE solicitudes_material SET estatus = 'Cancelada', comentario_solicitante = :comentario WHERE id = :id");
        $stmt->execute([':comentario' => $comentario, ':id' => $solicitudId]);

        $_SESSION['alerta'] = [
            'tipo' => 'success',
            'titulo' => 'Solicitud Cancelada',
            'mensaje' => 'La Solicitud ha Sido Cancelada Éxitosamente.'
        ];
        header('Location: mis_solicitudes');
        exit;
    }

    public function crearSolicitudMaterial(): void {
        Session::requireLogin(['Administrador', 'Empleado']);

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $nombre = $_SESSION['nombre'] ?? '';
        $role   = $_SESSION['role'] ?? '';
        $error  = '';
        $msg    = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errores = [];
            if (!Session::checkCsrf($_POST['csrf'] ?? '')) {
                $errores[] = 'La Sesión del Formulario Expiró. Recarga la Página e Intenta Nuevamente.';
            }

            $proyectoId = (int) ($_POST['proyecto_id'] ?? 0);
            if ($proyectoId <= 0) {
                $errores[] = 'Selecciona un Proyecto o Destino.';
            } else {
                $db = Database::getInstance()->getConnection();
                $stmtProyecto = $db->prepare('SELECT COUNT(*) FROM proyectos WHERE id = ?');
                $stmtProyecto->execute([$proyectoId]);
                if ((int) $stmtProyecto->fetchColumn() === 0) {
                    $errores[] = 'El Proyecto o Destino Seleccionado no Existe.';
                }
            }

            $fechaRequerida = trim((string) ($_POST['fecha_entrega'] ?? ''));
            $fechaValida = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRequerida) === 1;
            if ($fechaValida) {
                [$anio, $mes, $dia] = array_map('intval', explode('-', $fechaRequerida));
                $fechaValida = checkdate($mes, $dia, $anio);
            }
            if (!$fechaValida) {
                $errores[] = 'Captura una Fecha Requerida Válida.';
            } elseif ($fechaRequerida < date('Y-m-d')) {
                $errores[] = 'La Fecha Requerida no Puede ser Anterior a Hoy.';
            }

            $comentario = trim((string) ($_POST['comentario_general'] ?? $_POST['observacion'] ?? ''));
            if (mb_strlen($comentario) > 255) {
                $errores[] = 'El Motivo o las Indicaciones Generales no Pueden Superar 255 Caracteres.';
            }

            [$detalles, $noRegistrados] = $this->normalizarMaterialesSolicitud(
                (string) ($_POST['material'] ?? ''),
                $errores
            );

            if (!$errores) {
                try {
                    $solicitud = SolicitudMaterial::crearSolicitudCompleta([
                        'solicitante_id' => $userId,
                        'proyecto_id' => $proyectoId,
                        'fecha_requerida' => $fechaRequerida,
                        'comentario_solicitante' => $comentario,
                    ], $detalles, $noRegistrados);

                    $_SESSION['alerta'] = [
                        'tipo' => 'success',
                        'titulo' => 'Solicitud Registrada',
                        'mensaje' => 'La Solicitud ' . $solicitud['folio'] . ' se Registró Correctamente.',
                    ];
                    header('Location: ' . Session::url('crear_solicitud'));
                    exit;
                } catch (InvalidArgumentException | RuntimeException $e) {
                    $errores[] = $e->getMessage();
                } catch (Throwable $e) {
                    error_log('Error al crear solicitud de material: ' . $e->getMessage());
                    $errores[] = 'No Fue Posible Registrar la Solicitud. Revisa los Datos e Intenta Nuevamente.';
                }
            }

            $error = implode(' ', $errores);
        }

        $productos            = Producto::all();
        $almacenes            = Almacen::all();
        $proyectos            = Proyecto::all();

        $datos = [
            'nombre' => $nombre,
            'role' => $role,
            'last_update' => date('d/m/Y, h:i:s a')
        ];

        include __DIR__ . '/../views/empleado/crear_solicitud.php';
    }

    private function normalizarMaterialesSolicitud(string $materialJson, array &$errores): array
    {
        if ($materialJson === '') {
            $errores[] = 'Agrega al Menos un Material a la Solicitud.';
            return [[], []];
        }

        try {
            $materiales = json_decode($materialJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $errores[] = 'La Lista de Materiales no Tiene un Formato Válido.';
            return [[], []];
        }

        if (!is_array($materiales) || !$materiales) {
            $errores[] = 'Agrega al Menos un Material a la Solicitud.';
            return [[], []];
        }
        if (count($materiales) > 100) {
            $errores[] = 'Una Solicitud no Puede Contener Más de 100 Materiales.';
            return [[], []];
        }

        $detalles = [];
        $noRegistrados = [];
        $unidadesPermitidas = ['Pieza', 'Metro', 'Litro', 'Kilogramo'];

        foreach ($materiales as $indice => $material) {
            $numeroLinea = $indice + 1;
            if (!is_array($material)) {
                $errores[] = "El Material de la Línea {$numeroLinea} no es Válido.";
                continue;
            }

            $cantidad = filter_var($material['cantidad'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($cantidad === false || $cantidad <= 0 || $cantidad > 99999999.99) {
                $errores[] = "La Cantidad de la Línea {$numeroLinea} no es Válida.";
                continue;
            }

            $observaciones = trim((string) ($material['observacion'] ?? ''));
            if (mb_strlen($observaciones) > 255) {
                $errores[] = "Las Observaciones de la Línea {$numeroLinea} no Pueden Superar 255 Caracteres.";
                continue;
            }

            $productoId = (int) ($material['producto_id'] ?? 0);
            if ($productoId > 0) {
                $detalles[] = [
                    'producto_id' => $productoId,
                    'cantidad' => (float) $cantidad,
                    'observaciones' => $observaciones,
                ];
                continue;
            }

            $nombreMaterial = trim((string) ($material['producto_nombre'] ?? ''));
            $marca = trim((string) ($material['marca_modelo'] ?? ''));
            $dimensiones = trim((string) ($material['tamano'] ?? ''));
            $unidad = trim((string) ($material['unidad'] ?? ''));
            if ($nombreMaterial === '' || mb_strlen($nombreMaterial) > 100) {
                $errores[] = "El Nombre del Material no Registrado de la Línea {$numeroLinea} es Obligatorio y Debe Tener Máximo 100 Caracteres.";
                continue;
            }
            if (mb_strlen($marca) > 100 || mb_strlen($dimensiones) > 50) {
                $errores[] = "La Marca o las Dimensiones de la Línea {$numeroLinea} Superan la Longitud Permitida.";
                continue;
            }
            if (!in_array($unidad, $unidadesPermitidas, true)) {
                $errores[] = "La Unidad de Medida de la Línea {$numeroLinea} no es Válida.";
                continue;
            }

            $noRegistrados[] = [
                'nombre' => $nombreMaterial,
                'marca' => $marca,
                'dimensiones' => $dimensiones,
                'unidad_medida' => $unidad,
                'cantidad' => (float) $cantidad,
                'observaciones' => $observaciones,
            ];
        }

        if (!$detalles && !$noRegistrados && !$errores) {
            $errores[] = 'Agrega al Menos un Material Válido a la Solicitud.';
        }

        return [$detalles, $noRegistrados];
    }

}
