{{-- resources/views/usuarios/partials/firma-qr.blade.php --}}

<!-- Modal de Firma con QR -->
<div class="modal fade" id="modalFirmaQR" tabindex="-1" aria-labelledby="modalFirmaQRLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalFirmaQRLabel">
                    <i class="fas fa-mobile-alt me-2"></i>
                    Firmar desde tu Celular
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Body -->
            <div class="modal-body">
                <div class="row">
                    <!-- Columna izquierda: Instrucciones y QR -->
                    <div class="col-md-6 text-center">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Instrucciones:</strong>
                        </div>
                        
                        <ol class="text-start mb-4">
                            <li class="mb-2">
                                <i class="fas fa-qrcode text-primary me-2"></i>
                                Escanea el código QR con tu celular
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-signature text-primary me-2"></i>
                                Dibuja tu firma en la pantalla táctil
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-paper-plane text-primary me-2"></i>
                                Presiona "Enviar Firma"
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                La firma aparecerá automáticamente aquí
                            </li>
                        </ol>
                        
                        <!-- Código QR -->
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div id="qrcode" class="d-flex justify-content-center align-items-center" style="min-height: 256px;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Generando QR...</span>
                                    </div>
                                </div>
                                <p class="text-muted mt-3 mb-0">
                                    <small>
                                        <i class="fas fa-shield-alt me-1"></i>
                                        Conexión segura mediante WebSocket
                                    </small>
                                </p>
                            </div>
                        </div>
                        
                        <!-- ID de Sesión -->
                        <div class="mt-3">
                            <small class="text-muted">
                                ID de sesión: <code id="sessionId">Generando...</code>
                            </small>
                        </div>
                    </div>
                    
                    <!-- Columna derecha: Preview y Estado -->
                    <div class="col-md-6">
                        <div class="alert alert-secondary">
                            <i class="fas fa-eye me-2"></i>
                            <strong>Vista Previa</strong>
                        </div>
                        
                        <!-- Estado de conexión -->
                        <div id="estadoConexion" class="alert alert-warning">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                <span>Esperando conexión del celular...</span>
                            </div>
                        </div>
                        
                        <!-- Preview de firma recibida -->
                        <div id="previewFirmaRecibida" class="card shadow-sm" style="display: none;">
                            <div class="card-header bg-success text-white">
                                <i class="fas fa-check-circle me-2"></i>
                                Firma Recibida
                            </div>
                            <div class="card-body text-center">
                                <img id="imgFirmaRecibida" 
                                     src="" 
                                     alt="Firma recibida" 
                                     class="img-fluid border rounded"
                                     style="max-height: 200px;">
                                
                                <div class="mt-3">
                                    <button type="button" 
                                            class="btn btn-success btn-sm" 
                                            onclick="confirmarFirmaRecibida()">
                                        <i class="fas fa-check me-1"></i>
                                        Usar esta firma
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm" 
                                            onclick="rechazarFirmaRecibida()">
                                        <i class="fas fa-times me-1"></i>
                                        Rechazar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Placeholder cuando no hay firma -->
                        <div id="placeholderFirma" class="card shadow-sm">
                            <div class="card-body text-center text-muted py-5">
                                <i class="fas fa-signature fa-4x mb-3 opacity-25"></i>
                                <p class="mb-0">La firma aparecerá aquí cuando la envíes desde tu celular</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Cerrar
                </button>
                <button type="button" class="btn btn-primary" onclick="regenerarQR()">
                    <i class="fas fa-sync-alt me-1"></i>
                    Regenerar QR
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- Librería QRCode.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
// ============================================
// VARIABLES GLOBALES DEL MODAL QR
// ============================================
let qrCodeInstance = null;
let sessionId = null;
let wsConnection = null;
let firmaRecibidaTemp = null;

// ============================================
// INICIALIZAR MODAL CUANDO SE ABRE
// ============================================
document.getElementById('modalFirmaQR').addEventListener('shown.bs.modal', function () {
    console.log('📱 Modal de firma QR abierto');
    inicializarSesionFirma();
});

// ============================================
// LIMPIAR AL CERRAR MODAL
// ============================================
document.getElementById('modalFirmaQR').addEventListener('hidden.bs.modal', function () {
    console.log('🚪 Modal de firma QR cerrado');
    limpiarSesionFirma();
});

