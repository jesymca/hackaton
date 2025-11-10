<?php
// filepath: conf/functions.php

// Incluir la configuración de la base de datos
require_once __DIR__ . '/db.php';

// Crear variable global $db (alias de $pdo para mayor claridad)
$db = $pdo;

/**
 * Validar que una cédula contenga solo números
 */
function validarCedula($cedula) {
    return preg_match('/^\d+$/', trim($cedula));
}

/**
 * Generar código único para equipo
 */
function generarCodigoEquipo() {
    $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $codigo = '';
    for ($i = 0; $i < 6; $i++) {
        $codigo .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    return $codigo;
}

/**
 * Verificar si un usuario existe en la base de datos
 */
function usuarioExiste($cedula) {
    global $db;
    $stmt = $db->prepare("SELECT p.*, e.nombre_equipo, e.codigo_equipo, e.puntuacion_total, e.tiempo_inicio, e.inicio_tardio 
                         FROM participantes p 
                         LEFT JOIN equipos e ON p.equipo_id = e.id 
                         WHERE p.cedula = ?");
    $stmt->execute([$cedula]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Verificar si un equipo existe por código
 */
function equipoExiste($codigo_equipo) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM equipos WHERE codigo_equipo = ?");
    $stmt->execute([$codigo_equipo]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Contar miembros en un equipo
 */
function contarMiembrosEquipo($equipo_id) {
    global $db;
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM participantes WHERE equipo_id = ?");
    $stmt->execute([$equipo_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
}

/**
 * Registrar un nuevo equipo
 */
function registrarEquipo($nombre_equipo) {
    global $db;
    $codigo_equipo = generarCodigoEquipo();
    
    // Verificar que el código no exista (aunque es muy improbable)
    while (equipoExiste($codigo_equipo)) {
        $codigo_equipo = generarCodigoEquipo();
    }
    
    $stmt = $db->prepare("INSERT INTO equipos (nombre_equipo, codigo_equipo) VALUES (?, ?)");
    if ($stmt->execute([$nombre_equipo, $codigo_equipo])) {
        return $db->lastInsertId();
    }
    return false;
}

/**
 * Registrar un nuevo participante y asignar a equipo
 */
function registrarParticipante($nombre, $cedula, $equipo_id) {
    global $db;
    $stmt = $db->prepare("INSERT INTO participantes (nombre, cedula, equipo_id) VALUES (?, ?, ?)");
    return $stmt->execute([$nombre, $cedula, $equipo_id]);
}

/**
 * Obtener información del equipo
 */
function obtenerInfoEquipo($equipo_id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM equipos WHERE id = ?");
    $stmt->execute([$equipo_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Obtener miembros del equipo
 */
function obtenerMiembrosEquipo($equipo_id) {
    global $db;
    $stmt = $db->prepare("SELECT nombre, cedula FROM participantes WHERE equipo_id = ? ORDER BY creado_en");
    $stmt->execute([$equipo_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Verificar si un desafío ya fue completado por el equipo
 */
function desafioCompletado($equipo_id, $desafio_id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM desafios_completados WHERE equipo_id = ? AND desafio_id = ?");
    $stmt->execute([$equipo_id, $desafio_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

/**
 * Marcar desafío como completado y sumar puntos
 */
function completarDesafio($equipo_id, $desafio_id, $puntos) {
    global $db;
    
    // Verificar si ya está completado
    if (desafioCompletado($equipo_id, $desafio_id)) {
        return false;
    }
    
    // Registrar completado
    $stmt = $db->prepare("INSERT INTO desafios_completados (equipo_id, desafio_id) VALUES (?, ?)");
    $stmt->execute([$equipo_id, $desafio_id]);
    
    // Registrar tiempo acumulado
    registrarTiempoDesafioCompletado($equipo_id, $desafio_id);
    
    // Actualizar contador de desafíos completados
    actualizarDesafiosCompletados($equipo_id);
    
    // Sumar puntos al equipo
    $stmt = $db->prepare("UPDATE equipos SET puntuacion_total = puntuacion_total + ? WHERE id = ?");
    return $stmt->execute([$puntos, $equipo_id]);
}

/**
 * Iniciar sesión del usuario
 */
function iniciarSesion($participante) {
    $_SESSION['nombre'] = $participante['nombre'];
    $_SESSION['cedula'] = $participante['cedula'];
    $_SESSION['equipo_id'] = $participante['equipo_id'];
    $_SESSION['nombre_equipo'] = $participante['nombre_equipo'];
    $_SESSION['codigo_equipo'] = $participante['codigo_equipo'];
    $_SESSION['puntuacion_equipo'] = $participante['puntuacion_total'];
    $_SESSION['tiempo_inicio'] = $participante['tiempo_inicio'];
}

/**
 * Iniciar tiempo del equipo (SOLO cuando el hackathon esté activo)
 */
function iniciarTiempoEquipo($equipo_id) {
    global $db;
    
    // Verificar si el hackathon está activo
    $config = obtenerConfiguracionHackathon();
    if (!$config || !$config['hackathon_iniciado']) {
        return false; // No iniciar tiempo si el hackathon no ha comenzado
    }
    
    $tiempo_inicio = date('Y-m-d H:i:s');
    $stmt = $db->prepare("UPDATE equipos SET tiempo_inicio = ? WHERE id = ?");
    return $stmt->execute([$tiempo_inicio, $equipo_id]);
}

/**
 * Validar sesión activa
 */
function validarSesion() {
    if (!isset($_SESSION['cedula'])) {
        return false;
    }
    
    global $db;
    $stmt = $db->prepare("SELECT p.*, e.nombre_equipo, e.codigo_equipo, e.puntuacion_total, e.tiempo_inicio, e.inicio_tardio 
                         FROM participantes p 
                         LEFT JOIN equipos e ON p.equipo_id = e.id 
                         WHERE p.cedula = ?");
    $stmt->execute([$_SESSION['cedula']]);
    $participante = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$participante) {
        session_unset();
        session_destroy();
        return false;
    }
    
    return $participante;
}

/**
 * Calcular segundos transcurridos desde el inicio
 */
function calcularTiempoTranscurrido($tiempo_inicio) {
    if (!$tiempo_inicio) return 0;
    $tiempo_inicio = strtotime($tiempo_inicio);
    $ahora = time();
    return $ahora - $tiempo_inicio;
}

/**
 * Mostrar alerta con JavaScript
 */
function mostrarAlerta($mensaje) {
    echo "<script>alert('" . addslashes($mensaje) . "');window.location='index.php';</script>";
    exit;
}

/**
 * Verificar bandera (para los desafíos)
 */
function verificarBandera($bandera_usuario, $bandera_correcta) {
    return trim($bandera_usuario) === $bandera_correcta;
}

/**
 * Obtener configuración de desafíos
 */
function obtenerConfiguracionDesafios() {
    return [
        'ctf' => [
            'flag' => 'FLAG{SQL_INYECCION_EXITOSA}',
            'puntos' => 1,
            'tiempo' => 15 * 60
        ],
        're' => [
            'flag' => 'FLAG{REVERSE_IS_FUN}',
            'puntos' => 1,
            'tiempo' => 15 * 60
        ],
        'crypto' => [
            'flag' => 'FLAG{EL_DESENCRIPTADOR_MASTER}',
            'puntos' => 1,
            'tiempo' => 15 * 60
        ],
        'url' => [
            'flag' => 'FLAG{URL_HACK}',
            'puntos' => 1,
            'tiempo' => 15 * 60
        ],
        'meta' => [
            'flag' => 'FLAG{SOY_EINSTEIN_SIUUU}',
            'puntos' => 1,
            'tiempo' => 15 * 60
        ],
        'promo' => [
            'flag' => 'FLAG{SALDO_INSUFICIENTE}',
            'puntos' => 1,
            'tiempo' => 15 * 60
        ]
    ];
}

/**
 * Obtener configuración del hackathon
 */
function obtenerConfiguracionHackathon() {
    global $db;
    $stmt = $db->prepare("SELECT * FROM configuracion_hackathon ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Iniciar hackathon globalmente
 */
function iniciarHackathonGlobal() {
    global $db;
    $tiempo_inicio = date('Y-m-d H:i:s');
    
    $stmt = $db->prepare("UPDATE configuracion_hackathon SET hackathon_iniciado = TRUE, tiempo_inicio_global = ?");
    return $stmt->execute([$tiempo_inicio]);
}

/**
 * Reiniciar hackathon (para testing)
 */
function reiniciarHackathon() {
    global $db;
    
    $stmt = $db->prepare("UPDATE configuracion_hackathon SET hackathon_iniciado = FALSE, tiempo_inicio_global = NULL");
    $stmt->execute();
    
    // Reiniciar puntuaciones, desafíos completados, estado y TIEMPOS
    $stmt = $db->prepare("UPDATE equipos SET 
        puntuacion_total = 0, 
        tiempo_inicio = NULL, 
        inicio_tardio = FALSE, 
        estado = 0,
        tiempo_acumulado = 0,
        tiempo_finalizacion = NULL,
        desafios_completados = 0,
        completado = FALSE
    ");
    $stmt->execute();
    
    $stmt = $db->prepare("DELETE FROM desafios_completados");
    return $stmt->execute();
}

/**
 * Calcular tiempo transcurrido desde inicio global
 */
function calcularTiempoTranscurridoGlobal() {
    $config = obtenerConfiguracionHackathon();
    if (!$config || !$config['tiempo_inicio_global']) {
        return 0;
    }
    
    $tiempo_inicio = strtotime($config['tiempo_inicio_global']);
    $ahora = time();
    return $ahora - $tiempo_inicio;
}

/**
 * Calcular tiempo restante global
 */
function calcularTiempoRestanteGlobal() {
    $config = obtenerConfiguracionHackathon();
    if (!$config || !$config['tiempo_inicio_global']) {
        return $config ? $config['duracion_minutos'] * 60 : 90 * 60;
    }
    
    $transcurrido = calcularTiempoTranscurridoGlobal();
    $total_segundos = $config['duracion_minutos'] * 60;
    $restante = $total_segundos - $transcurrido;
    
    return max(0, $restante);
}

/**
 * Verificar si el hackathon está activo
 */
function hackathonEstaActivo() {
    $config = obtenerConfiguracionHackathon();
    if (!$config || !$config['hackathon_iniciado']) {
        return false;
    }
    
    $tiempo_restante = calcularTiempoRestanteGlobal();
    return $tiempo_restante > 0;
}

/**
 * Obtener tiempo de inicio para un equipo específico
 */
function obtenerTiempoInicioEquipo($equipo_id) {
    global $db;
    $stmt = $db->prepare("SELECT tiempo_inicio, inicio_tardio, estado FROM equipos WHERE id = ?");
    $stmt->execute([$equipo_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Iniciar tiempo para equipo que se une tarde (SOLO cuando el hackathon esté activo)
 */
function iniciarTiempoEquipoTardio($equipo_id) {
    global $db;
    
    // Verificar si el hackathon está activo
    $config = obtenerConfiguracionHackathon();
    if (!$config || !$config['hackathon_iniciado']) {
        return false; // No iniciar tiempo si el hackathon no ha comenzado
    }
    
    $tiempo_inicio = date('Y-m-d H:i:s');
    $stmt = $db->prepare("UPDATE equipos SET tiempo_inicio = ?, inicio_tardio = TRUE WHERE id = ?");
    return $stmt->execute([$tiempo_inicio, $equipo_id]);
}

/**
 * Forzar inicio de tiempo para equipo cuando accede después del inicio del hackathon
 * (Esta función ya no se usa - el tiempo solo se inicia desde equipos.php)
 */
function forzarInicioTiempoEquipo($equipo_id) {
    global $db;
    
    $config = obtenerConfiguracionHackathon();
    if (!$config || !$config['hackathon_iniciado']) {
        return false;
    }
    
    // Verificar si el equipo ya tiene tiempo iniciado
    $info_equipo = obtenerTiempoInicioEquipo($equipo_id);
    if ($info_equipo['tiempo_inicio']) {
        return true; // Ya tiene tiempo iniciado
    }
    
    // Iniciar tiempo marcando como tardío
    $tiempo_inicio = date('Y-m-d H:i:s');
    $stmt = $db->prepare("UPDATE equipos SET tiempo_inicio = ?, inicio_tardio = TRUE WHERE id = ?");
    return $stmt->execute([$tiempo_inicio, $equipo_id]);
}

/**
 * Obtener el último equipo creado
 */
function obtenerUltimoEquipo() {
    global $db;
    $stmt = $db->prepare("SELECT * FROM equipos ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Obtener ranking de equipos
 */
function obtenerRankingEquipos() {
    global $db;
    $stmt = $db->prepare("SELECT id, nombre_equipo, codigo_equipo, puntuacion_total, tiempo_inicio, inicio_tardio, estado FROM equipos ORDER BY puntuacion_total DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Unir usuario a equipo existente
 */
function unirAEquipo($cedula, $nombre, $codigo_equipo) {
    global $db;
    
    // Verificar que el equipo exista
    $equipo = equipoExiste($codigo_equipo);
    if (!$equipo) {
        return ['success' => false, 'message' => 'El código del equipo no existe'];
    }
    
    // Verificar que la cédula no esté ya registrada
    if (usuarioExiste($cedula)) {
        return ['success' => false, 'message' => 'La cédula ya está registrada en otro equipo'];
    }
    
    // Verificar que el equipo no tenga más de 4 miembros
    $miembros_actuales = contarMiembrosEquipo($equipo['id']);
    if ($miembros_actuales >= 4) {
        return ['success' => false, 'message' => 'El equipo ya tiene 4 miembros'];
    }
    
    // Registrar el participante
    if (registrarParticipante($nombre, $cedula, $equipo['id'])) {
        return ['success' => true, 'equipo_id' => $equipo['id']];
    }
    
    return ['success' => false, 'message' => 'Error al registrar el participante'];
}

/**
 * Verificar bandera y registrar puntos
 */
function verificarBanderaDesafio($equipo_id, $desafio_id, $bandera_usuario) {
    $config_desafios = obtenerConfiguracionDesafios();
    
    if (!isset($config_desafios[$desafio_id])) {
        return ['success' => false, 'message' => 'Desafío no encontrado'];
    }
    
    $desafio = $config_desafios[$desafio_id];
    
    // Verificar si ya fue completado
    if (desafioCompletado($equipo_id, $desafio_id)) {
        return ['success' => false, 'message' => 'Este desafío ya fue completado por tu equipo'];
    }
    
    // Verificar bandera
    if (verificarBandera($bandera_usuario, $desafio['flag'])) {
        // Registrar completado y sumar puntos
        if (completarDesafio($equipo_id, $desafio_id, $desafio['puntos'])) {
            return [
                'success' => true, 
                'message' => '¡Bandera correcta!', 
                'puntos' => $desafio['puntos']
            ];
        } else {
            return ['success' => false, 'message' => 'Error al registrar los puntos'];
        }
    } else {
        return ['success' => false, 'message' => 'Bandera incorrecta'];
    }
}

/**
 * Eliminar equipo y todos sus datos relacionados
 */
function eliminarEquipo($equipo_id) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // 1. Eliminar desafíos completados del equipo
        $stmt = $db->prepare("DELETE FROM desafios_completados WHERE equipo_id = ?");
        $stmt->execute([$equipo_id]);
        
        // 2. Eliminar participantes del equipo
        $stmt = $db->prepare("DELETE FROM participantes WHERE equipo_id = ?");
        $stmt->execute([$equipo_id]);
        
        // 3. Eliminar el equipo
        $stmt = $db->prepare("DELETE FROM equipos WHERE id = ?");
        $stmt->execute([$equipo_id]);
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error al eliminar equipo: " . $e->getMessage());
        return false;
    }
}


/**
 * Obtener equipos creados después de un ID específico
 */
function obtenerEquiposNuevos($ultimo_id) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT id, nombre_equipo, codigo_equipo, puntuacion_total, tiempo_inicio, inicio_tardio, estado, creado_en 
            FROM equipos 
            WHERE id > ? 
            ORDER BY id ASC
        ");
        $stmt->execute([$ultimo_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo equipos nuevos: " . $e->getMessage());
        return [];
    }
}


/**
 * Registrar tiempo cuando se completa un desafío
 */
function registrarTiempoDesafioCompletado($equipo_id, $desafio_id) {
    global $db;
    
    // Obtener tiempo actual del equipo
    $equipo = obtenerInfoEquipo($equipo_id);
    if (!$equipo || !$equipo['tiempo_inicio']) {
        return false;
    }
    
    // Calcular tiempo transcurrido hasta ahora
    $tiempo_transcurrido = calcularTiempoTranscurrido($equipo['tiempo_inicio']);
    
    // Actualizar tiempo acumulado
    $stmt = $db->prepare("UPDATE equipos SET tiempo_acumulado = ? WHERE id = ?");
    return $stmt->execute([$tiempo_transcurrido, $equipo_id]);
}

/**
 * Marcar equipo como completado (cuando termina los 6 desafíos)
 */
function marcarEquipoCompletado($equipo_id) {
    global $db;
    
    $tiempo_finalizacion = date('Y-m-d H:i:s');
    $stmt = $db->prepare("UPDATE equipos SET completado = TRUE, tiempo_finalizacion = ?, desafios_completados = 6 WHERE id = ?");
    return $stmt->execute([$tiempo_finalizacion, $equipo_id]);
}

/**
 * Obtener ranking de equipos considerando tiempo acumulado
 */
function obtenerRankingEquiposConTiempo() {
    global $db;
    
    $stmt = $db->prepare("
        SELECT 
            id, 
            nombre_equipo, 
            codigo_equipo, 
            puntuacion_total, 
            tiempo_inicio, 
            inicio_tardio, 
            estado,
            tiempo_acumulado,
            tiempo_finalizacion,
            desafios_completados,
            completado
        FROM equipos 
        ORDER BY 
            completado DESC,
            puntuacion_total DESC,
            tiempo_acumulado ASC,
            creado_en ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Actualizar desafíos completados
 */
function actualizarDesafiosCompletados($equipo_id) {
    global $db;
    
    $stmt = $db->prepare("
        UPDATE equipos 
        SET desafios_completados = (
            SELECT COUNT(*) FROM desafios_completados WHERE equipo_id = ?
        ) 
        WHERE id = ?
    ");
    $stmt->execute([$equipo_id, $equipo_id]);
    
    // Verificar si completó todos los desafíos
    $equipo = obtenerInfoEquipo($equipo_id);
    if ($equipo['desafios_completados'] >= 6 && !$equipo['completado']) {
        marcarEquipoCompletado($equipo_id);
    }
    
    return $equipo['desafios_completados'];
}


/**
 * Formatear segundos a formato MM:SS
 */
function formatearTiempo($segundos) {
    if ($segundos <= 0) return '--:--';
    
    $minutos = floor($segundos / 60);
    $segundos_restantes = $segundos % 60;
    
    return sprintf("%02d:%02d", $minutos, $segundos_restantes);
}


/**
 * Actualizar la duración del hackathon
 */
function actualizarDuracionHackathon($duracion_minutos) {
    global $db;
    
    // Validar que la duración sea un número positivo
    if (!is_numeric($duracion_minutos) || $duracion_minutos <= 0) {
        return false;
    }
    
    $stmt = $db->prepare("UPDATE configuracion_hackathon SET duracion_minutos = ?");
    return $stmt->execute([$duracion_minutos]);
}

/**
 * Obtener la duración actual del hackathon
 */
function obtenerDuracionHackathon() {
    $config = obtenerConfiguracionHackathon();
    return $config ? $config['duracion_minutos'] : 90; // Valor por defecto: 90 minutos
}

/**
 * Verificar si se puede modificar la duración (solo si el hackathon no ha iniciado)
 */
function sePuedeModificarDuracion() {
    $config = obtenerConfiguracionHackathon();
    return !$config || !$config['hackathon_iniciado'];
}


/**
 * Formatear duración en minutos a texto legible
 */
function formatearDuracionLegible($minutos) {
    if ($minutos < 60) {
        return $minutos . " minutos";
    } else {
        $horas = floor($minutos / 60);
        $minutos_restantes = $minutos % 60;
        
        if ($minutos_restantes == 0) {
            return $horas . " hora" . ($horas > 1 ? "s" : "");
        } else {
            return $horas . " hora" . ($horas > 1 ? "s" : "") . " y " . $minutos_restantes . " minutos";
        }
    }
}

?>