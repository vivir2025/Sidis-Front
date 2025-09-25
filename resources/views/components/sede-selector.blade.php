<!-- resources/views/components/sede-selector.blade.php -->
<div class="sede-selector-container">
    <!-- Indicador de Sede Actual -->
    <div class="sede-actual-info">
        <div class="d-flex align-items-center">
            <i class="fas fa-building text-primary me-2"></i>
            <div>
                <strong id="sedeActualNombre">{{ session('usuario')['sede']['nombre'] ?? 'Cargando...' }}</strong>
                <br>
                <small class="text-muted">Sede actual</small>
            </div>
            <button class="btn btn-outline-primary btn-sm ms-auto" 
                    id="btnCambiarSede"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalCambiarSede">
                <i class="fas fa-exchange-alt me-1"></i>
                Cambiar
            </button>
        </div>
    </div>

    <!-- Modal para Cambiar Sede -->
    <div class="modal fade" id="modalCambiarSede" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-building me-2"></i>
                        Cambiar Sede
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Seleccionar nueva sede:</label>
                        <select class="form-select" id="nuevaSedeSelect">
                            <option value="">Cargando sedes...</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Nota:</strong> Al cambiar de sede, tendrás acceso completo a todos los datos y funcionalidades de la sede seleccionada.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarCambio">
                        <i class="fas fa-check me-1"></i>
                        Cambiar Sede
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.sede-selector-container {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 20px;
    border-left: 4px solid var(--primary-color);
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.sede-actual-info {
    transition: all 0.3s ease;
}

.sede-actual-info:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

#btnCambiarSede {
    border-radius: 20px;
    transition: all 0.3s ease;
}

#btnCambiarSede:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
}

.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.modal-header {
    background: linear-gradient(135deg, var(--primary-color), #1e3d6f);
    color: white;
    border-radius: 15px 15px 0 0;
}

.form-select {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(44, 90, 160, 0.15);
}

.alert-info {
    border-radius: 10px;
    border-left: 4px solid #0dcaf0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalCambiarSede = new bootstrap.Modal(document.getElementById('modalCambiarSede'));
    const nuevaSedeSelect = document.getElementById('nuevaSedeSelect');
    const btnConfirmarCambio = document.getElementById('btnConfirmarCambio');
    const sedeActualNombre = document.getElementById('sedeActualNombre');

    // Cargar sedes cuando se abre el modal
    document.getElementById('btnCambiarSede').addEventListener('click', function() {
        cargarSedesDisponibles();
    });

    // Confirmar cambio de sede
    btnConfirmarCambio.addEventListener('click', function() {
        const nuevaSedeId = nuevaSedeSelect.value;
        
        if (!nuevaSedeId) {
            mostrarAlerta('Por favor selecciona una sede', 'warning');
            return;
        }

        cambiarSede(nuevaSedeId);
    });

    function cargarSedesDisponibles() {
        nuevaSedeSelect.innerHTML = '<option value="">Cargando...</option>';
        
        fetch('/sedes-disponibles', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                nuevaSedeSelect.innerHTML = '<option value="">Selecciona una sede...</option>';
                
                data.data.forEach(sede => {
                    const option = document.createElement('option');
                    option.value = sede.id;
                    option.textContent = sede.nombre;
                    
                    // Marcar la sede actual
                    if (sede.id === data.sede_actual) {
                        option.textContent += ' (Actual)';
                        option.disabled = true;
                        option.selected = true;
                    }
                    
                    nuevaSedeSelect.appendChild(option);
                });
            } else {
                nuevaSedeSelect.innerHTML = '<option value="">Error cargando sedes</option>';
                mostrarAlerta('Error cargando sedes disponibles', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            nuevaSedeSelect.innerHTML = '<option value="">Error de conexión</option>';
            mostrarAlerta('Error de conexión', 'error');
        });
    }

    function cambiarSede(nuevaSedeId) {
        btnConfirmarCambio.disabled = true;
        btnConfirmarCambio.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Cambiando...';

        fetch('/cambiar-sede', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                sede_id: parseInt(nuevaSedeId)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar nombre de sede actual
                sedeActualNombre.textContent = data.usuario.sede.nombre;
                
                // Cerrar modal
                modalCambiarSede.hide();
                
                // Mostrar mensaje de éxito
                mostrarAlerta(data.message, 'success');
                
                // Recargar página después de un momento para actualizar todo
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
                
            } else {
                mostrarAlerta(data.error || 'Error cambiando sede', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error de conexión al cambiar sede', 'error');
        })
        .finally(() => {
            btnConfirmarCambio.disabled = false;
            btnConfirmarCambio.innerHTML = '<i class="fas fa-check me-1"></i> Cambiar Sede';
        });
    }

    function mostrarAlerta(mensaje, tipo) {
        // Crear alerta dinámica
        const alertaDiv = document.createElement('div');
        alertaDiv.className = `alert alert-${tipo === 'error' ? 'danger' : tipo} alert-dismissible fade show position-fixed`;
        alertaDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        
        alertaDiv.innerHTML = `
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'warning' ? 'exclamation-triangle' : 'times-circle'} me-2"></i>
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertaDiv);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (alertaDiv.parentNode) {
                alertaDiv.remove();
            }
        }, 5000);
    }
});
</script>