// ============================================
// FUNCIÓN: INICIALIZAR SESIÓN DE FIRMA
// ============================================
async function inicializarSesionFirma() {
    try {
        console.log('🔄 Inicializando sesión de firma...');
        
        // 🔥 LLAMAR AL BACKEND PARA GENERAR SESIÓN
        const response = await fetch('/usuarios/generar-qr-firma', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || `Error HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Error generando sesión');
        }
        
        // Guardar session_id recibido del servidor
        sessionId = data.session_id;
        
        console.log('✅ Sesión generada en servidor:', {
            session_id: sessionId,
            url: data.url,
            expira_en: data.expira_en + ' segundos'
        });
        
        // Mostrar session ID en el modal
        document.getElementById('sessionId').textContent = sessionId;
        
        // Generar código QR con la URL del servidor
        generarCodigoQR(data.url);
        
        // Inicializar polling para detectar firma
        inicializarConexion();
        
        // Actualizar estado
        actualizarEstadoConexion('esperando', 'Esperando firma desde celular...');
        
    } catch (error) {
        console.error('❌ Error inicializando sesión:', error);
        
        actualizarEstadoConexion('error', 'Error al inicializar sesión. Intenta nuevamente.');
        
        mostrarNotificacion('danger', 'Error al generar código QR: ' + error.message);
    }
}


// ============================================
// FUNCIÓN: GENERAR CÓDIGO QR
// ============================================
function generarCodigoQR(url) {
    const qrContainer = document.getElementById('qrcode');
    
    // Limpiar QR anterior
    qrContainer.innerHTML = '';
    
    console.log('🔗 Generando QR con URL del servidor:', url);
    
    // Generar nuevo QR
    try {
        qrCodeInstance = new QRCode(qrContainer, {
            text: url,  // ✅ Usar URL completa del servidor
            width: 256,
            height: 256,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        
        console.log('✅ Código QR generado correctamente');
        
    } catch (error) {
        console.error('❌ Error al generar QR:', error);
        qrContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Error al generar código QR
            </div>
        `;
    }
}


// ============================================
// FUNCIÓN: INICIALIZAR CONEXIÓN (POLLING)
// ============================================
function inicializarConexion() {
    console.log('🔌 Inicializando polling para detectar firma...');
    
    // Limpiar intervalo anterior si existe
    if (window.firmaCheckInterval) {
        clearInterval(window.firmaCheckInterval);
    }
    
    // Polling cada 2 segundos para verificar si llegó la firma
    window.firmaCheckInterval = setInterval(async () => {
        try {
            console.log('🔍 Verificando si hay firma...');
            
            const response = await fetch(`/usuarios/verificar-firma/${sessionId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            
            if (!response.ok) {
                console.error('❌ Error en verificación:', response.status);
                return;
            }
            
            const data = await response.json();
            
            console.log('📊 Estado de firma:', data);
            
            if (data.firmado && data.firma) {
                console.log('✅ ¡Firma detectada!');
                clearInterval(window.firmaCheckInterval);
                mostrarFirmaRecibida(data.firma);
            }
            
        } catch (error) {
            console.error('❌ Error verificando firma:', error);
        }
    }, 2000); // Cada 2 segundos
    
    console.log('✅ Polling iniciado (cada 2 segundos)');
}


// ============================================
// FUNCIÓN: MOSTRAR FIRMA RECIBIDA
// ============================================
function mostrarFirmaRecibida(firmaBase64) {
    console.log('🎨 Mostrando firma recibida');
    
    firmaRecibidaTemp = firmaBase64;
    
    // Ocultar placeholder
    document.getElementById('placeholderFirma').style.display = 'none';
    
    // Mostrar preview
    const previewDiv = document.getElementById('previewFirmaRecibida');
    const imgFirma = document.getElementById('imgFirmaRecibida');
    
    imgFirma.src = firmaBase64;
    previewDiv.style.display = 'block';
    
    // Actualizar estado
    actualizarEstadoConexion('recibida', '¡Firma recibida correctamente!');
    
    // Reproducir sonido de éxito (opcional)
    reproducirSonidoExito();
}

// ============================================
// FUNCIÓN: CONFIRMAR FIRMA RECIBIDA
// ============================================
function confirmarFirmaRecibida() {
    if (!firmaRecibidaTemp) {
        console.warn('⚠️ No hay firma para confirmar');
        return;
    }
    
    console.log('✅ Confirmando firma recibida');
    
    // Llamar a la función global del formulario principal
    if (typeof window.recibirFirmaDesdeQR === 'function') {
        window.recibirFirmaDesdeQR(firmaRecibidaTemp);
    }
    
    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalFirmaQR'));
    modal.hide();
    
    // Mostrar mensaje de éxito
    mostrarNotificacion('success', '¡Firma aplicada correctamente!');
}

// ============================================
// FUNCIÓN: RECHAZAR FIRMA RECIBIDA
// ============================================
function rechazarFirmaRecibida() {
    console.log('❌ Rechazando firma recibida');
    
    firmaRecibidaTemp = null;
    
    // Ocultar preview
    document.getElementById('previewFirmaRecibida').style.display = 'none';
    
    // Mostrar placeholder
    document.getElementById('placeholderFirma').style.display = 'block';
    
    // Actualizar estado
    actualizarEstadoConexion('esperando', 'Esperando nueva firma...');
    
    // Reiniciar conexión
    inicializarConexion();
}

// ============================================
// FUNCIÓN: REGENERAR QR
// ============================================
function regenerarQR() {
    console.log('🔄 Regenerando código QR');
    
    limpiarSesionFirma();
    inicializarSesionFirma();
    
    mostrarNotificacion('info', 'Código QR regenerado');
}

// ============================================
// FUNCIÓN: LIMPIAR SESIÓN
// ============================================
function limpiarSesionFirma() {
    console.log('🧹 Limpiando sesión de firma');
    
    // Limpiar intervalo de polling
    if (window.firmaCheckInterval) {
        clearInterval(window.firmaCheckInterval);
        window.firmaCheckInterval = null;
    }
    
    // Limpiar localStorage
    if (sessionId) {
        localStorage.removeItem(`firma_${sessionId}`);
    }
    
    // Resetear variables
    firmaRecibidaTemp = null;
    qrCodeInstance = null;
    
    // Resetear UI
    document.getElementById('previewFirmaRecibida').style.display = 'none';
    document.getElementById('placeholderFirma').style.display = 'block';
}

// ============================================
// FUNCIÓN: ACTUALIZAR ESTADO DE CONEXIÓN
// ============================================
function actualizarEstadoConexion(estado, mensaje) {
    const estadoDiv = document.getElementById('estadoConexion');
    
    // Remover clases anteriores
    estadoDiv.classList.remove('alert-warning', 'alert-success', 'alert-info', 'alert-danger');
    
    let icono = '';
    let claseAlerta = '';
    
    switch (estado) {
        case 'esperando':
            claseAlerta = 'alert-warning';
            icono = '<div class="spinner-border spinner-border-sm me-2" role="status"></div>';
            break;
        case 'recibida':
            claseAlerta = 'alert-success';
            icono = '<i class="fas fa-check-circle me-2"></i>';
            break;
        case 'error':
            claseAlerta = 'alert-danger';
            icono = '<i class="fas fa-exclamation-triangle me-2"></i>';
            break;
        default:
            claseAlerta = 'alert-info';
            icono = '<i class="fas fa-info-circle me-2"></i>';
    }
    
    estadoDiv.className = `alert ${claseAlerta}`;
    estadoDiv.innerHTML = `
        <div class="d-flex align-items-center">
            ${icono}
            <span>${mensaje}</span>
        </div>
    `;
}

// ============================================
// FUNCIÓN: MOSTRAR NOTIFICACIÓN
// ============================================
function mostrarNotificacion(tipo, mensaje) {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `alert alert-${tipo} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    notificacion.style.zIndex = '9999';
    notificacion.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notificacion);
    
    // Auto-remover después de 3 segundos
    setTimeout(() => {
        notificacion.remove();
    }, 3000);
}

// ============================================
// FUNCIÓN: REPRODUCIR SONIDO DE ÉXITO
// ============================================
function reproducirSonidoExito() {
    try {
        // Crear contexto de audio
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
        
    } catch (error) {
        console.log('🔇 Audio no disponible:', error);
    }
}

// ============================================
// SIMULACIÓN: RECIBIR FIRMA DESDE CELULAR
// (Solo para pruebas - eliminar en producción)
// ============================================
window.simularFirmaDesdeMovil = function() {
    console.log('🧪 SIMULACIÓN: Recibiendo firma desde celular');
    
    // Crear canvas temporal para generar firma de prueba
    const canvas = document.createElement('canvas');
    canvas.width = 400;
    canvas.height = 200;
    const ctx = canvas.getContext('2d');
    
    // Fondo blanco
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // Dibujar firma de prueba
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 3;
    ctx.lineCap = 'round';
    
    ctx.beginPath();
    ctx.moveTo(50, 100);
    ctx.bezierCurveTo(100, 50, 150, 150, 200, 100);
    ctx.bezierCurveTo(250, 50, 300, 150, 350, 100);
    ctx.stroke();
    
    // Convertir a base64
    const firmaBase64 = canvas.toDataURL('image/png');
    
    // Guardar en localStorage (simular recepción)
    if (sessionId) {
        localStorage.setItem(`firma_${sessionId}`, firmaBase64);
        console.log('✅ Firma de prueba guardada en localStorage');
    }
};

console.log('✅ Modal de firma con QR inicializado');
console.log('💡 Para probar sin celular, ejecuta en consola: simularFirmaDesdeMovil()');
</script>
@endpush
