<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="icon" type="image/svg+xml" href="img/favicon.svg">
    <title>Puente de Hackatones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            min-height: 100vh;
            color: white;
        }
        .card {
            border: 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }
        .btn-primary, .btn-success {
            border-radius: 999px;
            padding: 0.8rem 1.25rem;
        }
        .footer-logo {
            width: 140px;
            height: auto;
            opacity: 0.9;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h1 class="display-5 fw-bold mb-3">Puente de Hackatones</h1>
                    <p class="lead">Elige el evento al que deseas entrar.</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card h-100 text-dark">
                            <div class="card-body p-4">
                                <h3 class="card-title">Hackathon 2025</h3>
                                <p class="card-text">Accede a la colección original del evento 2025 con sus desafíos y flujo de equipos.</p>
                                <a href="2025/index.php" class="btn btn-primary w-100">Entrar a 2025</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100 text-dark">
                            <div class="card-body p-4">
                                <h3 class="card-title">Hackathon 2026</h3>
                                <p class="card-text">Entra al nuevo evento 2026 con su propio conjunto de retos y navegación independiente.</p>
                                <a href="2026/index.php" class="btn btn-success w-100">Entrar a 2026</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <img src="img/cyt.png" alt="Logo Unidad de Ciencia y Tecnología" class="footer-logo">
                </div>
            </div>
        </div>
    </div>
</body>
</html>
