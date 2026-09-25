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
                WHERE s.estatus IN ('Rechazada', 'Aprobada', 'Auditoría')
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
                WHERE estatus IN ('Rechazada', 'Aprobada', 'Auditoría')";

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
                            s.estatus,
                            s.fecha_solicitud, 
                            s.fecha_requerida AS fecha_entrega,
                            s.fecha_entregado,
                            s.responsable_id,
                            s.comentario_solicitante AS comentarios,
                            u.nombre AS solicitante,
                            ur.nombre AS responsable,
                            pr.nombre AS proyecto
                        FROM solicitudes_material s
                        LEFT JOIN usuarios u ON s.solicitante_id = u.id
                        LEFT JOIN usuarios ur ON s.responsable_id = ur.id
                        LEFT JOIN proyectos pr ON s.proyecto_id = pr.id
                        WHERE s.id = ?";
        
        $stmt = $db->prepare($sqlCabecera);
        $stmt->execute([$solicitudId]);
        $solicitud = $stmt->fetch();

        if (!$solicitud) {
            return null;
        }

        $sqlDetalles = "SELECT 
                            d.id AS item_id,
                            d.producto_id,
                            p.nomenclatura,
                            p.nombre,
                            p.tipo, -- 'Herramienta', 'Consumible', 'Equipo'
                            um.apodo AS unidad_medida,
                            d.cantidad,
                            d.observaciones,
                            0 AS fuera_catalogo,
                            p.marca,
                            p.modelo,
                            NULL AS dimensiones,
                            COALESCE(si.stock_actual, 0) AS stock_actual
                        FROM solicitudes_material_detalles d
                        INNER JOIN inventario p ON d.producto_id = p.id
                        LEFT JOIN catalogo_unidades_medida um ON p.unidad_medida_id = um.id
                        LEFT JOIN (
                            SELECT sa.producto_id, SUM(sa.stock) AS stock_actual
                            FROM stock_almacen sa
                            INNER JOIN almacenes a ON a.id = sa.almacen_id AND a.activo = 1
                            GROUP BY sa.producto_id
                        ) si ON si.producto_id = p.id
                        WHERE d.solicitud_id = ?
                        UNION ALL
                        SELECT
                            nr.id AS item_id,
                            NULL AS producto_id,
                            NULL AS nomenclatura,
                            nr.nombre,
                            'Fuera del Catálogo' AS tipo,
                            nr.unidad_medida,
                            nr.cantidad,
                            nr.observaciones,
                            1 AS fuera_catalogo,
                            nr.marca,
                            NULL AS modelo,
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

    public static function entregarSolicitud(
        int $solicitudId,
        int $responsableId,
        array $cantidadesDetalles,
        array $cantidadesNoRegistrados
    ): array {
        if ($solicitudId <= 0 || $responsableId <= 0) {
            throw new InvalidArgumentException('La Solicitud y el Responsable son Obligatorios.');
        }

        $db = Database::getInstance()->getConnection();
        $gestionaTransaccion = !$db->inTransaction();
        if ($gestionaTransaccion) {
            $db->beginTransaction();
        }

        try {
            $stmtSolicitud = $db->prepare(
                'SELECT folio, estatus FROM solicitudes_material WHERE id = ? FOR UPDATE'
            );
            $stmtSolicitud->execute([$solicitudId]);
            $solicitud = $stmtSolicitud->fetch(PDO::FETCH_ASSOC);

            if (!$solicitud) {
                throw new RuntimeException('La Solicitud de Material no Existe.');
            }
            if (($solicitud['estatus'] ?? '') !== 'Aprobada') {
                throw new RuntimeException('La Solicitud ya no Está Disponible para Entrega.');
            }

            $stmtDetalles = $db->prepare(
                'SELECT id, producto_id, cantidad
                 FROM solicitudes_material_detalles
                 WHERE solicitud_id = ?
                 ORDER BY id
                 FOR UPDATE'
            );
            $stmtDetalles->execute([$solicitudId]);
            $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

            $stmtNoRegistrados = $db->prepare(
                'SELECT id, cantidad
                 FROM solicitudes_material_noregistrados
                 WHERE solicitud_id = ?
                 ORDER BY id
                 FOR UPDATE'
            );
            $stmtNoRegistrados->execute([$solicitudId]);
            $noRegistrados = $stmtNoRegistrados->fetchAll(PDO::FETCH_ASSOC);

            if ($detalles === [] && $noRegistrados === []) {
                throw new RuntimeException('La Solicitud no Tiene Materiales para Entregar.');
            }

            $normalizarCantidad = static function (array $cantidades, int $itemId, float $cantidadSolicitada, int $partida): float {
                if (!array_key_exists($itemId, $cantidades) || !is_numeric($cantidades[$itemId])) {
                    throw new InvalidArgumentException('Falta la Cantidad de la Partida ' . $partida . '.');
                }

                $cantidad = round((float) $cantidades[$itemId], 2);
                if ($cantidad < 0 || $cantidad > $cantidadSolicitada + 0.00001) {
                    throw new InvalidArgumentException(
                        'La Cantidad de la Partida ' . $partida . ' Debe Estar entre Cero y '
                        . rtrim(rtrim(number_format($cantidadSolicitada, 2, '.', ''), '0'), '.') . '.'
                    );
                }

                return $cantidad;
            };

            $actualizarDetalle = $db->prepare(
                'UPDATE solicitudes_material_detalles SET cantidad = ? WHERE id = ? AND solicitud_id = ?'
            );
            $actualizarNoRegistrado = $db->prepare(
                'UPDATE solicitudes_material_noregistrados SET cantidad = ? WHERE id = ? AND solicitud_id = ?'
            );
            $buscarStock = $db->prepare(
                'SELECT sa.almacen_id, sa.stock
                 FROM stock_almacen sa
                 INNER JOIN almacenes a ON a.id = sa.almacen_id AND a.activo = 1
                 WHERE sa.producto_id = ? AND sa.stock > 0
                 ORDER BY a.principal DESC, sa.almacen_id ASC
                 FOR UPDATE'
            );
            $descontarStock = $db->prepare(
                'UPDATE stock_almacen
                 SET stock = stock - ?
                 WHERE producto_id = ? AND almacen_id = ? AND stock >= ?'
            );
            $registrarMovimiento = $db->prepare(
                "INSERT INTO movimientos_inventario
                    (producto_id, tipo, cantidad, responsable_id, almacen_id, observaciones, folio_solicitud)
                 VALUES (?, 'Salida', ?, ?, ?, ?, ?)"
            );

            $partida = 0;
            $partidasEntregadas = 0;
            $cantidadTotal = 0.0;
            $movimientos = 0;

            foreach ($detalles as $detalle) {
                $partida++;
                $detalleId = (int) $detalle['id'];
                $productoId = (int) $detalle['producto_id'];
                $solicitado = (float) $detalle['cantidad'];
                $cantidad = $normalizarCantidad($cantidadesDetalles, $detalleId, $solicitado, $partida);

                if ($cantidad > 0) {
                    $buscarStock->execute([$productoId]);
                    $existencias = $buscarStock->fetchAll(PDO::FETCH_ASSOC);
                    $disponible = array_reduce(
                        $existencias,
                        static fn(float $total, array $fila): float => $total + (float) $fila['stock'],
                        0.0
                    );

                    if ($disponible + 0.00001 < $cantidad) {
                        throw new RuntimeException('Stock Insuficiente para la Partida ' . $partida . '.');
                    }

                    $pendiente = $cantidad;
                    foreach ($existencias as $existencia) {
                        if ($pendiente <= 0.00001) {
                            break;
                        }

                        $almacenId = (int) $existencia['almacen_id'];
                        $descuento = min($pendiente, (float) $existencia['stock']);
                        $descuento = round($descuento, 2);
                        if ($descuento <= 0) {
                            continue;
                        }

                        $descontarStock->execute([$descuento, $productoId, $almacenId, $descuento]);
                        if ($descontarStock->rowCount() !== 1) {
                            throw new RuntimeException('El Stock Cambió Mientras se Procesaba la Partida ' . $partida . '.');
                        }

                        $registrarMovimiento->execute([
                            $productoId,
                            $descuento,
                            $responsableId,
                            $almacenId,
                            'Entrega de Solicitud de Material',
                            $solicitud['folio'],
                        ]);
                        $movimientos++;
                        $pendiente = round($pendiente - $descuento, 2);
                    }

                    if ($pendiente > 0.00001) {
                        throw new RuntimeException('No Fue Posible Completar el Descuento de la Partida ' . $partida . '.');
                    }

                    $partidasEntregadas++;
                    $cantidadTotal += $cantidad;
                }

                $actualizarDetalle->execute([$cantidad, $detalleId, $solicitudId]);
            }

            foreach ($noRegistrados as $material) {
                $partida++;
                $materialId = (int) $material['id'];
                $solicitado = (float) $material['cantidad'];
                $cantidad = $normalizarCantidad($cantidadesNoRegistrados, $materialId, $solicitado, $partida);
                $actualizarNoRegistrado->execute([$cantidad, $materialId, $solicitudId]);

                if ($cantidad > 0) {
                    $partidasEntregadas++;
                    $cantidadTotal += $cantidad;
                }
            }

            if ($partidasEntregadas === 0) {
                throw new InvalidArgumentException('Debes Entregar una Cantidad Mayor a Cero en al Menos una Partida.');
            }

            $actualizarSolicitud = $db->prepare(
                "UPDATE solicitudes_material
                 SET estatus = 'Entregada', responsable_id = ?, fecha_entregado = CURDATE(), updated_at = NOW()
                 WHERE id = ? AND estatus = 'Aprobada'"
            );
            $actualizarSolicitud->execute([$responsableId, $solicitudId]);
            if ($actualizarSolicitud->rowCount() !== 1) {
                throw new RuntimeException('La Solicitud Cambió de Estado Durante el Proceso.');
            }

            if ($gestionaTransaccion) {
                $db->commit();
            }

            return [
                'folio' => (string) $solicitud['folio'],
                'partidas_entregadas' => $partidasEntregadas,
                'cantidad_total' => round($cantidadTotal, 2),
                'movimientos' => $movimientos,
            ];
        } catch (Throwable $e) {
            if ($gestionaTransaccion && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
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
                            p.marca,
                            p.modelo,
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
