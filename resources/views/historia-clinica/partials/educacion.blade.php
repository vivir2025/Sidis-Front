<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-graduation-cap me-2"></i>
            Educación en Salud
        </h5>
    </div>
    <div class="card-body">
        @php
        $educacionItems = [
            ['key' => 'alimentacion', 'label' => 'Alimentación'],
            ['key' => 'disminucion_consumo_sal_azucar', 'label' => 'Disminución consumo de sal y azúcar'],
            ['key' => 'fomento_actividad_fisica', 'label' => 'Fomento de actividad física'],
            ['key' => 'importancia_adherencia_tratamiento', 'label' => 'Importancia adherencia al tratamiento'],
            ['key' => 'consumo_frutas_verduras', 'label' => 'Consumo de frutas y verduras'],
            ['key' => 'manejo_estres', 'label' => 'Manejo del estrés'],
            ['key' => 'disminucion_consumo_cigarrillo', 'label' => 'Disminución consumo de cigarrillo'],
            ['key' => 'disminucion_peso', 'label' => 'Disminución de peso']
        ];
        @endphp

        <div class="row">
            @foreach($educacionItems as $index => $item)
                @if($index % 2 == 0 && $index > 0)
        </div>
        <div class="row">
                @endif
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ $item['label'] }}</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" 
                                       name="{{ $item['key'] }}" 
                                       id="{{ $item['key'] }}_si" 
                                       value="SI">
                                <label class="form-check-label" for="{{ $item['key'] }}_si">
                                    Sí
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" 
                                       name="{{ $item['key'] }}" 
                                       id="{{ $item['key'] }}_no" 
                                       value="NO">
                                <label class="form-check-label" for="{{ $item['key'] }}_no">
                                    No
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
