<?php
// app/helpers/Database.php

require_once __DIR__ . '/../config/config.php';

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        try {
            $host = DB_HOST;
            $port = DB_PORT;
            if (strpos($host, ':') !== false) {
                [$h, $p] = explode(':', $host, 2);
                $host = $h;
                if (ctype_digit($p)) {
                    $port = (int) $p;
                }
            }
            $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
            $dsn = "mysql:host={$host};port={$port};dbname=" . DB_NAME . ";charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Intenta fijar la zona horaria con nombre; si MySQL no la conoce usa el desplazamiento numérico.
            try {
                $this->pdo->exec("SET time_zone = 'America/Mexico_City'");
            } catch (PDOException $tzException) {
                $offset = (new DateTime('now', new DateTimeZone('America/Mexico_City')))->format('P');
                $this->pdo->exec("SET time_zone = '{$offset}'");
            }
        } catch (PDOException $e) {
            die('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }
}
