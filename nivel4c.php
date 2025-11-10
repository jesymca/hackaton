<?php
session_start();
require_once __DIR__ . '/conf/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['cedula'])) {
    header("Location: index.php");
    exit;
}

// Forzar que la URL muestre el parámetro p3
if (!isset($_GET['p3'])) {
    header("Location: nivel4c.php?p3={URL");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nivel 4 - Puzzle de URL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .puzzle-container {
            background: linear-gradient(135deg, #d299c2 0%, #fef9d7 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .puzzle-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .puzzle-header {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 20px 20px 0 0;
        }
        .puzzle-body {
            padding: 40px;
        }
        .btn-pista {
            background: linear-gradient(135deg, #d299c2 0%, #fef9d7 100%);
            border: none;
            color: #333;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-pista:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .btn-continuar {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            border: none;
            color: white;
            padding: 15px 40px;
            font-size: 1.1em;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-continuar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(250, 112, 154, 0.4);
        }
        .pista-box {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px dashed #ffd43b;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            display: none;
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .step-progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            text-align: center;
            flex: 1;
            position: relative;
        }
        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 20px;
            right: -50%;
            width: 100%;
            height: 3px;
            background: #e9ecef;
            z-index: 1;
        }
        .step.active:not(:last-child):after {
            background: #fa709a;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            border: 3px solid white;
            position: relative;
            z-index: 2;
        }
        .step.active .step-number {
            background: #fa709a;
            color: white;
            box-shadow: 0 0 0 3px rgba(250, 112, 154, 0.3);
        }
        .step.completed .step-number {
            background: #d299c2;
            color: white;
        }
    </style>
</head>
<body>
    <div class="puzzle-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="puzzle-card">
                        <div class="puzzle-header">
                            <h1><i class="fas fa-puzzle-piece me-2"></i>Puzzle de Redireccion</h1>
                            <p class="mb-0">Nivel 4 - Casi llegas</p>
                        </div>
                        
                        <div class="puzzle-body">
                            <div class="step-progress">
                                <div class="step completed">
                                    <div class="step-number"><i class="fas fa-check"></i></div>
                                    <small>Inicio</small>
                                </div>
                                <div class="step completed">
                                    <div class="step-number">2</div>
                                    <small>Siguiente</small>
                                </div>
                                <div class="step active">
                                    <div class="step-number">3</div>
                                    <small>Avance</small>
                                </div>
                                <div class="step">
                                    <div class="step-number">4</div>
                                    <small>Final</small>
                                </div>
                            </div>

                            <h3 class="text-center mb-4 text-primary">Tercer Paso</h3>
                            
                            <div class="text-center mb-4">
                                <p class="lead">El patrón comienza a revelarse.</p>
                            </div>

                            <div class="text-center mb-4">
                                <button class="btn btn-pista" onclick="mostrarPista()">
                                    <i class="fas fa-key me-2"></i>Pista
                                </button>
                            </div>

                            <div id="pistaBox" class="pista-box text-center">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>Toma nota de cada cambio que observes</strong>
                            </div>

                            <div class="text-center mt-4">
                                <a href="nivel4d.php?p3={URL" class="btn btn-continuar">
                                    <i class="fas fa-arrow-right me-2"></i>
                                    Continuar
                                </a>
                            </div>

                            <div class="text-center mt-3">
                                <a href="nivel4b.php?p2=AG" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Anterior
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function mostrarPista() {
            const pistaBox = document.getElementById('pistaBox');
            pistaBox.style.display = 'block';
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>