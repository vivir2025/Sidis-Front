{{-- resources/views/partials/analisis_plan_simple.blade.php --}}

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-clipboard-list me-2"></i>
            Análisis y Plan de Tratamiento
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <label for="observaciones_generales" class="form-label fw-bold">
                    <i class="fas fa-notes-medical me-2"></i>
                    Análisis y Plan
                    <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" 
                          name="observaciones_generales" 
                          id="observaciones_generales"
                          placeholder="Describa el análisis de la situación clínica del paciente y el plan de tratamiento a seguir..." 
                          required 
                          rows="6"></textarea>
                <small class="form-text text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Campo obligatorio. Incluya diagnóstico, tratamiento, exámenes y seguimiento.
                </small>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('observaciones_generales');
    
    if (textarea) {
        // Auto-expandir textarea
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
});
</script>
@endpush

@push('styles')
<style>
#observaciones_generales {
    min-height: 150px;
    resize: vertical;
}

#observaciones_generales:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
@endpush
