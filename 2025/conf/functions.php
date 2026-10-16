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
 * Marcar desafío como completado y sumar puntos - VERSIÓN CORREGIDA
 */
function completarDesafio($equipo_id, $desafio_id, $puntos) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Registrar completado
        $stmt = $db->prepare("INSERT INTO desafios_completados (equipo_id, desafio_id) VALUES (?, ?)");
        if (!$stmt->execute([$equipo_id, $desafio_id])) {
            throw new Exception("Error al registrar desafío completado");
        }
        
        // Sumar puntos al equipo
        $stmt = $db->prepare("UPDATE equipos SET puntuacion_total = puntuacion_total + ? WHERE id = ?");
        if (!$stmt->execute([$puntos, $equipo_id])) {
            throw new Exception("Error al actualizar puntuación");
        }
        
        // Registrar tiempo acumulado
        registrarTiempoDesafioCompletado($equipo_id, $desafio_id);
        
        // Actualizar contador de desafíos completados
        actualizarDesafiosCompletados($equipo_id);
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error en completarDesafio: " . $e->getMessage());
        return false;
    }
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
 * Mostrar modal de error en lugar de alerta JavaScript
 */
function mostrarAlerta($mensaje, $tipo = 'error') {
    // En lugar de mostrar alert, guardamos el mensaje en sesión para mostrarlo en un modal
    $_SESSION['modal_message'] = $mensaje;
    $_SESSION['modal_type'] = $tipo;
    
    // Redirigir de vuelta al formulario
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/**
 * Normalizar texto para comparar banderas sin importar mayúsculas, espacios o formato.
 */
function normalizarTexto($texto) {
    $texto = trim((string) $texto);
    $texto = strtolower($texto);
    return preg_replace('/\s+/', '', $texto);
}

/**
 * Verificar bandera (para los desafíos)
 */
function verificarBandera($bandera_usuario, $bandera_correcta) {
    return normalizarTexto($bandera_usuario) === normalizarTexto($bandera_correcta);
}

/**
 * Obtener configuración de desafíos para 2025.
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
 * Marcar equipo como completado (cuando termina los 6 desafíos) - VERSIÓN CORREGIDA
 */
function marcarEquipoCompletado($equipo_id) {
    global $db;
    
    try {
        $tiempo_finalizacion = date('Y-m-d H:i:s');
        $stmt = $db->prepare("
            UPDATE equipos 
            SET completado = TRUE, 
                tiempo_finalizacion = ?,
                estado = 1,
                desafios_completados = 6
            WHERE id = ?
        ");
        return $stmt->execute([$tiempo_finalizacion, $equipo_id]);
        
    } catch (Exception $e) {
        error_log("Error en marcarEquipoCompletado: " . $e->getMessage());
        return false;
    }
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
 * Actualizar desafíos completados - VERSIÓN CORREGIDA
 */
function actualizarDesafiosCompletados($equipo_id) {
    global $db;
    
    try {
        // Obtener el conteo actual de desafíos completados
        $stmt = $db->prepare("
            SELECT COUNT(*) as total_completados 
            FROM desafios_completados 
            WHERE equipo_id = ?
        ");
        $stmt->execute([$equipo_id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_completados = $resultado['total_completados'];
        
        // Actualizar el contador en la tabla equipos
        $stmt = $db->prepare("
            UPDATE equipos 
            SET desafios_completados = ? 
            WHERE id = ?
        ");
        $stmt->execute([$total_completados, $equipo_id]);
        
        // Verificar si completó todos los desafíos (6) y aún no está marcado como completado
        if ($total_completados >= 6) {
            $stmt = $db->prepare("SELECT completado FROM equipos WHERE id = ?");
            $stmt->execute([$equipo_id]);
            $equipo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$equipo['completado']) {
                $tiempo_finalizacion = date('Y-m-d H:i:s');
                $stmt = $db->prepare("
                    UPDATE equipos 
                    SET completado = TRUE, 
                        tiempo_finalizacion = ?,
                        estado = 1
                    WHERE id = ?
                ");
                $stmt->execute([$tiempo_finalizacion, $equipo_id]);
            }
        }
        
        return $total_completados;
        
    } catch (Exception $e) {
        error_log("Error en actualizarDesafiosCompletados: " . $e->getMessage());
        return 0;
    }
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

/**
 * Obtener el estado actual del hackathon para sincronización
 */
function obtenerEstadoHackathon() {
    $config = obtenerConfiguracionHackathon();
    if (!$config) {
        return [
            'hackathon_iniciado' => false,
            'tiempo_inicio_global' => null,
            'duracion_minutos' => 90,
            'timestamp' => time()
        ];
    }
    
    return [
        'hackathon_iniciado' => (bool)$config['hackathon_iniciado'],
        'tiempo_inicio_global' => $config['tiempo_inicio_global'],
        'duracion_minutos' => $config['duracion_minutos'],
        'timestamp' => time(),
        'tiempo_restante' => calcularTiempoRestanteGlobal()
    ];
}



/**
 * Obtener desafíos completados por un equipo
 */
function obtenerDesafiosCompletados($equipo_id) {
    global $db;
    $stmt = $db->prepare("
        SELECT desafio_id, completado_en 
        FROM desafios_completados 
        WHERE equipo_id = ?
    ");
    $stmt->execute([$equipo_id]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $completados = [];
    foreach ($resultados as $row) {
        $completados[$row['desafio_id']] = true;
    }
    return $completados;
}





// ============================================================
// functions.php - Lógica de Validación para Hackathon
// ============================================================

/**
 * Valida la respuesta del usuario contra la flag correcta
 * 
 * @param string $user_answer La respuesta ingresada por el usuario
 * @return array Array con 'feedback' y 'class' para mostrar el resultado
 */
function validateHackathonAnswer($user_answer) {
    // Flag correcta (definida aquí y no expuesta al frontend)
    $correct_flag = "H4CK4TH0N_3P1C_D3CRYPT10N";
    
    // Normalizar para comparación (sin espacios, minúsculas)
    $normalized_user = strtolower(preg_replace('/\s+/', '', $user_answer));
    $normalized_correct = strtolower(preg_replace('/\s+/', '', $correct_flag));
    
    if ($normalized_user === $normalized_correct) {
        return [
            'feedback' => '✅ ¡ACCESO CONCEDIDO! Has descifrado el mensaje. FLAG{CRYPTO_MASTER} ✅',
            'class' => 'success'
        ];
    } else {
        return [
            'feedback' => '❌ ACCESO DENEGADO. Sigue intentando, el cifrado es largo pero no imposible. ❌',
            'class' => 'error'
        ];
    }
}

/**
 * Obtiene el texto encriptado (para mantenerlo en el backend)
 * 
 * @return string El texto encriptado
 */
function getEncryptedText() {
    return "Vm0wd2QyVkZOVWRXV0doVFYwZG9XRll3Wkc5WFJsbDNXa2M1VjFadGVGWlZNbmhQVmpKS1NHVkdiR0ZXVjJoeVZtcEJlRmRIVmtsaVJtUk9ZV3RhU1ZadGNFZFpWMDE0V2toV2FWSnRVbkJXTUZwSFRURmFkRTFVVWxSTmJFcElWbTAxUzJGc1NuVlJhemxXWWxob1YxcFZXbUZrUlRGVlZXeHdWMkpJUWxsV1ZFa3hWREZzVjFOdVVsWmlSa3BvVm1wT1UyRkdiSEZTYlVacVRWWmFlVmRyV2xOVWJGcFpVV3BXVjFJemFHaFhWbHBhWlZaT2NtRkdXbWxTTW1oWFZtMTBWMlF5VW5OVmJsSnNVakJhY1ZSV1pGTk5SbFowWlVoa1YwMXJWalpWVjNoelZqRmFSbUo2UWxwbGExcDZWbXBHVDJSV1VuTmhSMnhYVFcxb2RsWnRNWGRVTVVWNFVsaG9WbUpyTlZSV2EyUTBWV3hhVjFWWVpGQlZWREE1";
}








// ============================================================
// functions.php - Lógica del Desafío Login
// ============================================================

/**
 * Procesa el login del usuario
 * 
 * @param string $usuario Usuario ingresado
 * @param string $contrasena Contraseña ingresada
 * @return string Mensaje HTML con el resultado
 */
function procesarLogin($usuario, $contrasena) {
    // Credenciales válidas (visibles en el HTML)
    if ($usuario == "admin" && $contrasena == "passwordsegura") {
        return "<div class='alert alert-success mt-4'><strong>¡ACCESO CONCEDIDO!</strong> La bandera es: <code>FLAG{INSPECCIONA_EL_CODIGO}</code></div>";
    } else {
        return "<div class='alert alert-danger mt-4'>Error: Credenciales inválidas.</div>";
    }
}






// ============================================================
// functions.php - Funciones para el Desafío de Buffer Overflow
// ============================================================

/**
 * Procesa el input del usuario para el desafío de Buffer Overflow
 * 
 * @param string $input Datos ingresados por el usuario
 * @return array Resultado con mensaje y estado
 */
function procesarBufferOverflow($input) {
    $flag = "FLAG{BUFFER_OVERFLOW_EXPLOIT}";
    $buffer_size = 64;
    $input_length = strlen($input);
    $mensaje = "";
    $show_input = true;
    
    if ($input_length > $buffer_size) {
        // Se ha desbordado! Verificar si contiene el "payload" para ejecutar flag_secreta
        $overflow_bytes = $input_length - $buffer_size;
        
        // Buscar la dirección simulada de flag_secreta en los bytes extra
        $flag_hex = "f1e2d3c4";
        $flag_pattern = "FLAG_SECRETA";
        
        if (strpos($input, $flag_pattern) !== false || substr($input, -8) === $flag_hex) {
            $mensaje = '<div class="alert alert-success">🎉 ¡EXPLOIT EXITOSO! Has sobrescrito el registro de retorno.<br>
                        <strong>' . $flag . '</strong></div>';
            $show_input = false;
        } else {
            $mensaje = '<div class="alert alert-warning">⚠️ Desbordamiento detectado! Se han escrito ' . $overflow_bytes . 
                       ' bytes extra. Pero no lograste ejecutar flag_secreta(). Sigue intentando.</div>';
        }
    } else {
        $mensaje = '<div class="alert alert-info">📝 Datos ingresados (' . $input_length . '/' . $buffer_size . 
                   ' bytes): ' . htmlspecialchars($input) . '<br>El programa terminó normalmente. No hubo overflow.</div>';
    }
    
    return [
        'mensaje' => $mensaje,
        'show_input' => $show_input
    ];
}






// ============================================================
// functions.php - Añadir función para Esteganografía
// ============================================================


/**
 * Verifica el mensaje descifrado de esteganografía
 * 
 * @param string $mensaje El mensaje ingresado por el usuario
 * @return array Resultado con éxito y mensaje
 */
function verificarEsteganografia($mensaje) {
    // El mensaje correcto (SOLO en el backend)
    $mensaje_correcto = "el ataque sera al amanecer";
    $mensaje_correcto2 = "elataqueseraalamanecer";
    $mensaje_correcto3 = "el ataque será al amanecer";
    
    // Limpiar entrada
    $mensaje_limpio = strtolower(trim($mensaje));
    
    // Verificar si coincide con alguno de los mensajes correctos
    if ($mensaje_limpio === $mensaje_correcto || 
        $mensaje_limpio === $mensaje_correcto2 || 
        $mensaje_limpio === $mensaje_correcto3) {
        return [
            'exito' => true,
            'mensaje' => '🎉 <strong>ACCESO CONCEDIDO</strong> 🎉<br><br>' .
                        '<span style="color:#ff0;">🏆 FLAG{STEGANOGRAPHY_SECRET} 🏆</span><br><br>' .
                        'Has completado la misión. Reporta este código a tu superior.'
        ];
    } else {
        return [
            'exito' => false,
            'mensaje' => '❌ <strong>ACCESO DENEGADO</strong> ❌<br><br>' .
                        'Mensaje incorrecto. Revisa la imagen con herramientas de esteganografía.'
        ];
    }
}




// ============================================================
// functions.php - Funciones del Desafío de Patrón Biométrico con Sistema de Bloqueo
// ============================================================


/**
 * Verifica el patrón biométrico ingresado con sistema de bloqueo
 */
function verificarPatronBiometrico($patron) {
    $patron_correcto = "5-2-1-4-5-6-9-8-5";
    $patron_limpio = trim($patron);
    
    // Inicializar contador si no existe
    if (!isset($_SESSION['intentos_biometricos'])) {
        $_SESSION['intentos_biometricos'] = 0;
    }
    
    // Verificar bloqueo
    if (isset($_SESSION['bloqueado_hasta']) && time() < $_SESSION['bloqueado_hasta']) {
        $tiempo_restante = $_SESSION['bloqueado_hasta'] - time();
        return [
            'exito' => false,
            'bloqueado' => true,
            'tiempo_restante' => $tiempo_restante,
            'mensaje' => '⏰ SISTEMA BLOQUEADO. Espera ' . $tiempo_restante . ' segundos.'
        ];
    }
    
    // Si expiró el bloqueo
    if (isset($_SESSION['bloqueado_hasta']) && time() >= $_SESSION['bloqueado_hasta']) {
        unset($_SESSION['bloqueado_hasta']);
        $_SESSION['intentos_biometricos'] = 0;
    }
    
    // Verificar patrón
    if ($patron_limpio === $patron_correcto) {
        $_SESSION['intentos_biometricos'] = 0;
        return [
            'exito' => true,
            'bloqueado' => false,
            'mensaje' => '🎉 <strong>¡ACCESO BIOMÉTRICO CONCEDIDO!</strong> 🎉<br><br>' .
                        '<span style="color:#ff0; font-size:1.3rem;">🏆 FLAG{BIOMETRIC_PATTERN_MASTER} 🏆</span>'
        ];
    } else {
        $_SESSION['intentos_biometricos']++;
        
        if ($_SESSION['intentos_biometricos'] >= 3) {
            $_SESSION['bloqueado_hasta'] = time() + 60;
            $_SESSION['intentos_biometricos'] = 0;
            return [
                'exito' => false,
                'bloqueado' => true,
                'tiempo_restante' => 60,
                'mensaje' => '⚠️ DEMASIADOS INTENTOS.<br>🔒 Sistema bloqueado 60 segundos.'
            ];
        }
        
        return [
            'exito' => false,
            'bloqueado' => false,
            'intentos' => $_SESSION['intentos_biometricos'],
            'mensaje' => '❌ Patrón incorrecto. Intentos: ' . $_SESSION['intentos_biometricos'] . '/3'
        ];
    }
}

/**
 * Obtiene el estado actual del bloqueo biométrico
 */
function obtenerEstadoBiometrico() {
    $estado = [
        'intentos' => isset($_SESSION['intentos_biometricos']) ? $_SESSION['intentos_biometricos'] : 0,
        'bloqueado' => false,
        'tiempo_restante' => 0
    ];
    
    if (isset($_SESSION['bloqueado_hasta']) && time() < $_SESSION['bloqueado_hasta']) {
        $estado['bloqueado'] = true;
        $estado['tiempo_restante'] = $_SESSION['bloqueado_hasta'] - time();
    }
    
    return $estado;
}























?>