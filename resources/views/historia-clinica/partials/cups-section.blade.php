{{-- ✅ SECCIÓN: CUPS --}}
<div class="card mb-4">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-clipboard-list me-2"></i>
            Procedimientos CUPS
        </h5>
        <button type="button" class="btn btn-light btn-sm" id="agregar_cups">
            <i class="fas fa-plus me-1"></i>Agregar CUPS
        </button>
    </div>
    <div class="card-body">
        <div id="cups_container">
            <!-- Los CUPS se agregarán aquí dinámicamente -->
        </div>
    </div>
</div>

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
