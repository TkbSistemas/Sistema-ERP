<?php

class Navigation
{
    private const ITEMS = [
        'dashboard'           => [
            'label' => 'Dashboard',
            'icon'  => 'fa-solid fa-house',
            'href'  => 'dashboard.php',
            'roles' => ['Administrador', 'Almacen', 'Compras', 'Empleado'],
        ],
        'usuarios'            => [
            'label' => 'Gestió n de Usuarios',
            'icon'  => 'fa-solid fa-users-cog',
            'href'  => 'usuarios.php',
            'roles' => ['Administrador', 'Almacen', 'Compras', 'Empleado'],
        ],
        'productos'           => [
            'label' => 'Gestión de Productos',
            'icon'  => 'fa-solid fa-boxes-stacked',
            'href'  => 'productos.php',
            'roles' => ['Administrador', 'Almacen', 'Compras'],
        ],
        'ordenes'             => [
            'label' => 'Órdenes de compra',
            'icon'  => 'fa-solid fa-file-invoice-dollar',
            'href'  => 'ordenes_compra.php',
            'roles' => ['Administrador', 'Almacen', 'Compras'],
        ],
        'facturas'           => [
            'label' => 'Facturas de compra',
            'icon'  => 'fa-solid fa-file-circle-check',
            'href'  => 'facturas.php',
            'roles' => ['Administrador', 'Almacen', 'Compras'],
        ],
        'ordenes_crear'       => [
            'label' => 'Registrar orden',
            'icon'  => 'fa-solid fa-plus',
            'href'  => 'ordenes_compra_crear.php',
            'roles' => ['Compras'],
        ],
        'compras_proveedor'   => [
            'label' => 'Compras por proveedor',
            'icon'  => 'fa-solid fa-file-invoice',
            'href'  => 'compras_proveedor.php',
            'roles' => ['Administrador', 'Almacen', 'Compras'],
        ],
        'inventario'          => [
            'label' => 'Inventario',
            'icon'  => 'fa-solid fa-list-check',
            'href'  => 'inventario_actual.php',
            'roles' => ['Administrador', 'Almacen', 'Compras'],
        ],
        'prestamos'           => [
            'label' => 'Préstamos de herramientas',
            'icon'  => 'fa-solid fa-screwdriver-wrench',
            'href'  => 'prestamos_pendientes.php',
            'roles' => ['Administrador', 'Almacen'],
        ],
        'solicitudes_revisar' => [
            'label' => 'Solicitudes de material',
            'icon'  => 'fa-solid fa-inbox',
            'href'  => 'revisar_solicitudes.php',
            'roles' => ['Administrador', 'Almacen'],
        ],

        'reportes_rotacion'   => [
            'label' => 'Rotación de inventario',
            'icon'  => 'fa-solid fa-arrows-rotate',
            'href'  => 'reportes_rotacion.php',
            'roles' => ['Administrador', 'Almacen', 'Compras'],
        ],

        'reportes'            => [
            'label' => 'Reportes',
            'icon'  => 'fa-solid fa-chart-line',
            'href'  => 'reportes.php',
            'roles' => ['Administrador', 'Almacen', 'Compras'],
        ],
        'logs'                => [
            'label' => 'Bitácora',
            'icon'  => 'fa-solid fa-clipboard-list',
            'href'  => 'logs.php',
            'roles' => ['Administrador', 'Almacen', 'Compras', 'Empleado'],
        ],
        'configuracion'       => [
            'label' => 'Configuración',
            'icon'  => 'fa-solid fa-gear',
            'href'  => 'ajustes.php',
            'roles' => ['Administrador', 'Almacen'],
        ],
        'documentacion'       => [
            'label' => 'Documentación',
            'icon'  => 'fa-solid fa-book',
            'href'  => 'documentacion.php',
            'roles' => ['Administrador', 'Almacen', 'Compras', 'Empleado'],
        ],

        'solicitudes_crear'   => [
            'label' => 'Solicitar material (servicio)',
            'icon'  => 'fa-solid fa-plus-square',
            'href'  => 'solicitudes_crear.php',
            'roles' => ['Empleado', 'Almacen'],
        ],
        'solicitar_general'   => [
            'label' => 'Solicitud general',
            'icon'  => 'fa-solid fa-box-open',
            'href'  => 'solicitar_material_general.php',
            'roles' => ['Empleado'],
        ],
        'mis_solicitudes'     => [
            'label' => 'Mis solicitudes',
            'icon'  => 'fa-solid fa-clipboard-list',
            'href'  => 'mis_solicitudes.php',
            'roles' => ['Empleado', 'Almacen'],
        ],
    ];

