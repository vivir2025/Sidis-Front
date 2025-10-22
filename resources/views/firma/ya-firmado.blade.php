<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Firma ya procesada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
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
        .icon-success {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="mensaje-container">
        <i class="fas fa-check-circle icon-success"></i>
        <h2>Firma ya procesada</h2>
        <p class="lead">Esta firma ya ha sido registrada correctamente.</p>
        <p class="text-muted">No es necesario volver a firmar. Puede cerrar esta ventana.</p>
        <div class="mt-4">
            <button class="btn btn-success" onclick="window.close()">
                <i class="fas fa-check-circle me-2"></i>Cerrar Ventana
            </button>
        </div>
    </div>
</body>
</html>