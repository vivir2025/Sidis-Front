<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Firmar Documento</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        .firma-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .firma-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .firma-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .firma-header p {
            color: #666;
            font-size: 14px;
        }
        
        #canvasFirma {
            border: 3px solid #667eea;
            border-radius: 15px;
            cursor: crosshair;
            touch-action: none;
            width: 100%;
            max-width: 100%;
            background: white;
            display: block;
        }
        
        .canvas-wrapper {
            position: relative;
            margin-bottom: 20px;
        }
        
        .canvas-placeholder {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #ccc;
            font-size: 18px;
            pointer-events: none;
            z-index: 1;
        }
        
        .btn-group-firma {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-group-firma button {
            flex: 1;
            padding: 15px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-limpiar {
            background: #f8f9fa;
            color: #666;
        }
        
        .btn-limpiar:hover {
            background: #e9ecef;
        }
        
        .btn-enviar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-enviar:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-enviar:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert-info-custom {
            background: #e7f3ff;
            border: 2px solid #2196F3;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .spinner-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }
        
        @media (max-width: 576px) {
            .firma-container {
                padding: 20px;
            }
            
            .firma-header h1 {
                font-size: 20px;
            }
            
            .btn-group-firma button {
                padding: 12px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="firma-container">
        <!-- Header -->
        <div class="firma-header">
            <div class="mb-3">
                <i class="fas fa-signature fa-3x text-primary"></i>
            </div>
            <h1>Firma Digital</h1>
            <p class="mb-0">Dibuja tu firma en el recuadro de abajo</p>
        </div>
        
        <!-- Instrucciones -->
        <div class="alert-info-custom">
            <div class="d-flex align-items-start">
                <i class="fas fa-info-circle me-2 mt-1"></i>
                <div>
                    <strong>Instrucciones:</strong>
                    <ol class="mb-0 mt-2 ps-3">
                        <li>Dibuja tu firma con el dedo o stylus</li>
                        <li>Si te equivocas, presiona "Limpiar"</li>
                        <li>Cuando estés conforme, presiona "Enviar Firma"</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <!-- Canvas de Firma -->
        <div class="canvas-wrapper">
            <div class="canvas-placeholder" id="canvasPlaceholder">
                <i class="fas fa-pen-fancy"></i> Dibuja aquí tu firma
            </div>
            <canvas id="canvasFirma" width="540" height="300"></canvas>
        </div>
        
        <!-- Botones -->
        <div class="btn-group-firma">
            <button type="button" class="btn-limpiar" onclick="limpiarFirma()">
                <i class="fas fa-eraser me-2"></i>
                Limpiar
            </button>
            <button type="button" class="btn-enviar" id="btnEnviar" onclick="enviarFirma()" disabled>
                <i class="fas fa-paper-plane me-2"></i>
                Enviar Firma
            </button>
        </div>
        
        <!-- Info de sesión -->
        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="fas fa-shield-alt me-1"></i>
                Sesión segura: <code>{{ $token }}</code>
            </small>
        </div>
    </div>
    
    <!-- Spinner de carga -->
    <div class="spinner-overlay" id="spinnerOverlay">
        <div class="spinner-content">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Enviando...</span>
            </div>
            <h5>Enviando firma...</h5>
            <p class="text-muted mb-0">Por favor espera</p>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
<script>
    // ============================================
    // VARIABLES GLOBALES
    // ============================================
    const canvas = document.getElementById('canvasFirma');
    const ctx = canvas.getContext('2d');
    const btnEnviar = document.getElementById('btnEnviar');
    const placeholder = document.getElementById('canvasPlaceholder');
    const token = '{{ $token }}';
    
    let dibujando = false;
    let firmaDibujada = false;
    let ultimoX = 0;
    let ultimoY = 0;
    
    // ============================================
    // CONFIGURACIÓN INICIAL DEL CANVAS
    // ============================================
    function inicializarCanvas() {
        // Obtener el tamaño real del canvas en la pantalla
        const rect = canvas.getBoundingClientRect();
        
        // Ajustar el tamaño interno del canvas al tamaño visual
        canvas.width = rect.width;
        canvas.height = rect.height;
        
        console.log('📐 Canvas inicializado:', {
            width: canvas.width,
            height: canvas.height,
            visualWidth: rect.width,
            visualHeight: rect.height
        });
        
        // Configurar estilo de dibujo
        ctx.strokeStyle = '#000000';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        
        // Fondo blanco
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }
    
    // ============================================
    // FUNCIÓN: OBTENER COORDENADAS PRECISAS
    // ============================================
    function obtenerCoordenadas(e) {
        const rect = canvas.getBoundingClientRect();
        
        // Calcular el factor de escala
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        
        let clientX, clientY;
        
        // Detectar si es touch o mouse
        if (e.touches && e.touches.length > 0) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        } else {
            clientX = e.clientX;
            clientY = e.clientY;
        }
        
        // Calcular coordenadas relativas al canvas
        const x = (clientX - rect.left) * scaleX;
        const y = (clientY - rect.top) * scaleY;
        
        return { x, y };
    }
    
    // ============================================
    // FUNCIÓN: INICIAR DIBUJO
    // ============================================
    function iniciarDibujo(e) {
        e.preventDefault();
        dibujando = true;
        
        const coords = obtenerCoordenadas(e);
        ultimoX = coords.x;
        ultimoY = coords.y;
        
        ctx.beginPath();
        ctx.moveTo(ultimoX, ultimoY);
        
        ocultarPlaceholder();
        
        console.log('🖊️ Inicio dibujo en:', coords);
    }
    
    // ============================================
    // FUNCIÓN: DIBUJAR
    // ============================================
    function dibujar(e) {
        e.preventDefault();
        
        if (!dibujando) return;
        
        const coords = obtenerCoordenadas(e);
        
        // Dibujar línea desde la última posición
        ctx.beginPath();
        ctx.moveTo(ultimoX, ultimoY);
        ctx.lineTo(coords.x, coords.y);
        ctx.stroke();
        
        // Actualizar última posición
        ultimoX = coords.x;
        ultimoY = coords.y;
        
        firmaDibujada = true;
        habilitarBotonEnviar();
    }
    
    // ============================================
    // FUNCIÓN: DETENER DIBUJO
    // ============================================
    function detenerDibujo(e) {
        if (e) e.preventDefault();
        
        if (dibujando) {
            console.log('🖊️ Fin del trazo');
        }
        
        dibujando = false;
    }
    
    // ============================================
    // EVENTOS MOUSE (DESKTOP)
    // ============================================
    canvas.addEventListener('mousedown', iniciarDibujo);
    canvas.addEventListener('mousemove', dibujar);
    canvas.addEventListener('mouseup', detenerDibujo);
    canvas.addEventListener('mouseleave', detenerDibujo);
    
    // ============================================
    // EVENTOS TOUCH (MÓVIL) - MÁS PRECISOS
    // ============================================
    canvas.addEventListener('touchstart', iniciarDibujo, { passive: false });
    canvas.addEventListener('touchmove', dibujar, { passive: false });
    canvas.addEventListener('touchend', detenerDibujo, { passive: false });
    canvas.addEventListener('touchcancel', detenerDibujo, { passive: false });
    
    // ============================================
    // FUNCIÓN: LIMPIAR FIRMA
    // ============================================
    function limpiarFirma() {
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        firmaDibujada = false;
        btnEnviar.disabled = true;
        mostrarPlaceholder();
        console.log('🧹 Firma limpiada');
    }
    
    // ============================================
// FUNCIÓN: ENVIAR FIRMA
// ============================================
async function enviarFirma() {
    if (!firmaDibujada) {
        alert('Por favor dibuja tu firma primero');
        return;
    }
    
    // Deshabilitar botón para evitar doble envío
    btnEnviar.disabled = true;
    btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
    
    try {
        console.log('📤 Enviando firma...');
        
        // Mostrar spinner
        document.getElementById('spinnerOverlay').style.display = 'flex';
        
        // Convertir canvas a base64
        const firmaBase64 = canvas.toDataURL('image/png');
        
        console.log('🖼️ Firma convertida a base64');
        console.log('📊 Tamaño:', Math.round(firmaBase64.length / 1024), 'KB');
        
        // Enviar al servidor
        const response = await fetch(`/firma-movil/${token}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                firma: firmaBase64
            })
        });
        
        console.log('📡 Respuesta del servidor:', response.status);
        
        // Ocultar spinner ANTES de procesar respuesta
        document.getElementById('spinnerOverlay').style.display = 'none';
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('❌ Error del servidor:', errorText);
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        console.log('✅ Respuesta:', data);
        
        if (data.success) {
            console.log('✅ Firma enviada exitosamente');
            
            // Mostrar mensaje de éxito (reemplaza todo el contenedor)
            document.querySelector('.firma-container').innerHTML = `
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 80px;"></i>
                    </div>
                    <h2 class="mb-3 text-success">¡Firma Enviada!</h2>
                    <p class="text-muted mb-4">Tu firma ha sido recibida correctamente</p>
                    
                    <div class="alert alert-success d-flex align-items-center justify-content-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <span>Puedes cerrar esta ventana</span>
                    </div>
                    
                    <div class="mt-4">
                        <p class="text-muted mb-2"><small>Vista previa de tu firma:</small></p>
                        <img src="${firmaBase64}" 
                             alt="Tu firma" 
                             style="max-width: 300px; 
                                    border: 2px solid #28a745; 
                                    border-radius: 10px;
                                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    </div>
                    
                    <div class="mt-4">
                        <button class="btn btn-secondary" onclick="window.close()">
                            <i class="fas fa-times me-2"></i>
                            Cerrar Ventana
                        </button>
                    </div>
                </div>
            `;
        } else {
            throw new Error(data.error || 'Error desconocido al procesar la firma');
        }
        
    } catch (error) {
        console.error('❌ Error enviando firma:', error);
        
        // Ocultar spinner en caso de error
        document.getElementById('spinnerOverlay').style.display = 'none';
        
        // Restaurar botón
        btnEnviar.disabled = false;
        btnEnviar.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Enviar Firma';
        
        // Mostrar error al usuario
        alert('❌ Error al enviar la firma:\n\n' + error.message + '\n\nPor favor, intenta nuevamente.');
    }
}


    // ============================================
    // FUNCIONES AUXILIARES
    // ============================================
    function ocultarPlaceholder() {
        placeholder.style.display = 'none';
    }
    
    function mostrarPlaceholder() {
        placeholder.style.display = 'block';
    }
    
    function habilitarBotonEnviar() {
        btnEnviar.disabled = false;
    }
    
    // ============================================
    // REDIMENSIONAR CANVAS AL CAMBIAR ORIENTACIÓN
    // ============================================
    window.addEventListener('resize', () => {
        console.log('📱 Orientación cambiada, reajustando canvas...');
        
        // Guardar la firma actual
        const firmaActual = canvas.toDataURL();
        
        // Reinicializar canvas
        inicializarCanvas();
        
        // Restaurar firma si existía
        if (firmaDibujada) {
            const img = new Image();
            img.onload = () => {
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            };
            img.src = firmaActual;
        }
    });
    
    // ============================================
    // PREVENIR SCROLL AL DIBUJAR (MÓVIL)
    // ============================================
    document.body.addEventListener('touchmove', (e) => {
        if (e.target === canvas) {
            e.preventDefault();
        }
    }, { passive: false });
    
    // ============================================
    // INICIALIZACIÓN
    // ============================================
    window.addEventListener('load', () => {
        inicializarCanvas();
        console.log('✅ Vista móvil de firma inicializada');
        console.log('🔑 Token:', token);
        console.log('📱 Dispositivo:', /Mobi|Android/i.test(navigator.userAgent) ? 'Móvil' : 'Desktop');
    });
</script>

</body>
</html>
