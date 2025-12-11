<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background-color: #f5f5f5;
        font-family: Arial, sans-serif;
        font-size: 10px;
        padding: 15px;
        line-height: 1.3;
        color: #000;
    }

    .container-historia {
        max-width: 1200px;
        margin: 0 auto;
        background-color: white;
        padding: 20px 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    /* ========== BOTÓN REGRESAR ========== */
    .btn-regresar {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background-color: #6c757d;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        margin-bottom: 20px;
        transition: background-color 0.3s;
        font-size: 14px;
    }

    .btn-regresar:hover {
        background-color: #5a6268;
    }

    /* ========== ENCABEZADO ========== */
    .header-box {
        border: 2px solid #0f0fef;
        padding: 12px;
        margin-bottom: 12px;
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }

    .header-logo {
        width: 70px;
        height: auto;
    }

    .header-text {
        flex: 1;
        text-align: center;
    }

    .header-text h3 {
        font-size: 12px;
        font-weight: bold;
        margin-bottom: 3px;
        color: #000;
    }

    .header-text p {
        font-size: 9px;
        margin: 1px 0;
        color: #000;
    }

/* ========== CAMPOS DE HISTORIA (UNIFORMES) ========== */
.campo-historia {
    margin-bottom: 8px;
    padding: 6px;
    border-bottom: 1px solid #e0e0e0;
}

.campo-titulo {
    font-weight: bold;
    font-size: 8px;
    margin-bottom: 3px;
    color: #000;
    text-transform: uppercase;
}

.campo-contenido {
    font-size: 8px;
    text-align: justify;
    line-height: 1.4;
    color: #000;
}

    /* ========== FIELDSETS ========== */
    fieldset {
        border: 1px solid #0f0fef;
        margin-bottom: 10px;
        padding: 10px;
        page-break-inside: avoid;
    }

    legend {
        padding: 0 6px;
        font-size: 10px;
        font-weight: bold;
        color: #0f0fef;
    }

    /* ========== GRIDS DE DATOS ========== */
    .datos-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 8px;
        margin-bottom: 8px;
    }

    .datos-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }

    .datos-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    .dato-item {
        text-align: center;
    }

    .dato-label {
        font-weight: bold;
        font-size: 8px;
        margin-bottom: 3px;
        color: #000;
        text-transform: uppercase;
    }

    .dato-valor {
        font-size: 9px;
        color: #000;
    }

    /* ========== TABLAS ========== */
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 8px 0;
        page-break-inside: auto;
    }

    table, th, td {
        border: 1px solid #000;
    }

    th {
        background-color: #fff;
        color: #000;
        padding: 5px;
        text-align: center;
        font-weight: bold;
        font-size: 8px;
    }

    td {
        padding: 4px 5px;
        text-align: left;
        font-size: 8px;
        color: #000;
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    /* ========== ANTECEDENTES ========== */
    .antecedentes-row {
        display: grid;
        grid-template-columns: 180px 1fr;
        gap: 8px;
        margin-bottom: 6px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
    }

    .antecedente-label {
        font-weight: bold;
        font-size: 8px;
        color: #000;
    }

    .antecedente-valor {
        font-size: 8px;
        color: #000;
    }


    /* ========== SIGNOS VITALES ========== */
    .signos-vitales-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin: 8px 0;
    }

    /* ========== EXAMEN FÍSICO ========== */
    .examen-fisico-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
        margin: 8px 0;
    }

    .examen-item {
        padding: 4px;
        border-bottom: 1px solid #ddd;
    }

    .examen-label {
        font-weight: bold;
        font-size: 8px;
        color: #000;
        margin-bottom: 2px;
    }

    .examen-valor {
        font-size: 8px;
        color: #000;
    }

    /* ========== OBSERVACIONES ========== */
    .observacion-box {
        border: 1px solid #ddd;
        padding: 6px;
        margin: 6px 0;
        background-color: #f9f9f9;
        page-break-inside: avoid;
    }

    .observacion-titulo {
        font-weight: bold;
        font-size: 8px;
        margin-bottom: 3px;
        color: #000;
    }

    .observacion-contenido {
        font-size: 8px;
        text-align: justify;
        line-height: 1.4;
        color: #000;
    }

    /* ========== FIRMAS ========== */
    .firmas-box {
        border: 1px solid #0f0fef;
        padding: 12px;
        margin-top: 12px;
        page-break-inside: avoid;
    }

    .firmas-content {
        display: flex;
        justify-content: space-around;
        align-items: flex-start;
        gap: 25px;
    }

    .firma-item {
        flex: 1;
        text-align: center;
    }

    .firma-imagen {
        width: 200px;
        height: 55px;
        margin-bottom: 6px;
    }

    .firma-titulo {
        font-weight: bold;
        font-size: 9px;
        margin-bottom: 3px;
        color: #000;
    }

    .firma-info {
        font-size: 8px;
        font-style: italic;
        color: #000;
    }

    /* ========== OCULTAR EN IMPRESIÓN ========== */
    .no-print {
        display: block;
    }

    /* ========================================
       ESTILOS PARA IMPRESIÓN - OPTIMIZADOS
       ======================================== */
    @media print {
        @page {
            size: letter;
            margin: 0.4cm 0.5cm; /* Márgenes mínimos pero legibles */
        }

        body {
            padding: 0;
            background-color: white;
            font-size: 7.5px; /* Tamaño base para impresión */
            line-height: 1.15;
        }

        .container-historia {
            box-shadow: none;
            padding: 3px;
            max-width: 100%;
        }

        .no-print {
            display: none !important;
        }

        /* ===== ENCABEZADO COMPACTO ===== */
        .header-box {
            padding: 5px;
            margin-bottom: 5px;
            border-width: 1.5px;
        }

        .header-logo {
            width: 40px;
        }

        .header-text h3 {
            font-size: 8.5px;
            margin-bottom: 1px;
        }

        .header-text p {
            font-size: 6.5px;
            margin: 0;
        }

        /* ===== FIELDSETS COMPACTOS ===== */
        fieldset {
            margin-bottom: 4px;
            padding: 4px;
            page-break-inside: avoid;
        }

        legend {
            font-size: 7.5px;
            padding: 0 3px;
        }

        /* ===== DATOS COMPACTOS ===== */
        .dato-label {
            font-size: 6px;
            margin-bottom: 1px;
        }

        .dato-valor {
            font-size: 6.5px;
        }

        .datos-grid,
        .datos-grid-3,
        .datos-grid-2 {
            gap: 3px;
            margin-bottom: 3px;
        }

        /* ===== TABLAS COMPACTAS ===== */
        table {
            margin: 3px 0;
        }

        th {
            padding: 1.5px 2px;
            font-size: 6px;
        }

        td {
            padding: 1.5px 2px;
            font-size: 6px;
        }

        /* ===== ANTECEDENTES COMPACTOS ===== */
        .antecedentes-row {
            grid-template-columns: 140px 1fr;
            gap: 5px;
            margin-bottom: 4px;
            padding-bottom: 3px;
        }

        .antecedente-label {
            font-size: 6px;
        }

        .antecedente-valor {
            font-size: 6px;
        }

        /* ===== SIGNOS VITALES COMPACTOS ===== */
        .signos-vitales-grid {
            gap: 3px;
            margin: 3px 0;
        }

        /* ===== EXAMEN FÍSICO COMPACTO (3 COLUMNAS) ===== */
        .examen-fisico-grid {
            gap: 2px;
            margin: 3px 0;
            grid-template-columns: repeat(3, 1fr); /* 3 columnas para ahorrar espacio */
        }

        .examen-item {
            padding: 1.5px;
        }

        .examen-label {
            font-size: 6px;
        }

        .examen-valor {
            font-size: 6px;
        }

        /* ===== OBSERVACIONES COMPACTAS ===== */
        .observacion-box {
            padding: 3px;
            margin: 3px 0;
        }

        .observacion-titulo {
            font-size: 6px;
        }

        .observacion-contenido {
            font-size: 6px;
            line-height: 1.15;
        }

        /* ===== FIRMAS COMPACTAS ===== */
        .firmas-box {
            padding: 5px;
            margin-top: 5px;
        }

        .firmas-content {
            gap: 12px;
        }

        .firma-imagen {
            width: 130px;
            height: 38px;
            margin-bottom: 3px;
        }

        .firma-titulo {
            font-size: 6.5px;
            margin-bottom: 2px;
        }

        .firma-info {
            font-size: 5.5px;
        }

        /* ===== OPTIMIZACIONES GENERALES ===== */
        h1, h2, h3, h4, h5, h6 {
            page-break-after: avoid;
        }

        /* Forzar colores en impresión */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* Ocultar fieldsets vacíos */
        fieldset:empty {
            display: none;
        }

        /* Optimizar grids */
        .datos-grid {
            grid-template-columns: repeat(5, 1fr);
        }

        .datos-grid-3 {
            grid-template-columns: repeat(3, 1fr);
        }

        .datos-grid-2 {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .clasificacion-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-top: 10px;
    }

    .clasificacion-columna {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    /* Responsive: en pantallas pequeñas vuelve a 1 columna */
    @media (max-width: 768px) {
        .clasificacion-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
