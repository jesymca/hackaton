<?php
session_start();
require_once __DIR__ . '/conf/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['cedula'])) {
    header("Location: index.php");
    exit;
}

// Forzar que la URL muestre el parámetro p2
if (!isset($_GET['p2'])) {
    header("Location: nivel4b.php?p2=AG");
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
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
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
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 20px 20px 0 0;
        }
        .puzzle-body {
            padding: 40px;
        }
        .btn-pista {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
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
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
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
            box-shadow: 0 8px 20px rgba(255, 154, 158, 0.4);
        }
        .pista-box {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border: 2px dashed #ba68c8;
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
            background: #ff9a9e;
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
            background: #ff9a9e;
            color: white;
            box-shadow: 0 0 0 3px rgba(255, 154, 158, 0.3);
        }
        .step.completed .step-number {
            background: #a8edea;
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
                            <p class="mb-0">Nivel 4 - Sigue explorando</p>
                        </div>
                        
                        <div class="puzzle-body">
                            <div class="step-progress">
                                <div class="step completed">
                                    <div class="step-number"><i class="fas fa-check"></i></div>
                                    <small>Inicio</small>
                                </div>
                                <div class="step active">
                                    <div class="step-number">2</div>
                                    <small>Siguiente</small>
                                </div>
                                <div class="step">
                                    <div class="step-number">3</div>
                                    <small>Avance</small>
                                </div>
                                <div class="step">
                                    <div class="step-number">4</div>
                                    <small>Final</small>
                                </div>
                            </div>

                            <h3 class="text-center mb-4 text-primary">Segundo Paso</h3>
                            
                            <div class="text-center mb-4">
                                <p class="lead">Cada paso revela una parte del misterio.</p>
                            </div>

                            <div class="text-center mb-4">
                                <button class="btn btn-pista" onclick="mostrarPista()">
                                    <i class="fas fa-key me-2"></i>Pista
                                </button>
                            </div>

                            <div id="pistaBox" class="pista-box text-center">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>Para pasar este desafio debes ser astuto</strong>
                            </div>

                            <div class="text-center mt-4">
                                <a href="nivel4c.php?p2=AG" class="btn btn-continuar">
                                    <i class="fas fa-arrow-right me-2"></i>
                                    Continuar
                                </a>
                            </div>

                            <div class="text-center mt-3">
                                <a href="nivel4.php?p1=FL" class="btn btn-outline-secondary btn-sm">
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