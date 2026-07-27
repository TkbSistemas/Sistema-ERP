<?php
// Archivo: /config/config.php
define('DB_CHARSET', 'utf8mb4'); 

 

//CONFIGURACION PARA SUBIR A PRODUCCION
/*
define('DB_HOST', 'localhost:3306');    //servidor local para desarrollo

//define('DB_HOST', '192.168.1.253');       //servidor local donde esta la base de datos de produccion, solo como dato, no se usará en desarrollo
define('DB_NAME', 'takab_inventario');
define('DB_USER', 'inventario_user');
define('DB_PASS', 'AdminTakab123');           //Cambia esto a la contraseña real



//CONFIGURACION LOCAL PARA DESARROLLO
define('DB_HOST', 'localhost:3308');    //servidor local para desarrollo

define('DB_NAME', 'takab_inventario');
define('DB_USER', 'root');
define('DB_PASS', '');           //Cambia esto a la contraseña real

/* 

define('DB_NAME', 'takab_inventario');
define('DB_USER', 'mau');
define('DB_PASS', 'mau');        

*/ 


//CONFIGURACION LOCAL PARA DESARROLLO - NUEVA
define('DB_HOST', 'localhost:3306');    //servidor local para desarrollo

define('DB_NAME', 'erp_takab');
define('DB_USER', 'root');
define('DB_PASS', '');           //Cambia esto a la contraseña real

// Opcional: Puerto (para XAMPP/WAMP suele ser 3306)
define('DB_PORT', 3306);

// Opciones extra
define('APP_NAME', 'Sistema de Inventario TAKAB');
define('APP_LANG', 'es_MX');
date_default_timezone_set('America/Mexico_City'); // esto es solo para PHP



