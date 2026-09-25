<?php
$detalles = is_array($orden['detalles'] ?? null) ? $orden['detalles'] : [];
$totalEstimado = (float) ($orden['total_estimado'] ?? 0);
$formatearCantidad = static function ($valor): string {
    return rtrim(rtrim(number_format((float) $valor, 2, '.', ','), '0'), '.');
};
$formatearFecha = static function (?string $fecha): string {
    if (!$fecha) {
        return 'Sin Fecha';
    }
    $timestamp = strtotime($fecha);
    return $timestamp === false ? $fecha : date('d/m/Y', $timestamp);
};
$folioDocumento = trim((string) ($orden['folio'] ?? '')) ?: (string) ($orden['id'] ?? 'orden_compra');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($folioDocumento, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: "TJEWVM+Tahoma", Arial, sans-serif;
            color: #002060;
            background: #f9f9f9;
        }
        .print-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 15px auto;
        }
        .btn-print {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            background: #0070C0;
            color: white;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
        }
        .pdf24_02 {
            width: 51em;
            margin: 0 auto;
            position: relative;
            padding: 1.5em;
            background: #fff;
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
        .doc-code {
            font-size: 0.95em;
            color: #000;
            font-weight: bold;
            white-space: normal;
            text-align: right;
            overflow-wrap: anywhere;
        }
        .company-name {
            font-size: 0.85em;
            color: #002060;
            font-weight: bold;
            text-align: center;
            margin: 0;
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
        .section-title {
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
        .materials-table {
            width: 100%;
            border-collapse: collapse;
            color: #002060;
            font-size: 0.75em;
        }
        .materials-table th {
            padding: 6px 4px;
            border: 1px solid #c0c0c0;
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .materials-table td {
            padding: 5px 4px;
            border: 1px solid #c0c0c0;
            vertical-align: top;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }
        .col-number { width: 5%; text-align: center; }
        .col-code { width: 13%; }
        .col-description { width: 29%; }
        .col-brand-model { width: 18%; }
        .col-quantity-unit { width: 12%; text-align: center; }
        .col-price { width: 11%; text-align: right; white-space: nowrap; }
        .col-amount { width: 12%; text-align: right; white-space: nowrap; }
        .product-name { display: block; color: #102a56; font-weight: 700; }
        .product-detail { display: block; margin-top: 2px; color: #475569; line-height: 1.35; }
        .empty-row { padding: 22px !important; color: #64748b; text-align: center; }
        .totals {
            display: flex;
            justify-content: flex-end;
            margin-top: 14px;
        }
        .total-box {
            width: 290px;
            padding: 12px 14px;
            border: 2px solid #0070c0;
            background: #f8fbff;
            color: #002060;
            text-align: right;
        }
        .total-label { display: block; font-size: 10px; text-transform: uppercase; }
        .total-value { display: block; margin-top: 3px; font-size: 21px; font-weight: 800; }
        .total-note { margin: 5px 0 0; color: #64748b; font-size: 9px; text-align: right; }
        .signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 3em;
            page-break-inside: avoid;
        }
        .signature {
            width: 38%;
            padding-top: 4px;
            border-top: 1px solid #002060;
            font-size: 0.75em;
            text-align: center;
        }
        .print-meta { margin-top: 30px; color: #64748b; font-size: 9px; text-align: right; }
        @media print {
            @page { size: Letter portrait; margin: 11mm; }
            body { background: #fff; }
            .print-actions { display: none !important; }
            .pdf24_02 { width: 100%; margin: 0; padding: 0; box-shadow: none; }
            .materials-table thead { display: table-header-group; }
            .materials-table tr { page-break-inside: avoid; break-inside: avoid; }
            .section-title,
            .materials-table th,
            .total-box,
            .info-item {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @media (max-width: 700px) {
            .pdf24_02 { width: calc(100% - 1em); padding: 1em; }
            .header-top-row { grid-template-columns: 5em 1fr; }
            .doc-code { grid-column: 1 / -1; text-align: center; margin-top: 0.5em; }
            .logo-takab { max-width: 5em; }
            .doc-title { font-size: 1.4em; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button type="button" class="btn-print" onclick="window.print()">Imprimir Orden de Compra</button>
    </div>

    <main class="pdf24_02">
        <header class="header-container">
            <div class="header-top-row">
                <img class="logo-takab" src="/proyectos/Sistema-ERP/public/assets/images/logo.png" alt="TAKAB Logo">
                <h1 class="doc-title">ORDEN DE COMPRA</h1>
                <div class="doc-code"><?= htmlspecialchars((string) ($orden['folio'] ?: 'Sin Folio'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="company-name">TAKAB, SISTEMAS TECNOLOGICOS INTELIGENTES &amp; SERVICIOS INTEGRALES, S DE RL DE CV.</div>
        </header>

        <section class="info-grid" aria-label="Información de la Orden">
            <div class="info-item"><span class="info-label">Elaboró</span><span class="info-value"><?= htmlspecialchars((string) ($orden['creado_por'] ?? 'Sin Registro'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-item"><span class="info-label">Proveedor</span><span class="info-value"><?= htmlspecialchars((string) ($orden['proveedor']['nombre'] ?? 'Sin Proveedor'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-item"><span class="info-label">Método de Entrega</span><span class="info-value"><?= htmlspecialchars((string) ($orden['metodo_entrega'] ?? 'Por Confirmar'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-item"><span class="info-label">Partidas</span><span class="info-value"><?= number_format(count($detalles)) ?> <?= count($detalles) === 1 ? 'Material' : 'Materiales' ?></span></div>
            <div class="info-item"><span class="info-label">Fecha Requerida</span><span class="info-value"><?= htmlspecialchars($formatearFecha($orden['fecha_compra'] ?? null), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-item"><span class="info-label">Proyecto</span><span class="info-value"><?= htmlspecialchars((string) ($orden['proyecto']['nombre'] ?? 'Sin Proyecto'), ENT_QUOTES, 'UTF-8') ?></span></div>
        </section>

        <section>
            <h2 class="section-title">Descripción de Materiales</h2>
            <table class="materials-table">
                <thead>
                    <tr>
                        <th class="col-number">No.</th>
                        <th class="col-code">Código</th>
                        <th class="col-description">Material / Descripción</th>
                        <th class="col-brand-model">Marca / Modelo</th>
                        <th class="col-quantity-unit">Cantidad / Unidad</th>
                        <th class="col-price">Precio Unitario</th>
                        <th class="col-amount">Importe</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($detalles === []): ?>
                    <tr><td colspan="7" class="empty-row">Esta Orden no Tiene Materiales Registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($detalles as $indice => $detalle): ?>
                        <?php
                        $codigo = $detalle['producto_nomenclatura'] ?: ($detalle['producto_sku'] ?: ($detalle['codigo_fabricante'] ?: 'Sin Código'));
                        $marca = trim((string) ($detalle['marca'] ?? ''));
                        $modelo = trim((string) ($detalle['modelo'] ?? ''));
                        $unidad = trim((string) ($detalle['unidad'] ?? '')) ?: 'Pza';
                        ?>
                        <tr>
                            <td class="col-number"><?= $indice + 1 ?></td>
                            <td class="col-code">
                                <?= htmlspecialchars((string) $codigo, ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($detalle['codigo_fabricante']) && $detalle['codigo_fabricante'] !== $codigo): ?>
                                    <span class="product-detail">Fabricante: <?= htmlspecialchars((string) $detalle['codigo_fabricante'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="col-description">
                                <span class="product-name"><?= htmlspecialchars((string) ($detalle['producto_nombre'] ?? 'Producto'), ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="col-brand-model">
                                <span class="product-detail"><strong>Marca:</strong> <?= htmlspecialchars($marca !== '' ? $marca : '-', ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="product-detail"><strong>Modelo:</strong> <?= htmlspecialchars($modelo !== '' ? $modelo : '-', ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="col-quantity-unit"><?= htmlspecialchars($formatearCantidad($detalle['cantidad_solicitada'] ?? 0) . ' ' . $unidad, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="col-price">$<?= number_format((float) ($detalle['precio_unitario'] ?? 0), 2) ?></td>
                            <td class="col-amount">$<?= number_format((float) ($detalle['importe_estimado'] ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </section>

        <div class="totals">
            <div>
                <div class="total-box">
                    <span class="total-label">Total Estimado de la Orden</span>
                    <span class="total-value">$<?= number_format($totalEstimado, 2) ?> MXN</span>
                </div>
                <p class="total-note">Calculado con la Cantidad Solicitada por el Precio Unitario.</p>
            </div>
        </div>

        <section class="signatures" aria-label="Firmas de Autorización">
            <div class="signature"><strong>ELABORÓ</strong><br><?= htmlspecialchars((string) ($orden['creado_por'] ?? 'Nombre y Firma'), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="signature"><strong>AUTORIZÓ</strong><br>Nombre y Firma</div>
        </section>

        <div class="print-meta">Formato Generado: <?= htmlspecialchars($fechaImpresion->format('d/m/Y H:i'), ENT_QUOTES, 'UTF-8') ?></div>
    </main>

    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
