<?php
require_once __DIR__ . '/../helpers/Database.php';

class OrdenCompra{

    public static function all(): array
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT
                    oc.id AS orden_id,
                    oc.folio,
                    oc.estatus,
                    oc.fecha_compra,
                    oc.metodo_entrega,
                    oc.id_almacen,
                    a.nombre AS almacen_nombre,
                    oc.created_by,
                    u.nombre AS creado_por,
                    cp.id AS proveedor_id,
                    cp.nombre AS proveedor_nombre,
                    cp.rfc AS proveedor_rfc,
                    p.id AS proyecto_id,
                    p.codigo AS proyecto_codigo,
                    p.nombre AS proyecto_nombre,
                    ocd.id AS detalle_id,
                    ocd.producto_id,
                    ocd.cantidad_solicitada,
                    ocd.precio_unitario,
                    ocd.cantidad_confirmada,
                    ocd.precio_confirmado,
                    i.nombre AS producto_nombre,
                    i.nomenclatura AS producto_nomenclatura,
                    i.sku AS producto_sku,
                    i.codigo_fabricante AS producto_codigo_fabricante,
                    i.descripcion AS producto_descripcion,
                    i.marca AS producto_marca,
                    i.modelo AS producto_modelo,
                    um.nombre AS unidad_medida,
                    um.apodo AS unidad_apodo
                FROM ordenes_compra oc
                LEFT JOIN catalogo_proveedores cp ON oc.proveedor_id = cp.id
                LEFT JOIN proyectos p ON oc.proyecto_id = p.id
                LEFT JOIN almacenes a ON a.id = oc.id_almacen
                LEFT JOIN usuarios u ON oc.created_by = u.id
                LEFT JOIN ordenes_compra_detalles ocd ON oc.id = ocd.orden_compra_id
                LEFT JOIN inventario i ON ocd.producto_id = i.id
                LEFT JOIN catalogo_unidades_medida um ON i.unidad_medida_id = um.id
                ORDER BY oc.fecha_compra DESC, oc.id DESC, ocd.id ASC";

        $resultados = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $ordenesAgrupadas = [];

        foreach ($resultados as $fila) {
            $idOrden = (int) $fila['orden_id'];
            if (!isset($ordenesAgrupadas[$idOrden])) {
                $ordenesAgrupadas[$idOrden] = [
                    'id' => $idOrden,
                    'folio' => (string) ($fila['folio'] ?? ''),
                    'estatus' => (string) ($fila['estatus'] ?? 'Pendiente'),
                    'fecha_compra' => $fila['fecha_compra'],
                    'metodo_entrega' => $fila['metodo_entrega'] ?: 'Por Confirmar',
                    'almacen' => [
                        'id' => $fila['id_almacen'] !== null ? (int) $fila['id_almacen'] : null,
                        'nombre' => $fila['almacen_nombre'] ?: 'Sin Almacén',
                    ],
                    'created_by' => $fila['created_by'],
                    'creado_por' => $fila['creado_por'] ?: 'Sin Registro',
                    'proveedor' => [
                        'id' => $fila['proveedor_id'],
                        'nombre' => $fila['proveedor_nombre'] ?: 'Sin Proveedor',
                        'rfc' => $fila['proveedor_rfc']
                    ],
                    'proyecto' => [
                        'id' => $fila['proyecto_id'],
                        'codigo' => $fila['proyecto_codigo'],
                        'nombre' => $fila['proyecto_nombre'] ?: 'Sin Proyecto'
                    ],

                    'detalles' => [],
                    'materiales_resumen' => '',
                    'total_estimado' => 0.0,
                    'total_confirmado' => 0.0,
                ];
            }
            if ($fila['detalle_id'] !== null) {
                $cantidad = (float) $fila['cantidad_solicitada'];
                $precio = (float) ($fila['precio_unitario'] ?? 0);
                $unidad = trim((string) ($fila['unidad_apodo'] ?: $fila['unidad_medida']));
                $nombreProducto = trim((string) ($fila['producto_nombre'] ?? ''));
                if ($nombreProducto === '') {
                    $nombreProducto = 'Producto #' . (int) $fila['producto_id'];
                }

                $cantidadConfirmada = $fila['cantidad_confirmada'] !== null
                    ? (float) $fila['cantidad_confirmada']
                    : 0.0;
                $precioConfirmado = $fila['precio_confirmado'] !== null
                    ? (float) $fila['precio_confirmado']
                    : 0.0;

                $ordenesAgrupadas[$idOrden]['detalles'][] = [
                    'id' => (int) $fila['detalle_id'],
                    'producto_id' => $fila['producto_id'],
                    'producto_nombre' => $nombreProducto,
                    'producto_nomenclatura' => $fila['producto_nomenclatura'],
                    'producto_sku' => $fila['producto_sku'],
                    'codigo_fabricante' => $fila['producto_codigo_fabricante'],
                    'descripcion' => $fila['producto_descripcion'],
                    'marca' => $fila['producto_marca'],
                    'modelo' => $fila['producto_modelo'],
                    'unidad' => $unidad,
                    'cantidad_solicitada' => $cantidad,
                    'precio_unitario' => $precio,
                    'importe_estimado' => round($cantidad * $precio, 2),
                    'cantidad_confirmada' => $fila['cantidad_confirmada'],
                    'precio_confirmado' => $fila['precio_confirmado'],
                    'importe_confirmado' => round($cantidadConfirmada * $precioConfirmado, 2),
                ];
                $ordenesAgrupadas[$idOrden]['total_estimado'] += $cantidad * $precio;
                $ordenesAgrupadas[$idOrden]['total_confirmado'] += $cantidadConfirmada * $precioConfirmado;
            }
        }

