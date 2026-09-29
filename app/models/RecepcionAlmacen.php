<?php
require_once __DIR__ . '/../helpers/Database.php';

class RecepcionAlmacen
{
    public const LONGITUD_REFERENCIA = 11;

    public static function folioDesdeReferencia(string $referencia): string
    {
        $referencia = trim($referencia);
        if (!preg_match('/^\d{' . self::LONGITUD_REFERENCIA . '}$/', $referencia)) {
            throw new InvalidArgumentException(
                'La Referencia Debe Contener Exactamente ' . self::LONGITUD_REFERENCIA . ' Dígitos.'
            );
        }

        return 'TAKAB-OC-' . substr($referencia, 0, 6) . '-' . substr($referencia, 6, 5);
    }

    public static function buscarOrdenPorFolio(string $folio): ?array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT
                oc.id,
                oc.folio,
                oc.estatus,
                oc.fecha_compra,
                oc.metodo_entrega,
                oc.id_almacen,
                a.nombre AS almacen_nombre,
                cp.nombre AS proveedor_nombre
             FROM ordenes_compra oc
             LEFT JOIN almacenes a ON a.id = oc.id_almacen
             LEFT JOIN catalogo_proveedores cp ON cp.id = oc.proveedor_id
             WHERE oc.folio = ?
               AND oc.estatus IN ('Parcial', 'Completa', 'Incompleta')
             LIMIT 1"
        );
        $stmt->execute([$folio]);
        $orden = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$orden) {
            return null;
        }

        $orden['id'] = (int) $orden['id'];
        $orden['id_almacen'] = (int) ($orden['id_almacen'] ?? 0);
        $orden['referencia'] = preg_replace('/\D+/', '', (string) $orden['folio']);
        $orden['detalles'] = self::detallesPendientes($db, $orden['id']);
        $orden['recepcion_completa'] = $orden['detalles'] === [];

        return $orden;
    }

    public static function buscarOrdenPorId(int $ordenId): ?array
    {
        if ($ordenId <= 0) {
            return null;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT folio FROM ordenes_compra WHERE id = ? LIMIT 1');
        $stmt->execute([$ordenId]);
        $folio = $stmt->fetchColumn();

        return $folio !== false ? self::buscarOrdenPorFolio((string) $folio) : null;
    }

    public static function registrar(
        int $ordenId,
        int $responsableId,
        array $cantidades,
        ?string $fechaRecepcion = null
    ): array
    {
        if ($ordenId <= 0 || $responsableId <= 0) {
            throw new InvalidArgumentException('La Orden y el Responsable son Obligatorios.');
        }

        $fechaRecepcion = $fechaRecepcion ?: date('Y-m-d');
        $fechaValida = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaRecepcion);
        if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fechaRecepcion) {
            throw new InvalidArgumentException('La Fecha de Recepción no es Válida.');
        }

        $db = Database::getInstance()->getConnection();
        $gestionaTransaccion = !$db->inTransaction();
        if ($gestionaTransaccion) {
            $db->beginTransaction();
        }

        try {
            $stmtOrden = $db->prepare(
                "SELECT oc.id, oc.folio, oc.estatus, oc.id_almacen, a.nombre AS almacen_nombre
                 FROM ordenes_compra oc
                 LEFT JOIN almacenes a ON a.id = oc.id_almacen AND a.activo = 1
                 WHERE oc.id = ?
                 FOR UPDATE"
            );
            $stmtOrden->execute([$ordenId]);
            $orden = $stmtOrden->fetch(PDO::FETCH_ASSOC);
            if (!$orden || !in_array((string) ($orden['estatus'] ?? ''), ['Parcial', 'Completa', 'Incompleta'], true)) {
                throw new RuntimeException('La Orden no Existe o no Está Disponible para Recepción.');
            }

            $almacenId = (int) ($orden['id_almacen'] ?? 0);
            if ($almacenId <= 0 || empty($orden['almacen_nombre'])) {
                throw new RuntimeException('La Orden no Tiene un Almacén Destino Activo.');
            }

            $bloquearRecepciones = $db->prepare(
                'SELECT id FROM recepciones_almacen WHERE orden_id = ? FOR UPDATE'
            );
            $bloquearRecepciones->execute([$ordenId]);
            $bloquearRecepciones->fetchAll(PDO::FETCH_COLUMN);

            $detalles = self::detallesPendientes($db, $ordenId);
            if ($detalles === []) {
                throw new RuntimeException('La Orden ya fue Recibida por Completo.');
            }

            $lineas = [];
            $recepcionCompleta = true;
            $cantidadTotal = 0.0;
            foreach ($detalles as $indice => $detalle) {
                $detalleId = (int) $detalle['detalle_id'];
                $cantidad = filter_var($cantidades[$detalleId] ?? null, FILTER_VALIDATE_FLOAT);
                $pendiente = (float) $detalle['cantidad_pendiente'];
                if ($cantidad === false || $cantidad < 0 || $cantidad > $pendiente + 0.00001) {
                    throw new InvalidArgumentException(
                        'La Cantidad Recibida de la Partida ' . ($indice + 1)
                        . ' Debe Estar entre Cero y ' . self::numero($pendiente) . '.'
                    );
                }

                $cantidad = round((float) $cantidad, 2);
                $faltante = round(max(0, $pendiente - $cantidad), 2);
                if ($faltante > 0.00001) {
                    $recepcionCompleta = false;
                }
                $cantidadTotal += $cantidad;
                $lineas[] = [
                    'detalle_id' => $detalleId,
                    'producto_id' => (int) $detalle['producto_id'],
                    'cantidad_recibida' => $cantidad,
                    'cantidad_faltante' => $faltante,
                ];
            }

            if ($cantidadTotal <= 0) {
                throw new InvalidArgumentException('Captura una Cantidad Mayor a Cero en al Menos una Partida.');
            }

            $estatus = $recepcionCompleta ? 'Completa' : 'Parcial';
            $folioTemporal = 'TMP-RE-' . bin2hex(random_bytes(8));
            $stmtRecepcion = $db->prepare(
                'INSERT INTO recepciones_almacen
                    (folio_entrada, orden_id, estatus, fecha_recepcion, responsable_id, created_at)
                 VALUES (?, ?, ?, ?, ?, CONCAT(?, \' 12:00:00\'))'
            );
            $stmtRecepcion->execute([
                $folioTemporal,
                $ordenId,
                $estatus,
                $fechaRecepcion,
                $responsableId,
                $fechaRecepcion,
            ]);
            $recepcionId = (int) $db->lastInsertId();
            $folioRecepcion = sprintf('TAKAB-RE-%s-%05d', $fechaValida->format('Ym'), $recepcionId);
            $actualizarFolio = $db->prepare('UPDATE recepciones_almacen SET folio_entrada = ? WHERE id = ?');
            $actualizarFolio->execute([$folioRecepcion, $recepcionId]);

            $stmtDetalle = $db->prepare(
                'INSERT INTO recepciones_detalles
                    (recepcion_id, producto_id, detalle_orden_id, cantidad_recibida, cantidad_faltante)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmtStock = $db->prepare(
                'INSERT INTO stock_almacen (producto_id, almacen_id, stock)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE stock = stock + VALUES(stock)'
            );
            $stmtMovimiento = $db->prepare(
                "INSERT INTO movimientos_inventario
                    (producto_id, folio_solicitud, tipo, cantidad, responsable_id, almacen_id, observaciones, created_at)
                 VALUES (?, ?, 'Entrada', ?, ?, ?, ?, CONCAT(?, ' 12:00:00'))"
            );

            $movimientos = 0;
            foreach ($lineas as $linea) {
                $stmtDetalle->execute([
                    $recepcionId,
                    $linea['producto_id'],
                    $linea['detalle_id'],
                    $linea['cantidad_recibida'],
                    $linea['cantidad_faltante'],
                ]);

                if ($linea['cantidad_recibida'] <= 0) {
                    continue;
                }
                $stmtStock->execute([$linea['producto_id'], $almacenId, $linea['cantidad_recibida']]);
                $stmtMovimiento->execute([
                    $linea['producto_id'],
                    $folioRecepcion,
                    $linea['cantidad_recibida'],
                    $responsableId,
                    $almacenId,
                    'Recepción de la Orden ' . $orden['folio'],
                    $fechaRecepcion,
                ]);
                $movimientos++;
            }

            $estatusOrden = $recepcionCompleta ? 'Recibida' : 'Incompleta';
            $actualizarOrden = $db->prepare('UPDATE ordenes_compra SET estatus = ? WHERE id = ?');
            $actualizarOrden->execute([$estatusOrden, $ordenId]);

            if ($gestionaTransaccion) {
                $db->commit();
            }

            return [
                'id' => $recepcionId,
                'folio' => $folioRecepcion,
                'estatus' => $estatus,
                'estatus_orden' => $estatusOrden,
                'orden_folio' => (string) $orden['folio'],
                'almacen_id' => $almacenId,
                'almacen' => (string) $orden['almacen_nombre'],
                'partidas' => count($lineas),
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

    public static function ultimas(int $limite = 8): array
    {
        $db = Database::getInstance()->getConnection();
        $limite = max(1, min(50, $limite));
        $sql = "SELECT
                    ra.folio_entrada,
                    ra.estatus,
                    ra.fecha_recepcion,
                    ra.created_at,
                    oc.folio AS orden_folio,
                    a.nombre AS almacen_nombre,
                    u.nombre AS responsable_nombre,
                    COUNT(rd.id) AS total_partidas,
                    COALESCE(SUM(rd.cantidad_recibida), 0) AS total_recibido
                FROM recepciones_almacen ra
                INNER JOIN ordenes_compra oc ON oc.id = ra.orden_id
                LEFT JOIN almacenes a ON a.id = oc.id_almacen
                LEFT JOIN usuarios u ON u.id = ra.responsable_id
                LEFT JOIN recepciones_detalles rd ON rd.recepcion_id = ra.id
                WHERE ra.estatus IN ('Parcial', 'Completa')
                GROUP BY ra.id, ra.folio_entrada, ra.estatus, ra.fecha_recepcion, ra.created_at,
                         oc.folio, a.nombre, u.nombre
                ORDER BY ra.id DESC
                LIMIT " . $limite;

        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function detallesPendientes(PDO $db, int $ordenId): array
    {
        $stmt = $db->prepare(
            'SELECT
                ocd.id AS detalle_id,
                ocd.producto_id,
                COALESCE(ocd.cantidad_confirmada, 0) AS cantidad_confirmada,
                COALESCE(recibido.total_recibido, 0) AS cantidad_recibida_previa,
                i.nombre AS producto_nombre,
                i.nomenclatura,
                i.sku,
                i.marca,
                i.modelo,
                COALESCE(NULLIF(um.apodo, \'\'), um.nombre, \'Pza\') AS unidad
             FROM ordenes_compra_detalles ocd
             LEFT JOIN inventario i ON i.id = ocd.producto_id
             LEFT JOIN catalogo_unidades_medida um ON um.id = i.unidad_medida_id
             LEFT JOIN (
                SELECT rd.detalle_orden_id, SUM(COALESCE(rd.cantidad_recibida, 0)) AS total_recibido
                FROM recepciones_detalles rd
                INNER JOIN recepciones_almacen ra ON ra.id = rd.recepcion_id
                WHERE ra.orden_id = ?
                  AND ra.estatus IN (\'Parcial\', \'Completa\')
                GROUP BY rd.detalle_orden_id
             ) recibido ON recibido.detalle_orden_id = ocd.id
             WHERE ocd.orden_compra_id = ?
             ORDER BY ocd.id ASC'
        );
        $stmt->execute([$ordenId, $ordenId]);

        $detalles = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $detalle) {
            $confirmada = (float) $detalle['cantidad_confirmada'];
            $recibida = (float) $detalle['cantidad_recibida_previa'];
            $pendiente = round(max(0, $confirmada - $recibida), 2);
            if ($pendiente <= 0.00001) {
                continue;
            }
            $detalle['detalle_id'] = (int) $detalle['detalle_id'];
            $detalle['producto_id'] = (int) $detalle['producto_id'];
            $detalle['cantidad_confirmada'] = $confirmada;
            $detalle['cantidad_recibida_previa'] = $recibida;
            $detalle['cantidad_pendiente'] = $pendiente;
            $detalles[] = $detalle;
        }

        return $detalles;
    }

    private static function numero(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
    }
}
