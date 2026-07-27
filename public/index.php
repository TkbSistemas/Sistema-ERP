<?php

require_once __DIR__ . '/../app/helpers/Session.php';
Session::start(); // Arranca la sesión de forma global para todo el sitio

// Capturamos la ruta que el usuario escribió en el navegador
$route = $_GET['route'] ?? 'home'; 

// Diccionario de rutas: mapeamos la URL con su Controlador y su Método
switch ($route) {
    // ================================== Rutas públicas (no requieren autenticación) =================================================
     case 'home':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        (new AuthController())->index();
        break;
    case 'login':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        (new AuthController())->login();
        break;
    case 'index':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        (new AuthController())->index();
        break;
    case 'logout':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        (new AuthController())->logout();
        break;
    case 'construccion':
       require_once __DIR__ . '/../app/controllers/AuthController.php';
        (new AuthController())->enConstruccion();
        break;    
    // ================================= Rutas para el Administrador =======================================================
    case 'dashboard_admin':
        require_once __DIR__ . '/../app/controllers/DashboardController.php';
        (new DashboardController())->obtenerDashboardAdmin();
        break;
    //Rutas para el Inventario
    case 'inventario':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        (new InventarioController())->actual();
        break;
    case 'catalogo_productos':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        (new InventarioController())->obtenerCatalogo();
        break;
    case 'descargar_plantilla':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        (new InventarioController())->downloadTemplate();
        break;
    case 'importar_catalogo':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        (new InventarioController())->importar();
        break;
    case 'view_producto_nuevo':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        (new InventarioController())->crearProducto();
        break;
    case 'producto_nuevo':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        (new InventarioController())->crearProducto();
        break;
    case 'ver_producto':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        $id = $_GET['id'] ?? 0;
        (new InventarioController())->ver_producto($id);
        break;
    case 'eliminar_producto':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        (new InventarioController())->eliminarProducto($_POST['id'] ?? 0, $_POST['active'] ?? 0);
        break;
    case 'editar_producto':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        $id = $_GET['id'] ?? 0;
        (new InventarioController())->editarProducto($id);
        break;
    case 'etiqueta_producto':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        $id = $_GET['id'] ?? 0;
        (new InventarioController())->obtenerEtiqueta($id);
        break;
    case 'rotacion_inventario':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        (new InventarioController())->obtenerRotacion();
        break;
    case 'ajustes':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        (new InventarioController())->crearEntrada();
        break;
    // ===================================== Rutas para el Almacén ======================================================
    case 'dashboard_almacen':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        (new AlmacenController())->obtenerDashboardAlmacen();
        break;
    case 'solicitudes_material':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        (new AlmacenController())->obtenerSolicitudesMaterial();
        break;
    case 'aprobar_solicitud_materiales':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        (new AlmacenController())->aprobarSolicitud();
        break;
    case 'rechazar_solicitud_materiales':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        (new AlmacenController())->rechazarSolicitud();
        break;
    case 'ver_solicitud_material':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        (new AlmacenController())->verSolicitudMaterial($_GET['id'] ?? 0);
        break;
    case 'registrar_entrada':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        (new AlmacenController())->viewRegistrarEntrada();
        break;
    case 'entrada_rapida':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        (new AlmacenController())->viewRegistrarEntradaRapida();
        break;
    case 'registrar_entrada_rapida':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        (new AlmacenController())->registrarEntradaRapida();
        break;
    case 'etiquetas':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        (new AlmacenController())->crearEtiquetas();
        break;
    case 'prestamos_herramientas':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        (new AlmacenController())->pendientes();
        break;
    case 'registrar_salida':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        (new AlmacenController())->viewRegistrarSalida();
        break;
    case 'crear_solicitud_salida':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        //(new AlmacenController())->();
        break;
    case 'reportes':
        require_once __DIR__ . '/../app/controllers/ReporteController.php';
        (new ReporteController())->index();
        break;
    case 'revisar_solicitudes':
        require_once __DIR__ . '/../app/controllers/SolicitudMaterialController.php';
        (new SolicitudMaterialController())->revisar();
        break;
    case 'gestion_usuarios':
        require_once __DIR__ . '/../app/controllers/UsuarioController.php';
        (new UsuarioController())->index();
        break;
    // ===================================== Rutas para Compras  ======================================================
    case 'dashboard_compras':
        require_once __DIR__ . '/../app/controllers/ComprasController.php';
        (new ComprasController())->obtenerDashboardCompras();
        break;
    default:
        // Si la ruta no existe, mandamos un error 404
        http_response_code(404);
        echo "Página no encontrada";
        break;
}