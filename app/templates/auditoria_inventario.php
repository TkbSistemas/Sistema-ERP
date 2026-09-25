<?php

class AuditoriaInventarioTemplate
{
    private const BLUE = [0.0, 0.44, 0.75];
    private const NAVY = [0.0, 0.13, 0.38];
    private const BLACK = [0.0, 0.0, 0.0];
    private const LIGHT_GRAY = [0.97, 0.98, 0.99];
    private const TABLE_GRAY = [0.95, 0.95, 0.95];
    private const BORDER = [0.75, 0.75, 0.75];

    public static function renderizarPagina(
        array $auditoria,
        string $responsable,
        array $lineas,
        int $pagina,
        int $totalPaginas,
        bool $conLogo
    ): string {
        $contenido = '';
        if ($conLogo) {
            $contenido .= "q 44 0 0 44 36 718 cm /Im1 Do Q\n";
        }

        $contenido .= self::textoCentrado(306, 744, 'AUDITORÍA DE INVENTARIO', 17, 'F2', self::BLUE);
        $contenido .= self::textoDerecha(
            576,
            744,
            self::recortar((string) ($auditoria['folio'] ?? 'Sin Folio'), 28),
            8,
            'F2',
            self::BLACK
        );
        $contenido .= self::textoCentrado(
            306,
            718,
            'TAKAB, SISTEMAS TECNOLOGICOS INTELIGENTES & SERVICIOS INTEGRALES, S DE RL DE CV.',
            7.4,
            'F2',
            self::NAVY
        );
        $contenido .= self::linea(36, 701, 576, 701, self::BLUE, 1.5);

        $contenido .= self::rectangulo(36, 598, 540, 88, self::LIGHT_GRAY, self::BORDER);
        $contenido .= self::etiquetaBloque(48, 671, 246, 'ALMACÉN', (string) ($auditoria['almacen'] ?? '-'), 42);
        $contenido .= self::etiquetaBloque(318, 671, 246, 'CATEGORÍA', (string) ($auditoria['categoria'] ?? '-'), 42);
        $contenido .= self::etiquetaBloque(48, 644, 246, 'TIPO', (string) ($auditoria['tipo'] ?? 'Todos'), 42);
        $contenido .= self::etiquetaBloque(
            318,
            644,
            246,
            'RESPONSABLE',
            $responsable !== '' ? $responsable : 'Sin Registro',
            42
        );
        $fecha = !empty($auditoria['fecha'])
            ? date('d/m/Y H:i', strtotime((string) $auditoria['fecha']))
            : date('d/m/Y H:i');
        $contenido .= self::etiquetaBloque(48, 617, 516, 'FECHA DE AUDITORÍA', $fecha, 84);

        $contenido .= self::rectangulo(36, 562, 540, 22, self::BLUE, self::BLUE);
        $contenido .= self::texto(44, 569, 'RESUMEN DE LA AUDITORÍA', 8.3, 'F2', [1, 1, 1]);
        $contenido .= self::rectangulo(36, 534, 540, 24, [0.93, 0.96, 1.0], self::BORDER);
        $contenido .= self::metrica(48, 542, 'PARTIDAS', (int) ($auditoria['total_partidas'] ?? 0));
        $contenido .= self::metrica(180, 542, 'COINCIDEN', (int) ($auditoria['coincidencias'] ?? 0));
        $contenido .= self::metrica(312, 542, 'FALTANTES', (int) ($auditoria['faltantes'] ?? 0));
        $contenido .= self::metrica(444, 542, 'SOBRANTES', (int) ($auditoria['sobrantes'] ?? 0));

        $columnas = [24, 68, 112, 62, 62, 52, 52, 108];
        $encabezados = ['No.', 'Código', 'Producto', 'Marca', 'Modelo', 'Teórico', 'Físico', 'Diferencia / Resultado'];
        $x = 36.0;
        $y = 502.0;
        foreach ($encabezados as $indice => $encabezado) {
            $contenido .= self::rectangulo($x, $y, $columnas[$indice], 24, self::TABLE_GRAY, self::BORDER);
            $contenido .= self::textoCentrado($x + ($columnas[$indice] / 2), $y + 8, $encabezado, 7.2, 'F2', self::NAVY);
            $x += $columnas[$indice];
        }

        $numeroBase = (($pagina - 1) * 19) + 1;
        foreach ($lineas as $indice => $linea) {
            $y -= 22;
            $fondo = $indice % 2 === 0 ? [1, 1, 1] : [0.985, 0.99, 1.0];
            $diferencia = (float) ($linea['diferencia'] ?? 0);
            $resultado = (string) ($linea['resultado'] ?? 'Coincide');
            $unidad = trim((string) ($linea['unidad'] ?? ''));
            $cantidadUnidad = static function ($valor) use ($unidad): string {
                return trim(self::cantidad($valor) . ' ' . $unidad);
            };
            $valores = [
                (string) ($numeroBase + $indice),
                self::recortar((string) ($linea['nomenclatura'] ?: 'S/R'), 13),
                self::recortar((string) ($linea['nombre'] ?? ''), 22),
                self::recortar(trim((string) ($linea['marca'] ?? '')) ?: 'Sin Registro', 12),
                self::recortar(trim((string) ($linea['modelo'] ?? '')) ?: 'Sin Registro', 12),
                self::recortar($cantidadUnidad($linea['stock_teorico'] ?? 0), 10),
                self::recortar($cantidadUnidad($linea['stock_fisico'] ?? 0), 10),
                ($diferencia > 0 ? '+' : '') . self::cantidad($diferencia) . ' ' . $resultado,
            ];

            $x = 36.0;
            foreach ($valores as $columna => $valor) {
                $contenido .= self::rectangulo($x, $y, $columnas[$columna], 22, $fondo, self::BORDER);
                $color = self::NAVY;
                if ($columna === 7) {
                    $color = match ($resultado) {
                        'Faltante' => [0.72, 0.08, 0.08],
                        'Sobrante' => [0.78, 0.38, 0.0],
                        default => [0.07, 0.45, 0.24],
                    };
                }
                $contenido .= self::texto(
                    $x + 4,
                    $y + 7,
                    $valor,
                    7.0,
                    $columna === 7 ? 'F2' : 'F1',
                    $color
                );
                $x += $columnas[$columna];
            }
        }

        $contenido .= self::linea(36, 68, 576, 68, [0.78, 0.82, 0.88], 0.7);
        $contenido .= self::texto(36, 51, 'Documento Generado por el Sistema ERP TAKAB.', 7.5, 'F1', [0.35, 0.42, 0.52]);
        $contenido .= self::textoDerecha(576, 51, 'Página ' . $pagina . ' de ' . $totalPaginas, 7.5, 'F2', self::NAVY);

        return $contenido;
    }

