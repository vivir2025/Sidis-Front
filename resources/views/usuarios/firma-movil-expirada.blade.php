<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesión Expirada</title>
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
        
        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 500px;
            text-align: center;
        }
        
        .error-icon {
            font-size: 80px;
            color: #ffc107;
            margin-bottom: 20px;
        }
        
        h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
        }
        
        .error-message {
            color: #666;
            font-size: 16px;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .alert-custom {
            background: #e7f3ff;
            border: 2px solid #2196F3;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .token-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .token-info code {
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h1>Sesión Expirada</h1>
        
        <p class="error-message">
            {{ $mensaje ?? 'La sesión ha expirado o no es válida.' }}
        </p>
        
        <div class="alert-custom">
            <i class="fas fa-info-circle me-2"></i>
            <strong>¿Qué hacer ahora?</strong>
            <p class="mb-0 mt-2">
                Por favor, genera un nuevo código QR desde tu computadora y escanéalo nuevamente.
            </p>
        </div>
        
        <hr class="my-4">
        
        <div class="token-info">
            <small class="text-muted">
                <i class="fas fa-clock me-1"></i>
                Las sesiones expiran después de <strong>5 minutos</strong> por seguridad
            </small>
            
            @if(isset($token))
            <div class="mt-2">
                <small class="text-muted">
                    Token: <code>{{ $token }}</code>
                </small>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
