<?php

require_once __DIR__ . '/../app/helpers/Session.php';
Session::start(); // Arranca la sesión de forma global para todo el sitio

// Capturamos la ruta que el usuario escribió en el navegador
$route = $_GET['route'] ?? 'home'; 

// Diccionario de rutas: mapeamos la URL con su Controlador y su Método
switch ($route) {
    // Rutas públicas (no requieren autenticación)
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
    //Rutas para el Administrador
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
    case 'producto_nuevo':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        (new InventarioController())->crearProducto();
        break;
    case 'inventario_entrada':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        (new InventarioController())->crearEntrada();
        break;
    case 'ajustes':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        (new InventarioController())->crearEntrada();
        break;
    case 'inventario_salida':
        require_once __DIR__ . '/../app/controllers/InventarioController.php';
        (new InventarioController())->crearSalida();
        break;
    //Rutas para el Almacén
    case 'dashboard_almacen':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        (new AlmacenController())->obtenerDashboardAlmacen();
        break;
    case 'solicitudes_material':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        (new AlmacenController())->obtenerSolicitudes();
        break;
    case 'etiquetas':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        (new AlmacenController())->crearEtiquetas();
        break;
    case 'prestamos_herramientas':
        require_once __DIR__ . '/../app/controllers/AlmacenController.php';
        (new AlmacenController())->pendientes();
        break;
    case 'rotacion_inventario':
        require_once __DIR__ . '/../app/controllers/ReporteController.php';
        (new ReporteController())->rotacion();
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
    default:
        // Si la ruta no existe, mandamos un error 404
        http_response_code(404);
        echo "Página no encontrada";
        break;
}