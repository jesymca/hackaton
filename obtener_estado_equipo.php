<?php
session_start();
require_once __DIR__ . '/conf/functions.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['cedula'])) {
    echo json_encode(['success' => false, 'message' => 'No hay sesión activa']);
    exit;
}

// Obtener estado del equipo
$info_equipo = obtenerTiempoInicioEquipo($_SESSION['equipo_id']);
$estado = $info_equipo['estado'] ?? 0;

echo json_encode([
    'success' => true,
    'estado' => (int)$estado,
    'equipo_id' => $_SESSION['equipo_id'],
    'timestamp' => time()
]);
?>