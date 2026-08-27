<?php
require_once __DIR__ . '/../app/controllers/InventarioController.php';

$controller = new InventarioController();
$controller->imprimirReporte();
