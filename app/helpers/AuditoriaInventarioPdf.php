<?php
require_once __DIR__ . '/../templates/auditoria_inventario.php';

class AuditoriaInventarioPdf
{
    private const PAGE_WIDTH = 612.0;
    private const PAGE_HEIGHT = 792.0;

    public static function generar(array $auditoria, string $responsable, ?string $logoPath = null): string
    {
        $lineas = is_array($auditoria['lineas'] ?? null) ? $auditoria['lineas'] : [];
        $paginas = array_chunk($lineas, 19);
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
            $contenido = AuditoriaInventarioTemplate::renderizarPagina(
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