    private static function etiquetaBloque(
        float $x,
        float $y,
        float $ancho,
        string $etiqueta,
        string $valor,
        int $limite
    ): string {
        return self::texto($x, $y, $etiqueta, 6.8, 'F2', self::BLUE)
            . self::texto($x, $y - 11, self::recortar($valor, $limite), 7.8, 'F1', self::NAVY)
            . self::lineaPunteada($x, $y - 15, $x + $ancho, $y - 15, [0.78, 0.82, 0.88], 0.5);
    }

    private static function metrica(float $x, float $y, string $etiqueta, int $valor): string
    {
        return self::texto($x, $y, $etiqueta . ':', 7.1, 'F2', self::BLUE)
            . self::texto($x + 48, $y, (string) $valor, 8.2, 'F2', self::NAVY);
    }

    private static function textoCentrado(
        float $centro,
        float $y,
        string $texto,
        float $tamano,
        string $fuente,
        array $color
    ): string {
        return self::texto($centro - (self::anchoTexto($texto, $tamano) / 2), $y, $texto, $tamano, $fuente, $color);
    }

    private static function textoDerecha(
        float $derecha,
        float $y,
        string $texto,
        float $tamano,
        string $fuente,
        array $color
    ): string {
        return self::texto($derecha - self::anchoTexto($texto, $tamano), $y, $texto, $tamano, $fuente, $color);
    }

    private static function anchoTexto(string $texto, float $tamano): float
    {
        return mb_strlen($texto, 'UTF-8') * $tamano * 0.52;
    }

    private static function texto(float $x, float $y, string $texto, float $tamano, string $fuente, array $color): string
    {
        [$r, $g, $b] = $color;
        return sprintf(
            "BT /%s %.2f Tf %.3f %.3f %.3f rg 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n",
            $fuente,
            $tamano,
            $r,
            $g,
            $b,
            $x,
            $y,
            self::textoPdf($texto)
        );
    }

    private static function rectangulo(float $x, float $y, float $w, float $h, array $relleno, array $borde): string
    {
        [$fr, $fg, $fb] = $relleno;
        [$sr, $sg, $sb] = $borde;
        return sprintf(
            "q %.3f %.3f %.3f rg %.3f %.3f %.3f RG 0.5 w %.2f %.2f %.2f %.2f re B Q\n",
            $fr,
            $fg,
            $fb,
            $sr,
            $sg,
            $sb,
            $x,
            $y,
            $w,
            $h
        );
    }

    private static function linea(float $x1, float $y1, float $x2, float $y2, array $color, float $ancho): string
    {
        [$r, $g, $b] = $color;
        return sprintf(
            "q %.3f %.3f %.3f RG %.2f w %.2f %.2f m %.2f %.2f l S Q\n",
            $r,
            $g,
            $b,
            $ancho,
            $x1,
            $y1,
            $x2,
            $y2
        );
    }

    private static function lineaPunteada(
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        array $color,
        float $ancho
    ): string {
        [$r, $g, $b] = $color;
        return sprintf(
            "q [2 2] 0 d %.3f %.3f %.3f RG %.2f w %.2f %.2f m %.2f %.2f l S Q\n",
            $r,
            $g,
            $b,
            $ancho,
            $x1,
            $y1,
            $x2,
            $y2
        );
    }

    private static function cantidad($valor): string
    {
        return rtrim(rtrim(number_format((float) $valor, 2, '.', ','), '0'), '.');
    }

    private static function recortar(string $texto, int $ancho): string
    {
        return mb_strimwidth(trim($texto), 0, $ancho, '...', 'UTF-8');
    }

    private static function textoPdf(string $texto): string
    {
        $texto = str_replace(["\r", "\n"], ' ', $texto);
        $convertido = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $texto);
        if ($convertido !== false) {
            $texto = $convertido;
        }
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $texto);
    }
}
