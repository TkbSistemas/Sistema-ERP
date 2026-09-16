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
$rutaLogo = Session::url('assets/images/icono_takab.png');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orden de Compra <?= htmlspecialchars((string) ($orden['folio'] ?: $orden['id']), ENT_QUOTES, 'UTF-8') ?> | TAKAB</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: Tahoma, Arial, sans-serif;
            color: #002060;
            background: #eef2f7;
        }
        .print-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 15px auto;
        }
        .btn-print {
            padding: 9px 18px;
            border: 0;
            border-radius: 5px;
            background: #0070c0;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }
        .document {
            width: min(900px, calc(100% - 2rem));
            min-height: 1050px;
            margin: 0 auto 24px;
            padding: 24px;
            background: #fff;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.14);
        }
        .header {
            padding-bottom: 13px;
            margin-bottom: 18px;
            border-bottom: 2px solid #0070c0;
        }
        .header-main {
            display: grid;
            grid-template-columns: 150px 1fr 150px;
            align-items: center;
            gap: 12px;
        }
        .logo { width: 120px; max-height: 58px; object-fit: contain; }
        .document-title {
            margin: 0;
            color: #0070c0;
            font-size: 25px;
            text-align: center;
        }
        .folio {
            color: #111827;
            font-size: 13px;
            font-weight: 700;
            text-align: right;
            overflow-wrap: anywhere;
        }
        .company-name {
            margin-top: 7px;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
            margin-bottom: 20px;
            border: 1px solid #b8c5d6;
            border-radius: 4px;
            overflow: hidden;
        }
        .info-item {
            min-height: 58px;
            padding: 9px 11px;
            border-right: 1px solid #d8e0ea;
            border-bottom: 1px solid #d8e0ea;
            background: #f8fafc;
        }
        .info-item:nth-child(3n) { border-right: 0; }
        .info-item:nth-last-child(-n + 3) { border-bottom: 0; }
        .info-label {
            display: block;
            margin-bottom: 4px;
            color: #0070c0;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .info-value { color: #172554; font-size: 12px; line-height: 1.35; }
        .section-title {
            margin: 0;
            padding: 7px 10px;
            background: #0070c0;
            color: #fff;
            font-size: 12px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .materials-table {
            width: 100%;
            border-collapse: collapse;
            color: #002060;
            font-size: 10px;
        }
        .materials-table th {
            padding: 7px 5px;
            border: 1px solid #b8c5d6;
            background: #edf2f7;
            font-weight: 700;
            text-align: center;
        }
        .materials-table td {
            padding: 7px 5px;
            border: 1px solid #b8c5d6;
            vertical-align: top;
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
            gap: 50px;
            margin-top: 64px;
            page-break-inside: avoid;
        }
        .signature {
            width: 36%;
            padding-top: 6px;
            border-top: 1px solid #002060;
            font-size: 10px;
            text-align: center;
        }
        .print-meta { margin-top: 30px; color: #64748b; font-size: 9px; text-align: right; }
        @media print {
            @page { size: Letter portrait; margin: 11mm; }
            body { background: #fff; }
            .print-actions { display: none !important; }
            .document { width: 100%; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
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
            .header-main { grid-template-columns: 90px 1fr; }
            .folio { grid-column: 1 / -1; text-align: center; }
            .logo { width: 80px; }
            .document-title { font-size: 20px; }
            .info-grid { grid-template-columns: 1fr; }
            .info-item,
            .info-item:nth-child(3n),
            .info-item:nth-last-child(-n + 3) { border-right: 0; border-bottom: 1px solid #d8e0ea; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button type="button" class="btn-print" onclick="window.print()">Imprimir Orden de Compra</button>
    </div>

    <main class="document">
        <header class="header">
            <div class="header-main">
                <img class="logo" src="<?= htmlspecialchars($rutaLogo, ENT_QUOTES, 'UTF-8') ?>" alt="TAKAB Technology">
                <h1 class="document-title">ORDEN DE COMPRA</h1>
                <div class="folio">FOLIO: <?= htmlspecialchars((string) ($orden['folio'] ?: 'Sin Folio'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="company-name">TAKAB, SISTEMAS TECNOLÓGICOS INTELIGENTES &amp; SERVICIOS INTEGRALES, S. DE R.L. DE C.V.</div>
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
