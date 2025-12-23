{{-- resources/views/historia-clinica/historial-historias/internista/control.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Clínica Internista - Control</title>
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
                    <div class="dato-valor">{{ $historia['acompanante'] ?? 'Sin acompañante' }}</div>
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

        {{-- ✅ HISTORIA CLÍNICA --}}
        <fieldset>
            <legend>HISTORIA CLÍNICA</legend>
            
            @if(!empty($historia['motivo_consulta']))
            <div class="campo-historia">
                <div class="campo-titulo">MOTIVO CONSULTA</div>
                <div class="campo-contenido">{{ $historia['motivo_consulta'] }}</div>
            </div>
            @endif

            @if(!empty($historia['enfermedad_actual']))
            <div class="campo-historia">
                <div class="campo-titulo">ENFERMEDAD ACTUAL</div>
                <div class="campo-contenido">{{ $historia['enfermedad_actual'] }}</div>
            </div>
            @endif
        </fieldset>

        {{-- ✅ ANTECEDENTES PERSONALES --}}
        @php
            $complementaria = $historia['complementaria'] ?? [];
        @endphp

        @if(!empty($complementaria))
        <fieldset>
            <legend>ANTECEDENTES PERSONALES</legend>
            
            <div class="examen-fisico-grid">
                @if(isset($complementaria['ef_sistema_nervioso']))
                <div class="examen-item">
                    <div class="examen-label">SISTEMA NERVIOSO:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['ef_sistema_nervioso'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_sistema_nervioso']))
                    <div class="examen-obs">{{ $complementaria['descripcion_sistema_nervioso'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['sistema_hemolinfatico']))
                <div class="examen-item">
                    <div class="examen-label">SISTEMA HEMOLINFÁTICO:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['sistema_hemolinfatico'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_sistema_hemolinfatico']))
                    <div class="examen-obs">{{ $complementaria['descripcion_sistema_hemolinfatico'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['aparato_digestivo']))
                <div class="examen-item">
                    <div class="examen-label">APARATO DIGESTIVO:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['aparato_digestivo'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_aparato_digestivo']))
                    <div class="examen-obs">{{ $complementaria['descripcion_aparato_digestivo'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['organo_sentido']))
                <div class="examen-item">
                    <div class="examen-label">ÓRGANOS DE LOS SENTIDOS:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['organo_sentido'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_organos_sentidos']))
                    <div class="examen-obs">{{ $complementaria['descripcion_organos_sentidos'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['endocrino_metabolico']))
                <div class="examen-item">
                    <div class="examen-label">ENDOCRINO-METABÓLICOS:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['endocrino_metabolico'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_endocrino_metabolico']))
                    <div class="examen-obs">{{ $complementaria['descripcion_endocrino_metabolico'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['inmunologico']))
                <div class="examen-item">
                    <div class="examen-label">INMUNOLÓGICOS:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['inmunologico'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_inmunologico']))
                    <div class="examen-obs">{{ $complementaria['descripcion_inmunologico'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['cancer_tumores_radioterapia_quimio']))
                <div class="examen-item">
                    <div class="examen-label">CÁNCER, TUMORES, RADIOTERAPIA O QUIMIOTERAPIA:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['cancer_tumores_radioterapia_quimio'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_cancer_tumores_radio_quimioterapia']))
                    <div class="examen-obs">{{ $complementaria['descripcion_cancer_tumores_radio_quimioterapia'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['glandula_mamaria']))
                <div class="examen-item">
                    <div class="examen-label">GLÁNDULAS MAMARIAS:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['glandula_mamaria'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_glandulas_mamarias']))
                    <div class="examen-obs">{{ $complementaria['descripcion_glandulas_mamarias'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['hipertension_diabetes_erc']))
                <div class="examen-item">
                    <div class="examen-label">HIPERTENSIÓN, DIABETES, ERC:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['hipertension_diabetes_erc'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_hipertension_diabetes_erc']))
                    <div class="examen-obs">{{ $complementaria['descripcion_hipertension_diabetes_erc'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['reacciones_alergica']))
                <div class="examen-item">
                    <div class="examen-label">REACCIONES ALÉRGICAS:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['reacciones_alergica'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_reacion_alergica']))
                    <div class="examen-obs">{{ $complementaria['descripcion_reacion_alergica'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['cardio_vasculares']))
                <div class="examen-item">
                    <div class="examen-label">CARDIO VASCULARES:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['cardio_vasculares'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_cardio_vasculares']))
                    <div class="examen-obs">{{ $complementaria['descripcion_cardio_vasculares'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['respiratorios']))
                <div class="examen-item">
                    <div class="examen-label">RESPIRATORIOS:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['respiratorios'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_respiratorios']))
                    <div class="examen-obs">{{ $complementaria['descripcion_respiratorios'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['urinarias']))
                <div class="examen-item">
                    <div class="examen-label">URINARIAS:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['urinarias'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_urinarias']))
                    <div class="examen-obs">{{ $complementaria['descripcion_urinarias'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['osteoarticulares']))
                <div class="examen-item">
                    <div class="examen-label">OSTEOARTICULARES:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['osteoarticulares'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_osteoarticulares']))
                    <div class="examen-obs">{{ $complementaria['descripcion_osteoarticulares'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['infecciosos']))
                <div class="examen-item">
                    <div class="examen-label">INFECCIOSOS:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['infecciosos'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_infecciosos']))
                    <div class="examen-obs">{{ $complementaria['descripcion_infecciosos'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['cirugia_trauma']))
                <div class="examen-item">
                    <div class="examen-label">CIRUGÍAS TRAUMAS(ACCIDENTES):</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['cirugia_trauma'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_cirugias_traumas']))
                    <div class="examen-obs">{{ $complementaria['descripcion_cirugias_traumas'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['tratamiento_medicacion']))
                <div class="examen-item">
                    <div class="examen-label">TRATAMIENTOS CON MEDICACIÓN:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['tratamiento_medicacion'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_tratamiento_medicacion']))
                    <div class="examen-obs">{{ $complementaria['descripcion_tratamiento_medicacion'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['antecedente_quirurgico']))
                <div class="examen-item">
                    <div class="examen-label">ANTECEDENTES QUIRÚRGICOS:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['antecedente_quirurgico'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_antecedentes_quirurgicos']))
                    <div class="examen-obs">{{ $complementaria['descripcion_antecedentes_quirurgicos'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['antecedentes_familiares']))
                <div class="examen-item">
                    <div class="examen-label">ANTECEDENTES FAMILIARES:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['antecedentes_familiares'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_antecedentes_familiares']))
                    <div class="examen-obs">{{ $complementaria['descripcion_antecedentes_familiares'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['consumo_tabaco']))
                <div class="examen-item">
                    <div class="examen-label">CONSUMO DE TABACO:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['consumo_tabaco'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_consumo_tabaco']))
                    <div class="examen-obs">{{ $complementaria['descripcion_consumo_tabaco'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['antecedentes_alcohol']))
                <div class="examen-item">
                    <div class="examen-label">ANTECEDENTES DE ALCOHOL:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['antecedentes_alcohol'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_antecedentes_alcohol']))
                    <div class="examen-obs">{{ $complementaria['descripcion_antecedentes_alcohol'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['sedentarismo']))
                <div class="examen-item">
                    <div class="examen-label">SEDENTARISMO:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['sedentarismo'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_sedentarismo']))
                    <div class="examen-obs">{{ $complementaria['descripcion_sedentarismo'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['ginecologico']))
                <div class="examen-item">
                    <div class="examen-label">GINECOLÓGICOS:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['ginecologico'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_ginecologicos']))
                    <div class="examen-obs">{{ $complementaria['descripcion_ginecologicos'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['citologia_vaginal']))
                <div class="examen-item">
                    <div class="examen-label">CITOLOGÍA VAGINAL PATOLÓGICAS O ANORMALES:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['citologia_vaginal'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['descripcion_citologia_vaginal']))
                    <div class="examen-obs">{{ $complementaria['descripcion_citologia_vaginal'] }}</div>
                    @endif
                </div>
                @endif
            </div>

            {{-- Métodos Anticonceptivos --}}
            @if(isset($complementaria['metodo_conceptivo']))
            <div class="campo-historia">
                <div class="campo-titulo">MÉTODOS ANTICONCEPTIVOS</div>
                <div class="campo-contenido">
                    <strong>{{ strtoupper($complementaria['metodo_conceptivo']) }}</strong>
                    @if($complementaria['metodo_conceptivo'] == 'SI' && !empty($complementaria['metodo_conceptivo_cual']))
                        - {{ $complementaria['metodo_conceptivo_cual'] }}
                    @endif
                </div>
            </div>
            @endif

            {{-- Observaciones Antecedentes Personales --}}
            @if(!empty($complementaria['antecedente_personal']))
            <div class="campo-historia">
                <div class="campo-titulo">OBSERVACIONES ANTECEDENTES PERSONALES</div>
                <div class="campo-contenido">{{ $complementaria['antecedente_personal'] }}</div>
            </div>
            @endif
        </fieldset>
        @endif

        {{-- ✅ REVISIÓN POR SISTEMA --}}
        @if(!empty($complementaria))
        <fieldset>
            <legend>REVISIÓN POR SISTEMA</legend>
            
            <div class="examen-fisico-grid">
                @if(isset($complementaria['ef_cabeza']))
                <div class="examen-item">
                    <div class="examen-label">CABEZA:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['ef_cabeza'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['ef_obs_cabeza']))
                    <div class="examen-obs">{{ $complementaria['ef_obs_cabeza'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['ef_cuello']))
                <div class="examen-item">
                    <div class="examen-label">CUELLO:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['ef_cuello'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['ef_obs_cuello']))
                    <div class="examen-obs">{{ $complementaria['ef_obs_cuello'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['ef_torax']))
                <div class="examen-item">
                    <div class="examen-label">TÓRAX:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['ef_torax'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['ef_obs_torax']))
                    <div class="examen-obs">{{ $complementaria['ef_obs_torax'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['ef_abdomen']))
                <div class="examen-item">
                    <div class="examen-label">ABDOMEN:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['ef_abdomen'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['ef_obs_abdomen']))
                    <div class="examen-obs">{{ $complementaria['ef_obs_abdomen'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['ef_extremidades']))
                <div class="examen-item">
                    <div class="examen-label">EXTREMIDADES:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['ef_extremidades'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['ef_obs_extremidades']))
                    <div class="examen-obs">{{ $complementaria['ef_obs_extremidades'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($complementaria['neurologico_estado_mental']))
                <div class="examen-item">
                    <div class="examen-label">NEUROLÓGICO Y ESTADO MENTAL:</div>
                    <div class="examen-valor">{{ strtoupper($complementaria['neurologico_estado_mental'] ?? 'NO') }}</div>
                    @if(!empty($complementaria['obs_neurologico_estado_mental']))
                    <div class="examen-obs">{{ $complementaria['obs_neurologico_estado_mental'] }}</div>
                    @endif
                </div>
                @endif
            </div>
        </fieldset>
        @endif

        {{-- ✅ EXAMEN FÍSICO --}}
        <fieldset>
            <legend>EXAMEN FÍSICO</legend>
            
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
                    <div class="dato-label">PRESIÓN ARTERIAL SISTÓLICA</div>
                    <div class="dato-valor">{{ $historia['presion_arterial_sistolica_sentado_pie'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">PRESIÓN ARTERIAL DIASTÓLICA</div>
                    <div class="dato-valor">{{ $historia['presion_arterial_distolica_sentado_pie'] ?? 'N/A' }}</div>
                </div>
            </div>

            <div class="datos-grid">
                <div class="dato-item">
                    <div class="dato-label">FRECUENCIA CARDIACA</div>
                    <div class="dato-valor">{{ $historia['frecuencia_cardiaca'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">MENARQUIA</div>
                    <div class="dato-valor">{{ $complementaria['menarquia'] ?? '0' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">GESTACIONES</div>
                    <div class="dato-valor">{{ $complementaria['gestaciones'] ?? '0' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">PARTOS</div>
                    <div class="dato-valor">{{ $complementaria['parto'] ?? '0' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">ABORTOS</div>
                    <div class="dato-valor">{{ $complementaria['aborto'] ?? '0' }}</div>
                </div>
            </div>

            <div class="datos-grid-3">
                <div class="dato-item">
                    <div class="dato-label">CESÁREAS</div>
                    <div class="dato-valor">{{ $complementaria['cesaria'] ?? '0' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">PERÍMETRO ABDOMINAL</div>
                    <div class="dato-valor">{{ $historia['perimetro_abdominal'] ?? 'N/A' }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">FRECUENCIA RESPIRATORIA</div>
                    <div class="dato-valor">{{ $historia['frecuencia_respiratoria'] ?? 'N/A' }}</div>
                </div>
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

        {{-- ✅ CAUSA EXTERNA --}}
        <div class="observacion-box">
            <strong>CAUSA EXTERNA:</strong> {{ $historia['causa_externa'] ?? 'OTRA' }}
        </div>

        {{-- ✅ ANÁLISIS PLAN --}}
        @if(!empty($historia['observaciones_generales']))
        <fieldset>
            <legend>ANÁLISIS PLAN</legend>
            <div class="campo-historia">
                <div class="campo-contenido">{{ $historia['observaciones_generales'] }}</div>
            </div>
        </fieldset>
        @endif

        {{-- ✅ REMISIONES --}}
        @if(!empty($historia['remisiones']) && count($historia['remisiones']) > 0)
        <fieldset>
            <legend>FORMATO REMISIÓN</legend>
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
                        <td>{{ $remision['observacion'] ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </fieldset>
        @endif

        {{-- ✅ AYUDAS DIAGNÓSTICAS --}}
        @if(!empty($historia['cups']) && count($historia['cups']) > 0)
        <fieldset>
            <legend>FORMATO AYUDA DIAGNOSTICAS</legend>
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
                        <td>{{ $cup['observacion'] ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </fieldset>
        @endif

        {{-- ✅ MEDICAMENTOS --}}
        @if(!empty($historia['medicamentos']) && count($historia['medicamentos']) > 0)
        <fieldset>
            <legend>FORMATO MEDICAMENTO</legend>
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
            <legend>FORMATO DIAGNOSTICA {{ $historia['cita']['fecha'] ?? date('Y-m-d') }}</legend>
            <table>
                <thead>
                    <tr>
                        <th>CÓDIGO</th>
                        <th>DIAGNÓSTICO</th>
                        <th>CLASIFICACIÓN</th>
                        <th>TIPO</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($historia['diagnosticos']) && count($historia['diagnosticos']) > 0)
                        @foreach($historia['diagnosticos'] as $index => $diag)
                        <tr>
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
                        <td colspan="4" style="text-align: center;">No hay diagnósticos registrados</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </fieldset>

        {{-- ✅ FIRMAS CON BORDE AZUL --}}
        @php
            $profesionalNombre = 'N/A';
            $profesionalProfesion = 'MEDICINA INTERNA';
            $profesionalRegistro = 'N/A';
            $profesionalFirma = null;
            
            if (isset($historia['cita']['agenda']['usuario_medico'])) {
                $medico = $historia['cita']['agenda']['usuario_medico'];
                $profesionalNombre = $medico['nombre_completo'] ?? 'N/A';
                $profesionalProfesion = isset($medico['especialidad']['nombre']) 
                    ? strtoupper($medico['especialidad']['nombre']) 
                    : 'MEDICINA INTERNA';
                $profesionalRegistro = $medico['registro_profesional'] ?? 'N/A';
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
                        {{ $tipoDocumento }}-{{ $documento }} {{ $nombreCompleto }}
                    </div>
                </div>
            </div>
        </div>

    </div>

    @include('historia-clinica.historial-historias.partials.scripts')
</body>
</html>