    private const ROLE_ORDER = [
        'Administrador' => [
            'dashboard',
            'usuarios',
            'productos',
            'ordenes',
            'facturas',
            'ordenes_crear',
            'inventario',
            'prestamos',
            'compras_proveedor',
            'reportes_rotacion',
            'solicitudes_revisar',
            'reportes',
            'configuracion',
            'logs',
            'documentacion',
        ],
        'Almacen'       => [
            'dashboard',
            'productos',
            'ordenes',
            'facturas',
            'ordenes_crear',
            'inventario',
            'prestamos',
            'compras_proveedor',
            'reportes_rotacion',
            'solicitudes_crear',
            'mis_solicitudes',
            'solicitudes_revisar',
            'reportes',
            'configuracion',
            'documentacion',
        ],
        'Compras'       => [
            'dashboard',
            'ordenes',
            'ordenes_crear',
            'facturas',
            'productos',
            'inventario',
            'compras_proveedor',
            'reportes',
            'documentacion',
        ],
        'Empleado'      => [
            'dashboard',
            'solicitudes_crear',
            'solicitar_general',
            'mis_solicitudes',
            'documentacion',
        ],
    ];

    private const ACTIVE_MAP = [
        'dashboard.php'                  => 'dashboard',
        'usuarios.php'                   => 'usuarios',
        'usuarios_create.php'            => 'usuarios',
        'usuarios_edit.php'              => 'usuarios',
        'usuarios_delete.php'            => 'usuarios',
        'usuarios_setactive.php'         => 'usuarios',
        'productos.php'                  => 'productos',
        'productos_create.php'           => 'productos',
        'productos_edit.php'             => 'productos',
        'productos_view.php'             => 'productos',
        'productos_import.php'           => 'productos',
        'productos_template.php'         => 'productos',
        'facturas.php'                 => 'facturas',
        'facturas_create.php'          => 'facturas',
        'facturas_detalle.php'         => 'facturas',
        'ordenes_compra.php'             => 'ordenes',
        'ordenes_compra_crear.php'       => 'ordenes_crear',
        'ordenes_compra_detalle.php'     => 'ordenes',
        'ordenes_compra_editar.php'      => 'ordenes',
        'inventario_actual.php'          => 'inventario',
        'inventario_entradas.php'        => 'inventario',
        'inventario_salidas.php'         => 'inventario',
        'inventario_transferencias.php'  => 'inventario',
        'prestamos_pendientes.php'       => 'prestamos',
        'prestamos_historial.php'        => 'prestamos',
        'compras_proveedor.php'          => 'compras_proveedor',
        'reportes_rotacion.php'          => 'reportes_rotacion',
        'revisar_solicitudes.php'        => 'solicitudes_revisar',
        'solicitud_aprobar.php'          => 'solicitudes_revisar',
        'solicitud_detalle.php'          => 'solicitudes_revisar',
        'solicitud_entregar.php'         => 'solicitudes_revisar',
        'solicitudes.php'                => 'solicitudes_revisar',
        'reportes.php'                   => 'reportes',
        'reportes_rotacion.php'          => 'reportes_rotacion',
        'reportes_valor.php'             => 'reportes',
        'ajustes.php'                    => 'configuracion',
        'respaldo.php'                   => 'configuracion',
        'almacenes.php'                  => 'configuracion',
        'almacenes_create.php'           => 'configuracion',
        'almacenes_edit.php'             => 'configuracion',
        'almacenes_delete.php'           => 'configuracion',
        'categorias.php'                 => 'configuracion',
        'categorias_create.php'          => 'configuracion',
        'categorias_edit.php'            => 'configuracion',
        'categorias_delete.php'          => 'configuracion',
        'clientes.php'                   => 'configuracion',
        'clientes_create.php'            => 'configuracion',
        'clientes_edit.php'              => 'configuracion',
        'clientes_delete.php'            => 'configuracion',
        'proveedores.php'                => 'proveedores',
        'proveedores_create.php'         => 'proveedores',
        'proveedores_edit.php'           => 'proveedores',
        'proveedores_delete.php'         => 'proveedores',
        'unidades.php'                   => 'configuracion',
        'unidades_create.php'            => 'configuracion',
        'unidades_edit.php'              => 'configuracion',
        'unidades_delete.php'            => 'configuracion',
        'logs.php'                       => 'logs',
        'documentacion.php'              => 'documentacion',
        'solicitudes_crear.php'          => 'solicitudes_crear',
        'solicitar_material_general.php' => 'solicitar_general',
        'mis_solicitudes.php'            => 'mis_solicitudes',
    ];

