<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Error servidor</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body>
    <div class="error-container">
        <div class="error-code">500</div>
        <h1>error en el servidor</h1>
        <p>Lo sentimos</p>
        <a href="<?= BASE_URL ?>" class="btn btn-primary">Volver al inicio</a>
    </div>
    
    <style>
        .error-container {
            max-width: 500px;
            margin: 100px auto;
            text-align: center;
            padding: 20px;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #dc3545;
        }
    </style>
</body>
</html>