<?php
require_once __DIR__ . '/../helpers/Database.php';

class SolicitudMaterial {
    public static function crearSolicitudCompleta(array $cabecera, array $detalles, array $noRegistrados): array
    {
        if (!$detalles && !$noRegistrados) {
            throw new InvalidArgumentException('La Solicitud Debe Contener al Menos un Material.');
        }

        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();

        try {
            $folio = self::generarFolioSolicitud();
            $stmtSolicitud = $db->prepare(
                'INSERT INTO solicitudes_material
                    (folio, solicitante_id, estatus, proyecto_id, fecha_solicitud, fecha_requerida, comentario_solicitante, activo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
            );
            $stmtSolicitud->execute([
                $folio,
                (int) $cabecera['solicitante_id'],
                'Pendiente',
                (int) $cabecera['proyecto_id'],
                date('Y-m-d'),
                $cabecera['fecha_requerida'],
                ($cabecera['comentario_solicitante'] ?? '') !== '' ? $cabecera['comentario_solicitante'] : null,
            ]);
            $solicitudId = (int) $db->lastInsertId();

            $stmtProducto = $db->prepare(
                "SELECT p.id, p.tipo, c.nombre AS categoria
                   FROM inventario p
                   LEFT JOIN catalogo_categorias_inventario c ON c.id = p.categoria_id
                  WHERE p.id = ? AND p.activo = '1'"
            );
            $stmtDetalle = $db->prepare(
                'INSERT INTO solicitudes_material_detalles
                    (solicitud_id, producto_id, categoria, cantidad, observaciones)
                 VALUES (?, ?, ?, ?, ?)'
            );

            foreach ($detalles as $indice => $detalle) {
                $productoId = (int) ($detalle['producto_id'] ?? 0);
                $stmtProducto->execute([$productoId]);
                $producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);
                if (!$producto) {
                    throw new RuntimeException('El Producto de la Línea ' . ($indice + 1) . ' no Existe o Está Inactivo.');
                }

                $categoria = trim((string) ($producto['categoria'] ?? $producto['tipo'] ?? 'Sin Categoría'));
                $stmtDetalle->execute([
                    $solicitudId,
                    $productoId,
                    mb_substr($categoria !== '' ? $categoria : 'Sin Categoría', 0, 50),
                    (float) $detalle['cantidad'],
                    ($detalle['observaciones'] ?? '') !== '' ? $detalle['observaciones'] : null,
                ]);
            }

            $stmtNoRegistrado = $db->prepare(
                'INSERT INTO solicitudes_material_noregistrados
                    (solicitud_id, nombre, marca, dimensiones, unidad_medida, cantidad, observaciones)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($noRegistrados as $material) {
                $stmtNoRegistrado->execute([
                    $solicitudId,
                    $material['nombre'],
                    ($material['marca'] ?? '') !== '' ? $material['marca'] : null,
                    ($material['dimensiones'] ?? '') !== '' ? $material['dimensiones'] : null,
                    $material['unidad_medida'],
                    (float) $material['cantidad'],
                    ($material['observaciones'] ?? '') !== '' ? $material['observaciones'] : null,
                ]);
            }

            $db->commit();
            return ['id' => $solicitudId, 'folio' => $folio];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function generarFolioSolicitud(): string {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT MAX(id) AS ultimo_id FROM solicitudes_material";
        $stmt = $db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $siguienteNumero = ($row && $row['ultimo_id']) ? ((int)$row['ultimo_id'] + 1) : 1;

        $numeroFormateado = str_pad($siguienteNumero, 5, '0', STR_PAD_LEFT);
        $anioActual = date('Y') ; 
        $mesActual = date('m');
        return "TAKAB-SM-{$anioActual}{$mesActual}-{$numeroFormateado}";
    }

    // Crear solicitud con múltiples productos y extras
    public static function create($data, $detalles) {
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        $sql = "INSERT INTO solicitudes_material (
            usuario_id, tipo, tipo_solicitud, comentario, observacion, extras, estado, fecha_solicitud
        ) VALUES (?, ?, ?, ?, ?, ?, 'pendiente', NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['usuario_id'],
            $data['tipo'],
            $data['tipo_solicitud'],
            $data['comentario'],
            $data['observacion'],
            isset($data['extras']) && !empty($data['extras']) ? json_encode($data['extras']) : null
        ]);
        $solicitud_id = $db->lastInsertId();

        $updateProducto = $db->prepare("UPDATE productos SET last_requested_by_user_id = ?, last_request_date = NOW() WHERE id = ?");
        foreach ($detalles as $detalle) {
            $sql_det = "INSERT INTO detalle_solicitud (solicitud_id, producto_id, cantidad, observacion) VALUES (?, ?, ?, ?)";
            $stmt_det = $db->prepare($sql_det);
            $stmt_det->execute([
                $solicitud_id,
                $detalle['producto_id'],
                $detalle['cantidad'],
                isset($detalle['observacion']) ? $detalle['observacion'] : null
            ]);
            if (!empty($detalle['producto_id'])) {
                $updateProducto->execute([
                    $data['usuario_id'],
                    $detalle['producto_id'],
                ]);
            }
        }
        $db->commit();
        return $solicitud_id;
    }

    public static function historialPorUsuario($usuario_id, $filtros = []) {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT s.*, 
            (SELECT COUNT(*) FROM detalle_solicitud d WHERE d.solicitud_id = s.id) as total_productos
            FROM solicitudes_material s
            WHERE s.usuario_id = ?";
            
        $params = [$usuario_id];
        
        if (!empty($filtros['search'])) {
            $sql .= " AND (s.comentario LIKE ? OR s.observacion LIKE ? OR s.tipo_solicitud LIKE ?)";
            $search = '%' . $filtros['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        if (!empty($filtros['fecha_inicio'])) {
            $sql .= " AND DATE(s.fecha_solicitud) >= ?";
            $params[] = $filtros['fecha_inicio'];
        }
        if (!empty($filtros['fecha_fin'])) {
            $sql .= " AND DATE(s.fecha_solicitud) <= ?";
            $params[] = $filtros['fecha_fin'];
        }
        if (!empty($filtros['estado'])) {
            $sql .= " AND s.estado = ?";
            $params[] = $filtros['estado'];
        }

        $sql .= " ORDER BY s.fecha_solicitud DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    //Obtiene las solicitudes de baja de materiales para listar
    public static function obtenerSalidasHistorial(int $pagina = 1, int $limite = 5) {
        $db = Database::getInstance()->getConnection();

        $limite = min(max(1, $limite), 5);
        $pagina = max(1, $pagina);
        $offset = ($pagina - 1) * $limite;

        $sql = "SELECT s.*, u.nombre AS solicitante
                FROM solicitudes_bajas s
                LEFT JOIN usuarios u ON s.solicitante_id = u.id
                WHERE s.estatus IN ('Rechazada', 'Aprobada')
                ORDER BY s.created_at DESC, s.id DESC
                LIMIT :limite OFFSET :offset";

        $stmt = $db->prepare($sql);
        
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //Obtiene las solicitudes de baja de materiales con estatus pendiente para listar
    public static function obtenerSalidasPendientes(int $pagina = 1, int $limite = 5) {
        $db = Database::getInstance()->getConnection();

        $limite = min(max(1, $limite), 5);
        $pagina = max(1, $pagina);
        $offset = ($pagina - 1) * $limite;


        $sql = "SELECT s.*, u.nombre AS solicitante
                FROM solicitudes_bajas s
                LEFT JOIN usuarios u ON s.solicitante_id = u.id
                WHERE s.estatus = 'Pendiente'
                ORDER BY s.created_at DESC, s.id DESC
                LIMIT :limite OFFSET :offset";

        $stmt = $db->prepare($sql);

        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function contarBajasHistorial(): int{
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT COUNT(*) 
                FROM solicitudes_bajas 
                WHERE estatus IN ('Rechazada', 'Aprobada')";

        $stmt = $db->query($sql);

        return (int) $stmt->fetchColumn();
    }

    public static function contarBajasPendientes(): int{
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT COUNT(*) 
                FROM solicitudes_bajas 
                WHERE estatus = 'Pendiente'";

        $stmt = $db->query($sql);

        return (int) $stmt->fetchColumn();
    }

    //Obtiene la solicitud de material con sus detalles (productos) para mostrar la lista de materiales
    public static function obtenerSolicitudConDetalles($solicitudId) {
        $db = Database::getInstance()->getConnection();

        $sqlCabecera = "SELECT 
                            s.id, 
                            s.folio,
                            s.solicitante_id,
                            s.fecha_solicitud, 
                            s.fecha_requerida AS fecha_entrega,
                            s.fecha_entregado, 
                            s.comentario_solicitante AS comentarios,
                            u.nombre AS solicitante,
                            pr.nombre AS proyecto
                        FROM solicitudes_material s
                        LEFT JOIN usuarios u ON s.solicitante_id = u.id
                        LEFT JOIN proyectos pr ON s.proyecto_id = pr.id
                        WHERE s.id = ?";
        
        $stmt = $db->prepare($sqlCabecera);
        $stmt->execute([$solicitudId]);
        $solicitud = $stmt->fetch();

        if (!$solicitud) {
            return null;
        }

        $sqlDetalles = "SELECT 
                            p.nomenclatura,
                            p.nombre,
                            p.tipo, -- 'Herramienta', 'Consumible', 'Equipo'
                            um.apodo AS unidad_medida,
                            d.cantidad,
                            d.observaciones
                        FROM solicitudes_material_detalles d
                        INNER JOIN inventario p ON d.producto_id = p.id
                        LEFT JOIN catalogo_unidades_medida um ON p.unidad_medida_id = um.id
                        WHERE d.solicitud_id = ?
                        UNION ALL
                        SELECT
                            NULL AS nomenclatura,
                            CONCAT(
                                nr.nombre,
                                IF(nr.marca IS NULL OR nr.marca = '', '', CONCAT(' · ', nr.marca)),
                                IF(nr.dimensiones IS NULL OR nr.dimensiones = '', '', CONCAT(' · ', nr.dimensiones))
                            ) AS nombre,
                            'Materiales' AS tipo,
                            nr.unidad_medida,
                            nr.cantidad,
                            nr.observaciones
                        FROM solicitudes_material_noregistrados nr
                        WHERE nr.solicitud_id = ?
                        ORDER BY tipo ASC, nombre ASC";

        $stmtDetalles = $db->prepare($sqlDetalles);
        $stmtDetalles->execute([$solicitudId, $solicitudId]);
        $solicitud['items'] = $stmtDetalles->fetchAll();

        return $solicitud;
    }

    public static function obtenerBajaConDetalles($solicitudId) {
        $db = Database::getInstance()->getConnection();

        $sqlCabecera = "SELECT 
                            s.id, 
                            s.folio,
                            s.solicitante_id,
                            s.created_at AS fecha, 
                            u.nombre AS solicitante
                        FROM solicitudes_bajas s
                        LEFT JOIN usuarios u ON s.solicitante_id = u.id
                        WHERE s.id = ?";
        
        $stmt = $db->prepare($sqlCabecera);
        $stmt->execute([$solicitudId]);
        $solicitud = $stmt->fetch();

        if (!$solicitud) {
            return null;
        }

        $sqlDetalles = "SELECT 
                            p.nomenclatura,
                            p.nombre,
                            p.tipo, -- 'Herramienta', 'Consumible', 'Equipo'
                            um.apodo AS unidad_medida,
                            d.cantidad,
                            d.motivos AS notas
                        FROM solicitudes_bajas_detalles d
                        INNER JOIN inventario p ON d.producto_id = p.id
                        LEFT JOIN catalogo_unidades_medida um ON p.unidad_medida_id = um.id
                        WHERE d.solicitud_id = ?
                        ORDER BY p.tipo ASC, p.nombre ASC";

        $stmtDetalles = $db->prepare($sqlDetalles);
        $stmtDetalles->execute([$solicitudId]);
        $solicitud['items'] = $stmtDetalles->fetchAll();

        return $solicitud;
    }


    public static function find($id, $usuario_id = null) {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM solicitudes_material WHERE id=?";
        $params = [$id];
        if ($usuario_id) {
            $sql .= " AND usuario_id=?";
            $params[] = $usuario_id;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public static function detalles($solicitud_id) {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT d.*, p.nombre AS producto
                FROM detalle_solicitud d
                LEFT JOIN productos p ON d.producto_id = p.id
                WHERE d.solicitud_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$solicitud_id]);
        return $stmt->fetchAll();
    }

    // Solicitudes pendientes por aprobar para admin/almacén (todas o filtradas por estado, fecha y buscador)
    public static function listarPendientes($estado = ['pendiente'], $fecha_inicio = null, $fecha_fin = null, $search = null) {
        $db = Database::getInstance()->getConnection();
        $placeholders = implode(',', array_fill(0, count($estado), '?'));
        $sql = "SELECT s.*, u.nombre_completo AS usuario 
                FROM solicitudes_material s 
                LEFT JOIN usuarios u ON s.usuario_id = u.id
                WHERE s.estado IN ($placeholders)";
        $params = $estado;

        if ($fecha_inicio) {
            $sql .= " AND DATE(s.fecha_solicitud) >= ?";
            $params[] = $fecha_inicio;
        }
        if ($fecha_fin) {
            $sql .= " AND DATE(s.fecha_solicitud) <= ?";
            $params[] = $fecha_fin;
        }
        if ($search) {
            $sql .= " AND (s.comentario LIKE ? OR s.observacion LIKE ? OR s.tipo_solicitud LIKE ? OR u.nombre_completo LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql .= " ORDER BY s.fecha_solicitud DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(); //un array con todas las solicitudes pendientes
    }

    public function crearEntrada(){
        Session::requireLogin(['Administrador', 'Almacen', 'Compras']);

        $productos     = Producto::all();
        $almacenes     = Almacen::all();
        $msg           = '';
        $error         = '';
        $entradaItems  = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $entradaItems = $this->normalizarLineasEntrada($_POST);
            if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
               $error = 'Token CSRF invalido.';
            } elseif (empty($entradaItems)) {
                $error = 'Agrega al menos un producto a la captura de entrada.';
            } else {
                $db = Database::getInstance()->getConnection();

                try {
                    $db->beginTransaction();
    
                    foreach ($entradaItems as $indice => $linea) {
                        $productoId = (int) ($linea['producto_id'] ?? 0);
                        $almacenId  = (int) ($linea['almacen_id'] ?? 0);
                        $cantidad   = isset($linea['cantidad']) ? (float) $linea['cantidad'] : 0;
                        if ($productoId <= 0 || $almacenId <= 0 || $cantidad <= 0) {
                            throw new RuntimeException('La linea ' . ($indice + 1) . ' es invalida.');
                        }

                        $data = [
                            'producto_id'        => $productoId,
                            'tipo'               => 'Entrada',
                            'cantidad'           => $cantidad,
                            'usuario_id'         => $_SESSION['user_id'],
                            'almacen_destino_id' => $almacenId,
                            'observaciones'      => trim((string) ($linea['observaciones'] ?? '')),
                        ];

                        if (! MovimientoInventario::registrar($data)) {
                            throw new RuntimeException('No fue posible registrar la linea ' . ($indice + 1) . '.');
                        }

                        Producto::sumarStock($productoId, $cantidad, $almacenId);
                        ActivityLogger::log('inventario_entrada', 'Entrada de inventario registrada', [
                            'producto_id' => $productoId,
                            'almacen_id'  => $almacenId,
                            'cantidad'    => $cantidad,
                            'linea'       => $indice + 1,
                        ]);
                    }

                    $db->commit();

                    $totalLineas = count($entradaItems);
                        $msg = $totalLineas === 1
                            ? 'Entrada registrada correctamente.'
                            : 'Se registraron ' . $totalLineas . ' productos en la entrada correctamente.';

                        $entradaItems = [];
                        $_POST = [];
                    } catch (\Throwable $e) {
                        if ($db->inTransaction()) {
                            $db->rollBack();
                        }
                        $error = 'No fue posible registrar la entrada. Revisa los datos e intenta nuevamente.';
                    }
                }
            }

            $movimientosRecientes = MovimientoInventario::ultimos('Entrada', 6);

            include __DIR__ . '/../views/inventario/main/entrada.php';
        }

    // Cambia estado (aprobada, rechazada, entregada, cancelada), guarda usuario y observación de respuesta
    public static function actualizarEstado($id, $nuevoEstado, $usuarioId, $observacion = null) {
        $db = Database::getInstance()->getConnection();
        $col = '';
        switch ($nuevoEstado) {
            case 'aprobada':
            case 'rechazada':
                $col = 'usuario_aprueba_id';
                break;
            case 'entregada':
                $col = 'usuario_entrega_id';
                break;
            default:
                $col = null;
        }
        $sql = "UPDATE solicitudes_material SET estado=?, " . ($col ? "$col=?," : "") . " fecha_respuesta=NOW(), observaciones_respuesta=? WHERE id=?";
        $params = $col ? [$nuevoEstado, $usuarioId, $observacion, $id] : [$nuevoEstado, $observacion, $id];
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
}
