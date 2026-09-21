<?php

class CatalogoSat
{
    private const TABLA_PRODUCTOS = 'cfdi_40_productos_servicios';
    private const LIMITE_RESULTADOS = 20;

    private static ?PDO $conexion = null;

    public static function buscar(string $termino): array
    {
        $termino = trim(preg_replace('/\s+/u', ' ', $termino) ?? '');
        if (mb_strlen($termino, 'UTF-8') < 2) {
            return [];
        }

        $termino = mb_substr($termino, 0, 100, 'UTF-8');
        $tokens = array_slice(
            array_values(array_filter(preg_split('/\s+/u', $termino) ?: [])),
            0,
            6
        );

        $condicionesTexto = [];
        $parametros = [
            ':clave' => $termino,
            ':exacta' => $termino,
            ':frase' => $termino,
            ':hoy_desde' => date('Y-m-d'),
            ':hoy_hasta' => date('Y-m-d'),
        ];

        foreach ($tokens as $indice => $token) {
            $nombre = ':token_' . $indice;
            $condicionesTexto[] = "instr(lower(texto), lower({$nombre})) > 0";
            $parametros[$nombre] = $token;
        }

        $coincidenciaTexto = $condicionesTexto
            ? '(' . implode(' AND ', $condicionesTexto) . ')'
            : '0 = 1';

        $sql = 'SELECT id, texto, vigencia_desde, vigencia_hasta
                FROM ' . self::TABLA_PRODUCTOS . '
                WHERE (substr(id, 1, length(:clave)) = :clave OR ' . $coincidenciaTexto . ')
                  AND (vigencia_desde = \'\' OR vigencia_desde <= :hoy_desde)
                  AND (vigencia_hasta = \'\' OR vigencia_hasta >= :hoy_hasta)
                ORDER BY CASE
                    WHEN id = :exacta THEN 0
                    WHEN substr(id, 1, length(:clave)) = :clave THEN 1
                    WHEN instr(lower(texto), lower(:frase)) = 1 THEN 2
                    ELSE 3
                END, id
                LIMIT ' . self::LIMITE_RESULTADOS;

        $stmt = self::conexion()->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public static function obtenerVigente(string $clave): ?array
    {
        $clave = trim($clave);
        if ($clave === '') {
            return null;
        }

        $stmt = self::conexion()->prepare(
            'SELECT id, texto, vigencia_desde, vigencia_hasta
             FROM ' . self::TABLA_PRODUCTOS . '
             WHERE id = :clave
               AND (vigencia_desde = \'\' OR vigencia_desde <= :hoy_desde)
               AND (vigencia_hasta = \'\' OR vigencia_hasta >= :hoy_hasta)
             LIMIT 1'
        );
        $stmt->execute([
            ':clave' => $clave,
            ':hoy_desde' => date('Y-m-d'),
            ':hoy_hasta' => date('Y-m-d'),
        ]);
        $resultado = $stmt->fetch();

        return $resultado ?: null;
    }

    private static function conexion(): PDO
    {
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        $rutas = [
            __DIR__ . '/../../storage/database/sat-catalogs.db',
            __DIR__ . '/../../storage/database/sat-catalogos.db',
        ];
        $ruta = null;
        foreach ($rutas as $candidata) {
            if (is_file($candidata) && is_readable($candidata)) {
                $ruta = $candidata;
                break;
            }
        }

        if ($ruta === null) {
            throw new RuntimeException('No se Encontró la Base de Datos del Catálogo SAT.');
        }

        $conexion = new PDO('sqlite:' . $ruta, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $conexion->exec('PRAGMA query_only = ON');
        self::$conexion = $conexion;

        return self::$conexion;
    }
}
