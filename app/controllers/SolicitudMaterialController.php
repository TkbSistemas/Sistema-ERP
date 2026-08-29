<?php
    require_once __DIR__ . '/../models/SolicitudMaterial.php';
    require_once __DIR__ . '/../models/Producto.php';
    require_once __DIR__ . '/../helpers/Session.php';
    require_once __DIR__ . '/../models/Prestamo.php';
    require_once __DIR__ . '/../helpers/Database.php';

    class SolicitudMaterialController
    {
        // Formulario de solicitud para inventario (proyecto/servicio)
        public function crear()
        {
            $this->crearSolicitud(false);
        }

        // Formulario de solicitud general (material no en inventario)
        public function crearGeneral()
        {
            $this->crearSolicitud(true);
        }

        // Logica compartida
        private function crearSolicitud($isGeneral = false)
        {
            Session::requireLogin(['Empleado', 'Almacen']);
            $productos_consumibles  = $isGeneral ? [] : Producto::all(['tipo' => 'Consumible']);
            $productos_herramientas = $isGeneral ? [] : Producto::all(['tipo' => 'Herramienta']);
            $msg                    = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                    $msg = 'Token CSRF invalido.';
                    if ($isGeneral) {
                        include __DIR__ . '/../views/solicitudes/crear_general.php';
                    } else {
                        include __DIR__ . '/../views/solicitudes/crear.php';
                    }
                    return;
                }

                $comentario     = trim($_POST['comentario']);
                $observacion    = trim($_POST['observacion'] ?? '');
                $tipo_solicitud = $isGeneral ? 'General' : 'Servicio';
                $detalles       = [];
                $extras         = [];
                $tiposProducto  = [];

                if (empty($comentario)) {
                    $msg = $isGeneral
                        ? 'Debes especificar el motivo de la solicitud.'
                        : 'Debes especificar para que proyecto/destino se va a utilizar el material.';
                } elseif (empty($_POST['material'])) {
                    $msg = 'Debes agregar al menos un material o herramienta a la solicitud.';
                } else {
                    $materiales = json_decode($_POST['material'], true);
                    foreach ($materiales as $item) {
                        if ($isGeneral) {
                            if (! empty($item['producto_nombre']) && (float) $item['cantidad'] > 0) {
                                $extras[] = [
                                    'descripcion' => $item['producto_nombre'],
                                    'cantidad'    => $item['cantidad'],
                                    'observacion' => $item['observacion'] ?? '',
                                ];
                            }
                        } else {
                            if (($item['tipo'] ?? '') === 'Extra') {
                                $extras[] = [
                                    'descripcion' => $item['producto_nombre'],
                                    'cantidad'    => $item['cantidad'],
                                    'observacion' => $item['observacion'] ?? '',
                                ];
                            } elseif (! empty($item['producto_id']) && (float) $item['cantidad'] > 0) {
                                $detalles[] = [
                                    'producto_id' => $item['producto_id'],
                                    'cantidad'    => $item['cantidad'],
                                    'observacion' => $item['observacion'] ?? '',
                                ];
                                $tipoItem = $item['tipo'] ?? null;
                                if ($tipoItem && !in_array($tipoItem, $tiposProducto, true)) {
                                    $tiposProducto[] = $tipoItem;
                                }
                            }
                        }
                    }

                    if (count($detalles) === 0 && count($extras) === 0) {
                        $msg = 'Debes agregar al menos un material, herramienta o material extra valido.';
                    } else {
                        if (count($tiposProducto) === 0) {
                            $tipo = 'Consumible';
                        } elseif (count($tiposProducto) === 1) {
                            $tipo = $tiposProducto[0];
                        } else {
                            $tipo = 'Equipo';
                        }

                        $data = [
                            'usuario_id'     => $_SESSION['user_id'],
                            'tipo'           => $tipo,
                            'tipo_solicitud' => $tipo_solicitud,
                            'comentario'     => $comentario,
                            'observacion'    => $observacion,
                            'extras'         => $extras,
                        ];
                        SolicitudMaterial::create($data, $detalles);
                        $msg = 'Solicitud enviada correctamente.';
                    }
                }
            }

            if ($isGeneral) {
                include __DIR__ . '/../views/solicitudes/crear_general.php';
            } else {
                include __DIR__ . '/../views/solicitudes/crear.php';
            }
        }

        public function historial()
        {
            Session::requireLogin(['Empleado', 'Almacen']);
            
            $filtros = [
                'search'       => $_GET['search'] ?? '',
                'fecha_inicio' => $_GET['fecha_inicio'] ?? '',
                'fecha_fin'    => $_GET['fecha_fin'] ?? '',
                'estado'       => $_GET['estado'] ?? ''
            ];
            
            $solicitudes = SolicitudMaterial::historialPorUsuario($_SESSION['user_id'], $filtros);
            include __DIR__ . '/../views/solicitudes/historial.php';
        }

        public function detalle($id)
        {
            Session::requireLogin(['Empleado', 'Almacen']);
            $solicitud = SolicitudMaterial::find($id, $_SESSION['user_id']);
            $detalles  = $solicitud ? SolicitudMaterial::detalles($id) : [];
            include __DIR__ . '/../views/solicitudes/detalle.php';
        }

        // Listar solicitudes pendientes para revision/entrega
        public function revisar()
        {
            Session::requireLogin(['Administrador', 'Almacen']);
            $solicitudes = SolicitudMaterial::listarPendientes(['pendiente', 'aprobada']);
            include __DIR__ . '/../views/solicitudes/revisar.php';
        }

        // Ver y aprobar/rechazar solicitud
        public function aprobar($id)
        {
            Session::requireLogin(['Administrador', 'Almacen']);
            $solicitud = SolicitudMaterial::find($id);
            $detalles  = $solicitud ? SolicitudMaterial::detalles($id) : [];
            $msg       = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                    $msg = 'Token CSRF invalido.';
                } else {
                    $accion      = $_POST['accion'] ?? '';
                    $observacion = trim($_POST['observacion'] ?? '');
                    if ($accion === 'aprobar') {
                        SolicitudMaterial::actualizarEstado($id, 'aprobada', $_SESSION['user_id'], $observacion);
                        $msg = 'Solicitud aprobada correctamente.';
                    } elseif ($accion === 'rechazar') {
                        SolicitudMaterial::actualizarEstado($id, 'rechazada', $_SESSION['user_id'], $observacion);
                        $msg = 'Solicitud rechazada.';
                    }
                }
                $solicitud = SolicitudMaterial::find($id);
            }
            include __DIR__ . '/../views/solicitudes/aprobar.php';
        }

        // Entregar materiales (solo si ya esta aprobada)
        public function entregar($id)
        {
            Session::requireLogin(["Administrador", "Almacen"]);
            $solicitud = SolicitudMaterial::find($id);
            $detalles  = $solicitud ? SolicitudMaterial::detalles($id) : [];
            $msg       = '';
            if (! $solicitud || strtolower($solicitud['estado']) !== 'aprobada') {
                $msg = 'La solicitud no esta aprobada o no existe.';
                include __DIR__ . '/../views/solicitudes/entregar.php';
                return;
            }

            $productosCache = [];
            $obtenerProducto = function ($productoId) use (&$productosCache) {
                $productoId = (int) $productoId;
                if ($productoId <= 0) {
                    return null;
                }
                if (!array_key_exists($productoId, $productosCache)) {
                    $productosCache[$productoId] = Producto::find($productoId);
                }
                return $productosCache[$productoId];
            };

            $usuarioNombre = null;
            if (!empty($solicitud['usuario_id'])) {
                $db = Database::getInstance()->getConnection();
                $stmtUsuario = $db->prepare("SELECT nombre_completo FROM usuarios WHERE id = ?");
                $stmtUsuario->execute([(int) $solicitud['usuario_id']]);
                $usuarioNombre = $stmtUsuario->fetchColumn() ?: null;
            }

            $consumibles = [];
            $herramientas = [];
            foreach ($detalles as $detalle) {
                $producto = $obtenerProducto($detalle['producto_id'] ?? 0);
                if (! $producto) {
                    continue;
                }
                $unidad = $producto['unidad_abreviacion'] ?? $producto['unidad_medida_nombre'] ?? '';
                $item = [
                    'cantidad'      => (float) ($detalle['cantidad'] ?? 0),
                    'unidad'        => $unidad,
                    'descripcion'   => $producto['nombre'] ?? ($detalle['producto'] ?? ''),
                    'marca'         => $producto['marca'] ?? '',
                    'especificacion'=> $producto['especificaciones_tecnicas'] ?? '',
                ];
                if (($producto['tipo'] ?? '') === 'Consumible') {
                    $consumibles[] = $item;
                } else {
                    $herramientas[] = $item;
                }
            }

            if (!empty($_GET['formato']) && $_GET['formato'] === 'salida') {
                $formatoFecha = date('d/m/Y');
                include __DIR__ . '/../views/solicitudes/salida_almacen.php';
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (! Session::checkCsrf($_POST['csrf'] ?? '')) {
                    $msg = 'Token CSRF invalido.';
                    include __DIR__ . '/../views/solicitudes/entregar.php';
                    return;
                }

                $observacion = trim($_POST['observacion'] ?? '');
                $fechaEstim  = trim($_POST['fecha_estimada_devolucion'] ?? '');

                $tieneHerramienta = false;
                foreach ($detalles as $d) {
                    if ($d['producto_id']) {
                        $productoTmp = $obtenerProducto($d['producto_id']);
                        if ($productoTmp && $productoTmp['tipo'] === 'Herramienta') {$tieneHerramienta = true;
                            break;}
                    }
                }
                if ($tieneHerramienta && $fechaEstim === '') {
                    $msg = 'Debes indicar la fecha estimada de devolucion para las herramientas.';
                    include __DIR__ . '/../views/solicitudes/entregar.php';
                    return;
                }

                $faltantes = [];
                foreach ($detalles as $d) {
                    $producto = $obtenerProducto($d['producto_id']);
                    if (! $producto) {
                        $faltantes[] = 'Producto #' . (int) $d['producto_id'] . ' (no encontrado)';
                        continue;
                    }
                    $cantidad = (float) $d['cantidad'];
                    $stockDisponible = Producto::stockTotal((int) $d['producto_id']);
                    if ($cantidad > $stockDisponible) {
                        $faltantes[] = ($producto['nombre'] ?? 'Producto') . ' (solicitado ' . $cantidad . ', disponible ' . $stockDisponible . ')';
                    }
                }
                if (!empty($faltantes)) {
                    $msg = 'No hay stock suficiente para entregar: ' . implode('; ', $faltantes) . '.';
                    include __DIR__ . '/../views/solicitudes/entregar.php';
                    return;
                }

                if ($solicitud['tipo_solicitud'] === 'Servicio' || $solicitud['tipo_solicitud'] === 'Mixta') {
                    foreach ($detalles as $d) {
                        if ($d['producto_id']) {
                            $producto = $obtenerProducto($d['producto_id']);
                            if ($producto && $producto['tipo'] === 'Herramienta') {
                                Prestamo::crear([
                                    'producto_id'               => $d['producto_id'],
                                    'empleado_id'               => $solicitud['usuario_id'],
                                    'autorizado_by_user_id'     => $_SESSION['user_id'],
                                    'fecha_prestamo'            => date('Y-m-d H:i:s'),
                                    'fecha_estimada_devolucion' => $fechaEstim,
                                    'estado'                    => 'Prestado',
                                    'observaciones'             => $d['observacion'],
                                ], $d['cantidad']);
                                Producto::restarStock($d['producto_id'], $d['cantidad'], (int) ($producto['almacen_id'] ?? 0));
                            } else {
                                Producto::restarStock($d['producto_id'], $d['cantidad'], (int) ($producto['almacen_id'] ?? 0));
                            }
                        }
                    }
                }

                SolicitudMaterial::actualizarEstado($id, 'entregada', $_SESSION['user_id'], $observacion);
                $msg       = 'Solicitud entregada correctamente.';
                $solicitud = SolicitudMaterial::find($id);
                $detalles  = $solicitud ? SolicitudMaterial::detalles($id) : [];
            }
            include __DIR__ . '/../views/solicitudes/entregar.php';
        }
}


