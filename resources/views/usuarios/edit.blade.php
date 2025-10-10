<!-- ✅ SECCIÓN DE FIRMA DIGITAL (Solo para médicos) -->
@if(isset($usuario['es_medico']) && $usuario['es_medico'])
<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-signature me-2"></i>
            Firma Digital
        </h6>
    </div>
    <div class="card-body">
        <!-- Mostrar firma actual si existe -->
        @if(isset($usuario['tiene_firma']) && $usuario['tiene_firma'])
        <div class="alert alert-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-check-circle"></i>
                    <strong>Firma actual registrada</strong>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" 
                        onclick="toggleFirmaActual()">
                    <i class="fas fa-eye" id="iconVerFirma"></i>
                    <span id="textVerFirma">Ver firma</span>
                </button>
            </div>
            
            <!-- Contenedor de firma actual (oculto por defecto) -->
            <div id="firmaActualContainer" class="mt-3" style="display: none;">
                <div class="text-center border rounded p-3 bg-white">
                    <img src="{{ $usuario['firma'] ?? '' }}" 
                         alt="Firma actual" 
                         class="img-fluid"
                         style="max-width: 400px; max-height: 150px; border: 1px solid #dee2e6;">
                </div>
            </div>
        </div>
        @endif

        <!-- Opciones de firma -->
        <div class="mb-3">
            <label class="form-label fw-bold">Opciones de firma:</label>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="opcion_firma" 
                       id="mantenerFirma" value="mantener" checked>
                <label class="form-check-label" for="mantenerFirma">
                    @if(isset($usuario['tiene_firma']) && $usuario['tiene_firma'])
                        Mantener firma actual
                    @else
                        No agregar firma
                    @endif
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="opcion_firma" 
                       id="nuevaFirma" value="nueva">
                <label class="form-check-label" for="nuevaFirma">
                    @if(isset($usuario['tiene_firma']) && $usuario['tiene_firma'])
                        Reemplazar con nueva firma
                    @else
                        Agregar nueva firma
                    @endif
                </label>
            </div>
            @if(isset($usuario['tiene_firma']) && $usuario['tiene_firma'])
            <div class="form-check">
                <input class="form-check-input" type="radio" name="opcion_firma" 
                       id="eliminarFirma" value="eliminar">
                <label class="form-check-label text-danger" for="eliminarFirma">
                    Eliminar firma actual
                </label>
            </div>
            @endif
        </div>

        <!-- Canvas para nueva firma (oculto por defecto) -->
        <div id="nuevaFirmaContainer" style="display: none;">
            <label class="form-label fw-bold">Dibuje la nueva firma:</label>
            <div class="text-center">
                <canvas id="firmaCanvas" 
                        width="500" 
                        height="200" 
                        style="border: 2px solid #dee2e6; border-radius: 8px; cursor: crosshair; background-color: white;">
                </canvas>
            </div>
            <div class="text-center mt-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limpiarFirma()">
                    <i class="fas fa-eraser"></i> Limpiar
                </button>
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-info-circle"></i>
                    Dibuje su firma con el mouse o dedo (en dispositivos táctiles)
                </small>
            </div>
            <input type="hidden" name="firma" id="firmaInput">
        </div>

        <!-- Input oculto para eliminar firma -->
        <input type="hidden" name="eliminar_firma" id="eliminarFirmaInput" value="0">
    </div>
</div>

<!-- ✅ JAVASCRIPT PARA MANEJAR LA FIRMA -->
@push('scripts')
<script>
    let canvas, ctx, dibujando = false;
    let firmaModificada = false;

    document.addEventListener('DOMContentLoaded', function() {
        canvas = document.getElementById('firmaCanvas');
        if (canvas) {
            ctx = canvas.getContext('2d');
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';

            // Eventos de mouse
            canvas.addEventListener('mousedown', iniciarDibujo);
            canvas.addEventListener('mousemove', dibujar);
            canvas.addEventListener('mouseup', detenerDibujo);
            canvas.addEventListener('mouseout', detenerDibujo);

            // Eventos táctiles
            canvas.addEventListener('touchstart', iniciarDibujoTactil);
            canvas.addEventListener('touchmove', dibujarTactil);
            canvas.addEventListener('touchend', detenerDibujo);
        }

        // Manejar cambio de opción de firma
        document.querySelectorAll('input[name="opcion_firma"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const nuevaFirmaContainer = document.getElementById('nuevaFirmaContainer');
                const eliminarFirmaInput = document.getElementById('eliminarFirmaInput');

                if (this.value === 'nueva') {
                    nuevaFirmaContainer.style.display = 'block';
                    eliminarFirmaInput.value = '0';
                } else if (this.value === 'eliminar') {
                    nuevaFirmaContainer.style.display = 'none';
                    eliminarFirmaInput.value = '1';
                    document.getElementById('firmaInput').value = '';
                } else {
                    nuevaFirmaContainer.style.display = 'none';
                    eliminarFirmaInput.value = '0';
                    document.getElementById('firmaInput').value = '';
                }
            });
        });

        // Validar antes de enviar el formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const opcionFirma = document.querySelector('input[name="opcion_firma"]:checked').value;
            
            if (opcionFirma === 'nueva') {
                if (!firmaModificada) {
                    e.preventDefault();
                    alert('Por favor, dibuje la firma antes de guardar.');
                    return false;
                }
                
                const firmaData = canvas.toDataURL('image/png');
                document.getElementById('firmaInput').value = firmaData;
            }
        });
    });

    function iniciarDibujo(e) {
        dibujando = true;
        firmaModificada = true;
        const rect = canvas.getBoundingClientRect();
        ctx.beginPath();
        ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
    }

    function dibujar(e) {
        if (!dibujando) return;
        const rect = canvas.getBoundingClientRect();
        ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
        ctx.stroke();
    }

    function detenerDibujo() {
        dibujando = false;
    }

    function iniciarDibujoTactil(e) {
        e.preventDefault();
        const touch = e.touches[0];
        const mouseEvent = new MouseEvent('mousedown', {
            clientX: touch.clientX,
            clientY: touch.clientY
        });
        canvas.dispatchEvent(mouseEvent);
    }

    function dibujarTactil(e) {
        e.preventDefault();
        const touch = e.touches[0];
        const mouseEvent = new MouseEvent('mousemove', {
            clientX: touch.clientX,
            clientY: touch.clientY
        });
        canvas.dispatchEvent(mouseEvent);
    }

    function limpiarFirma() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        firmaModificada = false;
        document.getElementById('firmaInput').value = '';
    }

    function toggleFirmaActual() {
        const container = document.getElementById('firmaActualContainer');
        const icon = document.getElementById('iconVerFirma');
        const text = document.getElementById('textVerFirma');
        
        if (container.style.display === 'none') {
            container.style.display = 'block';
            icon.className = 'fas fa-eye-slash';
            text.textContent = 'Ocultar firma';
        } else {
            container.style.display = 'none';
            icon.className = 'fas fa-eye';
            text.textContent = 'Ver firma';
        }
    }
</script>
@endpush
@endif
