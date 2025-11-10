<?php
session_start();
require_once __DIR__ . '/../conf/functions.php';

header('Content-Type: application/json');

try {
    // Obtener todos los equipos con su tiempo acumulado
    global $db;
    $stmt = $db->prepare("
        SELECT id, tiempo_acumulado, completado, puntuacion_total
        FROM equipos 
        WHERE tiempo_acumulado > 0
        ORDER BY id
    ");
    $stmt->execute();
    $equipos_con_tiempo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'equipos_actualizados' => $equipos_con_tiempo,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'equipos_actualizados' => []
    ]);
}
?>