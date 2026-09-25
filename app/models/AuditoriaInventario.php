<?php
require_once __DIR__ . '/../helpers/Database.php';

class AuditoriaInventario
{
    public static function obtenerProductos(int $almacenId, int $categoriaId, string $tipo = ''): array
    {
        if ($almacenId <= 0 || $categoriaId <= 0) {
            return [];
        }

        $db = Database::getInstance()->getConnection();
        $sql = "SELECT
                    p.id,
                    p.nomenclatura,
                    p.nombre,
                    p.marca,
                    p.modelo,
                    p.tipo,
                    COALESCE(sa.stock, 0) AS stock_actual,
                    um.nombre AS unidad_medida_nombre,
                    um.apodo AS unidad_abreviacion
                FROM inventario p
                LEFT JOIN stock_almacen sa
                    ON sa.producto_id = p.id AND sa.almacen_id = ?
                LEFT JOIN catalogo_unidades_medida um ON um.id = p.unidad_medida_id
                WHERE p.categoria_id = ?
                  AND p.activo = 1";
        $parametros = [$almacenId, $categoriaId];

        if ($tipo !== '') {
            $sql .= ' AND p.tipo = ?';
            $parametros[] = $tipo;
        }

        $sql .= ' ORDER BY p.nombre ASC, p.id ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function registrar(
        int $almacenId,
        int $categoriaId,
        string $tipo,
        int $responsableId,
        array $conteosFisicos
    ): array {
        if ($almacenId <= 0 || $categoriaId <= 0 || $responsableId <= 0) {
            throw new InvalidArgumentException('El Almacén, la Categoría y el Responsable son Obligatorios.');
        }

        $db = Database::getInstance()->getConnection();
        $nombreLock = 'takab_auditoria_inventario_folio';
        $lockObtenido = false;
        $gestionaTransaccion = !$db->inTransaction();

        try {
            $stmtLock = $db->prepare('SELECT GET_LOCK(?, 10)');
            $stmtLock->execute([$nombreLock]);
            $lockObtenido = (int) $stmtLock->fetchColumn() === 1;
            if (!$lockObtenido) {
                throw new RuntimeException('No Fue Posible Reservar el Folio de la Auditoría. Intenta Nuevamente.');
            }

            if ($gestionaTransaccion) {
                $db->beginTransaction();
            }

            $stmtAlmacen = $db->prepare('SELECT id, nombre FROM almacenes WHERE id = ? FOR UPDATE');
            $stmtAlmacen->execute([$almacenId]);
            $almacen = $stmtAlmacen->fetch(PDO::FETCH_ASSOC);
            if (!$almacen) {
                throw new RuntimeException('El Almacén Seleccionado no Existe.');
            }

            $stmtCategoria = $db->prepare('SELECT id, nombre FROM catalogo_categorias_inventario WHERE id = ?');
            $stmtCategoria->execute([$categoriaId]);
            $categoria = $stmtCategoria->fetch(PDO::FETCH_ASSOC);
            if (!$categoria) {
                throw new RuntimeException('La Categoría Seleccionada no Existe.');
            }

            $sqlProductos = "SELECT
                                p.id,
                                p.nomenclatura,
                                p.nombre,
                                p.marca,
                                p.modelo,
                                p.tipo,
                                COALESCE(um.apodo, um.nombre, '') AS unidad,
                                COALESCE(sa.stock, 0) AS stock_teorico
                             FROM inventario p
                             LEFT JOIN stock_almacen sa
                                ON sa.producto_id = p.id AND sa.almacen_id = ?
                             LEFT JOIN catalogo_unidades_medida um ON um.id = p.unidad_medida_id
                             WHERE p.categoria_id = ?
                               AND p.activo = 1";
            $parametrosProductos = [$almacenId, $categoriaId];
            if ($tipo !== '') {
                $sqlProductos .= ' AND p.tipo = ?';
                $parametrosProductos[] = $tipo;
            }
            $sqlProductos .= ' ORDER BY p.nombre ASC, p.id ASC FOR UPDATE';

            $stmtProductos = $db->prepare($sqlProductos);
            $stmtProductos->execute($parametrosProductos);
            $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);
            if ($productos === []) {
                throw new RuntimeException('No hay Productos para Auditar con los Filtros Seleccionados.');
            }

            $lineas = [];
            $faltantes = [];
            $sobrantes = [];
            foreach ($productos as $indice => $producto) {
                $productoId = (int) $producto['id'];
                if (!array_key_exists($productoId, $conteosFisicos) || !is_numeric($conteosFisicos[$productoId])) {
                    throw new InvalidArgumentException('Captura el Stock Físico de la Partida ' . ($indice + 1) . '.');
                }

                $stockFisico = round((float) $conteosFisicos[$productoId], 2);
                if ($stockFisico < 0) {
                    throw new InvalidArgumentException('El Stock Físico de la Partida ' . ($indice + 1) . ' no Puede ser Negativo.');
                }

                $stockTeorico = round((float) $producto['stock_teorico'], 2);
                $diferencia = round($stockFisico - $stockTeorico, 2);
                $resultado = abs($diferencia) < 0.00001
                    ? 'Coincide'
                    : ($diferencia < 0 ? 'Faltante' : 'Sobrante');

                $linea = [
                    'producto_id' => $productoId,
                    'nomenclatura' => (string) ($producto['nomenclatura'] ?? ''),
                    'nombre' => (string) ($producto['nombre'] ?? ''),
                    'marca' => (string) ($producto['marca'] ?? ''),
                    'modelo' => (string) ($producto['modelo'] ?? ''),
                    'tipo' => (string) ($producto['tipo'] ?? ''),
                    'unidad' => (string) ($producto['unidad'] ?? ''),
                    'stock_teorico' => $stockTeorico,
                    'stock_fisico' => $stockFisico,
                    'diferencia' => $diferencia,
                    'resultado' => $resultado,
                ];
                $lineas[] = $linea;

                if ($diferencia < -0.00001) {
                    $faltantes[] = $linea;
                } elseif ($diferencia > 0.00001) {
                    $sobrantes[] = $linea;
                }
            }

            $folio = self::generarFolio($db);

            // La recepción funciona como cabecera permanente de toda auditoría,
            // incluso cuando el conteo coincide y no hay ajustes de entrada.
            $stmtRecepcion = $db->prepare(
                "INSERT INTO recepciones_almacen
                    (folio_entrada, orden_id, estatus, fecha_recepcion, responsable_id, created_at)
                 VALUES (?, NULL, 'Auditoría', CURDATE(), ?, NOW())"
            );
            $stmtRecepcion->execute([$folio, $responsableId]);
            $recepcionId = (int) $db->lastInsertId();

            $stmtRecepcionDetalle = $db->prepare(
                'INSERT INTO recepciones_detalles
                    (recepcion_id, producto_id, detalle_orden_id, cantidad_recibida)
                 VALUES (?, ?, NULL, ?)'
            );
            foreach ($sobrantes as $linea) {
                $stmtRecepcionDetalle->execute([
                    $recepcionId,
                    $linea['producto_id'],
                    $linea['diferencia'],
                ]);
            }

            $solicitudBajaId = null;
            if ($faltantes !== []) {
                $stmtBaja = $db->prepare(
                    "INSERT INTO solicitudes_bajas
                        (folio, estatus, solicitante_id, almacen_id, updated_at, created_at)
                     VALUES (?, 'Auditoría', ?, ?, NOW(), NOW())"
                );
                $stmtBaja->execute([$folio, $responsableId, $almacenId]);
                $solicitudBajaId = (int) $db->lastInsertId();

                $stmtBajaDetalle = $db->prepare(
                    'INSERT INTO solicitudes_bajas_detalles
                        (solicitud_id, producto_id, cantidad, motivos, created_at)
                     VALUES (?, ?, ?, ?, NOW())'
                );
                foreach ($faltantes as $linea) {
                    $stmtBajaDetalle->execute([
                        $solicitudBajaId,
                        $linea['producto_id'],
                        abs((float) $linea['diferencia']),
                        'Ajuste por Auditoría de Inventario',
                    ]);
                }
            }

            $guardarStock = $db->prepare(
                'INSERT INTO stock_almacen (producto_id, almacen_id, stock)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE stock = VALUES(stock)'
            );
            $registrarMovimiento = $db->prepare(
                'INSERT INTO movimientos_inventario
                    (producto_id, folio_solicitud, tipo, cantidad, responsable_id, almacen_id, observaciones, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );

            $movimientos = 0;
            foreach ($lineas as $linea) {
                if (abs((float) $linea['diferencia']) < 0.00001) {
                    continue;
                }

                $guardarStock->execute([
                    $linea['producto_id'],
                    $almacenId,
                    $linea['stock_fisico'],
                ]);

                $tipoMovimiento = $linea['diferencia'] > 0 ? 'Entrada' : 'Salida';
                $registrarMovimiento->execute([
                    $linea['producto_id'],
                    $folio,
                    $tipoMovimiento,
                    abs((float) $linea['diferencia']),
                    $responsableId,
                    $almacenId,
                    'Ajuste por Auditoría de Inventario',
                ]);
                $movimientos++;
            }

            if ($gestionaTransaccion) {
                $db->commit();
            }

            return [
                'folio' => $folio,
                'fecha' => date('Y-m-d H:i:s'),
                'almacen' => (string) $almacen['nombre'],
                'categoria' => (string) $categoria['nombre'],
                'tipo' => $tipo !== '' ? $tipo : 'Todos',
                'recepcion_id' => $recepcionId,
                'solicitud_baja_id' => $solicitudBajaId,
                'lineas' => $lineas,
                'total_partidas' => count($lineas),
                'coincidencias' => count($lineas) - count($faltantes) - count($sobrantes),
                'faltantes' => count($faltantes),
                'sobrantes' => count($sobrantes),
                'movimientos' => $movimientos,
            ];
        } catch (Throwable $e) {
            if ($gestionaTransaccion && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        } finally {
            if ($lockObtenido) {
                try {
                    $stmtRelease = $db->prepare('SELECT RELEASE_LOCK(?)');
                    $stmtRelease->execute([$nombreLock]);
                } catch (Throwable $e) {
                    // La conexión libera el lock al cerrarse; no ocultar el resultado principal.
                }
            }
        }
    }

    private static function generarFolio(PDO $db): string
    {
        $prefijo = 'TAKAB-AU-' . date('Ym') . '-';
        $stmt = $db->prepare(
            "SELECT MAX(numero) FROM (
                SELECT CAST(SUBSTRING_INDEX(folio_entrada, '-', -1) AS UNSIGNED) AS numero
                FROM recepciones_almacen
                WHERE folio_entrada LIKE ?
                UNION ALL
                SELECT CAST(SUBSTRING_INDEX(folio, '-', -1) AS UNSIGNED) AS numero
                FROM solicitudes_bajas
                WHERE folio LIKE ?
             ) folios_auditoria"
        );
        $stmt->execute([$prefijo . '%', $prefijo . '%']);
        $siguiente = (int) $stmt->fetchColumn() + 1;

        return $prefijo . str_pad((string) $siguiente, 5, '0', STR_PAD_LEFT);
    }
}
