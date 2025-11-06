{{-- resources/views/partials/revision_por_sistema.blade.php --}}

<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-stethoscope me-2"></i>
            Revisión por Sistema (Examen Físico)
        </h5>
    </div>
    <div class="card-body">
        @php
        $revisionSistemas = [
            [
                'key' => 'ef_cabeza',
                'obs_key' => 'ef_obs_cabeza',
                'label' => 'Cabeza',
                'descripcion' => 'Examen de cráneo, cuero cabelludo, facies'
            ],
            [
                'key' => 'ef_cuello',
                'obs_key' => 'ef_obs_cuello',
                'label' => 'Cuello',
                'descripcion' => 'Tiroides, ganglios, movilidad'
            ],
            [
                'key' => 'ef_torax',
                'obs_key' => 'ef_obs_torax',
                'label' => 'Tórax',
                'descripcion' => 'Auscultación cardiopulmonar, inspección'
            ],
            [
                'key' => 'ef_abdomen',
                'obs_key' => 'ef_obs_abdomen',
                'label' => 'Abdomen',
                'descripcion' => 'Palpación, auscultación, percusión'
            ],
            [
                'key' => 'ef_extremidades',
                'obs_key' => 'ef_obs_extremidades',
                'label' => 'Extremidades',
                'descripcion' => 'Movilidad, fuerza, edema, pulsos'
            ],
            [
                'key' => 'neurologico_estado_mental',
                'obs_key' => 'obs_neurologico_estado_mental',
                'label' => 'Neurológico y Estado Mental',
                'descripcion' => 'Orientación, memoria, reflejos, sensibilidad'
            ]
        ];
        @endphp

        @foreach($revisionSistemas as $sistema)
        <div class="row mb-4 border-bottom pb-3">
            <div class="col-md-3">
                <label class="form-label fw-bold">{{ $sistema['label'] }}</label>
                <p class="text-muted small mb-2">{{ $sistema['descripcion'] }}</p>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input revision-sistema" 
                               type="radio" 
                               name="{{ $sistema['key'] }}" 
                               id="{{ $sistema['key'] }}_si" 
                               value="SI">
                        <label class="form-check-label" for="{{ $sistema['key'] }}_si">
                            <span class="badge bg-danger">Anormal</span>
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input revision-sistema" 
                               type="radio" 
                               name="{{ $sistema['key'] }}" 
                               id="{{ $sistema['key'] }}_no" 
                               value="NO" 
                               checked>
                        <label class="form-check-label" for="{{ $sistema['key'] }}_no">
                            <span class="badge bg-success">Normal</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <label for="{{ $sistema['obs_key'] }}" class="form-label">
                    <i class="fas fa-notes-medical me-1"></i>
                    Observaciones {{ $sistema['label'] }}
                </label>
                <textarea class="form-control observacion-textarea" 
                          id="{{ $sistema['obs_key'] }}" 
                          name="{{ $sistema['obs_key'] }}" 
                          rows="2" 
                          placeholder="Hallazgos normales o anormales del examen físico..."
                          required>NINGUNO</textarea>
                <small class="form-text text-muted">
                    Describa los hallazgos encontrados durante el examen físico
                </small>
            </div>
        </div>
        @endforeach

        {{-- Observaciones Generales del Examen Físico --}}
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Nota:</strong> Marque "Normal" si no se encuentran hallazgos patológicos. 
                    Marque "Anormal" si hay hallazgos que requieren descripción detallada.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript para manejar la habilitación/deshabilitación de campos --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar revisión por sistema
    document.querySelectorAll('.revision-sistema').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const name = this.name;
            
            // Mapeo de nombres de campos
            const mappings = {
                'ef_cabeza': 'obs_cabeza',
                'cuello': 'obs_cuello',
                'torax': 'obs_torax',
                'abdomen': 'obs_abdomen',
                'extremidades': 'obs_extremidades',
                'neurologico_estado_mental': 'obs_neurologico_estado_mental'
            };
            
            const obsKey = mappings[name];
            const obsTextarea = document.getElementById(obsKey);
            
            if (obsTextarea) {
                if (this.value === 'SI') {
                    // Si es anormal (SI), limpiar el valor por defecto y enfocar
                    if (obsTextarea.value === 'NINGUNO') {
                        obsTextarea.value = '';
                    }
                    obsTextarea.classList.add('border-danger');
                    obsTextarea.classList.remove('border-success');
                    obsTextarea.focus();
                } else {
                    // Si es normal (NO), establecer valor por defecto
                    obsTextarea.value = 'NINGUNO';
                    obsTextarea.classList.remove('border-danger');
                    obsTextarea.classList.add('border-success');
                }
            }
        });
    });

    // Inicializar el estado de los campos al cargar la página
    document.querySelectorAll('.observacion-textarea').forEach(function(textarea) {
        const mappings = {
            'obs_cabeza': 'ef_cabeza',
            'obs_cuello': 'cuello',
            'obs_torax': 'torax',
            'obs_abdomen': 'abdomen',
            'obs_extremidades': 'extremidades',
            'obs_neurologico_estado_mental': 'neurologico_estado_mental'
        };
        
        const radioName = mappings[textarea.id];
        const radioChecked = document.querySelector(`input[name="${radioName}"]:checked`);
        
        if (radioChecked && radioChecked.value === 'NO') {
            textarea.classList.add('border-success');
        }
    });

    // Validación antes de enviar el formulario
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            document.querySelectorAll('.revision-sistema:checked').forEach(function(radio) {
                if (radio.value === 'SI') {
                    const mappings = {
                        'ef_cabeza': 'obs_cabeza',
                        'cuello': 'obs_cuello',
                        'torax': 'obs_torax',
                        'abdomen': 'obs_abdomen',
                        'extremidades': 'obs_extremidades',
                        'neurologico_estado_mental': 'obs_neurologico_estado_mental'
                    };
                    
                    const obsKey = mappings[radio.name];
                    const obsTextarea = document.getElementById(obsKey);
                    
                    if (obsTextarea && (obsTextarea.value.trim() === '' || obsTextarea.value === 'NINGUNO')) {
                        isValid = false;
                        obsTextarea.classList.add('is-invalid');
                        obsTextarea.focus();
                        
                        // Mostrar mensaje de error
                        if (!obsTextarea.nextElementSibling || !obsTextarea.nextElementSibling.classList.contains('invalid-feedback')) {
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'invalid-feedback';
                            errorDiv.textContent = 'Por favor, describa los hallazgos anormales encontrados.';
                            obsTextarea.parentNode.appendChild(errorDiv);
                        }
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor, complete las observaciones de los sistemas marcados como anormales.');
            }
        });
    }

    // Remover mensaje de error al escribir
    document.querySelectorAll('.observacion-textarea').forEach(function(textarea) {
        textarea.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            const errorDiv = this.nextElementSibling;
            if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                errorDiv.remove();
            }
        });
    });
});
</script>
@endpush

{{-- Estilos adicionales --}}
@push('styles')
<style>
.border-bottom {
    border-bottom: 1px solid #dee2e6 !important;
}

.revision-sistema:checked + label {
    font-weight: 600;
}

.observacion-textarea {
    transition: border-color 0.3s ease;
}

.observacion-textarea.border-success {
    border-color: #198754 !important;
    border-width: 2px;
}

.observacion-textarea.border-danger {
    border-color: #dc3545 !important;
    border-width: 2px;
}

.observacion-textarea:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.badge {
    font-size: 0.75rem;
    padding: 0.35em 0.65em;
}

.small {
    font-size: 0.875rem;
}

.text-muted {
    color: #6c757d !important;
}

.form-check-inline {
    margin-right: 1rem;
}

.alert-info {
    background-color: #cff4fc;
    border-color: #b6effb;
    color: #055160;
}
</style>
@endpush
