<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Hoja de Inventario - TAKAB</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "TJEWVM+Tahoma", Arial, sans-serif;
            color: #002060;
            background: #eef2f7;
        }
        
        .pdf24_02 {
            width: min(820px, calc(100% - 2em));
            margin: 0 auto;
            position: relative;
            background: #fff;
            padding: 1.5em;
            box-sizing: border-box;
        }

        .header-container {
			width: 100%;
			display: flex;
			flex-direction: column;
			gap: 0.6em;
			border-bottom: 2px solid #0070C0;
			padding-bottom: 1em;
			margin-bottom: 1.5em;
			font-family: "TJEWVM+Tahoma", Arial, sans-serif;
		}


.header-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center; /* Alinea verticalmente el logo, título y código al centro */
    width: 100%;
}

.logo-takab {
    height: 3.5em; /* Tamaño controlado para el logo de la esquina */
    width: auto;
    object-fit: contain;
}

.doc-title {
    font-size: 1.9em;
    font-family: "TQEVHM+Calibri Bold", sans-serif;
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

/* Datos de la empresa y fiscales */
.company-name {
    font-size: 0.85em;
    color: #002060;
    font-weight: bold;
    margin: 0;
    text-align: center;
}

.company-data {
    font-size: 0.72em;
    color: #002060;
    text-align: center;
    line-height: 1.3;
}

/* Fila inferior: Fecha de impresión alineada a la derecha */
.header-bottom-row {
    display: flex;
    justify-content: flex-end;
    margin-top: 0.5em;
}

.print-date {
    font-size: 0.75em;
    color: #002060;
    font-weight: bold;
}

/* Línea del auditor */
.auditor-line {
    font-size: 0.75em;
    color: #002060;
    width: 100%;
    margin-top: 0.5em;
}

        .inventario-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1em;
            font-size: 0.7em;
            color: #002060;
        }

        /* Cabecera idéntica a tu diseño original */
        .inventario-table th {
            background-color: #f2f2f2;
            border: 1px solid #c0c0c0;
            padding: 8px 4px;
            font-family: "DKUBPC+Verdana Bold", sans-serif;
            font-size: 0.9em;
            text-align: center;
            vertical-align: middle;
        }

        /* Control de filas y comportamiento del texto largo */
        .inventario-table td {
            border: 1px solid #c0c0c0;
            padding: 6px 4px;
            vertical-align: top;
            white-space: normal;       /* Permite que el texto salte a una segunda fila */
            word-wrap: break-word;     /* Rompe palabras largas si es necesario */
            overflow-wrap: anywhere;
        }

        /* Anchos controlados para evitar que se desarme horizontalmente */
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

        /* --- DISEÑO EXCLUSIVO PARA IMPRESIÓN --- */
        @media print {
            @page { size: Letter portrait; margin: 11mm; }
            body { background: #fff; }
            .btn-print { display: none !important; }
            .pdf24_02 { width: 100%; padding: 0; }
            .inventario-table th {
                background-color: #e5e5e5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            tr { page-break-inside: avoid; break-inside: avoid; }
        }

        .btn-print {
            padding: 8px 16px;
            background: #0070C0;
            color: white;
            border: none;
            cursor: pointer;
            margin-bottom: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print();">🖨️ Imprimir Inventario</button>

    <div class="pdf24_ pdf24_02">
        
        <div class="header-container">
    
    <div class="header-top-row">
		
    <?php $ruta_logo = $rutaLogo ?? Session::url('assets/images/icono_takab.png'); ?>
    <img class="logo-takab" src="<?php echo $ruta_logo; ?>" alt="TAKAB Technology Logo" />
    <h1 class="doc-title">HOJA DE INVENTARIO</h1>
    
    <div class="doc-code">SA-TT-02</div>
</div>
    
    <!-- Fila 2: Nombre de la Empresa -->
    <div class="company-name">
        TAKAB, SISTEMAS TECNOLOGICOS INTELIGENTES & SERVICIOS INTEGRALES, S DE RL DE CV.
    </div>
    
    <!-- Fila 4: Fecha de Impresión -->
    <div class="header-bottom-row">
        <div class="print-date">FECHA DE IMPRESIÓN: <?= htmlspecialchars($fechaImpresion->format('d/m/Y H:i')) ?> | PÁGINA DE INVENTARIO: <?= (int) $pagina ?> DE <?= (int) $totalPaginas ?></div>
    </div>
    
    <!-- Fila 5: Firma o nombre del auditor -->
    <div class="auditor-line">
        NOMBRE AUDITOR: <?= $auditor !== '' ? htmlspecialchars($auditor) : '________________________________________' ?>
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
