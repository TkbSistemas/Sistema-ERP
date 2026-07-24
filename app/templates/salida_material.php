<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Baja de Inventario - TAKAB</title>
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
            margin-bottom: 1.5em;
            font-size: 0.8em;
        }

        .info-item {
            display: flex;
            flex-direction: column;
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

        .baja-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75em;
            color: #002060;
            margin-bottom: 2em;
        }

        .baja-table th {
            background-color: #f2f2f2;
            border: 1px solid #c0c0c0;
            padding: 8px 6px;
            font-weight: bold;
            text-align: center;
        }

        .baja-table td {
            border: 1px solid #c0c0c0;
            padding: 6px;
            vertical-align: top;
            white-space: normal;
            word-wrap: break-word;
        }

        .col-no          { width: 5%; text-align: center; }
        .col-cod-fab     { width: 20%; }
        .col-nombre      { width: 38%; }
        .col-cant        { width: 10%; text-align: center; font-weight: bold; }
        .col-notas       { width: 27%; }

        .signatures-container {
            display: flex;
            justify-content: space-between;
            margin-top: 4em;
            page-break-inside: avoid;
            padding: 0 2em;
        }

        .signature-box {
            width: 40%;
            text-align: center;
            font-size: 0.78em;
        }

        .signature-line {
            border-top: 1px solid #002060;
            margin-bottom: 6px;
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
            .baja-table th {
                background-color: #e5e5e5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            tr { page-break-inside: avoid; break-inside: avoid; }
        }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print();">🖨️ Imprimir Baja</button>

    <div class="pdf24_02">
        
        <div class="header-container">
            <div class="header-top-row">
                <img class="logo-takab" src="/proyectos/Sistema-ERP/public/assets/images/logo.png" alt="TAKAB Logo" />
                
                <h1 class="doc-title">BAJA DE INVENTARIO</h1>
                
                <div class="doc-code"><?= htmlspecialchars($baja['folio'] ?? 'N/A') ?></div>
            </div>
            
            <div class="company-name">
                TAKAB, SISTEMAS TECNOLOGICOS INTELIGENTES & SERVICIOS INTEGRALES, S DE RL DE CV.
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Responsable de Almacén</span>
                <span class="info-value"><?= htmlspecialchars($baja['responsable'] ?? 'N/A') ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Fecha de Emisión</span>
                <span class="info-value"><?= !empty($baja['fecha']) ? date('d/m/Y H:i', strtotime($baja['fecha'])) : date('d/m/Y H:i') ?></span>
            </div>
        </div>

        <table class="baja-table">
            <thead>
                <tr>
                    <th class="col-no">No.</th>
                    <th class="col-cod-fab">Código Fabricante</th>
                    <th class="col-nombre">Nombre / Descripción</th>
                    <th class="col-cant">Cantidad</th>
                    <th class="col-notas">Notas / Motivo</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($baja['items']) && is_array($baja['items'])): 
                    $num = 1;
                    foreach ($baja['items'] as $item): 
                ?>
                        <tr>
                            <td class="col-no"><?= $num ?></td>
                            <td class="col-cod-fab"><?= htmlspecialchars($item['codigo_fabricante'] ?? ($item['codigo'] ?? 'N/A')) ?></td>
                            <td class="col-nombre"><?= htmlspecialchars($item['nombre'] ?? '-') ?></td>
                            <td class="col-cant"><?= htmlspecialchars($item['cantidad'] ?? 1) ?></td>
                            <td class="col-notas"><?= htmlspecialchars($item['notas'] ?? ($item['motivo'] ?? '-')) ?></td>
                        </tr>
                <?php 
                    $num++;
                    endforeach; 
                else: 
                ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 1.5em; color: #64748b;">
                            No Hay Productos Registrados en esta Solicitud.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="signatures-container">
            <div class="signature-box">
                <div class="signature-line"></div>
                <strong>ENTREGÓ / RESPONSABLE</strong><br>
                <span><?= htmlspecialchars($baja['responsable'] ?? 'Nombre y Firma') ?></span>
            </div>
            
            <div class="signature-box">
                <div class="signature-line"></div>
                <strong>AUTORIZADO POR</strong><br>
                <span>Nombre, Firma y Sello</span>
            </div>
        </div>

    </div>

</body>
</html>