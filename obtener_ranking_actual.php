<?php
session_start();
require_once __DIR__ . '/conf/functions.php';

header('Content-Type: application/json');

try {
    $ranking = obtenerRankingEquiposConTiempo();
    $hackathon_activo = hackathonEstaActivo();
    $tiempo_restante = calcularTiempoRestanteGlobal();
    
    echo json_encode([
        'success' => true,
        'ranking' => $ranking,
        'hackathon_activo' => $hackathon_activo,
        'tiempo_restante' => $tiempo_restante
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>