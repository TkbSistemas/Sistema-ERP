<?php
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/OrdenCompra.php';
require_once __DIR__ . '/RecepcionAlmacen.php';

class FacturaCompra
{
    private const ESTATUS_ELEGIBLES = ['Parcial', 'Completa', 'Incompleta', 'Recibida'];

    public static function estatusElegibles(): array
    {
        return self::ESTATUS_ELEGIBLES;
    }

    public static function ordenesElegibles(): array
    {
        $db = Database::getInstance()->getConnection();
        $marcadores = implode(',', array_fill(0, count(self::ESTATUS_ELEGIBLES), '?'));
        $sql = "SELECT
                    oc.id,
                    oc.folio,
                    oc.estatus,
                    oc.proyecto_id,
                    oc.fecha_compra,
                    cp.nombre AS proveedor_nombre,
                    p.nombre AS proyecto_nombre,
                    p.codigo AS proyecto_codigo,
                    fc.id AS factura_id,
                    fc.folio_fiscal,
                    fc.monto_total,
                    fc.fecha_emision,
                    fc.created_at AS factura_creada_at,
                    COUNT(ocd.id) AS numero_partidas,
                    COALESCE(SUM(
                        COALESCE(ocd.cantidad_confirmada, ocd.cantidad_solicitada)
                        * COALESCE(ocd.precio_confirmado, ocd.precio_unitario)
                    ), 0) AS total_orden
                FROM ordenes_compra oc
                LEFT JOIN catalogo_proveedores cp ON cp.id = oc.proveedor_id
                LEFT JOIN proyectos p ON p.id = oc.proyecto_id
                LEFT JOIN facturas_compras fc ON fc.orden_id = oc.id
                LEFT JOIN ordenes_compra_detalles ocd ON ocd.orden_compra_id = oc.id
                WHERE oc.estatus IN ($marcadores)
                GROUP BY
                    oc.id, oc.folio, oc.estatus, oc.proyecto_id, oc.fecha_compra,
                    cp.nombre, p.nombre, p.codigo,
                    fc.id, fc.folio_fiscal, fc.monto_total, fc.fecha_emision, fc.created_at
                ORDER BY FIELD(oc.estatus, 'Parcial', 'Completa', 'Incompleta', 'Recibida'), oc.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute(self::ESTATUS_ELEGIBLES);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function guardarParaOrden(
        int $ordenId,
        int $proyectoId,
        string $folioFiscal,
        float $montoTotal,
        string $fechaEmision
    ): array {
        $db = Database::getInstance()->getConnection();
        $gestionaTransaccion = !$db->inTransaction();
        if ($gestionaTransaccion) {
            $db->beginTransaction();
        }

        try {
            $stmtOrden = $db->prepare('SELECT id, folio, estatus FROM ordenes_compra WHERE id = ? FOR UPDATE');
            $stmtOrden->execute([$ordenId]);
            $orden = $stmtOrden->fetch(PDO::FETCH_ASSOC);
            if (!$orden) {
                throw new RuntimeException('La Orden de Compra no Existe.');
            }
            if (!in_array((string) $orden['estatus'], self::ESTATUS_ELEGIBLES, true)) {
                throw new RuntimeException('La Orden aún no Puede Facturarse o ya no se Encuentra en un Estatus Permitido.');
            }

            $stmtProyecto = $db->prepare('SELECT id FROM proyectos WHERE id = ?');
            $stmtProyecto->execute([$proyectoId]);
            if (!$stmtProyecto->fetchColumn()) {
                throw new RuntimeException('El Proyecto Seleccionado no Existe.');
            }

            $stmtDuplicado = $db->prepare(
                'SELECT id FROM facturas_compras WHERE folio_fiscal = ? AND orden_id <> ? LIMIT 1'
            );
            $stmtDuplicado->execute([$folioFiscal, $ordenId]);
            if ($stmtDuplicado->fetchColumn()) {
                throw new RuntimeException('El Folio Fiscal ya Está Asignado a Otra Orden de Compra.');
            }

            $stmtFactura = $db->prepare('SELECT id FROM facturas_compras WHERE orden_id = ? FOR UPDATE');
            $stmtFactura->execute([$ordenId]);
            $facturaId = $stmtFactura->fetchColumn();
            $esNueva = $facturaId === false;

            if ($esNueva) {
                $insertar = $db->prepare(
                    'INSERT INTO facturas_compras (orden_id, folio_fiscal, monto_total, fecha_emision)
                     VALUES (?, ?, ?, ?)'
                );
                $insertar->execute([$ordenId, $folioFiscal, $montoTotal, $fechaEmision]);
                $facturaId = (int) $db->lastInsertId();
            } else {
                $actualizar = $db->prepare(
                    'UPDATE facturas_compras
                     SET folio_fiscal = ?, monto_total = ?, fecha_emision = ?
                     WHERE id = ?'
                );
                $actualizar->execute([$folioFiscal, $montoTotal, $fechaEmision, (int) $facturaId]);
            }

            $actualizarOrden = $db->prepare('UPDATE ordenes_compra SET proyecto_id = ? WHERE id = ?');
            $actualizarOrden->execute([$proyectoId, $ordenId]);

            if ($gestionaTransaccion) {
                $db->commit();
            }

            return [
                'id' => (int) $facturaId,
                'es_nueva' => $esNueva,
                'folio_orden' => (string) ($orden['folio'] ?? ''),
                'estatus_orden' => (string) $orden['estatus'],
            ];
        } catch (Throwable $e) {
            if ($gestionaTransaccion && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function registrarHistorica(array $cabecera, array $materiales): array
    {
        if ($materiales === []) {
            throw new InvalidArgumentException('La Factura Debe Contener al Menos un Material.');
        }

        $proveedorId = (int) ($cabecera['proveedor_id'] ?? 0);
        $proyectoId = (int) ($cabecera['proyecto_id'] ?? 0);
        $almacenId = (int) ($cabecera['almacen_id'] ?? 0);
        $responsableId = (int) ($cabecera['responsable_id'] ?? 0);
        $folioFiscal = strtoupper(trim((string) ($cabecera['folio_fiscal'] ?? '')));
        $fechaEmision = trim((string) ($cabecera['fecha_emision'] ?? ''));

        if ($proveedorId <= 0 || $proyectoId <= 0 || $almacenId <= 0 || $responsableId <= 0) {
            throw new InvalidArgumentException('Proveedor, Proyecto, Almacén y Responsable son Obligatorios.');
        }
        if ($folioFiscal === '' || $fechaEmision === '') {
            throw new InvalidArgumentException('El Folio Fiscal y la Fecha de Emisión son Obligatorios.');
        }

        $detallesOrden = [];
        $total = 0.0;
        $productosUsados = [];
        foreach ($materiales as $indice => $material) {
            $productoId = (int) ($material['producto_id'] ?? 0);
            $cantidad = (float) ($material['cantidad'] ?? 0);
            $precio = (float) ($material['precio_unitario'] ?? 0);
            if ($productoId <= 0 || $cantidad <= 0 || $precio <= 0) {
                throw new InvalidArgumentException('La Partida ' . ($indice + 1) . ' Contiene Datos Inválidos.');
            }
            if (isset($productosUsados[$productoId])) {
                throw new InvalidArgumentException('Un Producto no Puede Repetirse en la Misma Factura.');
            }
            $productosUsados[$productoId] = true;
            $cantidad = round($cantidad, 2);
            $precio = round($precio, 2);
            $detallesOrden[] = [
                'producto_id' => $productoId,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
            ];
            $total += $cantidad * $precio;
        }
        $total = round($total, 2);
        if ($total <= 0 || $total > 999999999999.99) {
            throw new InvalidArgumentException('El Monto Total de la Factura no es Válido.');
        }

        $db = Database::getInstance()->getConnection();
        $gestionaTransaccion = !$db->inTransaction();
        if ($gestionaTransaccion) {
            $db->beginTransaction();
        }

        try {
            $orden = OrdenCompra::crearOrden([
                'proyecto_id' => $proyectoId,
                'proveedor_id' => $proveedorId,
                'fecha_compra' => $fechaEmision,
                'metodo_entrega' => 'Por Confirmar',
                'almacen_id' => $almacenId,
                'created_by' => $responsableId,
            ], $detallesOrden);

            OrdenCompra::aprobar((int) $orden['id']);
            $ordenCompleta = OrdenCompra::find((int) $orden['id']);
            if ($ordenCompleta === null || count($ordenCompleta['detalles'] ?? []) !== count($detallesOrden)) {
                throw new RuntimeException('No Fue Posible Preparar las Partidas de la Compra Histórica.');
            }

            $detallesConfirmados = [];
            foreach ($ordenCompleta['detalles'] as $detalle) {
                $detallesConfirmados[] = [
                    'id' => (int) $detalle['id'],
                    'cantidad_confirmada' => (float) $detalle['cantidad_solicitada'],
                    'precio_confirmado' => (float) $detalle['precio_unitario'],
                ];
            }
            OrdenCompra::confirmarCompra((int) $orden['id'], $detallesConfirmados, 'Por Confirmar', $almacenId);

            $factura = self::guardarParaOrden(
                (int) $orden['id'],
                $proyectoId,
                $folioFiscal,
                $total,
                $fechaEmision
            );

            $ordenRecepcion = RecepcionAlmacen::buscarOrdenPorId((int) $orden['id']);
            if ($ordenRecepcion === null || empty($ordenRecepcion['detalles'])) {
                throw new RuntimeException('No Fue Posible Preparar la Recepción de la Compra Histórica.');
            }
            $cantidades = [];
            foreach ($ordenRecepcion['detalles'] as $detalle) {
                $cantidades[(int) $detalle['detalle_id']] = (float) $detalle['cantidad_pendiente'];
            }
            $recepcion = RecepcionAlmacen::registrar(
                (int) $orden['id'],
                $responsableId,
                $cantidades,
                $fechaEmision
            );

            if ($gestionaTransaccion) {
                $db->commit();
            }

            return [
                'orden_id' => (int) $orden['id'],
                'folio_orden' => (string) $orden['folio'],
                'factura_id' => (int) $factura['id'],
                'folio_fiscal' => $folioFiscal,
                'recepcion_id' => (int) $recepcion['id'],
                'folio_recepcion' => (string) $recepcion['folio'],
                'estatus_orden' => (string) $recepcion['estatus_orden'],
                'partidas' => count($detallesOrden),
                'total' => $total,
            ];
        } catch (Throwable $e) {
            if ($gestionaTransaccion && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
