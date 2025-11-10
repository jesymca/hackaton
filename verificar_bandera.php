<?php
session_start();
require_once __DIR__ . '/conf/functions.php';

if (!isset($_SESSION['equipo_id'])) {
    echo json_encode(['success' => false, 'message' => 'No hay sesión activa']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['desafio'], $_POST['bandera'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// Verificar si el hackathon está activo
if (!hackathonEstaActivo()) {
    echo json_encode(['success' => false, 'message' => 'El hackathon no ha iniciado. Espera a que el administrador lo active.']);
    exit;
}

// Verificar si el equipo ha iniciado tiempo
$info_equipo = obtenerTiempoInicioEquipo($_SESSION['equipo_id']);
if (!$info_equipo['tiempo_inicio']) {
    echo json_encode(['success' => false, 'message' => 'Tu equipo no ha iniciado el hackathon. Vuelve a acceder al dashboard.']);
    exit;
}

// Verificar tiempo restante global
$tiempo_restante = calcularTiempoRestanteGlobal();
if ($tiempo_restante <= 0) {
    echo json_encode(['success' => false, 'message' => 'El tiempo del hackathon ha terminado']);
    exit;
}

$desafio = $_POST['desafio'];
$bandera_usuario = trim($_POST['bandera']);
$equipo_id = $_SESSION['equipo_id'];

$configuracion = obtenerConfiguracionDesafios();

if (!isset($configuracion[$desafio])) {
    echo json_encode(['success' => false, 'message' => 'Desafío no válido']);
    exit;
}

// Verificar si ya fue completado
if (desafioCompletado($equipo_id, $desafio)) {
    echo json_encode(['success' => false, 'message' => 'Este desafío ya fue completado por tu equipo']);
    exit;
}

// Verificar bandera
if (verificarBandera($bandera_usuario, $configuracion[$desafio]['flag'])) {
    // Registrar completado y sumar puntos
    if (completarDesafio($equipo_id, $desafio, $configuracion[$desafio]['puntos'])) {
        // Actualizar sesión
        $_SESSION['puntuacion_equipo'] += $configuracion[$desafio]['puntos'];
        echo json_encode([
            'success' => true, 
            'message' => '¡Bandera correcta!', 
            'puntos' => $configuracion[$desafio]['puntos']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar el desafío']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Bandera incorrecta']);
}
?>