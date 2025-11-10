<?php
session_start();
require_once __DIR__ . '/conf/functions.php';

// Verificar autenticación administrativa
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

try {
    // Obtener el último ID de equipo conocido desde la sesión
    $ultimo_id_conocido = $_SESSION['ultimo_equipo_id'] ?? 0;
    
    // Obtener equipos nuevos (con ID mayor al último conocido)
    global $db;
    $stmt = $db->prepare("
        SELECT id, nombre_equipo, codigo_equipo, puntuacion_total, tiempo_inicio, inicio_tardio, estado, creado_en 
        FROM equipos 
        WHERE id > ? 
        ORDER BY id ASC
    ");
    $stmt->execute([$ultimo_id_conocido]);
    $nuevos_equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Actualizar el último ID conocido
    if (!empty($nuevos_equipos)) {
        $ultimo_equipo = end($nuevos_equipos);
        $_SESSION['ultimo_equipo_id'] = $ultimo_equipo['id'];
    }
    
    // Obtener el ranking completo actualizado
    $ranking_completo = obtenerRankingEquipos();
    $total_equipos = count($ranking_completo);
    
    // Devolver respuesta JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'nuevos_equipos' => $nuevos_equipos,
        'total_equipos' => $total_equipos,
        'ultimo_id' => $_SESSION['ultimo_equipo_id'] ?? $ultimo_id_conocido
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener equipos: ' . $e->getMessage()
    ]);
}
?>