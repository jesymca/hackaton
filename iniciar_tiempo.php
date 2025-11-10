<?php
session_start();
require_once __DIR__ . '/conf/functions.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['cedula'])) {
    echo json_encode(['success' => false, 'message' => 'No hay sesión activa']);
    exit;
}

// Verificar que el hackathon esté activo
$hackathon_activo = hackathonEstaActivo();
if (!$hackathon_activo) {
    echo json_encode(['success' => false, 'message' => 'El hackathon no está activo']);
    exit;
}

// Verificar que el equipo no tenga tiempo ya iniciado
$info_equipo = obtenerTiempoInicioEquipo($_SESSION['equipo_id']);
if ($info_equipo['tiempo_inicio']) {
    echo json_encode(['success' => false, 'message' => 'El tiempo ya está iniciado']);
    exit;
}

// Iniciar tiempo manualmente
global $db;
$tiempo_inicio = date('Y-m-d H:i:s');
$stmt = $db->prepare("UPDATE equipos SET tiempo_inicio = ?, inicio_tardio = TRUE WHERE id = ?");

if ($stmt->execute([$tiempo_inicio, $_SESSION['equipo_id']])) {
    echo json_encode(['success' => true, 'message' => 'Tiempo iniciado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
}
?>