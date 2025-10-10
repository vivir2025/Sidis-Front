@extends('layouts.app')

@section('title', 'Sincronizar Usuarios')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-sync-alt me-2"></i>
                        Sincronizar Usuarios Offline
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong> Esta acción descargará todos los usuarios (incluyendo firmas) 
                        para que estén disponibles en modo offline.
                    </div>

                    <form action="{{ route('usuarios.sincronizar.ejecutar') }}" method="POST" id="formSincronizar">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label">Filtrar por Sede (opcional)</label>
                            <select name="sede_id" class="form-select">
                                <option value="">Todas las sedes</option>
                                @foreach($sedes as $sede)
                                    <option value="{{ $sede['id'] }}">{{ $sede['nombre'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-download me-2"></i>
                                Iniciar Sincronización
                            </button>
                            <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Volver
                            </a>
                        </div>
                    </form>

                    <div id="progressContainer" class="mt-4" style="display: none;">
                        <h5>Sincronizando...</h5>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" 
                                 style="width: 0%"
                                 id="progressBar"></div>
                        </div>
                        <p class="mt-2 text-center" id="progressText">0%</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('formSincronizar').addEventListener('submit', function(e) {
    e.preventDefault();
    
    document.getElementById('progressContainer').style.display = 'block';
    
    // Aquí puedes implementar AJAX para mostrar progreso en tiempo real
    this.submit();
});
</script>
@endpush