        foreach ($ordenesAgrupadas as &$orden) {
            $resumen = [];
            foreach ($orden['detalles'] as $detalle) {
                $cantidad = rtrim(rtrim(number_format((float) $detalle['cantidad_solicitada'], 2, '.', ''), '0'), '.');
                $unidad = $detalle['unidad'] !== '' ? ' ' . $detalle['unidad'] : '';
                $resumen[] = $detalle['producto_nombre'] . ' — ' . $cantidad . $unidad;
            }
            $orden['materiales_resumen'] = implode("\n", $resumen);
            $orden['total_estimado'] = round((float) $orden['total_estimado'], 2);
            $orden['total_confirmado'] = round((float) $orden['total_confirmado'], 2);
            $orden['total_mostrado'] = in_array($orden['estatus'], ['Completa', 'Parcial', 'Recibida', 'Incompleta'], true)
                ? $orden['total_confirmado']
                : $orden['total_estimado'];
        }
        unset($orden);

        return array_values($ordenesAgrupadas);
    }


    public static function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        foreach (self::all() as $orden) {
            if ((int) ($orden['id'] ?? 0) === $id) {
                return $orden;
            }
        }

        return null;
    }

    public static function crearOrden(array $cabecera, array $detalles): array
    {
        if ($detalles === []) {
            throw new InvalidArgumentException('La Orden Debe Contener al Menos un Material.');
        }

        $db = Database::getInstance()->getConnection();
        $gestionaTransaccion = !$db->inTransaction();
        if ($gestionaTransaccion) {
            $db->beginTransaction();
        }

        try {
            $stmtOrden = $db->prepare(
                "INSERT INTO ordenes_compra
                    (folio, estatus, proyecto_id, proveedor_id, fecha_compra, metodo_entrega, id_almacen, created_by)
                 VALUES (NULL, 'Pendiente', ?, ?, ?, ?, ?, ?)"
            );
            $stmtOrden->execute([
                isset($cabecera['proyecto_id']) && (int) $cabecera['proyecto_id'] > 0
                    ? (int) $cabecera['proyecto_id']
                    : null,
                (int) $cabecera['proveedor_id'],
                $cabecera['fecha_compra'],
                $cabecera['metodo_entrega'],
                (int) $cabecera['almacen_id'],
                (int) $cabecera['created_by'],
            ]);

            $ordenId = (int) $db->lastInsertId();
            $fechaFolio = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $cabecera['fecha_compra']);
            $periodo = $fechaFolio ? $fechaFolio->format('Ym') : date('Ym');
            $folio = sprintf('TAKAB-OC-%s-%05d', $periodo, $ordenId);

            $stmtFolio = $db->prepare('UPDATE ordenes_compra SET folio = ? WHERE id = ?');
            $stmtFolio->execute([$folio, $ordenId]);

            $stmtDetalle = $db->prepare(
                'INSERT INTO ordenes_compra_detalles
                    (orden_compra_id, producto_id, cantidad_solicitada, precio_unitario)
                 VALUES (?, ?, ?, ?)'
            );
            foreach ($detalles as $detalle) {
                $stmtDetalle->execute([
                    $ordenId,
                    (int) $detalle['producto_id'],
                    (float) $detalle['cantidad'],
                    (float) $detalle['precio_unitario'],
                ]);
            }

            if ($gestionaTransaccion) {
                $db->commit();
            }
            return ['id' => $ordenId, 'folio' => $folio];
        } catch (Throwable $e) {
            if ($gestionaTransaccion && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function aprobar(int $ordenId): array
    {
        if ($ordenId <= 0) {
            throw new InvalidArgumentException('La Orden de Compra no es Válida.');
        }

        $db = Database::getInstance()->getConnection();
        $gestionaTransaccion = !$db->inTransaction();
        if ($gestionaTransaccion) {
            $db->beginTransaction();
        }

        try {
            $stmt = $db->prepare('SELECT id, folio, estatus FROM ordenes_compra WHERE id = ? FOR UPDATE');
            $stmt->execute([$ordenId]);
            $orden = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$orden) {
                throw new RuntimeException('La Orden de Compra no Existe.');
            }
            if ((string) $orden['estatus'] !== 'Pendiente') {
                throw new RuntimeException('Solo las Órdenes Pendientes Pueden Aprobarse.');
            }

            $actualizar = $db->prepare("UPDATE ordenes_compra SET estatus = 'Aprobada' WHERE id = ?");
            $actualizar->execute([$ordenId]);

            if ($gestionaTransaccion) {
                $db->commit();
            }

            return [
                'id' => (int) $orden['id'],
                'folio' => (string) ($orden['folio'] ?? ''),
                'estatus' => 'Aprobada',
            ];
        } catch (Throwable $e) {
            if ($gestionaTransaccion && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function confirmarCompra(
        int $ordenId,
        array $detalles,
        string $metodoEntrega,
        int $almacenId
    ): string
    {
        if ($ordenId <= 0 || $detalles === []) {
            throw new InvalidArgumentException('La Orden y sus Materiales son Obligatorios.');
        }
        if (!in_array($metodoEntrega, ['Reparto', 'Recolección', 'Por Confirmar'], true)) {
            throw new InvalidArgumentException('La Forma de Entrega no es Válida.');
        }
        if ($almacenId <= 0) {
            throw new InvalidArgumentException('El Almacén Destino es Obligatorio.');
        }

        $db = Database::getInstance()->getConnection();
        $gestionaTransaccion = !$db->inTransaction();
        if ($gestionaTransaccion) {
            $db->beginTransaction();
        }

        try {
            $stmtOrden = $db->prepare('SELECT estatus FROM ordenes_compra WHERE id = ? FOR UPDATE');
            $stmtOrden->execute([$ordenId]);
            $orden = $stmtOrden->fetch(PDO::FETCH_ASSOC);
            if (!$orden) {
                throw new RuntimeException('La Orden de Compra no Existe.');
            }
            if (!in_array((string) $orden['estatus'], ['Aprobada', 'Parcial'], true)) {
                throw new RuntimeException('La Orden ya no se Encuentra Disponible para Procesar.');
            }

            $stmtDetalle = $db->prepare(
                'UPDATE ordenes_compra_detalles
                 SET cantidad_confirmada = ?, precio_confirmado = ?
                 WHERE id = ? AND orden_compra_id = ?'
            );
            foreach ($detalles as $detalle) {
                $stmtDetalle->execute([
                    (float) $detalle['cantidad_confirmada'],
                    (float) $detalle['precio_confirmado'],
                    (int) $detalle['id'],
                    $ordenId,
                ]);
                if ($stmtDetalle->rowCount() === 0) {
                    $verificarDetalle = $db->prepare(
                        'SELECT id FROM ordenes_compra_detalles WHERE id = ? AND orden_compra_id = ?'
                    );
                    $verificarDetalle->execute([(int) $detalle['id'], $ordenId]);
                    if (!$verificarDetalle->fetchColumn()) {
                        throw new RuntimeException('Uno de los Materiales ya no Pertenece a la Orden.');
                    }
                }
            }

            $stmtEstado = $db->prepare(
                "SELECT COUNT(*)
                 FROM ordenes_compra_detalles
                 WHERE orden_compra_id = ?
                   AND COALESCE(cantidad_confirmada, 0) + 0.00001 < cantidad_solicitada"
            );
            $stmtEstado->execute([$ordenId]);
            $estatus = (int) $stmtEstado->fetchColumn() === 0 ? 'Completa' : 'Parcial';

            $actualizarOrden = $db->prepare(
                'UPDATE ordenes_compra SET estatus = ?, metodo_entrega = ?, id_almacen = ? WHERE id = ?'
            );
            $actualizarOrden->execute([$estatus, $metodoEntrega, $almacenId, $ordenId]);

            if ($gestionaTransaccion) {
                $db->commit();
            }

            return $estatus;
        } catch (Throwable $e) {
            if ($gestionaTransaccion && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
