{{-- resources/views/historia-clinica/historial-historias/medicina-general/control.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Clínica Medicina General - Control</title>
    <link rel="stylesheet" href="{{ asset('css/vendor/fontawesome/css/all.min.css') }}">
    @include('historia-clinica.historial-historias.partials.styles')
</head>
<body>
    <div class="container-historia">
        {{-- ✅ BOTÓN REGRESAR (NO SE IMPRIME) --}}
        <div class="no-print">
            <a href="{{ route('historia-clinica.index') }}" class="btn-regresar">
                <i class="fas fa-arrow-left"></i>
                <span>Regresar a Historias Clínicas</span>
            </a>
        </div>

        {{-- ✅ ENCABEZADO CON BORDE AZUL --}}
        <div class="header-box">
            <div class="header-content">
                <div>
                    <img class="header-logo" src="{{ asset('images/logo-fundacion.png') }}" alt="Logo">
                </div>
                <div class="header-text">
                    <h3>FUNDACIÓN NACER PARA VIVIR IPS</h3>
                    <p>NIT: 900817959-1</p>
                    <p>CONTROL PROGRAMA DE GESTIÓN DEL RIESGO CARDIO RENAL</p>
                    <p>{{ $historia['cita']['fecha'] ?? date('Y-m-d') }}</p>
                </div>
                <div>
                    <img class="header-logo" src="{{ asset('images/logo-fundacion.png') }}" alt="Logo">
                </div>
            </div>
        </div>

        {{-- ✅ DATOS DEL PACIENTE --}}
        @php
            $paciente = $historia['cita']['paciente'] ?? null;
            $edad = 'N/A';
            $sexo = 'N/A';
            $estadoCivil = 'N/A';
            $telefono = 'N/A';
            $direccion = 'N/A';
            $departamento = 'N/A';
            $municipio = 'N/A';
            $empresa = 'N/A';
            $regimen = 'N/A';
            $ocupacion = 'N/A';
            $nombreCompleto = 'N/A';
            $brigada = 'N/A';
            $documento = 'N/A';
            $tipoDocumento = 'CC';
            $fechaNacimiento = 'N/A';
            
            if ($paciente) {
                if (isset($paciente['fecha_nacimiento']) && $paciente['fecha_nacimiento']) {
                    $fechaNac = \Carbon\Carbon::parse($paciente['fecha_nacimiento']);
                    $edad = $fechaNac->age . ' Años';
                    $fechaNacimiento = $paciente['fecha_nacimiento'];
                }
                
                $sexo = ($paciente['sexo'] ?? '') == 'M' ? 'MASCULINO' : (($paciente['sexo'] ?? '') == 'F' ? 'FEMENINO' : 'N/A');
                $estadoCivil = $paciente['estado_civil'] ?? 'N/A';
                $telefono = $paciente['telefono'] ?? 'N/A';
                $tipoDocumento = $paciente['tipo_documento'] ?? 'CC';
                $documento = $paciente['documento'] ?? 'N/A';
                $nombreCompleto = $paciente['nombre_completo'] ?? 'N/A';
                
                $departamento = $paciente['departamento']['nombre'] ?? 'N/A';
                $municipio = $paciente['municipio']['nombre'] ?? 'N/A';
                $direccion = ($paciente['direccion'] ?? '') . ' - ' . $municipio;
                $empresa = $paciente['empresa']['nombre'] ?? 'N/A';
                $regimen = $paciente['regimen']['nombre'] ?? 'N/A';
                $ocupacion = $paciente['ocupacion']['nombre'] ?? 'N/A';
                $brigada = $paciente['brigada']['nombre'] ?? 'N/A';
            }
        @endphp

        <fieldset>
            <legend>DATOS PACIENTE</legend>
            
            <div class="datos-grid">
                <div class="dato-item">
                    <div class="dato-label">NOMBRE</div>
                    <div class="dato-valor">{{ $tipoDocumento }} {{ $documento }}</div>
                    <div class="dato-valor">{{ $nombreCompleto }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">FECHA NACIMIENTO Y EDAD</div>
                    <div class="dato-valor">{{ $fechaNacimiento }}</div>
                    <div class="dato-valor">{{ $edad }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">SEXO</div>
                    <div class="dato-valor">{{ $sexo }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">ESTADO CIVIL</div>
                    <div class="dato-valor">{{ $estadoCivil }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">TELÉFONO</div>
                    <div class="dato-valor">{{ $telefono }}</div>
                </div>
            </div>

            <div class="datos-grid">
                <div class="dato-item">
                    <div class="dato-label">DIRECCIÓN</div>
                    <div class="dato-valor">{{ $direccion }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">ASEGURADORA</div>
                    <div class="dato-valor">{{ $empresa }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">RÉGIMEN</div>
                    <div class="dato-valor">{{ $regimen }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">OCUPACIÓN</div>
                    <div class="dato-valor">{{ $ocupacion }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">BRIGADA</div>
                    <div class="dato-valor">{{ $brigada }}</div>
                </div>
            </div>
        </fieldset>

        {{-- ✅ ACUDIENTE (SI EXISTE) --}}
        @if(!empty($historia['acompanante']))
        <fieldset>
            <legend>ACUDIENTE</legend>
            <div class="datos-grid-3">
                <div class="dato-item">
                    <div class="dato-label">NOMBRE ACOMPAÑANTE</div>
                    <div class="dato-valor">{{ $historia['acompanante'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">PARENTESCO</div>
                    <div class="dato-valor">{{ $historia['acu_parentesco'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">TELÉFONO</div>
                    <div class="dato-valor">{{ $historia['acu_telefono'] ?? 'N/A' }}</div>
                </div>
            </div>
        </fieldset>
        @endif

        {{-- ✅ MEDIDAS ANTROPOMÉTRICAS --}}
        <fieldset>
            <legend>MEDIDAS ANTROPOMÉTRICAS</legend>
            <div class="datos-grid">
                <div class="dato-item">
                    <div class="dato-label">PESO KG</div>
                    <div class="dato-valor">{{ $historia['peso'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">TALLA CM</div>
                    <div class="dato-valor">{{ $historia['talla'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">IMC</div>
                    <div class="dato-valor">{{ $historia['imc'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">CLASIFICACIÓN</div>
                    <div class="dato-valor">{{ $historia['clasificacion'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">PERÍMETRO ABDOMINAL</div>
                    <div class="dato-valor">{{ $historia['perimetro_abdominal'] ?? 'N/A' }}</div>
                </div>
                
            </div>
        </fieldset>

        {{-- ✅ TEST MORISKY --}}
        <fieldset>
            <legend>TEST MORISKY</legend>
            <div class="clasificacion-grid">
                <div class="clasificacion-columna">
                    <div class="examen-item">
                        <div class="examen-label">OLVIDA ALGUNA VEZ TOMAR SUS MEDICAMENTOS:</div>
                        <div class="examen-valor">{{ strtoupper($historia['test_morisky_olvida_tomar_medicamentos'] ?? 'NO') }}</div>
                    </div>
                    <div class="examen-item">
                        <div class="examen-label">TOMAR LOS MEDICAMENTOS A LA HORA INDICADA:</div>
                        <div class="examen-valor">{{ strtoupper($historia['test_morisky_toma_medicamentos_hora_indicada'] ?? 'SI') }}</div>
                    </div>
                    <div class="examen-item">
                        <div class="examen-label">CUANDO SE ENCUENTRA BIEN ¿DEJA DE TOMAR SUS MEDICAMENTOS?:</div>
                        <div class="examen-valor">{{ strtoupper($historia['test_morisky_cuando_esta_bien_deja_tomar_medicamentos'] ?? 'NO') }}</div>
                    </div>
                </div>
                <div class="clasificacion-columna">

                    <div class="examen-item">
                        <div class="examen-label">SI ALGUNA VEZ SE SIENTE MAL ¿DEJA DE TOMARLOS?:</div>
                        <div class="examen-valor">{{ strtoupper($historia['test_morisky_siente_mal_deja_tomarlos'] ?? 'NO') }}</div>
                    </div>
                    <div class="examen-item">
                        <div class="examen-label">VALORACIÓN POR PSICOLOGÍA:</div>
                        <div class="examen-valor">{{ strtoupper($historia['test_morisky_valoracio_psicologia'] ?? 'NO') }}</div>
                    </div>
                </div>
            </div>    
        </fieldset>

        {{-- ✅ REVISIÓN POR SISTEMAS --}}
        <fieldset>
            <legend>REVISIÓN POR SISTEMAS</legend>
            <div class="examen-fisico-grid">
                <div class="examen-item">
                    <div class="examen-label">GENERAL:</div>
                    <div class="examen-valor">{{ $historia['general'] ?? 'NORMAL NO REFIERE' }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">CABEZA:</div>
                    <div class="examen-valor">{{ $historia['cabeza'] ?? 'NORMAL NO REFIERE' }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">RESPIRATORIO:</div>
                    <div class="examen-valor">{{ $historia['respiratorio'] ?? 'NORMAL NO REFIERE' }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">CARDIOVASCULAR:</div>
                    <div class="examen-valor">{{ $historia['cardiovascular'] ?? 'NORMAL NO REFIERE' }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">GASTROINTESTINAL:</div>
                    <div class="examen-valor">{{ $historia['gastrointestinal'] ?? 'NORMAL NO REFIERE' }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">OSTEOATROMUSCULAR:</div>
                    <div class="examen-valor">{{ $historia['osteoatromuscular'] ?? 'NORMAL NO REFIERE' }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">SISTEMA NERVIOSO CENTRAL:</div>
                    <div class="examen-valor">{{ $historia['snc'] ?? 'NORMAL NO REFIERE' }}</div>
                </div>
            </div>
        </fieldset>

        {{-- ✅ SIGNOS VITALES --}}
        <fieldset>
            <legend>SIGNOS VITALES</legend>
            <div class="signos-vitales-grid">
                <div class="dato-item">
                    <div class="dato-label">PRESIÓN SISTÓLICA</div>
                    <div class="dato-valor">{{ $historia['presion_arterial_sistolica_sentado_pie'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">PRESIÓN DIASTÓLICA</div>
                    <div class="dato-valor">{{ $historia['presion_arterial_distolica_sentado_pie'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">FRECUENCIA CARDIACA</div>
                    <div class="dato-valor">{{ $historia['frecuencia_cardiaca'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">FRECUENCIA RESPIRATORIA</div>
                    <div class="dato-valor">{{ $historia['frecuencia_respiratoria'] ?? 'N/A' }}</div>
                </div>
            </div>
        </fieldset>

        {{-- ✅ EXAMEN FÍSICO POR SISTEMA --}}
        <fieldset>
            <legend>EXAMEN FÍSICO POR SISTEMA</legend>
            @php
                $examenFisico = [
                    ['key' => 'ef_cabeza', 'label' => 'CABEZA'],
                    ['key' => 'ef_agudeza_visual', 'label' => 'OJOS (AGUDEZA VISUAL)'],
                    ['key' => 'oidos', 'label' => 'OÍDOS'],
                    ['key' => 'nariz_senos_paranasales', 'label' => 'NARIZ Y SENOS PARANASALES'],
                    ['key' => 'cavidad_oral', 'label' => 'CAVIDAD ORAL'],
                    ['key' => 'ef_cuello', 'label' => 'CUELLO'],
                    ['key' => 'cardio_respiratorio', 'label' => 'CARDIO RESPIRATORIO'],
                    ['key' => 'ef_mamas', 'label' => 'MAMAS'],
                    ['key' => 'gastrointestinal', 'label' => 'GASTROINTESTINAL'],
                    ['key' => 'ef_genito_urinario', 'label' => 'GENITOURINARIO'],
                    ['key' => 'musculo_esqueletico', 'label' => 'MÚSCULO ESQUELÉTICO'],
                    ['key' => 'ef_piel_anexos_pulsos', 'label' => 'PIEL Y ANEXOS PULSOS'],
                    ['key' => 'inspeccion_sensibilidad_pies', 'label' => 'INSPECCIÓN Y SENSIBILIDAD EN PIES'],
                    ['key' => 'ef_sistema_nervioso', 'label' => 'SISTEMA NERVIOSO'],
                    ['key' => 'capacidad_congnitiva_orientacion', 'label' => 'CAPACIDAD COGNITIVA, ORIENTACIÓN'],
                    ['key' => 'ef_reflejo_aquiliar', 'label' => 'REFLEJO AQUILIANO'],
                    ['key' => 'ef_reflejo_patelar', 'label' => 'REFLEJO PATELAR']
                ];
            @endphp

            <div class="examen-fisico-grid">
                @foreach($examenFisico as $examen)
                <div class="examen-item">
                    <div class="examen-label">{{ $examen['label'] }}:</div>
                    <div class="examen-valor">{{ $historia[$examen['key']] ?? 'NORMAL NO REFIERE' }}</div>
                </div>
                @endforeach
            </div>

            <div class="examen-item">
                <div class="examen-label">ANTECEDENTE DISLIPIDEMIA FAMILIAR:</div>
                <div class="examen-valor">{{ strtoupper($historia['dislipidemia'] ?? 'NO') }}</div>
            </div>

            <div class="examen-item">
                <div class="examen-label">LESIÓN DE ÓRGANO BLANCO:</div>
                <div class="examen-valor">{{ strtoupper($historia['lesion_organo_blanco'] ?? 'NO') }}</div>
            </div>

            @if(!empty($historia['descripcion_lesion_organo_blanco']))
            <div class="observacion-box">
                <div class="observacion-titulo">DESCRIPCIÓN LESIÓN DE ÓRGANO BLANCO:</div>
                <div class="observacion-contenido">{{ $historia['descripcion_lesion_organo_blanco'] }}</div>
            </div>
            @endif
        </fieldset>

        {{-- ✅ EXÁMENES --}}
        <fieldset>
            <legend>EXÁMENES</legend>
            
            @if(!empty($historia['fex_es']) || !empty($historia['hcElectrocardiograma']))
            <div class="examen-item">
                <div class="examen-label">ELECTROCARDIOGRAMA ({{ $historia['fex_es'] ?? 'N/A' }}):</div>
                <div class="examen-valor">{{ $historia['electrocardiograma'] ?? 'N/A' }}</div>
            </div>
            @endif

            @if(!empty($historia['fex_es1']) || !empty($historia['hcEcocardiograma']))
            <div class="examen-item">
                <div class="examen-label">ECOCARDIOGRAMA ({{ $historia['fex_es1'] ?? 'N/A' }}):</div>
                <div class="examen-valor">{{ $historia['ecocardiograma'] ?? 'N/A' }}</div>
            </div>
            @endif

            @if(!empty($historia['fex_es2']) || !empty($historia['hcEcografiaRenal']))
            <div class="examen-item">
                <div class="examen-label">ECOGRAFÍA RENAL ({{ $historia['fex_es2'] ?? 'N/A' }}):</div>
                <div class="examen-valor">{{ $historia['ecografia_renal'] ?? 'N/A' }}</div>
            </div>
            @endif
        </fieldset>

        {{-- ✅ CLASIFICACIÓN --}}
        <fieldset>
            <legend>CLASIFICACIÓN</legend>
            
            <div class="clasificacion-grid">
                {{-- COLUMNA 1 --}}
                <div class="clasificacion-columna">
                    <div class="antecedentes-row">
                        <div class="antecedente-label">HIPERTENSIÓN ARTERIAL:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['hipertension_arterial_personal'] ?? 'NO') }}</div>
                    </div>
                    @if(!empty($historia['obs_hipertension_arterial_personal']))
                    <div class="antecedentes-row">
                        <div class="antecedente-label">OBSERVACIÓN:</div>
                        <div class="antecedente-valor">{{ $historia['obs_hipertension_arterial_personal'] }}</div>
                    </div>
                    @endif
                    <div class="antecedentes-row">
                        <div class="antecedente-label">CLASIFICACIÓN HTA:</div>
                        <div class="antecedente-valor">{{ $historia['clasificacion_hta'] ?? 'N/A' }}</div>
                    </div>

                    <div class="antecedentes-row">
                        <div class="antecedente-label">DIABETES MELLITUS:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['diabetes_mellitus_personal'] ?? 'NO') }}</div>
                    </div>
                    @if(!empty($historia['obs_diabetes_mellitus_personal']))
                    <div class="antecedentes-row">
                        <div class="antecedente-label">OBSERVACIÓN:</div>
                        <div class="antecedente-valor">{{ $historia['obs_diabetes_mellitus_personal'] }}</div>
                    </div>
                    @endif
                    <div class="antecedentes-row">
                        <div class="antecedente-label">CLASIFICACIÓN DM:</div>
                        <div class="antecedente-valor">{{ $historia['clasificacion_dm'] ?? 'NO DIABETICO' }}</div>
                    </div>
                    <div class="antecedentes-row">
                        <div class="antecedente-label">CLASIFICACIÓN ERC ESTADIO:</div>
                        <div class="antecedente-valor">{{ $historia['clasificacion_erc_estado'] ?? 'N/A' }}</div>
                    </div>

                    <div class="antecedentes-row">
                        <div class="antecedente-label">CLASIFICACIÓN ERC ESTADIO DOS:</div>
                        <div class="antecedente-valor">{{ $historia['clasificacion_erc_estadodos'] ?? 'N/A' }}</div>
                    </div>
                </div>

                {{-- COLUMNA 2 --}}
                <div class="clasificacion-columna">
                    


                    <div class="antecedentes-row">
                        <div class="antecedente-label">CLASIFICACIÓN ERC CATEGORÍA DE ALBUMINURIA PERSISTENTE:</div>
                        <div class="antecedente-valor">{{ $historia['clasificacion_erc_categoria_ambulatoria_persistente'] ?? 'N/A' }}</div>
                    </div>
                    <div class="antecedentes-row">
                        <div class="antecedente-label">CLASIFICACIÓN RIESGO CARDIO VASCULAR:</div>
                        <div class="antecedente-valor">{{ $historia['clasificacion_rcv'] ?? 'N/A' }}</div>
                    </div>
                    <div class="antecedentes-row">
                        <div class="antecedente-label">CLASIFICACIÓN ESTADO METABÓLICO:</div>
                        <div class="antecedente-valor">{{ $historia['clasificacion_estado_metabolico'] ?? 'N/A' }}</div>
                    </div>

                    <div class="antecedentes-row">
                        <div class="antecedente-label">TASA FILTRACIÓN GLOMERURAL CKD-EPI:</div>
                        <div class="antecedente-valor">{{ $historia['tasa_filtracion_glomerular_ckd_epi'] ?? 'N/A' }}</div>
                    </div>
                    <div class="antecedentes-row">
                        <div class="antecedente-label">TASA FILTRACIÓN GLOMERURAL COCKCROFT-GAULT:</div>
                        <div class="antecedente-valor">{{ $historia['tasa_filtracion_glomerular_gockcroft_gault'] ?? 'N/A' }}</div>
                    </div>
                    
                </div>
            </div>
        </fieldset>
        {{-- ✅ EDUCACIÓN --}}
        <fieldset>
            <legend>EDUCACIÓN</legend>
            <div class="examen-fisico-grid">
                <div class="examen-item">
                    <div class="examen-label">ALIMENTACIÓN:</div>
                    <div class="examen-valor">{{ strtoupper($historia['alimentacion'] ?? 'SI') }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">DISMINUCIÓN SAL/AZÚCAR:</div>
                    <div class="examen-valor">{{ strtoupper($historia['disminucion_consumo_sal_azucar'] ?? 'SI') }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">ACTIVIDAD FÍSICA:</div>
                    <div class="examen-valor">{{ strtoupper($historia['fomento_actividad_fisica'] ?? 'SI') }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">ADHERENCIA TRATAMIENTO:</div>
                    <div class="examen-valor">{{ strtoupper($historia['importancia_adherencia_tratamiento'] ?? 'SI') }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">FRUTAS Y VERDURAS:</div>
                    <div class="examen-valor">{{ strtoupper($historia['consumo_frutas_verduras'] ?? 'SI') }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">MANEJO ESTRÉS:</div>
                    <div class="examen-valor">{{ strtoupper($historia['manejo_estres'] ?? 'SI') }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">DISMINUCIÓN CIGARRILLO:</div>
                    <div class="examen-valor">{{ strtoupper($historia['disminucion_consumo_cigarrillo'] ?? 'SI') }}</div>
                </div>
                <div class="examen-item">
                    <div class="examen-label">DISMINUCIÓN PESO:</div>
                    <div class="examen-valor">{{ strtoupper($historia['disminucion_peso'] ?? 'SI') }}</div>
                </div>
            </div>
        </fieldset>

        {{-- ✅ OBSERVACIONES GENERALES --}}
        @if(!empty($historia['observaciones_generales']))
        <fieldset>
            <legend>OBSERVACIONES GENERALES</legend>
            <div class="observacion-contenido">
                {{ $historia['observaciones_generales'] }}
            </div>
        </fieldset>
        @endif

        {{-- ✅ REMISIONES (SI EXISTEN) --}}
        @if(!empty($historia['remisiones']) && count($historia['remisiones']) > 0)
        <fieldset>
            <legend>REMISIONES</legend>
            <table>
                <thead>
                    <tr>
                        <th>CÓDIGO</th>
                        <th>NOMBRE</th>
                        <th>OBSERVACIÓN</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historia['remisiones'] as $remision)
                    <tr>
                        <td>{{ $remision['remision']['codigo'] ?? 'N/A' }}</td>
                        <td>{{ $remision['remision']['nombre'] ?? 'N/A' }}</td>
                        <td>{{ $remision['observacion'] ?? 'Sin observaciones' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </fieldset>
        @endif

        {{-- ✅ AYUDAS DIAGNÓSTICAS (CUPS) --}}
        @if(!empty($historia['cups']) && count($historia['cups']) > 0)
        <fieldset>
            <legend>AYUDAS DIAGNÓSTICAS</legend>
            <table>
                <thead>
                    <tr>
                        <th>CÓDIGO</th>
                        <th>CUPS</th>
                        <th>OBSERVACIÓN</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historia['cups'] as $cup)
                    <tr>
                        <td>{{ $cup['cups']['codigo'] ?? 'N/A' }}</td>
                        <td>{{ $cup['cups']['nombre'] ?? 'N/A' }}</td>
                        <td>{{ $cup['observacion'] ?? 'Sin observaciones' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </fieldset>
        @endif

        {{-- ✅ MEDICAMENTOS --}}
        @if(!empty($historia['medicamentos']) && count($historia['medicamentos']) > 0)
        <fieldset>
            <legend>MEDICAMENTOS</legend>
            <table>
                <thead>
                    <tr>
                        <th>MEDICAMENTO</th>
                        <th>DOSIS</th>
                        <th>CANTIDAD</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historia['medicamentos'] as $med)
                    <tr>
                        <td>{{ $med['medicamento']['nombre'] ?? 'N/A' }}</td>
                        <td>{{ $med['dosis'] ?? 'N/A' }}</td>
                        <td>{{ $med['cantidad'] ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </fieldset>
        @endif

        {{-- ✅ DIAGNÓSTICOS --}}
        <fieldset>
            <legend>DIAGNÓSTICOS</legend>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>CÓDIGO</th>
                        <th>DIAGNÓSTICO</th>
                        <th>CLASIFICACIÓN</th>
                        <th>TIPO</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($historia['diagnosticos']) && is_array($historia['diagnosticos']))
                        @foreach($historia['diagnosticos'] as $index => $diag)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $diag['diagnostico']['codigo'] ?? 'N/A' }}</td>
                            <td>{{ $diag['diagnostico']['nombre'] ?? 'N/A' }}</td>
                            <td>{{ $diag['tipo'] == 'PRINCIPAL' ? 'PRINCIPAL' : 'TIPO' }}</td>
                            <td>
                                @if($diag['tipo_diagnostico'] == 'IMPRESION_DIAGNOSTICA')
                                    IMPRESIÓN DIAGNÓSTICA
                                @elseif($diag['tipo_diagnostico'] == 'CONFIRMADO_NUEVO')
                                    CONFIRMADO NUEVO
                                @elseif($diag['tipo_diagnostico'] == 'CONFIRMADO_REPETIDO')
                                    REPETIDO
                                @else
                                    {{ $diag['tipo_diagnostico'] }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    @else
                    <tr>
                        <td colspan="5" style="text-align: center;">No hay diagnósticos registrados</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </fieldset>

        {{-- ✅ CAUSA EXTERNA --}}
        <div class="observacion-box">
            <strong>CAUSA EXTERNA:</strong> {{ $historia['causa_externa'] ?? 'OTRA' }}
        </div>

        {{-- ✅ FINALIDAD --}}
        <div class="observacion-box">
            <strong>FINALIDAD:</strong> {{ $historia['finalidad'] ?? 'NO APLICA' }}
        </div>


        {{-- ✅ FIRMAS CON BORDE AZUL --}}
        @php
            $profesionalNombre = 'N/A';
            $profesionalProfesion = 'PSICOLOGÍA';
            $profesionalRegistro = 'N/A';
            $profesionalFirma = null;
            
            if (isset($historia['cita']['agenda']['usuario_medico'])) {
                $medico = $historia['cita']['agenda']['usuario_medico'];
                
                // ✅ NOMBRE COMPLETO (viene directo del backend)
                $profesionalNombre = $medico['nombre_completo'] ?? 'N/A';
                
                // ✅ ESPECIALIDAD (acceder al array anidado)
                $profesionalProfesion = isset($medico['especialidad']['nombre']) 
                    ? strtoupper($medico['especialidad']['nombre']) 
                    : 'PSICOLOGÍA';
                
                // ✅ REGISTRO PROFESIONAL
                $profesionalRegistro = $medico['registro_profesional'] ?? 'N/A';
                
                // ✅ FIRMA
                $profesionalFirma = $medico['firma'] ?? null;
            }
        @endphp

        <div class="firmas-box">
            <div class="firmas-content">
                <div class="firma-item">
                    @if($profesionalFirma)
                        <img class="firma-imagen" src="data:image/jpeg;base64,{{ $profesionalFirma }}" alt="Firma Digital">
                    @endif
                    <div class="firma-titulo">FIRMA DIGITAL</div>
                    <div class="firma-info">
                        PROFESIONAL: {{ $profesionalNombre }}<br>
                        {{ $profesionalProfesion }}<br>
                        RM: {{ $profesionalRegistro }}
                    </div>
                </div>
                <div class="firma-item">
                    <div class="firma-titulo">FIRMA PACIENTE</div>
                    <div class="firma-info">
                        {{ $tipoDocumento }}-{{ $documento }}<br>
                        {{ $nombreCompleto }}
                    </div>
                </div>
            </div>
        </div>

    </div>
    @include('historia-clinica.historial-historias.partials.scripts')

</body>
</html>
