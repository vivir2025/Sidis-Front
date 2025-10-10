{{-- ✅ SECCIÓN: REMISIONES --}}
<div class="card mb-4">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-share me-2"></i>
            Remisiones
        </h5>
        <button type="button" class="btn btn-light btn-sm" id="agregar_remision">
            <i class="fas fa-plus me-1"></i>Agregar Remisión
        </button>
    </div>
    <div class="card-body">
        <div id="remisiones_container">
            <!-- Las remisiones se agregarán aquí dinámicamente -->
        </div>
    </div>
</div>

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
