<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Hoja de Inventario - TAKAB</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "TJEWVM+Tahoma", Arial, sans-serif;
        }
        
        .pdf24_02 {
            width: 51em;
            margin: 0 auto;
            position: relative;
            background: #fff;
            padding: 1.5em;
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
    font-size: 2.1em;
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
            font-size: 0.75em; /* Ajuste para emular el tamaño de fuente original */
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
        .col-codigo { width: 12%; }
        .col-nombre { width: 33%; } /* Más espacio para la descripción larga */
        .col-marca  { width: 12%; }
        .col-modelo { width: 12%; }
        .col-udm    { width: 8%; text-align: center; } /* Nueva columna */
        .col-stock  { width: 6%; text-align: center; }
        .col-precio { width: 6%; text-align: right; }  /* Nueva columna */
        .col-total  { width: 6%; text-align: right; }  /* Nueva columna */

        /* --- DISEÑO EXCLUSIVO PARA IMPRESIÓN --- */
        @media print {
            body { background: #fff; }
            .btn-print { display: none !important; }
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
		
    <?php 
    $ruta_logo = '/proyectos/Sistema-ERP/public/assets/images/icono_takab_mini.png'; 
    ?>
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
        <div class="print-date">FECHA DE IMPRESIÓN: <?php echo date('d/m/Y'); ?></div>
    </div>
    
    <!-- Fila 5: Firma o nombre del auditor -->
    <div class="auditor-line">
        NOMBRE AUDITOR: ____________________________________________________________________________________
    </div>
    
</div>

        <table class="inventario-table">
            <thead>
                <tr>
                    <th class="col-no">No.</th>
                    <th class="col-codigo">Código</th>
                    <th class="col-nombre">Descripción / Nombre</th>
                    <th class="col-marca">Marca</th>
                    <!--th class="col-modelo">Modelo</th-->
                    <th class="col-udm">U. Medida</th>
                    <th class="col-stock">Stock</th>
                    <th class="col-stock">Stock Actual</th>
                    <th class="col-precio">Precio U.</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $numero = 1;
                foreach ($productos as $fila) {
                    echo '<tr>';
                    echo '<td class="col-no">' . $numero . '</td>';
                    echo '<td class="col-codigo">' . htmlspecialchars($fila['codigo']) . '</td>';
                    echo '<td class="col-nombre">' . htmlspecialchars($fila['nombre']) . '</td>';
                    echo '<td class="col-marca">' . htmlspecialchars($fila['marca']) . '</td>';
                    //echo '<td class="col-modelo">' . htmlspecialchars($fila['modelo']) . '</td>';
                    
                    echo '<td class="col-udm">' . htmlspecialchars($fila['unidad_medida_nombre'] ?? 'Pza') . '</td>'; 
                    
                    echo '<td class="col-stock">' . htmlspecialchars($fila['stock_actual']) . '</td>';
                    
                    echo '<td class="col-real"></td>'; 

					echo '<td class="col-stock">' . htmlspecialchars($fila['valor_total']) . '</td>';
                    
                    echo '</tr>';
                    $numero++;
                }
                ?>
            </tbody>
        </table>
    </div>

</body>
</html>