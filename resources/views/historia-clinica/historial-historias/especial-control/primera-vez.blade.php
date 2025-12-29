{{-- resources/views/historia-clinica/historial-historias/medicina-general/primera-vez.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Clínica Medicina General - Primera Vez</title>
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
                    <p>PRIMERA VEZ DE APERTURA PROGRAMA DE GESTIÓN DEL RIESGO CARDIO RENAL</p>
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
                
                // Manejar tipo_documento como string o array (offline/online)
                $tipoDoc = $paciente['tipo_documento'] ?? 'CC';
                $tipoDocumento = is_array($tipoDoc) ? ($tipoDoc['abreviacion'] ?? $tipoDoc['nombre'] ?? 'CC') : $tipoDoc;
                
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

        {{-- ✅ ANTECEDENTES - DISCAPACIDADES --}}
        <fieldset>
            <legend>ANTECEDENTES</legend>
            
            <div class="datos-grid">
                <div class="dato-item">
                    <div class="dato-label">DISCAPACIDAD FÍSICA</div>
                    <div class="dato-valor">{{ strtoupper($historia['discapacidad_fisica'] ?? 'NO') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">DISCAPACIDAD VISUAL</div>
                    <div class="dato-valor">{{ strtoupper($historia['discapacidad_visual'] ?? 'NO') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">DISCAPACIDAD MENTAL</div>
                    <div class="dato-valor">{{ strtoupper($historia['discapacidad_mental'] ?? 'NO') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">DISCAPACIDAD AUDITIVA</div>
                    <div class="dato-valor">{{ strtoupper($historia['discapacidad_auditiva'] ?? 'NO') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">DISCAPACIDAD INTELECTUAL</div>
                    <div class="dato-valor">{{ strtoupper($historia['discapacidad_intelectual'] ?? 'NO') }}</div>
                </div>
            </div>

            <div class="datos-grid-3">
                <div class="dato-item">
                    <div class="dato-label">DROGODEPENDIENTE?</div>
                    <div class="dato-valor">{{ strtoupper($historia['drogodependiente'] ?? 'NO') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">DROGODEPENDIENTE CUAL</div>
                    <div class="dato-valor">{{ strtoupper($historia['drogodependiente_cual'] ?? 'NO') }}</div>
                </div>
            </div>
        </fieldset>

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
            @if(!empty($historia['obs_perimetro_abdominal']))
            <div class="observacion-box">
                <div class="observacion-titulo">OBSERVACIONES PERÍMETRO ABDOMINAL:</div>
                <div class="observacion-contenido">{{ $historia['obs_perimetro_abdominal'] }}</div>
            </div>
            @endif
        </fieldset>

        {{-- ✅ ANTECEDENTES FAMILIARES --}}
        <fieldset>
            <legend>ANTECEDENTES FAMILIARES</legend>
            
            <div class="clasificacion-grid">
                {{-- COLUMNA 1 --}}
                <div class="clasificacion-columna">
                    <div class="antecedentes-row">
                        <div class="antecedente-label">¿HIPERTENSIÓN ARTERIAL?:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['hipertension_arterial'] ?? 'NO') }}</div>
                    </div>
                    @if(!empty($historia['parentesco_hipertension_arterial']))
                    <div class="antecedentes-row">
                        <div class="antecedente-label">PARENTESCO Y DESCRIPCIÓN:</div>
                        <div class="antecedente-valor">{{ $historia['parentesco_hipertension_arterial'] }}</div>
                    </div>
                    @endif

                    <div class="antecedentes-row">
                        <div class="antecedente-label">¿DIABETES MELLITUS?</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['diabetes_mellitus'] ?? 'NO') }}</div>
                    </div>
                    @if(!empty($historia['parentesco_diabetes_mellitus']))
                    <div class="antecedentes-row">
                        <div class="antecedente-label">PARENTESCO Y DESCRIPCIÓN:</div>
                        <div class="antecedente-valor">{{ $historia['parentesco_diabetes_mellitus'] }}</div>
                    </div>
                    @endif

                    <div class="antecedentes-row">
                        <div class="antecedente-label">¿ARTRITIS?:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['artritis'] ?? 'NO') }}</div>
                    </div>
                    @if(!empty($historia['parentesco_artritis']))
                    <div class="antecedentes-row">
                        <div class="antecedente-label">PARENTESCO Y DESCRIPCIÓN:</div>
                        <div class="antecedente-valor">{{ $historia['parentesco_artritis'] }}</div>
                    </div>
                    @endif

                    <div class="antecedentes-row">
                        <div class="antecedente-label">¿ENF. CARDIOVASCULAR?:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['enfermedad_cardiovascular'] ?? 'NO') }}</div>
                    </div>
                    @if(!empty($historia['parentesco_enfermedad_cardiovascular']))
                    <div class="antecedentes-row">
                        <div class="antecedente-label">PARENTESCO Y DESCRIPCIÓN:</div>
                        <div class="antecedente-valor">{{ $historia['parentesco_enfermedad_cardiovascular'] }}</div>
                    </div>
                    @endif

                    <div class="antecedentes-row">
                        <div class="antecedente-label">ANTECEDENTES METABÓLICOS:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['antecedentes_metabolico'] ?? 'NO') }}</div>
                    </div>
                    @if(!empty($historia['parentesco_antecedentes_metabolico']))
                    <div class="antecedentes-row">
                        <div class="antecedente-label">PARENTESCO Y DESCRIPCIÓN:</div>
                        <div class="antecedente-valor">{{ $historia['parentesco_antecedentes_metabolico'] }}</div>
                    </div>
                    @endif
                </div>

                {{-- COLUMNA 2 --}}
                <div class="clasificacion-columna">
                    <div class="antecedentes-row">
                        <div class="antecedente-label">CÁNCER (MAMA, ESTÓMAGO, PRÓSTATA, COLON):</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['cancer'] ?? 'NO') }}</div>
                    </div>
                    @if(!empty($historia['parentesco_cancer']))
                    <div class="antecedentes-row">
                        <div class="antecedente-label">PARENTESCO Y DESCRIPCIÓN:</div>
                        <div class="antecedente-valor">{{ $historia['parentesco_cancer'] }}</div>
                    </div>
                    @endif

                    <div class="antecedentes-row">
                        <div class="antecedente-label">LEUCEMIA:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['lucemia'] ?? 'NO') }}</div>
                    </div>
                    @if(!empty($historia['parentesco_lucemia']))
                    <div class="antecedentes-row">
                        <div class="antecedente-label">PARENTESCO Y DESCRIPCIÓN:</div>
                        <div class="antecedente-valor">{{ $historia['parentesco_lucemia'] }}</div>
                    </div>
                    @endif

                    <div class="antecedentes-row">
                        <div class="antecedente-label">VIH:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['vih'] ?? 'NO') }}</div>
                    </div>
                    @if(!empty($historia['parentesco_vih']))
                    <div class="antecedentes-row">
                        <div class="antecedente-label">PARENTESCO Y DESCRIPCIÓN:</div>
                        <div class="antecedente-valor">{{ $historia['parentesco_vih'] }}</div>
                    </div>
                    @endif

                    <div class="antecedentes-row">
                        <div class="antecedente-label">OTROS:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['otro'] ?? 'NO') }}</div>
                    </div>
                    @if(!empty($historia['parentesco_otro']))
                    <div class="antecedentes-row">
                        <div class="antecedente-label">PARENTESCO Y DESCRIPCIÓN:</div>
                        <div class="antecedente-valor">{{ $historia['parentesco_otro'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </fieldset>


        {{-- ✅ ANTECEDENTES PERSONALES --}}
        <fieldset>
            <legend>ANTECEDENTES PERSONALES</legend>
            
            @php
                // COLUMNA 1 - 8 items
                $antecedentesCol1 = [
                    ['key' => 'enfermedad_cardiovascular_personal', 'obs' => 'obs_enfermedad_cardiovascular_personal', 'label' => 'ENFERMEDAD CARDIOVASCULAR'],
                    ['key' => 'arterial_periferica_personal', 'obs' => 'obs_arterial_periferica_personal', 'label' => 'ENFERMEDAD ARTERIAL PERIFÉRICA'],
                    ['key' => 'carotidea_personal', 'obs' => 'obs_carotidea_personal', 'label' => 'ENFERMEDAD CAROTÍDEA'],
                    ['key' => 'aneurisma_aorta_peronal', 'obs' => 'obs_aneurisma_aorta_peronal', 'label' => 'ANEURISMA AORTA'],
                    ['key' => 'coronario_personal', 'obs' => 'obs_coronario_personal', 'label' => 'SÍNDROME CORONARIO AGUDO-ANGINA'],
                    ['key' => 'artritis_personal', 'obs' => 'obs_artritis_personal', 'label' => 'ARTRITIS'],
                    ['key' => 'iam_personal', 'obs' => 'obs_iam_personal', 'label' => 'INFARTO AGUDO MIOCARDIO'],
                    ['key' => 'revascul_coronaria_personal', 'obs' => 'obs_revascul_coronaria_personal', 'label' => 'REVASCUL CORONARIA'],
                ];

                // COLUMNA 2 - 7 items
                $antecedentesCol2 = [
                    ['key' => 'insuficiencia_cardiaca_personal', 'obs' => 'obs_insuficiencia_cardiaca_personal', 'label' => 'INSUFICIENCIA CARDIACA'],
                    ['key' => 'amputacion_pie_diabetico_personal', 'obs' => 'obs_amputacion_pie_diabetico_personal', 'label' => 'AMPUTACIÓN PIE DIABÉTICO'],
                    ['key' => 'enfermedad_pulmonar_personal', 'obs' => 'obs_enfermedad_pulmonar_personal', 'label' => 'ENFERMEDAD PULMONARES (TB-TB-MDR) OTRAS'],
                    ['key' => 'victima_maltrato_personal', 'obs' => 'obs_victima_maltrato_personal', 'label' => 'VÍCTIMA DE MALTRATO/VIOLENCIA SEXUAL'],
                    ['key' => 'antecedentes_quirurgicos_personal', 'obs' => 'obs_antecedentes_quirurgicos_personal', 'label' => 'ANTECEDENTES QUIRÚRGICOS'],
                    ['key' => 'acontosis_personal', 'obs' => 'obs_acontosis_personal', 'label' => 'ACANTOSIS NIGRICANS'],
                    ['key' => 'otro_personal', 'obs' => 'obs_otro_personal', 'label' => 'OTROS']
                ];
            @endphp

            <div class="clasificacion-grid">
                {{-- COLUMNA 1 --}}
                <div class="clasificacion-columna">
                    @foreach($antecedentesCol1 as $ant)
                    <div class="antecedentes-row">
                        <div class="antecedente-label">{{ $ant['label'] }}:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia[$ant['key']] ?? 'NO') }}</div>
                    </div>
                    @if(!empty($historia[$ant['obs']]))
                    <div class="antecedentes-row">
                        <div class="antecedente-label">OBSERVACIÓN:</div>
                        <div class="antecedente-valor">{{ $historia[$ant['obs']] }}</div>
                    </div>
                    @endif
                    @endforeach
                </div>

                {{-- COLUMNA 2 --}}
                <div class="clasificacion-columna">
                    @foreach($antecedentesCol2 as $ant)
                    <div class="antecedentes-row">
                        <div class="antecedente-label">{{ $ant['label'] }}:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia[$ant['key']] ?? 'NO') }}</div>
                    </div>
                    @if(!empty($historia[$ant['obs']]))
                    <div class="antecedentes-row">
                        <div class="antecedente-label">OBSERVACIÓN:</div>
                        <div class="antecedente-valor">{{ $historia[$ant['obs']] }}</div>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
        </fieldset>


        {{-- ✅ TEST MORISKY --}}
        <fieldset>
            <legend>TEST MORISKY</legend>
            <div class="clasificacion-grid">
                <div class="clasificacion-columna">
                    <div class="antecedentes-row">
                        <div class="antecedente-label">OLVIDA ALGUNA VEZ TOMAR SUS MEDICAMENTOS:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['test_morisky_olvida_tomar_medicamentos'] ?? 'NO') }}</div>
                    </div>
                    <div class="antecedentes-row">
                        <div class="antecedente-label">TOMAR LOS MEDICAMENTOS A LA HORA INDICADA:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['test_morisky_toma_medicamentos_hora_indicada'] ?? 'SI') }}</div>
                    </div>
                    <div class="antecedentes-row">
                        <div class="antecedente-label">CUANDO SE ENCUENTRA BIEN ¿DEJA DE TOMAR SUS MEDICAMENTOS?:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['test_morisky_cuando_esta_bien_deja_tomar_medicamentos'] ?? 'NO') }}</div>
                    </div>
                </div>
                <div class="clasificacion-columna">
                    <div class="antecedentes-row">
                        <div class="antecedente-label">SI ALGUNA VEZ SE SIENTE MAL ¿DEJA DE TOMARLOS?:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['test_morisky_siente_mal_deja_tomarlos'] ?? 'NO') }}</div>
                    </div>
                    <div class="antecedentes-row">
                        <div class="antecedente-label">VALORACIÓN POR PSICOLOGÍA:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia['test_morisky_valoracio_psicologia'] ?? 'NO') }}</div>
                    </div>
                </div>
            </div>
        </fieldset>

        {{-- ✅ OTROS TRATAMIENTOS --}}
        <fieldset>
            <legend>OTROS TRATAMIENTOS</legend>
            <div class="datos-grid-3">
                <div class="dato-item">
                    <div class="dato-label">RECIBE TRATAMIENTO ALTERNATIVO?</div>
                    <div class="dato-valor">{{ strtoupper($historia['recibe_tratamiento_alternativo'] ?? 'NO') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">¿RECIBE TRATAMIENTO CON PLANTAS MEDICINALES?</div>
                    <div class="dato-valor">{{ strtoupper($historia['recibe_tratamiento_plantas_medicinales'] ?? 'NO') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">¿RECIBE O REALIZA RITUALIDAD POR MEDICINA TRADICIONAL?</div>
                    <div class="dato-valor">{{ strtoupper($historia['recibe_ritual_medicina_tradicional'] ?? 'NO') }}</div>
                </div>
            </div>
        </fieldset>

        {{-- ✅ REVISIÓN POR SISTEMA --}}
        <fieldset>
            <legend>REVISIÓN POR SISTEMA</legend>
            <div class="datos-grid-3">
                <div class="dato-item">
                    <div class="dato-label">GENERAL</div>
                    <div class="dato-valor">{{ strtoupper($historia['general'] ?? 'NORMAL') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">CABEZA</div>
                    <div class="dato-valor">{{ strtoupper($historia['ef_cabeza'] ?? 'NORMAL') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">RESPIRATORIO</div>
                    <div class="dato-valor">{{ strtoupper($historia['respiratorio'] ?? 'NORMAL') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">CARDIOVASCULAR</div>
                    <div class="dato-valor">{{ strtoupper($historia['cardiovascular'] ?? 'NORMAL') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">GASTROINTESTINAL</div>
                    <div class="dato-valor">{{ strtoupper($historia['gastrointestinal'] ?? 'NORMAL') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">OSTEOATROMUSCULAR</div>
                    <div class="dato-valor">{{ strtoupper($historia['osteoatromuscular'] ?? 'NORMAL') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">SISTEMA NERVIOSO CENTRAL</div>
                    <div class="dato-valor">{{ strtoupper($historia['sistema_nervioso_central'] ?? 'NORMAL') }}</div>
                </div>
            </div>
        </fieldset>

        {{-- ✅ EXAMEN FÍSICO - SIGNOS VITALES --}}
        <fieldset>
            <legend>EXAMEN FÍSICO</legend>
            <div class="signos-vitales-grid">
                <div class="dato-item">
                    <div class="dato-label">PRESIÓN ARTERIAL SISTÓLICA</div>
                    <div class="dato-valor">{{ $historia['presion_arterial_sistolica_sentado_pie'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">PRESION ARTERIAL DISTOLICA</div>
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

                    @php
                // COLUMNA 1 - 6 items
                $examenFisicoCol1 = [
                    ['key' => 'ef_cabeza', 'obs' => 'ef_obs_cabeza', 'label' => 'CABEZA'],
                    ['key' => 'ef_agudeza_visual', 'obs' => 'ef_obs_agudeza_visual', 'label' => 'AGUDEZA VISUAL'],
                    ['key' => 'ef_cuello', 'obs' => 'ef_obs_cuello', 'label' => 'CUELLO'],
                    ['key' => 'ef_torax', 'obs' => 'ef_obs_torax', 'label' => 'TORAX'],
                    ['key' => 'ef_mamas', 'obs' => 'ef_obs_mamas', 'label' => 'MAMAS'],
                    ['key' => 'ef_abdomen', 'obs' => 'ef_obs_abdomen', 'label' => 'ABDOMEN'],
                ];

                // COLUMNA 2 - 5 items
                $examenFisicoCol2 = [
                    ['key' => 'ef_genito_urinario', 'obs' => 'ef_obs_genito_urinario', 'label' => 'GENITO URINARIO'],
                    ['key' => 'ef_extremidades', 'obs' => 'ef_obs_extremidades', 'label' => 'EXTREMIDADES'],
                    ['key' => 'ef_piel_anexos_pulsos', 'obs' => 'ef_obs_piel_anexos_pulsos', 'label' => 'PIEL Y ANEXOS PULSOS'],
                    ['key' => 'ef_sistema_nervioso', 'obs' => 'ef_obs_sistema_nervioso', 'label' => 'SISTEMA NERVIOSO'],
                    ['key' => 'ef_orientacion', 'obs' => 'ef_obs_orientacion', 'label' => 'ORIENTACIÓN']
                ];
            @endphp

            <div class="clasificacion-grid">
                {{-- COLUMNA 1 --}}
                <div class="clasificacion-columna">
                    @foreach($examenFisicoCol1 as $examen)
                    <div class="antecedentes-row">
                        <div class="antecedente-label">{{ $examen['label'] }}:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia[$examen['key']] ?? 'NORMAL') }}</div>
                    </div>
                    @if(!empty($historia[$examen['obs']]))
                    <div class="antecedentes-row">
                        <div class="antecedente-label">OBSERVACIONES:</div>
                        <div class="antecedente-valor">{{ $historia[$examen['obs']] }}</div>
                    </div>
                    @endif
                    @endforeach
                </div>

                {{-- COLUMNA 2 --}}
                <div class="clasificacion-columna">
                    @foreach($examenFisicoCol2 as $examen)
                    <div class="antecedentes-row">
                        <div class="antecedente-label">{{ $examen['label'] }}:</div>
                        <div class="antecedente-valor">{{ strtoupper($historia[$examen['key']] ?? 'NORMAL') }}</div>
                    </div>
                    @if(!empty($historia[$examen['obs']]))
                    <div class="antecedentes-row">
                        <div class="antecedente-label">OBSERVACIONES:</div>
                        <div class="antecedente-valor">{{ $historia[$examen['obs']] }}</div>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>

            {{-- HALLAZGOS POSITIVOS - Ocupa todo el ancho --}}
            @if(!empty($historia['ef_hallazco_positivo_examen_fisico']))
            <div class="observacion-box">
                <div class="observacion-titulo">HALLAZGOS POSITIVOS AL EXAMEN FISICO:</div>
                <div class="observacion-contenido">{{ $historia['ef_hallazco_positivo_examen_fisico'] }}</div>
            </div>
            @endif

        </fieldset>

        {{-- ✅ FACTORES DE RIESGO --}}
        <fieldset>
            <legend>FACTORES DE RIESGO</legend>
            <div class="datos-grid">
                <div class="dato-item">
                    <div class="dato-label">NÚMERO DE PORCIONES DE FRUTAS Y VERDURAS DIARIAS</div>
                    <div class="dato-valor">{{ $historia['numero_frutas_diarias'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">ELEVADO CONSUMO DE GRASAS SATURADAS</div>
                    <div class="dato-valor">{{ strtoupper($historia['elevado_consumo_grasa_saturada'] ?? 'NO') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">ADICIONA SAL DESPUÉS DE PREPARADOS LOS ALIMENTOS</div>
                    <div class="dato-valor">{{ strtoupper($historia['adiciona_sal_despues_preparar_alimentos'] ?? 'NO') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">DISLIPIDEMIA</div>
                    <div class="dato-valor">{{ strtoupper($historia['dislipidemia'] ?? 'NO') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">CONDICIÓN CLÍNICA ASOCIADA</div>
                    <div class="dato-valor">{{ strtoupper($historia['condicion_clinica_asociada'] ?? 'NO') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">LESIÓN DE ÓRGANO BLANCO</div>
                    <div class="dato-valor">{{ strtoupper($historia['lesion_organo_blanco'] ?? 'NO') }}</div>
                </div>
            </div>
        </fieldset>

        {{-- ✅ EXÁMENES --}}
        <fieldset>
            <legend>EXÁMENES</legend>
            
   
            <div class="antecedentes-row">
                <div class="antecedente-label">ELECTROCARDIOGRAMA:</div>
                <div class="antecedente-valor">{{ $historia['fex_es'] ?? 'N/A' }}</div>
            </div>
          
            <div class="observacion-box">
                <div class="observacion-contenido">{{ $historia['electrocardiograma'] }}</div>
            </div>
 
            <div class="antecedentes-row">
                <div class="antecedente-label">ECOCARDIOGRAMA:</div>
                <div class="antecedente-valor">{{ $historia['fex_es1'] ?? 'N/A' }}</div>
            </div>
            
            <div class="observacion-box">
                <div class="observacion-contenido">{{ $historia['ecocardiograma'] }}</div>
            </div>
           

            
            <div class="antecedentes-row">
                <div class="antecedente-label">ECOGRAFÍA RENAL:</div>
                <div class="antecedente-valor">{{ $historia['fex_es2'] ?? 'N/A' }}</div>
            </div>
            
            <div class="observacion-box">
                <div class="observacion-contenido">{{ $historia['ecografia_renal'] }}</div>
            </div>
           
         
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
            <div class="datos-grid-3">
                <div class="dato-item">
                    <div class="dato-label">ALIMENTACIÓN</div>
                    <div class="dato-valor">{{ strtoupper($historia['alimentacion'] ?? 'SI') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">DISMINUCIÓN DE CONSUMO DE SAL/AZÚCAR</div>
                    <div class="dato-valor">{{ strtoupper($historia['disminucion_consumo_sal_azucar'] ?? 'SI') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">FOMENTO DE ACTIVIDAD FÍSICA</div>
                    <div class="dato-valor">{{ strtoupper($historia['fomento_actividad_fisica'] ?? 'SI') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">IMPORTANCIA DE ADHERENCIA A TRATAMIENTO</div>
                    <div class="dato-valor">{{ strtoupper($historia['importancia_adherencia_tratamiento'] ?? 'SI') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">CONSUMO DE FRUTAS Y VERDURAS</div>
                    <div class="dato-valor">{{ strtoupper($historia['consumo_frutas_verduras'] ?? 'SI') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">MANEJO DEL ESTRÉS</div>
                    <div class="dato-valor">{{ strtoupper($historia['manejo_estres'] ?? 'SI') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">DISMINUCIÓN DE CONSUMO CIGARRILLO</div>
                    <div class="dato-valor">{{ strtoupper($historia['disminucion_consumo_cigarrillo'] ?? 'SI') }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">DISMINUCIÓN DE PESO</div>
                    <div class="dato-valor">{{ strtoupper($historia['disminucion_peso'] ?? 'SI') }}</div>
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
        

        {{-- ✅ REMISIÓN --}}
        @if(!empty($historia['remisiones']) && count($historia['remisiones']) > 0)
        <fieldset>
            <legend>REMISIÓN</legend>
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
            <legend>AYUDA DIAGNOSTICAS</legend>
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
            <legend>MEDICAMENTO</legend>
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
            <legend>DIAGNÓSTICO</legend>
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
