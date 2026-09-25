<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>LISTA INVENTARIO | TAKAB</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "TJEWVM+Tahoma", Arial, sans-serif;
            color: #002060;
            background: #f9f9f9;
        }
        
        .pdf24_02 {
            width: 51em;
            margin: 0 auto;
            position: relative;
            background: #fff;
            padding: 1.5em;
            box-sizing: border-box;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.15);
        }

        .header-container {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.5em;
            border-bottom: 2px solid #0070C0;
            padding-bottom: 0.8em;
            margin-bottom: 1.2em;
        }

        .header-top-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            width: 100%;
        }

        .logo-takab {
            height: 3.5em;
            max-width: 9em;
            width: auto;
            object-fit: contain;
        }

        .doc-title {
            font-size: 1.8em;
            font-family: "TQEVHM+Calibri Bold", Arial, sans-serif;
            color: #0070C0;
            font-weight: bold;
            margin: 0;
            text-align: center;
        }

        .header-spacer { width: 9em; }

        .company-name {
            font-size: 0.85em;
            color: #002060;
            font-weight: bold;
            margin: 0;
            text-align: center;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8em 1.5em;
            background-color: #f8fafc;
            border: 1px solid #c0c0c0;
            border-radius: 4px;
            padding: 0.8em 1em;
            margin-bottom: 1.2em;
            font-size: 0.78em;
        }

        .info-item { display: flex; flex-direction: column; }
        .info-item.full-width { grid-column: span 2; }

        .info-label {
            font-weight: bold;
            color: #0070C0;
            font-size: 0.9em;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .info-value {
            color: #002060;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 2px;
            min-height: 1.2em;
        }

        .inventario-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2em;
            font-size: 0.75em;
            color: #002060;
        }

        .inventario-table th {
            background-color: #f2f2f2;
            border: 1px solid #c0c0c0;
            padding: 6px 4px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .inventario-table td {
            border: 1px solid #c0c0c0;
            padding: 5px 4px;
            vertical-align: top;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }

        .col-no     { width: 5%; text-align: center; }
        .col-codigo { width: 13%; }
        .col-nombre { width: 29%; }
        .col-marca  { width: 13%; }
        .col-modelo { width: 12%; }
        .col-stock  { width: 11%; text-align: center; }
        .col-real   { width: 9%; text-align: center; }
        .col-precio { width: 8%; text-align: right; }

        .empty-row {
            padding: 20px !important;
            text-align: center;
            color: #63769f;
        }

        @media print {
            @page { size: Letter portrait; margin: 11mm; }
            body { background: #fff; }
            .btn-print { display: none !important; }
            .pdf24_02 { width: 100%; padding: 0; box-shadow: none; }
            .inventario-table th {
                background-color: #e5e5e5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            tr { page-break-inside: avoid; break-inside: avoid; }
        }

        .btn-print {
            display: block;
            margin: 15px auto;
            padding: 8px 20px;
            background: #0070C0;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print();">🖨️ Imprimir Inventario</button>

    <div class="pdf24_ pdf24_02">
        
        <div class="header-container">
            <div class="header-top-row">
                <img class="logo-takab" src="/proyectos/Sistema-ERP/public/assets/images/logo.png" alt="TAKAB Logo" />
                <h1 class="doc-title">HOJA DE INVENTARIO</h1>
                <div class="header-spacer" aria-hidden="true"></div>
            </div>
            <div class="company-name">
                TAKAB, SISTEMAS TECNOLOGICOS INTELIGENTES & SERVICIOS INTEGRALES, S DE RL DE CV.
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Fecha de Impresión</span>
                <span class="info-value"><?= htmlspecialchars($fechaImpresion->format('d/m/Y H:i')) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Página de Inventario</span>
                <span class="info-value"><?= (int) $pagina ?> de <?= (int) $totalPaginas ?></span>
            </div>
        </div>

        <table class="inventario-table">
            <thead>
                <tr>
                    <th class="col-no">No.</th>
                    <th class="col-codigo">Código</th>
                    <th class="col-nombre">Descripción / Nombre</th>
                    <th class="col-marca">Marca</th>
                    <th class="col-modelo">Modelo</th>
                    <th class="col-stock">Stock Sistema</th>
                    <th class="col-real">Stock Físico</th>
                    <?php if ($mostrarCostos): ?><th class="col-precio">Precio U.</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $numero = $offset + 1;
                if (!$listado):
                ?>
                    <tr><td class="empty-row" colspan="<?= $mostrarCostos ? 8 : 7 ?>">No Hay Productos para Imprimir con la Selección Actual.</td></tr>
                <?php
                else:
                foreach ($listado as $fila) {
                    echo '<tr>';
                    echo '<td class="col-no">' . $numero . '</td>';
                    echo '<td class="col-codigo">' . htmlspecialchars((string) ($fila['codigo'] ?? '-')) . '</td>';
                    echo '<td class="col-nombre">' . htmlspecialchars((string) ($fila['nombre'] ?? '-')) . '</td>';
                    echo '<td class="col-marca">' . htmlspecialchars((string) ($fila['marca'] ?? '-')) . '</td>';
                    echo '<td class="col-modelo">' . htmlspecialchars((string) ($fila['modelo'] ?? '-')) . '</td>';
                    $unidad = trim((string) ($fila['unidad_abreviacion'] ?? $fila['unidad_medida_nombre'] ?? 'Pza'));
                    echo '<td class="col-stock">' . number_format((float) ($fila['stock_actual'] ?? 0), 2) . ' ' . htmlspecialchars($unidad) . '</td>';
                    echo '<td class="col-real"></td>';
                    if ($mostrarCostos) {
                        echo '<td class="col-precio">$' . number_format((float) ($fila['precio_unitario'] ?? 0), 2) . '</td>';
                    }
                    echo '</tr>';
                    $numero++;
                }
                endif;
                ?>
            </tbody>
        </table>
    </div>

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>
</body>
</html>
