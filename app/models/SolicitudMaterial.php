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

    public static function generarFolioSolicitud(): string {
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
                            d.observaciones,
                            0 AS fuera_catalogo,
                            NULL AS marca,
                            NULL AS dimensiones,
                            COALESCE(si.stock_actual, 0) AS stock_actual
                        FROM solicitudes_material_detalles d
                        INNER JOIN inventario p ON d.producto_id = p.id
                        LEFT JOIN catalogo_unidades_medida um ON p.unidad_medida_id = um.id
                        LEFT JOIN (
                            SELECT producto_id, SUM(stock) AS stock_actual
                            FROM stock_almacen
                            GROUP BY producto_id
                        ) si ON si.producto_id = p.id
                        WHERE d.solicitud_id = ?
                        UNION ALL
                        SELECT
                            NULL AS nomenclatura,
                            nr.nombre,
                            'Fuera del Catálogo' AS tipo,
                            nr.unidad_medida,
                            nr.cantidad,
                            nr.observaciones,
                            1 AS fuera_catalogo,
                            nr.marca,
                            nr.dimensiones,
                            NULL AS stock_actual
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
}
