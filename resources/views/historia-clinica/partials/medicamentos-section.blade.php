{{-- ✅ SECCIÓN: MEDICAMENTOS --}}
<div class="card mb-4">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-pills me-2"></i>
            Medicamentos
        </h5>
        <button type="button" class="btn btn-light btn-sm" id="agregar_medicamento">
            <i class="fas fa-plus me-1"></i>Agregar Medicamento
        </button>
    </div>
    <div class="card-body">
        <div id="medicamentos_container">
            <!-- Los medicamentos se agregarán aquí dinámicamente -->
        </div>
    </div>
</div>

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
