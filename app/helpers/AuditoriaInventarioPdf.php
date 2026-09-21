<?php

class AuditoriaInventarioPdf
{
    private const PAGE_WIDTH = 612.0;
    private const PAGE_HEIGHT = 792.0;
    private const BLUE = [0.0, 0.31, 0.56];
    private const NAVY = [0.0, 0.13, 0.38];
    private const GRAY = [0.95, 0.96, 0.98];

    public static function generar(array $auditoria, string $responsable, ?string $logoPath = null): string
    {
        $lineas = is_array($auditoria['lineas'] ?? null) ? $auditoria['lineas'] : [];
        $paginas = array_chunk($lineas, 21);
        if ($paginas === []) {
            $paginas = [[]];
        }

        $imagen = self::prepararLogo($logoPath);
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $imageObject = null;
        if ($imagen !== null) {
            $imageObject = 5;
            $objects[$imageObject] = sprintf(
                '<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>' . "\nstream\n%s\nendstream",
                $imagen['width'],
                $imagen['height'],
                strlen($imagen['data']),
                $imagen['data']
            );
        }

        $pageRefs = [];
        $totalPaginas = count($paginas);
        foreach ($paginas as $indicePagina => $lineasPagina) {
            $contenido = self::contenidoPagina(
                $auditoria,
                $responsable,
                $lineasPagina,
                $indicePagina + 1,
                $totalPaginas,
                $imageObject !== null
            );
            $contentObject = count($objects) + 1;
            $objects[$contentObject] = self::stream($contenido);

            $pageObject = count($objects) + 1;
            $xObjects = $imageObject !== null ? ' /XObject << /Im1 ' . $imageObject . ' 0 R >>' : '';
            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '
                . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >>'
                . $xObjects . ' >> /Contents ' . $contentObject . ' 0 R >>';
            $pageRefs[] = $pageObject . ' 0 R';
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageRefs) . '] /Count ' . count($pageRefs) . ' >>';

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        $objectCount = count($objects);
        for ($i = 1; $i <= $objectCount; $i++) {
            $offsets[$i] = strlen($pdf);
            $pdf .= $i . " 0 obj\n" . $objects[$i] . "\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . ($objectCount + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $objectCount; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= 'trailer << /Size ' . ($objectCount + 1) . " /Root 1 0 R >>\nstartxref\n"
            . $xref . "\n%%EOF";

        return $pdf;
    }

    private static function contenidoPagina(
        array $auditoria,
        string $responsable,
        array $lineas,
        int $pagina,
        int $totalPaginas,
        bool $conLogo
    ): string {
        $c = '';
        if ($conLogo) {
            $c .= "q 48 0 0 48 36 708 cm /Im1 Do Q\n";
        }

        $c .= self::texto(102, 744, 'REPORTE DE AUDITORÍA DE INVENTARIO', 17, 'F2', self::BLUE);
        $c .= self::texto(420, 706, 'FOLIO: ' . (string) ($auditoria['folio'] ?? ''), 8, 'F2', [0, 0, 0]);
        $c .= self::texto(102, 725, 'TAKAB, SISTEMAS TECNOLÓGICOS INTELIGENTES & SERVICIOS INTEGRALES', 7.4, 'F2', self::NAVY);
        $c .= self::linea(36, 700, 576, 700, self::BLUE, 1.5);

        $c .= self::rectangulo(36, 642, 540, 45, self::GRAY, [0.78, 0.82, 0.88]);
        $c .= self::etiquetaValor(48, 670, 'ALMACÉN', (string) ($auditoria['almacen'] ?? '-'));
        $c .= self::etiquetaValor(202, 670, 'CATEGORÍA', (string) ($auditoria['categoria'] ?? '-'));
        $c .= self::etiquetaValor(360, 670, 'TIPO', (string) ($auditoria['tipo'] ?? 'Todos'));
        $c .= self::etiquetaValor(48, 650, 'RESPONSABLE', $responsable !== '' ? $responsable : 'Sin Registro');
        $fecha = !empty($auditoria['fecha']) ? date('d/m/Y H:i', strtotime((string) $auditoria['fecha'])) : date('d/m/Y H:i');
        $c .= self::etiquetaValor(360, 650, 'FECHA', $fecha);

        $resumen = sprintf(
            'Partidas: %d    Coinciden: %d    Faltantes: %d    Sobrantes: %d',
            (int) ($auditoria['total_partidas'] ?? 0),
            (int) ($auditoria['coincidencias'] ?? 0),
            (int) ($auditoria['faltantes'] ?? 0),
            (int) ($auditoria['sobrantes'] ?? 0)
        );
        $c .= self::rectangulo(36, 610, 540, 22, [0.88, 0.94, 1.0], self::BLUE);
        $c .= self::texto(48, 617, $resumen, 9, 'F2', self::NAVY);

        $columnas = [22, 60, 115, 65, 65, 55, 55, 102];
        $encabezados = ['No.', 'Código', 'Producto', 'Marca', 'Modelo', 'Teórico', 'Físico', 'Diferencia / Resultado'];
        $x = 36.0;
        $y = 584.0;
        foreach ($encabezados as $i => $encabezado) {
            $c .= self::rectangulo($x, $y, $columnas[$i], 22, [0.91, 0.93, 0.97], [0.72, 0.76, 0.82]);
            $c .= self::texto($x + 4, $y + 7, $encabezado, 7.6, 'F2', self::NAVY);
            $x += $columnas[$i];
        }

        $numeroBase = (($pagina - 1) * 21) + 1;
        foreach ($lineas as $indice => $linea) {
            $y -= 22;
            $fondo = $indice % 2 === 0 ? [1, 1, 1] : [0.975, 0.98, 0.99];
            $diferencia = (float) ($linea['diferencia'] ?? 0);
            $diferenciaResultado = ($diferencia > 0 ? '+' : '')
                . self::cantidad($diferencia)
                . ' '
                . (string) ($linea['resultado'] ?? '');
            $valores = [
                (string) ($numeroBase + $indice),
                self::recortar((string) ($linea['nomenclatura'] ?: 'S/R'), 11),
                self::recortar((string) ($linea['nombre'] ?? ''), 21),
                self::recortar(trim((string) ($linea['marca'] ?? '')) ?: 'Sin Registro', 12),
                self::recortar(trim((string) ($linea['modelo'] ?? '')) ?: 'Sin Registro', 12),
                self::cantidad($linea['stock_teorico'] ?? 0),
                self::cantidad($linea['stock_fisico'] ?? 0),
                $diferenciaResultado,
            ];
            $x = 36.0;
            foreach ($valores as $i => $valor) {
                $c .= self::rectangulo($x, $y, $columnas[$i], 22, $fondo, [0.82, 0.84, 0.88]);
                $color = self::NAVY;
                if ($i === 7) {
                    $color = match ((string) ($linea['resultado'] ?? '')) {
                        'Faltante' => [0.72, 0.08, 0.08],
                        'Sobrante' => [0.78, 0.38, 0.0],
                        default => [0.07, 0.45, 0.24],
                    };
                }
                $c .= self::texto($x + 4, $y + 7, $valor, 7.1, $i === 7 ? 'F2' : 'F1', $color);
                $x += $columnas[$i];
            }
        }

        $c .= self::linea(36, 68, 576, 68, [0.78, 0.82, 0.88], 0.7);
        $c .= self::texto(36, 51, 'Documento generado por el Sistema ERP TAKAB.', 7.5, 'F1', [0.35, 0.42, 0.52]);
        $c .= self::texto(500, 51, 'Página ' . $pagina . ' de ' . $totalPaginas, 7.5, 'F2', self::NAVY);

        return $c;
    }

    private static function etiquetaValor(float $x, float $y, string $etiqueta, string $valor): string
    {
        return self::texto($x, $y, $etiqueta . ':', 6.8, 'F2', self::BLUE)
            . self::texto($x + 60, $y, self::recortar($valor, 27), 7.6, 'F1', self::NAVY);
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
        return sprintf("q %.3f %.3f %.3f RG %.2f w %.2f %.2f m %.2f %.2f l S Q\n", $r, $g, $b, $ancho, $x1, $y1, $x2, $y2);
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

    private static function stream(string $contenido): string
    {
        return '<< /Length ' . strlen($contenido) . ">>\nstream\n" . $contenido . "endstream";
    }

    private static function prepararLogo(?string $logoPath): ?array
    {
        if (!$logoPath || !is_file($logoPath) || !function_exists('imagecreatefrompng')) {
            return null;
        }

        $origen = @imagecreatefrompng($logoPath);
        if (!$origen) {
            return null;
        }

        $width = imagesx($origen);
        $height = imagesy($origen);
        $lienzo = imagecreatetruecolor($width, $height);
        $blanco = imagecolorallocate($lienzo, 255, 255, 255);
        imagefill($lienzo, 0, 0, $blanco);
        imagealphablending($lienzo, true);
        imagecopy($lienzo, $origen, 0, 0, 0, 0, $width, $height);

        ob_start();
        imagejpeg($lienzo, null, 90);
        $data = ob_get_clean();
        imagedestroy($origen);
        imagedestroy($lienzo);

        if (!is_string($data) || $data === '') {
            return null;
        }

        return ['data' => $data, 'width' => $width, 'height' => $height];
    }
}
