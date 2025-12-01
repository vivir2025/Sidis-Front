{{-- resources/views/historia-clinica/historial-historias/especial-control/remisiones.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formato de Remisión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <p>FORMATO DE REMISIÓN</p>
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
            $nombreCompleto = 'N/A';
            $documento = 'N/A';
            $tipoDocumento = 'CC';
            $fechaNacimiento = 'N/A';
            $direccion = 'N/A';
            $telefono = 'N/A';
            $fecha = 'N/A';
            
            if ($paciente) {
                if (isset($paciente['fecha_nacimiento']) && $paciente['fecha_nacimiento']) {
                    $fechaNac = \Carbon\Carbon::parse($paciente['fecha_nacimiento']);
                    $edad = $fechaNac->age . ' Años';
                    $fechaNacimiento = $paciente['fecha_nacimiento'];
                }
                
                $tipoDocumento = $paciente['tipo_documento'] ?? 'CC';
                $documento = $paciente['documento'] ?? 'N/A';
                $nombreCompleto = $paciente['nombre_completo'] ?? 'N/A';
                $direccion = $paciente['direccion'] ?? 'N/A';
                $telefono = $paciente['telefono'] ?? 'N/A';
            }
            
            $fecha = $historia['cita']['fecha'] ?? date('Y-m-d');
        @endphp

        <fieldset>
            <legend>DATOS PACIENTE</legend>
            
            <div class="datos-grid-3">
                <div class="dato-item">
                    <div class="dato-label">DOCUMENTO</div>
                    <div class="dato-valor">{{ $documento }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">NOMBRE</div>
                    <div class="dato-valor">{{ $nombreCompleto }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">FECHA NACIMIENTO Y EDAD</div>
                    <div class="dato-valor">{{ $fechaNacimiento }} - {{ $edad }}</div>
                </div>
            </div>

            <div class="datos-grid-3">
                <div class="dato-item">
                    <div class="dato-label">DIRECCIÓN</div>
                    <div class="dato-valor">{{ $direccion }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">TELÉFONO</div>
                    <div class="dato-valor">{{ $telefono }}</div>
                </div>
                <div class="dato-item">
                    <div class="dato-label">FECHA</div>
                    <div class="dato-valor">{{ $fecha }}</div>
                </div>
            </div>
        </fieldset>

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
                    @if(!empty($historia['diagnosticos']) && count($historia['diagnosticos']) > 0)
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
                                    CONFIRMADO NUEVO
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

        {{-- ✅ REMISIONES --}}
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
                    @if(!empty($historia['remisiones']) && count($historia['remisiones']) > 0)
                        @foreach($historia['remisiones'] as $remision)
                        <tr>
                            <td>{{ $remision['remision']['codigo'] ?? 'N/A' }}</td>
                            <td>{{ $remision['remision']['nombre'] ?? 'N/A' }}</td>
                            <td>{{ $remision['observacion'] ?? '' }}</td>
                        </tr>
                        @endforeach
                    @else
                    <tr>
                        <td colspan="3" style="text-align: center;">No hay remisiones registradas</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </fieldset>

        {{-- ✅ FIRMAS CON BORDE AZUL --}}
        @php
            $profesionalNombre = 'N/A';
            $profesionalRegistro = 'N/A';
            $profesionalFirma = null;
            
            if (isset($historia['cita']['agenda']['usuario_medico'])) {
                $medico = $historia['cita']['agenda']['usuario_medico'];
                $profesionalNombre = $medico['nombre_completo'] ?? 'N/A';
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

    <script>
    // ✅ DETECTAR SI VIENE CON PARÁMETRO DE IMPRESIÓN Y VALIDAR REMISIONES
    window.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const shouldPrint = urlParams.get('print');
        
        if (shouldPrint === '1') {
            // ✅ VALIDAR SI HAY REMISIONES
            const hayRemisiones = {{ !empty($historia['remisiones']) && count($historia['remisiones']) > 0 ? 'true' : 'false' }};
            
            if (!hayRemisiones) {
                // ❌ NO HAY REMISIONES - MOSTRAR ALERTA Y CERRAR
                alert('No hay remisiones registradas para esta historia clínica.');
                window.close();
            } else {
                // ✅ HAY REMISIONES - PROCEDER CON IMPRESIÓN
                setTimeout(function() {
                    window.print();
                    
                    // Cerrar la ventana después de imprimir o cancelar
                    window.onafterprint = function() {
                        window.close();
                    };
                }, 1500); // Esperar 1.5 segundos
            }
        }
    });
    </script>
</body>
</html>
