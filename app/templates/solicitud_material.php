<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Solicitud de Materiales - TAKAB</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: "TJEWVM+Tahoma", Arial, sans-serif;
            background-color: #f9f9f9;
            color: #002060;
        }

        .pdf24_02 {
            width: 51em;
            margin: 0 auto;
            position: relative;
            background: #fff;
            padding: 1.5em;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .logo-takab {
            height: 3.5em;
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
            flex-grow: 1;
        }

        .doc-code {
            font-size: 0.95em;
            color: #000;
            font-weight: bold;
            white-space: nowrap;
            text-align: right;
        }

        .company-name {
            font-size: 0.85em;
            color: #002060;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }

        .company-data {
            font-size: 0.72em;
            color: #002060;
            text-align: center;
            line-height: 1.2;
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

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item.full-width {
            grid-column: span 2;
        }

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

        .comments-box {
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 6px;
            min-height: 2.8em;
            font-style: italic;
            color: #334155;
        }

        .category-block {
            margin-bottom: 1.2em;
        }

        .category-title {
            background-color: #0070C0;
            color: #ffffff;
            font-size: 0.85em;
            font-weight: bold;
            padding: 4px 8px;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 2px 2px 0 0;
        }

        .material-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75em;
            color: #002060;
        }

        .material-table th {
            background-color: #f2f2f2;
            border: 1px solid #c0c0c0;
            padding: 6px 4px;
            font-weight: bold;
            text-align: center;
        }

        .material-table td {
            border: 1px solid #c0c0c0;
            padding: 5px 4px;
            vertical-align: top;
            white-space: normal;
            word-wrap: break-word;
        }

        .col-no      { width: 6%; text-align: center; }
        .col-codigo  { width: 18%; }
        .col-nombre  { width: 52%; }
        .col-udm     { width: 12%; text-align: center; }
        .col-cant    { width: 12%; text-align: center; font-weight: bold; }

        .signatures-container {
            display: flex;
            justify-content: space-around;
            margin-top: 3em;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 38%;
            text-align: center;
            font-size: 0.75em;
        }

        .signature-line {
            border-top: 1px solid #002060;
            margin-bottom: 4px;
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

        @media print {
            body { background: #fff; }
            .btn-print { display: none !important; }
            .pdf24_02 { box-shadow: none; padding: 0; width: 100%; }
            .material-table th {
                background-color: #e5e5e5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .category-title {
                background-color: #0070C0 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            tr { page-break-inside: avoid; break-inside: avoid; }
        }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print();">🖨️ Imprimir Solicitud</button>

    <div class="pdf24_02">
        
        <div class="header-container">
            <div class="header-top-row">
                <?php 
                $ruta_logo = $_SERVER['DOCUMENT_ROOT'] . '/proyectos/Sistema-ERP/public/assets/images/logo.png'; 
                ?>
                <img class="logo-takab" src="/proyectos/Sistema-ERP/public/assets/images/logo.png" alt="TAKAB Logo" />
                
                <h1 class="doc-title">SOLICITUD DE MATERIALES</h1>
                
                <div class="doc-code">SA-TT-03</div>
            </div>
            
            <div class="company-name">
                TAKAB, SISTEMAS TECNOLOGICOS INTELIGENTES & SERVICIOS INTEGRALES, S DE RL DE CV.
            </div>
            <div class="company-data">
                RFC: TST190527EBA | Priv. 48 Norte 1239 | Col. Agrícola Resurgimiento | Puebla, pue. C.P. 72370 | Oficina: 222.583.7297
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Solicitante</span>
                <span class="info-value"><?= htmlspecialchars($solicitud['solicitante'] ?? 'N/A') ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Proyecto / Destino</span>
                <span class="info-value"><?= htmlspecialchars($solicitud['proyecto'] ?? 'N/A') ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Fecha de Solicitud</span>
                <span class="info-value"><?= !empty($solicitud['fecha_solicitud']) ? date('d/m/Y', strtotime($solicitud['fecha_solicitud'])) : date('d/m/Y') ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Fecha Requerida de Entrega</span>
                <span class="info-value"><?= !empty($solicitud['fecha_entrega']) ? date('d/m/Y', strtotime($solicitud['fecha_entrega'])) : 'N/A' ?></span>
            </div>

            <div class="info-item full-width">
                <span class="info-label">Comentarios / Observaciones del Solicitante</span>
                <div class="comments-box">
                    <?= !empty($solicitud['comentarios']) ? nl2br(htmlspecialchars($solicitud['comentarios'])) : 'Sin observaciones adicionales.' ?>
                </div>
            </div>
        </div>

        <!-- CLASIFICACIÓN Y LISTADO DE MATERIALES -->
        <?php
        // Agrupación automática de ítems por categoría
        $grupos = [
            'Herramientas' => [],
            'Consumibles'  => [],
            'Materiales'   => []
        ];

        if (!empty($solicitud['items']) && is_array($solicitud['items'])) {
            foreach ($solicitud['items'] as $item) {
                $tipo = ucfirst(strtolower($item['tipo'] ?? 'Materiales'));
                if (array_key_exists($tipo, $grupos)) {
                    $grupos[$tipo][] = $item;
                } else {
                    $grupos['Materiales'][] = $item; // Categoría por defecto
                }
            }
        }
        ?>

        <?php 
        $hayItems = false;
        foreach ($grupos as $categoria => $itemsCategoria): 
            if (!empty($itemsCategoria)): 
                $hayItems = true;
        ?>
            <div class="category-block">
                <div class="category-title"><?= $categoria ?></div>
                <table class="material-table">
                    <thead>
                        <tr>
                            <th class="col-no">No.</th>
                            <th class="col-codigo">Código</th>
                            <th class="col-nombre">Nombre / Descripción del Producto</th>
                            <th class="col-udm">U. Medida</th>
                            <th class="col-cant">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $num = 1;
                        foreach ($itemsCategoria as $prod): 
                        ?>
                            <tr>
                                <td class="col-no"><?= $num ?></td>
                                <td class="col-codigo"><?= htmlspecialchars($prod['codigo_fabricante'] ?? 'N/A') ?></td>
                                <td class="col-nombre"><?= htmlspecialchars($prod['nombre'] ?? '-') ?></td>
                                <td class="col-udm"><?= htmlspecialchars($prod['unidad_medida'] ?? 'Pza') ?></td>
                                <td class="col-cant"><?= htmlspecialchars($prod['cantidad'] ?? 1) ?></td>
                            </tr>
                        <?php 
                        $num++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        <?php 
            endif; 
        endforeach; 

        if (!$hayItems): 
        ?>
            <div style="text-align: center; padding: 2em; border: 1px dashed #cbd5e1; color: #64748b; font-size: 0.85em;">
                No se han agregado materiales a esta solicitud.
            </div>
        <?php endif; ?>

        <!-- FIRMAS DE CONFORMIDAD -->
        <div class="signatures-container">
            <div class="signature-box">
                <div class="signature-line"></div>
                <strong>FIRMA DEL SOLICITANTE</strong><br>
                <span><?= htmlspecialchars($solicitud['solicitante'] ?? 'Nombre y Firma') ?></span>
            </div>
            
            <div class="signature-box">
                <div class="signature-line"></div>
                <strong>AUTORIZADO / RECIBIDO</strong><br>
                <span>Firma de Conformidad Almacén</span>
            </div>
        </div>

    </div>

</body>
</html>