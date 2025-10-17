<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Firma Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .firma-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
        }
        #firma-canvas {
            border: 3px solid #dee2e6;
            border-radius: 15px;
            width: 100%;
            height: 250px;
            touch-action: none;
            cursor: crosshair;
        }
        .btn-custom {
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="firma-container">
        <div class="text-center mb-4">
            <i class="fas fa-signature fa-3x text-primary mb-3"></i>
            <h3>Firma Digital</h3>
            <p class="text-muted">Dibuja tu firma con el dedo</p>
        </div>

        <canvas id="firma-canvas"></canvas>

        <div class="d-grid gap-2 mt-4">
            <button class="btn btn-outline-secondary btn-custom" onclick="limpiarFirma()">
                <i class="fas fa-eraser"></i> Limpiar
            </button>
            <button class="btn btn-primary btn-custom" onclick="guardarFirma()">
                <i class="fas fa-check"></i> Guardar Firma
            </button>
        </div>

        <div id="mensaje" class="alert d-none mt-3"></div>
    </div>

    <script>
        const canvas = document.getElementById('firma-canvas');
        const ctx = canvas.getContext('2d');
        let dibujando = false;

        // Ajustar tamaño del canvas
        function ajustarCanvas() {
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width;
            canvas.height = rect.height;
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 3;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
        }

        ajustarCanvas();
        window.addEventListener('resize', ajustarCanvas);

        // Eventos táctiles
        canvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            dibujando = true;
            const touch = e.touches[0];
            const rect = canvas.getBoundingClientRect();
            ctx.beginPath();
            ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
        });

        canvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            if (!dibujando) return;
            const touch = e.touches[0];
            const rect = canvas.getBoundingClientRect();
            ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
            ctx.stroke();
        });

        canvas.addEventListener('touchend', () => {
            dibujando = false;
        });

        // Eventos de mouse (para pruebas en PC)
        canvas.addEventListener('mousedown', (e) => {
            dibujando = true;
            const rect = canvas.getBoundingClientRect();
            ctx.beginPath();
            ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
        });

        canvas.addEventListener('mousemove', (e) => {
            if (!dibujando) return;
            const rect = canvas.getBoundingClientRect();
            ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
            ctx.stroke();
        });

        canvas.addEventListener('mouseup', () => {
            dibujando = false;
        });

        function limpiarFirma() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        function guardarFirma() {
            const firmaBase64 = canvas.toDataURL('image/png');
            const token = '{{ $token }}';

            fetch(`/firma-movil/${token}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ firma: firmaBase64 })
            })
            .then(response => response.json())
            .then(data => {
                const mensaje = document.getElementById('mensaje');
                if (data.success) {
                    mensaje.className = 'alert alert-success';
                    mensaje.textContent = '✅ Firma guardada exitosamente. Puedes cerrar esta ventana.';
                } else {
                    mensaje.className = 'alert alert-danger';
                    mensaje.textContent = '❌ ' + (data.error || 'Error guardando firma');
                }
                mensaje.classList.remove('d-none');
            })
            .catch(error => {
                const mensaje = document.getElementById('mensaje');
                mensaje.className = 'alert alert-danger';
                mensaje.textContent = '❌ Error de conexión';
                mensaje.classList.remove('d-none');
            });
        }
    </script>
</body>
</html>
