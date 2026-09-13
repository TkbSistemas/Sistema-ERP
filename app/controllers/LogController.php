<?php
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../models/Log.php';

class LogController
{
    public function index(): void
    {
        Session::requireLogin('Administrador');

        $db = Database::getInstance()->getConnection();

        $filtros = [
            'usuario_id' => $_GET['usuario_id'] ?? '',
            'accion'     => trim($_GET['accion'] ?? ''),
            'desde'      => $_GET['desde'] ?? '',
            'hasta'      => $_GET['hasta'] ?? '',
        ];

        $pagina    = max(1, (int) ($_GET['page'] ?? 1));
        $porPagina = 50;
        $offset    = ($pagina - 1) * $porPagina;

        $resultado    = Log::listar($filtros, $porPagina, $offset);
        $logs         = $resultado['items'];
        $total        = $resultado['total'];
        $totalPaginas = max(1, (int) ceil($total / $porPagina));

        $usuarios = $db->query('SELECT id, nombre FROM usuarios ORDER BY nombre ASC')->fetchAll();

        include __DIR__ . '/../views/logs/index.php';
    }
}