    private const DEFAULT_BREADCRUMBS = [
        'dashboard'           => [],
        'usuarios'            => [
            ['label' => 'Usuarios'],
        ],
        'productos'           => [
            ['label' => 'Productos'],
        ],
        'facturas'           => [
            ['label' => 'Compras'],
            ['label' => 'Facturas'],
        ],
        'ordenes'             => [
            ['label' => 'Compras'],
            ['label' => 'Órdenes de compra'],
        ],
        'ordenes_crear'       => [
            ['label' => 'Compras', 'url' => 'ordenes_compra.php'],
            ['label' => 'Registrar orden'],
        ],
        'compras_proveedor'   => [
            ['label' => 'Compras'],
            ['label' => 'Compras por proveedor'],
        ],
         'proveedores'   => [
            ['label' => 'Compras'],
            ['label' => 'Proveedores'],
        ],
        'inventario'          => [
            ['label' => 'Inventario'],
        ],
        'prestamos'           => [
            ['label' => 'Inventario', 'url' => 'inventario_actual.php'],
            ['label' => 'Préstamos de herramientas'],
        ],
        'reportes_rotacion'   => [
            ['label' => 'Reportes'],
            ['label' => 'Rotación de inventario'],
        ],
        'solicitudes_revisar' => [
            ['label' => 'Solicitudes'],
            ['label' => 'Revisión de solicitudes'],
        ],
        'reportes'            => [
            ['label' => 'Reportes'],
        ],
        'configuracion'       => [
            ['label' => 'Configuración'],
        ],
        'documentacion'       => [
            ['label' => 'Documentación'],
        ],
        'logs'                => [
            ['label' => 'Bitácora del sistema'],
        ],
        'solicitudes_crear'   => [
            ['label' => 'Solicitudes'],
            ['label' => 'Nueva solicitud'],
        ],
        'solicitar_general'   => [
            ['label' => 'Solicitudes'],
            ['label' => 'Solicitud general'],
        ],
        'mis_solicitudes'     => [
            ['label' => 'Solicitudes'],
            ['label' => 'Mis solicitudes'],
        ],
    ];

    public static function sidebarItems(string $role): array
    {
        $role  = self::normalizeRole($role);
        $order = self::ROLE_ORDER[$role] ?? self::ROLE_ORDER['Empleado'];
        $items = [];

        foreach ($order as $key) {
            $config = self::ITEMS[$key] ?? null;
            if ($config === null) {
                continue;
            }
            if (! in_array($role, $config['roles'], true)) {
                continue;
            }
            $items[$key] = $config;
        }

        return $items;
    }

    public static function activeKey(): string
    {
        static $active;
        if ($active !== null) {
            return $active;
        }

        $script = basename($_SERVER['PHP_SELF'] ?? '') ?: '';
        $active = self::ACTIVE_MAP[$script] ?? 'dashboard';

        return $active;
    }

    public static function renderSidebar(string $role, string $nombre): string
    {
        $items  = self::sidebarItems($role);
        $active = self::activeKey();

        $html   = [];
        $html[] = '<aside class="sidebar">';
        $html[] = '    <div class="sidebar-header">';
        $html[] = '        <div class="login-logo"><img src="assets/images/icono_takab.png" alt="logo TAKAB" width="90" height="55"></div>';
        $html[] = '        <div>';
        $html[] = '            <div class="sidebar-title">TAKAB</div>';
        $html[] = '            <div class="sidebar-desc">Dashboard</div>';
        $html[] = '        </div>';
        $html[] = '    </div>';
        $html[] = '    <nav class="sidebar-nav">';

        foreach ($items as $key => $item) {
            $isActive = $key === $active ? ' class="active"' : '';
            $html[]   = sprintf(
                '        <a href="%s"%s><i class="%s"></i> %s</a>',
                htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'),
                $isActive,
                htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8')
            );
        }

        $html[] = '        <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesi?n</a>';
        $html[] = '    </nav>';
        $html[] = '</aside>';

        return implode("\n", $html);
    }

    public static function renderBreadcrumbs(?array $extra = null): string
    {
        $active = self::activeKey();
        $base   = self::DEFAULT_BREADCRUMBS[$active] ?? [];

        if (! empty($extra)) {
            foreach ($extra as $crumb) {
                if (is_string($crumb)) {
                    $base[] = ['label' => $crumb];
                } elseif (is_array($crumb) && isset($crumb['label'])) {
                    $base[] = $crumb;
                }
            }
        }

        if (empty($base)) {
            return '';
        }

        $parts   = [];
        $parts[] = '<nav class="breadcrumbs" aria-label="Breadcrumb">';
        $parts[] = '    <ol>';

        $lastIndex = count($base) - 1;
        foreach ($base as $index => $crumb) {
            $label  = htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8');
            $isLast = $index === $lastIndex;

            if (! $isLast && ! empty($crumb['url'])) {
                $url     = htmlspecialchars($crumb['url'], ENT_QUOTES, 'UTF-8');
                $parts[] = sprintf('        <li><a href="%s">%s</a></li>', $url, $label);
            } else {
                $parts[] = sprintf('        <li%s>%s</li>', $isLast ? ' class="current"' : '', $label);
            }
        }

        $parts[] = '    </ol>';
        $parts[] = '</nav>';

        return implode("\n", $parts);
    }

    public static function normalizeRole(?string $role): string
    {
        $role = $role ?? '';
        $role = trim($role);
        if ($role === '') {
            return 'Empleado';
        }

        $roleMap = [
            'Almacén' => 'Almacen',
            'almacén' => 'Almacen',
            'almacen' => 'Almacen',
        ];
        if (isset($roleMap[$role])) {
            $role = $roleMap[$role];
        }

        $valid = array_keys(self::ROLE_ORDER);
        return in_array($role, $valid, true) ? $role : 'Empleado';
    }
}

