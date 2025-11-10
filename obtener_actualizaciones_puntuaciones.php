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
    // Obtener el último timestamp de verificación de puntuaciones
    $ultima_verificacion = $_SESSION['ultima_verificacion_puntuaciones'] ?? date('Y-m-d H:i:s', strtotime('-1 minute'));
    
    // Guardar las puntuaciones actuales en sesión para comparar
    if (!isset($_SESSION['puntuaciones_anteriores'])) {
        $_SESSION['puntuaciones_anteriores'] = [];
        $equipos_actuales = obtenerRankingEquipos();
        foreach ($equipos_actuales as $equipo) {
            $_SESSION['puntuaciones_anteriores'][$equipo['id']] = $equipo['puntuacion_total'];
        }
    }
    
    // Obtener el ranking actual
    $ranking_actual = obtenerRankingEquipos();
    $equipos_actualizados = [];
    
    // Comparar con las puntuaciones anteriores
    foreach ($ranking_actual as $equipo) {
        $equipo_id = $equipo['id'];
        $puntuacion_actual = $equipo['puntuacion_total'];
        $puntuacion_anterior = $_SESSION['puntuaciones_anteriores'][$equipo_id] ?? 0;
        
        // Si la puntuación cambió, agregar a la lista de actualizados
        if ($puntuacion_actual != $puntuacion_anterior) {
            $equipos_actualizados[] = $equipo;
            $_SESSION['puntuaciones_anteriores'][$equipo_id] = $puntuacion_actual;
        }
    }
    
    // También verificar equipos nuevos que no estaban en la sesión anterior
    $equipos_actuales_ids = array_column($ranking_actual, 'id');
    $equipos_anteriores_ids = array_keys($_SESSION['puntuaciones_anteriores']);
    
    $equipos_nuevos_ids = array_diff($equipos_actuales_ids, $equipos_anteriores_ids);
    foreach ($equipos_nuevos_ids as $equipo_id) {
        $equipo = current(array_filter($ranking_actual, function($e) use ($equipo_id) {
            return $e['id'] == $equipo_id;
        }));
        if ($equipo) {
            $equipos_actualizados[] = $equipo;
            $_SESSION['puntuaciones_anteriores'][$equipo_id] = $equipo['puntuacion_total'];
        }
    }
    
    // Actualizar timestamp de última verificación
    $_SESSION['ultima_verificacion_puntuaciones'] = date('Y-m-d H:i:s');
    
    // Devolver respuesta JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'equipos_actualizados' => $equipos_actualizados,
        'ranking_completo' => $ranking_actual,
        'total_equipos' => count($ranking_actual),
        'ultima_verificacion' => $_SESSION['ultima_verificacion_puntuaciones'],
        'cambios_detectados' => count($equipos_actualizados)
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener actualizaciones: ' . $e->getMessage()
    ]);
}
?>