<div class="card mb-4">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-plus-circle me-2"></i>
            Diagnósticos Adicionales
        </h5>
        <button type="button" class="btn btn-dark btn-sm" id="agregar_diagnostico_adicional">
            <i class="fas fa-plus me-1"></i>Agregar Diagnóstico
        </button>
    </div>
    <div class="card-body">
        <div id="diagnosticos_adicionales_container">
            {{-- Los diagnósticos adicionales se agregarán aquí dinámicamente --}}
        </div>
    </div>
</div>

{{-- ✅ TEMPLATE PARA DIAGNÓSTICOS ADICIONALES --}}
<script type="text/html" id="diagnostico_adicional_template">
    <div class="diagnostico-adicional-item border rounded p-3 mb-3" style="background-color: #f8f9fa;">
        <div class="row">
            <div class="col-md-10">
                {{-- Buscador de Diagnóstico --}}
                <div class="mb-3">
                    <label class="form-label">Buscar Diagnóstico Adicional <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="text" 
                               class="form-control buscar-diagnostico-adicional" 
                               placeholder="Escriba código o nombre del diagnóstico..." 
                               autocomplete="off">
                        
                        <input type="hidden" 
                               class="diagnostico-adicional-id" 
                               name="diagnosticos_adicionales[][idDiagnostico]">  {{-- ✅ CAMBIADO --}}
                        
                        {{-- Dropdown de resultados --}}
                        <div class="dropdown-menu diagnosticos-adicionales-resultados" 
                             style="width: 100%; max-height: 300px; overflow-y: auto;">
                        </div>
                    </div>
                    
                    {{-- Alerta de diagnóstico seleccionado --}}
                    <div class="alert alert-info mt-2 diagnostico-adicional-seleccionado" style="display: none;">
                        <i class="fas fa-check-circle me-2"></i>
                        <span class="diagnostico-adicional-info"></span>
                    </div>
                </div>
                
                {{-- Tipo de Diagnóstico --}}
                <div class="mb-3">
                    <label class="form-label">Tipo de Diagnóstico <span class="text-danger">*</span></label>
                    <select class="form-select" name="diagnosticos_adicionales[][tipo_diagnostico]" required>
                        <option value="">Seleccione...</option>
                        <option value="IMPRESION_DIAGNOSTICA">Impresión Diagnóstica</option>
                        <option value="CONFIRMADO_NUEVO">Confirmado Nuevo</option>
                        <option value="CONFIRMADO_REPETIDO">Confirmado Repetido</option>
                    </select>
                </div>
            </div>
            
            {{-- Botón Eliminar --}}
            <div class="col-md-2 d-flex align-items-start justify-content-end">
                <button type="button" class="btn btn-danger btn-sm eliminar-diagnostico-adicional">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</script>
