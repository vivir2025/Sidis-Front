<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sesión Expirada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .mensaje-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .icon-error {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="mensaje-container">
        <i class="fas fa-exclamation-circle icon-error"></i>
        <h2>Sesión Expirada</h2>
        <p class="lead">El enlace para firmar ha expirado o no es válido.</p>
        <p class="text-muted">Por favor, solicite un nuevo código QR para continuar.</p>
        <div class="mt-4">
            <button class="btn btn-primary" onclick="window.close()">
                <i class="fas fa-times-circle me-2"></i>Cerrar Ventana
            </button>
        </div>
    </div>
</body>
</html>