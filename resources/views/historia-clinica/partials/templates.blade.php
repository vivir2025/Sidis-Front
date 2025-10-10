{{-- TEMPLATE DIAGNÓSTICO ADICIONAL --}}
<template id="diagnostico_adicional_template">
    <div class="diagnostico-adicional-item border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label">Buscar Diagnóstico</label>
                    <input type="text" class="form-control buscar-diagnostico-adicional" placeholder="Escriba código o nombre del diagnóstico...">
                    <div class="diagnosticos-adicionales-resultados dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;"></div>
                    <input type="hidden" class="diagnostico-adicional-id" name="diagnosticos_adicionales[][idDiagnostico]">
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Tipo de Diagnóstico</label>
                    <select class="form-select" name="diagnosticos_adicionales[][tipo_diagnostico]">
                        <option value="">Seleccione...</option>
                        <option value="IMPRESION_DIAGNOSTICA">Impresión Diagnóstica</option>
                        <option value="CONFIRMADO_NUEVO">Confirmado Nuevo</option>
                        <option value="CONFIRMADO_REPETIDO">Confirmado Repetido</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Observación</label>
                    <input type="text" class="form-control" name="diagnosticos_adicionales[][observacion]" placeholder="Observación opcional">
                </div>
            </div>
            <div class="col-md-1">
                <div class="mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm d-block eliminar-diagnostico-adicional">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info diagnostico-adicional-seleccionado" style="display: none;">
                    <strong>Diagnóstico Seleccionado:</strong>
                    <span class="diagnostico-adicional-info"></span>
                </div>
            </div>
        </div>
    </div>
</template>

{{-- TEMPLATE MEDICAMENTO --}}
<template id="medicamento_template">
    <div class="medicamento-item border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label">Buscar Medicamento</label>
                    <input type="text" class="form-control buscar-medicamento" placeholder="Escriba el nombre del medicamento...">
                    <div class="medicamentos-resultados dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;"></div>
                    <input type="hidden" class="medicamento-id" name="medicamentos[][idMedicamento]">
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Cantidad</label>
                    <input type="text" class="form-control" name="medicamentos[][cantidad]" placeholder="Ej: 30 tabletas">
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Dosis</label>
                    <input type="text" class="form-control" name="medicamentos[][dosis]" placeholder="Ej: 1 tableta cada 8 horas">
                </div>
            </div>
            <div class="col-md-1">
                <div class="mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm d-block eliminar-medicamento">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info medicamento-seleccionado" style="display: none;">
                    <strong>Medicamento Seleccionado:</strong>
                    <span class="medicamento-info"></span>
                </div>
            </div>
        </div>
    </div>
</template>

{{-- TEMPLATE REMISIÓN --}}
<template id="remision_template">
    <div class="remision-item border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Buscar Remisión</label>
                    <input type="text" class="form-control buscar-remision" placeholder="Escriba el nombre de la remisión...">
                    <div class="remisiones-resultados dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;"></div>
                    <input type="hidden" class="remision-id" name="remisiones[][idRemision]">
                </div>
            </div>
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label">Observación</label>
                    <textarea class="form-control" name="remisiones[][remObservacion]" rows="2" placeholder="Observación de la remisión..."></textarea>
                </div>
            </div>
            <div class="col-md-1">
                <div class="mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm d-block eliminar-remision">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info remision-seleccionada" style="display: none;">
                    <strong>Remisión Seleccionada:</strong>
                    <span class="remision-info"></span>
                </div>
            </div>
        </div>
    </div>
</template>

{{-- TEMPLATE CUPS --}}
<template id="cups_template">
    <div class="cups-item border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Buscar CUPS</label>
                    <input type="text" class="form-control buscar-cups" placeholder="Escriba código o nombre del procedimiento...">
                    <div class="cups-resultados dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;"></div>
                    <input type="hidden" class="cups-id" name="cups[][idCups]">
                </div>
            </div>
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label">Observación</label>
                    <textarea class="form-control" name="cups[][cupObservacion]" rows="2" placeholder="Observación del procedimiento..."></textarea>
                </div>
            </div>
            <div class="col-md-1">
                <div class="mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm d-block eliminar-cups">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info cups-seleccionado" style="display: none;">
                    <strong>CUPS Seleccionado:</strong>
                    <span class="cups-info"></span>
                </div>
            </div>
        </div>
    </div>
</template>
