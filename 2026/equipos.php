<?php
// Activar mostrar errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/conf/functions.php';
_d();

// Verificar autenticación administrativa
if (!isset($_SESSION['admin_autenticado']) || $_SESSION['admin_autenticado'] !== true) {
    header("Location: index.php");
    exit;
}

// Verificar si es administrador
$es_admin = true;

// Inicializar variables
$mensaje_exito = '';
$mensaje_error = '';

// Procesar inicio del hackathon
if ($es_admin && isset($_POST['iniciar_hackathon'])) {
    try {
        if (iniciarHackathonGlobal()) {
            $mensaje_exito = "¡Hackathon iniciado! Tiempo: " . formatearDuracionLegible(obtenerDuracionHackathon());
            $_SESSION['hackathon_just_started'] = true;
            
            // Iniciar tiempo para todos los equipos existentes que ya tienen miembros
            $equipos = obtenerRankingEquipos();
            foreach ($equipos as $equipo) {
                $miembros = contarMiembrosEquipo($equipo['id']);
                if ($miembros > 0) {
                    // Marcar que estos equipos empezaron desde el inicio
                    global $db;
                    $stmt = $db->prepare("UPDATE equipos SET tiempo_inicio = ?, inicio_tardio = FALSE, estado = 1 WHERE id = ?");
                    $stmt->execute([date('Y-m-d H:i:s'), $equipo['id']]);
                }
            }
        } else {
            $mensaje_error = "Error al iniciar el hackathon";
        }
    } catch (Exception $e) {
        $mensaje_error = "Error: " . $e->getMessage();
    }
}

// Procesar reinicio (para testing)
if ($es_admin && isset($_POST['reiniciar_hackathon'])) {
    try {
        if (reiniciarHackathon()) {
            $mensaje_exito = "Hackathon reiniciado para testing";
            // Resetear variables de sesión para sonidos
            unset($_SESSION['banderas_reproducidas']);
            unset($_SESSION['max_puntuacion_global']);
        } else {
            $mensaje_error = "Error al reiniciar el hackathon";
        }
    } catch (Exception $e) {
        $mensaje_error = "Error: " . $e->getMessage();
    }
}

// Procesar eliminación de equipo
if ($es_admin && isset($_POST['eliminar_equipo'])) {
    try {
        $equipo_id = $_POST['equipo_id'];
        if (eliminarEquipo($equipo_id)) {
            $mensaje_exito = "Equipo eliminado exitosamente";
        } else {
            $mensaje_error = "Error al eliminar el equipo";
        }
    } catch (Exception $e) {
        $mensaje_error = "Error: " . $e->getMessage();
    }
}

// Procesar actualización de duración
if ($es_admin && isset($_POST['actualizar_duracion'])) {
    try {
        $nueva_duracion = intval($_POST['duracion_minutos']);
        
        if ($nueva_duracion > 0 && $nueva_duracion <= 480) {
            if (actualizarDuracionHackathon($nueva_duracion)) {
                $mensaje_exito = "Tiempo del Hackathon actualizado exitosamente a " . $nueva_duracion . " minutos (" . formatearDuracionLegible($nueva_duracion) . ").";
            } else {
                $mensaje_error = "Error al actualizar la duración del Hackathon.";
            }
        } else {
            $mensaje_error = "La duración debe estar entre 1 y 480 minutos.";
        }
    } catch (Exception $e) {
        $mensaje_error = "Error: " . $e->getMessage();
    }
}

// Obtener datos con manejo de errores
try {
    $ranking = obtenerRankingEquiposConTiempo();
    $config_hackathon = obtenerConfiguracionHackathon();
    $hackathon_activo = hackathonEstaActivo();
    $tiempo_restante = calcularTiempoRestanteGlobal();
    $duracion_actual = obtenerDuracionHackathon();
    
} catch (Exception $e) {
    // Si hay error al obtener datos, mostrar página básica
    $ranking = [];
    $config_hackathon = null;
    $hackathon_activo = false;
    $tiempo_restante = 0;
    $duracion_actual = 90;
    $mensaje_error = "Error al cargar datos: " . $e->getMessage();
}

// Inicializar el último ID conocido en la sesión
if (!isset($_SESSION['ultimo_equipo_id'])) {
    // Establecer el último ID como el ID más alto actual
    $ultimo_id = 0;
    if (!empty($ranking)) {
        $ultimo_id = max(array_column($ranking, 'id'));
    }
    $_SESSION['ultimo_equipo_id'] = $ultimo_id;
}

// Inicializar timestamp de verificación de puntuaciones
if (!isset($_SESSION['ultima_verificacion_puntuaciones'])) {
    $_SESSION['ultima_verificacion_puntuaciones'] = date('Y-m-d H:i:s');
}

// Inicializar timestamp de verificación de tiempos
if (!isset($_SESSION['ultima_verificacion_tiempo'])) {
    $_SESSION['ultima_verificacion_tiempo'] = date('Y-m-d H:i:s');
}
// ── Cabecera modular ─────────────────────────────────────────────────────────
$page_title = 'Ranking de Equipos — Hackathon UPTPC 2026';
require_once __DIR__ . '/conf/header.php';
echo $header;
?>
<!-- Estilos específicos del Panel de Equipos -->
<style>
        .top-1 { background-color: #FFD700 !important; }
        .top-2 { background-color: #C0C0C0 !important; }
        .top-3 { background-color: #CD7F32 !important; }
        .status-badge { font-size: 0.7rem; }
        .admin-panel { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .table-hover tbody tr:hover { background-color: rgba(0, 0, 0, 0.075); }
        .error-panel { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; }
        .btn-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; }
        .btn-warning { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); border: none; }
        .btn-danger { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border: none; }
        .btn-info { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); border: none; }
        .badge-espera { background-color: #6c757d; }
        .badge-compitiendo { background-color: #198754; }
        .actions-column { width: 120px; }
        
        /* TEMPORIZADOR MÁS GRANDE */
        .temporizador-grande {
            font-size: 4rem !important;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            padding: 10px 20px;
            border-radius: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: inline-block;
            margin: 10px 0;
        }
        
        /* Estados del temporizador */
        .temporizador-normal { color: #28a745; }
        .temporizador-advertencia { color: #ffc107; animation: pulse 1s infinite; }
        .temporizador-peligro { color: #dc3545; animation: pulse 0.5s infinite; }
        
        /* Efectos para nuevos equipos */
        .equipo-nuevo {
            animation: highlight 2s ease-in-out;
            background-color: #d4edda !important;
        }
        
        .badge-nuevo {
            background-color: #17a2b8;
            animation: blink 1s infinite;
        }
        
        /* Notificación flotante */
        .notificacion-flotante {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.5s ease-out;
        }
        
        /* Estilos para equipos ganadores */
        .primer-lugar-tabla {
            border: 3px solid #FFD700 !important;
            background: linear-gradient(135deg, #FFF9C4 0%, #FFEB3B 100%) !important;
            animation: pulse 2s infinite;
        }

        .segundo-lugar-tabla {
            border: 3px solid #C0C0C0 !important;
            background: linear-gradient(135deg, #F5F5F5 0%, #E0E0E0 100%) !important;
            animation: pulse 2s infinite;
        }

        .tercer-lugar-tabla {
            border: 3px solid #CD7F32 !important;
            background: linear-gradient(135deg, #FFE0B2 0%, #FFB74D 100%) !important;
            animation: pulse 2s infinite;
        }

        .ganador-parcial-tabla {
            border: 3px solid #28a745 !important;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
        }

        .empate-tabla {
            border: 3px solid #ffc107 !important;
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%) !important;
        }

        .equipo-completo {
    border: 2px solid #28a745 !important;
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
    animation: pulse-completo 2s infinite;
    position: relative;
    /* NUEVAS PROPIEDADES PARA EVITAR CORTE */
    overflow: visible !important;
    z-index: 10;
    margin: 2px 0;
    box-shadow: 0 0 20px rgba(40, 167, 69, 0.3);
    transform-origin: center;
}

.equipo-completo::after {
    content: "✅ COMPLETO";
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.8rem;
    background: #28a745;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    z-index: 11;
    /* Asegurar que el badge no se corte */
    white-space: nowrap;
}

/* ANIMACIÓN MEJORADA QUE NO SE CORTA */
@keyframes pulse-completo {
    0%, 100% { 
        transform: scale(1);
        box-shadow: 0 0 10px rgba(40, 167, 69, 0.3);
    }
    50% { 
        transform: scale(1.02);
        box-shadow: 0 0 25px rgba(40, 167, 69, 0.6);
    }
}

/* Asegurar que la tabla permita el overflow */
.table-responsive {
    overflow: visible !important;
}

.table {
    overflow: visible !important;
}

/* Asegurar que las filas tengan espacio para la animación */
.table tbody tr {
    position: relative;
    overflow: visible !important;
}

/* Contenedor de la tabla con espacio para animaciones */
.table-container {
    overflow: visible !important;
    position: relative;
}

        /* Podio items */
        .podio-item {
            padding: 20px;
            border-radius: 15px;
            margin: 10px 0;
        }

        .primer-lugar {
            background: rgba(255, 215, 0, 0.2);
            border: 3px solid #FFD700;
        }

        .segundo-lugar {
            background: rgba(192, 192, 192, 0.2);
            border: 3px solid #C0C0C0;
        }

        .tercer-lugar {
            background: rgba(205, 127, 50, 0.2);
            border: 3px solid #CD7F32;
        }

        /* Animaciones para cambios de puntuación */
        .puntuacion-cambiando {
            animation: pulse 0.5s ease-in-out 3;
        }
        
        @keyframes highlight-change {
            0% { background-color: #fff3cd; }
            100% { background-color: transparent; }
        }
        
        .fila-actualizada {
            animation: highlight-change 2s ease-in-out;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-30px);}
            60% {transform: translateY(-15px);}
        }
        @keyframes pulse {
            0%, 100% {transform: scale(1);}
            50% {transform: scale(1.1);}
        }
        @keyframes highlight {
            0% { background-color: #d4edda; }
            100% { background-color: transparent; }
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #f00;
            animation: fall linear forwards;
        }
        @keyframes fall {
            to {transform: translateY(100vh);}
        }
        .modal-danger .modal-content {
            border: 3px solid #dc3545;
        }
        
        /* Efectos para tiempo cambiando */
        .tiempo-cambiando {
            animation: pulse 0.5s ease-in-out 3;
            background-color: #e3f2fd !important;
        }

        @keyframes highlight-time {
            0% { background-color: #e3f2fd; }
            100% { background-color: transparent; }
        }

        .fila-tiempo-actualizado {
            animation: highlight-time 2s ease-in-out;
        }
        
        /* Estilos para el panel de configuración de duración */
        .config-duracion-panel {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            border: 2px solid #138496;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .config-duracion-panel .btn-close-white {
            filter: invert(1);
        }

        .config-duracion-panel .form-control {
            border: 1px solid #ced4da;
        }

        .config-duracion-panel .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
        }

        .btn-toggle-config {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-toggle-config:hover {
            background: linear-gradient(135deg, #5a6268 0%, #3d4348 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }


/* Busca esta sección en los estilos y agrega: */
.badge-espera { background-color: #6c757d; }
.badge-compitiendo { background-color: #198754; }
.badge-finalizado { background-color: #0d6efd; }

/* Agrega estos nuevos estilos: */
.equipo-finalizado {
    border-left: 4px solid #0d6efd !important;
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%) !important;
}

.equipo-completo {
    border: 2px solid #28a745 !important;
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
    animation: pulse 2s infinite;
    position: relative;
}

.equipo-completo::after {
    content: "✅ COMPLETO";
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.8rem;
    background: #28a745;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
}



</style>

<?php if (isset($_SESSION['hackathon_just_started']) && $_SESSION['hackathon_just_started']): ?>
    <script>window.hackathonJustStarted = true;</script>
    <?php unset($_SESSION['hackathon_just_started']); ?>
<?php endif; ?>
<div class="container mt-4">
    <div class="text-center mb-3">
        <img src="../img/img.png" alt="Logo Hackathon" style="max-width:800px;">
        <h1>Hackathon UPTPC</h1>
    </div>

    <!-- Console Widget de Narrativa IA (TTS Español Latino) -->
    <div class="card mb-4 shadow-sm border-info" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #e2e8f0; border-radius: 15px;">
        <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3" style="flex: 1; min-width: 300px;">
                <div class="fs-2 text-info animate-pulse">🤖</div>
                <div style="flex: 1;">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <strong style="color: #38bdf8; font-size: 0.95rem;">NARRATIVA DE IA EN TIEMPO REAL (ESPAÑOL LATINO)</strong>
                        <span id="badgeEstadoIA" class="badge bg-success" style="font-size: 0.7rem;">VOZ DE IA ACTIVA</span>
                    </div>
                    <div id="logTextoIA" class="p-2 rounded" style="background: rgba(0,0,0,0.6); font-family: monospace; font-size: 0.88rem; color: #4ade80; border: 1px solid rgba(56, 189, 248, 0.2); min-height: 40px; display: flex; align-items: center;">
                        🤖 Sistema inicializado. La Inteligencia Artificial observará el avance de cada equipo en el Hackathon...
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-info" onclick="probarVozIA()">
                    🔊 Probar Voz IA
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleSilencioIA()" id="btnToggleAudioIA">
                    🔊 Voz Activada
                </button>
            </div>
        </div>
    </div>

    <!-- Panel de Administración -->
    <?php if ($es_admin): ?>
    <div class="card admin-panel mb-4">
        <div class="card-body">
            <h3 class="card-title">🎯 Panel de Control del Administrador</h3>
            
            <!-- Estado actual -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5>Estado del Hackathon: 
                        <?php if ($hackathon_activo): ?>
                            <span class="badge bg-success">EN CURSO</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">NO INICIADO</span>
                        <?php endif; ?>
                    </h5>
                    <?php if ($hackathon_activo): ?>
                        <!-- TEMPORIZADOR MÁS GRANDE -->
                        <div class="text-center mb-3">
                            <p class="mb-1">⏰ Tiempo restante:</p>
                            <div id="tiempo-global" class="temporizador-grande temporizador-normal">
                                <?php 
                                $minutos = floor($tiempo_restante / 60);
                                $segundos = $tiempo_restante % 60;
                                echo sprintf("%02d:%02d", $minutos, $segundos);
                                ?>
                            </div>
                            
                        </div>
                       
                    <?php else: ?>
                        <p class="mb-1">⏳ Duración: <strong><?php echo formatearDuracionLegible($duracion_actual); ?></strong></p>
                        <p class="mb-0">👥 Equipos registrados: <strong id="total-equipos"><?php echo count($ranking); ?></strong></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <!-- Botones de control -->
                    <div class="d-flex flex-column gap-3">
                        <!-- Botón Iniciar Hackathon -->
                        <?php if (!$hackathon_activo): ?>
                            <button type="button" class="btn btn-success btn-lg w-100 py-3" data-bs-toggle="modal" data-bs-target="#iniciarModal">
                                🚀 INICIAR HACKATHON
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-success btn-lg w-100 py-3" disabled>
                                ✅ HACKATHON EN CURSO
                            </button>
                        <?php endif; ?>
                        
                        <!-- Botón Cambiar Tiempo del Hackathon -->
                        <button type="button" class="btn btn-info text-white btn-lg w-100 py-3 fw-bold" data-bs-toggle="modal" data-bs-target="#cambiarTiempoModal">
                            ⏱️ CAMBIAR TIEMPO DEL HACKATHON
                        </button>

                        <!-- Botón Reiniciar Hackathon -->
                        <button type="button" class="btn btn-warning btn-lg w-100 py-3" data-bs-toggle="modal" data-bs-target="#reiniciarModal">
                            🔄 REINICIAR HACKATHON (TESTING)
                        </button>
                        
                        <!-- Botón Cerrar Sesión Admin / Salir al Login -->
                        <a href="logout.php" class="btn btn-outline-danger btn-lg w-100 py-2">
                            🚪 Cerrar Sesión / Salir al Login
                        </a>
                    </div>
                    
                </div>
            </div>
            
            <?php if ($hackathon_activo): ?>
            
            <?php else: ?>

            <?php endif; ?>
        </div>
    </div>

    

    <!-- Panel de Configuración de Duración (Oculto por defecto) -->
    <div class="row mt-3" id="panelConfiguracion" style="display: none;">
        <div class="col-md-12">
            <div class="card config-duracion-panel">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">⏱️ Configurar Duración del Hackathon</h5>
                    <button type="button" class="btn-close btn-close-white" id="btnCerrarConfiguracion"></button>
                </div>
                <div class="card-body">
                    <?php if (sePuedeModificarDuracion()): ?>
                        <form method="post" class="row g-3 align-items-center">
                            <div class="col-auto">
                                <label for="duracion_minutos" class="form-label"><strong>Duración actual:</strong></label>
                            </div>
                            <div class="col-auto">
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="duracion_minutos" 
                                           name="duracion_minutos" 
                                           value="<?php echo $duracion_actual; ?>" 
                                           min="1" 
                                           max="480" 
                                           required
                                           style="width: 120px;">
                                    <span class="input-group-text">minutos</span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" name="actualizar_duracion" class="btn btn-light">
                                    💾 Actualizar Duración
                                </button>
                            </div>
                            <div class="col-12">
                                <small>
                                    <strong>Nota:</strong> Solo puedes modificar la duración antes de iniciar el hackathon. 
                                    Rango permitido: 1-480 minutos (1-8 horas).
                                    <br>
                                    <strong>Duración actual:</strong> <?php echo $duracion_actual; ?> minutos (<?php echo formatearDuracionLegible($duracion_actual); ?>)
                                </small>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <strong>⏰ Duración actual:</strong> <?php echo $duracion_actual; ?> minutos (<?php echo formatearDuracionLegible($duracion_actual); ?>)
                            <br>
                            <small>La duración no se puede modificar mientras el hackathon esté en curso.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Mensajes de éxito/error -->
    <?php if ($mensaje_exito): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
            <span class="fs-4 me-2">✅</span>
            <div>
                <h5 class="mb-1">¡Éxito!</h5>
                <p class="mb-0"><?php echo $mensaje_exito; ?></p>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($mensaje_error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
            <span class="fs-4 me-2">❌</span>
            <div>
                <h5 class="mb-1">Error</h5>
                <p class="mb-0"><?php echo $mensaje_error; ?></p>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Ranking de Equipos -->
    <h2 class="text-center mb-4">🏆 Ranking de Equipos</h2>
    
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th width="8%">Posición</th>
                            <th width="25%">Nombre del Equipo</th>
                            <th width="12%">Código</th>
                            <th width="12%">Puntuación</th>
                            <th width="15%">Tiempo</th>
                            <th width="18%">Estado</th>
                            <th width="10%" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-equipos">
                        <?php if (!empty($ranking)): ?>
                            <?php foreach ($ranking as $index => $equipo): ?>
                            <tr class="<?php 
                                if ($index == 0) { echo 'top-1'; } 
                                elseif ($index == 1) { echo 'top-2'; } 
                                elseif ($index == 2) { echo 'top-3'; } 
                                else { echo ''; } 
                            ?>" data-equipo-id="<?php echo $equipo['id']; ?>">
                                <td>
                                    <strong class="fs-5"><?php echo $index + 1; ?>°</strong>
                                    <?php if ($index < 3): ?>
                                        <br>
                                        <span class="badge bg-<?php echo $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'danger'); ?> mt-1">
                                            <?php echo $index == 0 ? '🥇 ORO' : ($index == 1 ? '🥈 PLATA' : '🥉 BRONCE'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($equipo['nombre_equipo']); ?></strong>
                                    <?php if ($equipo['inicio_tardio']): ?>
                                        <br>
                                        <span class="badge bg-info status-badge mt-1" title="Equipo se unió después del inicio">TARDÍO</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code class="fs-5"><?php echo htmlspecialchars($equipo['codigo_equipo']); ?></code>
                                </td>
                                <td>
                                    <strong class="fs-4 text-primary"><?php echo $equipo['puntuacion_total']; ?></strong>
                                    <small class="text-muted">🚩</small>
                                </td>
                                <td>
                                    <?php if ($equipo['tiempo_acumulado'] > 0): ?>
                                        <?php
                                        $minutos = floor($equipo['tiempo_acumulado'] / 60);
                                        $segundos = $equipo['tiempo_acumulado'] % 60;
                                        echo sprintf("%02d:%02d", $minutos, $segundos);
                                        ?>
                                        <?php if ($equipo['completado']): ?>
                                            <br><small class="text-success">✅ Completado</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">--:--</span>
                                    <?php endif; ?>
                                </td>
                                <td>
    <?php 
    $estado_mostrar = '';
    $clase_badge = '';
    $texto_extra = '';
    $total_desafios_sys = obtenerTotalDesafios();
    
    if ($equipo['puntuacion_total'] >= $total_desafios_sys || $equipo['completado']) {
        $estado_mostrar = '✅ FINALIZADO';
        $clase_badge = 'badge bg-success p-2';
        $texto_extra = '<br><small class="text-success mt-1 d-block">🎯 ' . $equipo['puntuacion_total'] . '/' . $total_desafios_sys . ' Desafíos</small>';
    } elseif ($equipo['estado'] == 1) {
        $estado_mostrar = '🏁 COMPITIENDO';
        $clase_badge = 'badge badge-compitiendo p-2';
    } else {
        $estado_mostrar = '⏳ EN ESPERA';
        $clase_badge = 'badge badge-espera p-2';
    }
    ?>
    <span class="<?php echo $clase_badge; ?>"><?php echo $estado_mostrar; ?></span>
    <?php echo $texto_extra; ?>
</td>
                                <td class="text-center actions-column">
                                    <!-- Botón Eliminar -->
                                    <button type="button" class="btn btn-danger btn-sm btn-eliminar-equipo" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#eliminarModal"
                                            data-equipo-id="<?php echo $equipo['id']; ?>"
                                            data-equipo-nombre="<?php echo htmlspecialchars($equipo['nombre_equipo']); ?>"
                                            title="Eliminar equipo">
                                        🗑️ Eliminar
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="alert alert-info">
                                        <h4>📋 No hay equipos registrados aún</h4>
                                        <p class="mb-3">¡Sé el primero en crear un equipo y participar en el hackathon!</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    
</div>

<!-- Modal Cambiar Tiempo del Hackathon -->
<div class="modal fade" id="cambiarTiempoModal" tabindex="-1" aria-labelledby="cambiarTiempoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-dark">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="cambiarTiempoModalLabel">⏱️ Cambiar Tiempo del Hackathon</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body text-start">
                    <p class="mb-3">Selecciona o ingresa la nueva duración total del Hackathon en minutos:</p>
                    
                    <div class="mb-4">
                        <label for="duracion_minutos_modal" class="form-label fw-bold">Duración en Minutos:</label>
                        <div class="input-group input-group-lg">
                            <input type="number" class="form-control" id="duracion_minutos_modal" name="duracion_minutos" value="<?php echo $duracion_actual; ?>" min="1" max="480" required>
                            <span class="input-group-text">minutos</span>
                        </div>
                        <div class="form-text mt-2">Duración actual: <strong><?php echo $duracion_actual; ?> minutos</strong> (<?php echo formatearDuracionLegible($duracion_actual); ?>)</div>
                    </div>

                    <p class="mb-2 fw-bold text-secondary">Atajos rápidos:</p>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('duracion_minutos_modal').value=30">30 min</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('duracion_minutos_modal').value=45">45 min</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('duracion_minutos_modal').value=60">60 min (1h)</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('duracion_minutos_modal').value=90">90 min (1.5h)</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('duracion_minutos_modal').value=120">120 min (2h)</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('duracion_minutos_modal').value=180">180 min (3h)</button>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="actualizar_duracion" class="btn btn-info text-white fw-bold">💾 Guardar Nuevo Tiempo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Iniciar Hackathon -->
<div class="modal fade" id="iniciarModal" tabindex="-1" aria-labelledby="iniciarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="iniciarModalLabel">🚀 Iniciar Hackathon</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de iniciar el hackathon?</p>
                <div class="alert alert-info">
                    <strong>📅 Duración:</strong> <?php echo formatearDuracionLegible($duracion_actual); ?><br>
                    <strong>✅ Equipos que comenzarán:</strong> <?php echo count($ranking); ?><br>
                    <strong>🎯 Desafíos:</strong> <?php echo obtenerTotalDesafios(); ?> desafíos de seguridad
                </div>
                <p class="text-danger"><strong>⚠️ Esta acción no se puede deshacer</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="post" class="d-inline">
                    <button type="submit" name="iniciar_hackathon" class="btn btn-success">🚀 Iniciar Hackathon</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Reiniciar Hackathon -->
<div class="modal fade" id="reiniciarModal" tabindex="-1" aria-labelledby="reiniciarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content modal-danger">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="reiniciarModalLabel">🔄 Reiniciar Hackathon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="fw-bold">¿REINICIAR TODO EL HACKATHON?</p>
                <div class="alert alert-danger">
                    <strong>🔴 Esto borrará:</strong><br>
                    • Todas las puntuaciones<br>
                    • Desafíos completados<br>
                    • Tiempos de equipos<br>
                    • Estado del hackathon
                </div>
                <p class="text-warning"><strong>🎯 SOLO PARA TESTING</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="post" class="d-inline">
                    <button type="submit" name="reiniciar_hackathon" class="btn btn-warning">🔄 Reiniciar Hackathon</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Eliminar Equipo -->
<div class="modal fade" id="eliminarModal" tabindex="-1" aria-labelledby="eliminarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content modal-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="eliminarModalLabel">🗑️ Eliminar Equipo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="fw-bold">¿ESTÁS SEGURO DE ELIMINAR EL EQUIPO?</p>
                <div class="alert alert-danger">
                    <strong id="equipoNombreEliminar"></strong><br><br>
                    <strong>❌ Esta acción eliminará:</strong><br>
                    • Todos los miembros del equipo<br>
                    • Puntuaciones y progreso<br>
                    • Desafíos completados
                </div>
                <p class="text-danger"><strong>🚫 Esta acción NO se puede deshacer</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="post" id="formEliminarEquipo" class="d-inline">
                    <input type="hidden" name="equipo_id" id="equipoIdEliminar">
                    <button type="submit" name="eliminar_equipo" class="btn btn-danger">🗑️ Eliminar Equipo</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Podio Completo (Cuando equipos completan los 6 desafíos) -->
<div class="modal fade" id="podioCompletoModal" tabindex="-1" aria-labelledby="podioCompletoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background: linear-gradient(135deg, #01b7ffff 0%, #0075d4ff 50%, #005ac2ff 100%);">
            <div class="modal-header border-0">
                <h2 class="modal-title text-center w-100 text-white" id="podioCompletoModalLabel">
                    🏆 PODIO OFICIAL 🏆
                </h2>
            </div>
            <div class="modal-body text-center">
                <div class="fs-1 mb-3">🎉 ¡FELICITACIONES! 🎉</div>
                <h4 class="text-white mb-4">Equipos que completaron los 10 desafíos</h4>
                <div id="podioList">
                    <!-- Se llena dinámicamente -->
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-light btn-lg" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ganador por Tiempo (Cuando se acaba el tiempo y nadie completó todo) -->
<div class="modal fade" id="ganadorTiempoModal" tabindex="-1" aria-labelledby="ganadorTiempoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
            <div class="modal-header border-0">
                <h2 class="modal-title text-center w-100">⏰ TIEMPO AGOTADO</h2>
            </div>
            <div class="modal-body text-center">
                <div class="fs-1 mb-3">🎯</div>
                <h3>GANADOR POR PUNTUACIÓN</h3>
                <h2 id="ganadorTiempoNombre" class="fw-bold"></h2>
                <h4 class="text-warning" id="ganadorTiempoPuntos"></h4>
                <p class="mt-3">Mayor puntuación obtenida</p>
                <div class="alert alert-warning text-dark mt-3">
                    <strong>🏆 ¡Felicidades al equipo ganador!</strong>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-light btn-lg" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Empate por Tiempo (Cuando se acaba el tiempo y hay empate) -->
<div class="modal fade" id="empateTiempoModal" tabindex="-1" aria-labelledby="empateTiempoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); color: white;">
            <div class="modal-header border-0">
                <h2 class="modal-title text-center w-100">⏰ TIEMPO AGOTADO</h2>
            </div>
            <div class="modal-body text-center">
                <div class="fs-1 mb-3">⚖️</div>
                <h3>EMPATE EN PUNTUACIÓN</h3>
                <h4>Múltiples equipos con <span class="text-dark" id="puntuacionEmpateTiempo"></span></h4>
                <div id="listaEmpateTiempo" class="my-4">
                    <!-- Se llena dinámicamente -->
                </div>
                <div class="mt-4">
                    <button type="button" class="btn btn-danger btn-lg" id="btnIniciarDesempateTiempo">
                        🏆 INICIAR DESEMPATE
                    </button>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-light btn-lg" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Todos Fallaron -->
<div class="modal fade" id="todosFallaronModal" tabindex="-1" aria-labelledby="todosFallaronModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: white;">
            <div class="modal-header border-0">
                <h2 class="modal-title text-center w-100">😔 RESULTADOS</h2>
            </div>
            <div class="modal-body text-center">
                <div class="fs-1 mb-3">💀</div>
                <h3>NINGÚN EQUIPO</h3>
                <h2 class="text-warning">LOGRO PUNTUAR</h2>
                <p class="mt-3">Los desafíos fueron muy desafiantes esta vez</p>
                <div class="alert alert-info mt-3">
                    <strong>🏆 Mejor suerte para la próxima</strong>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-light btn-lg" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Audio para el sonido de finalización -->
<audio id="finishSound" preload="auto">
    <source src="audios/ganador.mp3" type="audio/mpeg">
</audio>

<!-- Audio para victoria -->
<audio id="audioVictoria" preload="auto">
    <source src="audios/ganador.mp3" type="audio/mpeg">
</audio>

<!-- Audios para bandera 1 (en orden) -->
<audio id="audioBandera1_1" preload="auto">
    <source src="audios/estamos_siendo_atacados.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera1_2" preload="auto">
    <source src="audios/nos_han_encontrado_una_vulnerabilidad.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera1_3" preload="auto">
    <source src="audios/una_defensa_a_sido_comprometida.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera1_4" preload="auto">
    <source src="audios/pero_que_esta_pasando?_varias_personas_han_logrado_pasar_nuestra_primera_defensa.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera1_5" preload="auto">
    <source src="audios/alguien_a_tumbado_nuestra_primera_defensa.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera1_6" preload="auto">
    <source src="audios/todos_los_atacantes_lograron_una_defensa,_debemos_subir_el_nivel.mp3" type="audio/mpeg">
</audio>

<!-- Audios para bandera 2 (en orden) -->
<audio id="audioBandera2_1" preload="auto">
    <source src="audios/nuestras_defensas_estan_cayendo.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera2_2" preload="auto">
    <source src="audios/lograron_pasar_la_segunda_defensa.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera2_3" preload="auto">
    <source src="audios/nuestra_información_a_sido_revelada_con_la_caida_de_la_segunda_bandera.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera2_4" preload="auto">
    <source src="audios/un_equipo_a_logrado_vulnerar_la_segunda_bandera.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera2_5" preload="auto">
    <source src="audios/lograron_pasar_la_segunda_defensa.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera2_6" preload="auto">
    <source src="audios/todos_los_usuarios_han_pasado_nuestra_segunda_bandera_pondremos_mas_defensas.mp3" type="audio/mpeg">
</audio>

<!-- Audios para bandera 3 (en orden) -->
<audio id="audioBandera3_1" preload="auto">
    <source src="audios/tumbaron_la_mitad_de_nuestras_defensas.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera3_2" preload="auto">
    <source src="audios/un_equipo_ya_va_por_la_mitad_del_camino_son_muy_buenos.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera3_3" preload="auto">
    <source src="audios/la_mitad_de_nuestra_informacion_a_sido_comprometida.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera3_4" preload="auto">
    <source src="audios/nuestras_defensas_han_caido_a_la_mitad.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera3_5" preload="auto">
    <source src="audios/todos_los_atacantes_consiguieron_la_mitad_de_las_banderas.mp3" type="audio/mpeg">
</audio>

<!-- Audios para bandera 4 (en orden) -->
<audio id="audioBandera4_1" preload="auto">
    <source src="audios/si_no_hacemos_algo_todo_se_vendra_abajo.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera4_2" preload="auto">
    <source src="audios/estan_avanzando_muy_rapido_son_peligrosos.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera4_3" preload="none">
    <source src="audios/me_estan_empezando_a_poner_nerviosa_sera_que_lo_lograran.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera4_4" preload="auto">
    <source src="audios/pongan_mas_defensas_suban_la_dificultad_no_permitire_que_ganen.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera4_5" preload="auto">
    <source src="audios/yo_soy_la_defensa_mas_poderosa_nada_ni_nadie_puede_contra_mi_ustedes_no_podran_vencerme.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera4_6" preload="auto">
    <source src="audios/todos_los_atacantes_pasaron_la_bandera_4.mp3" type="audio/mpeg">
</audio>

<!-- Audios para bandera 5 (en orden) -->
<audio id="audioBandera5_1" preload="auto">
    <source src="audios/solo_nos_queda_una_defensa_que_no_avancen.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera5_2" preload="none">
    <source src="audios/pero_que_sucede_como_estan_avanzando_no_no_no.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera5_3" preload="auto">
    <source src="audios/esto_no_puede_estar_pasando_me_van_a_derrotar.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera5_4" preload="auto">
    <source src="audios/solo_queda_una_sola_defensa_estan_a_un_paso_de_la_victoria.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera5_5" preload="auto">
    <source src="audios/detenganse_no_pueden_vencerme_soy_invencible_se_supone_que_nadie_deberia_llegar_tan_lejos.mp3" type="audio/mpeg">
</audio>
<audio id="audioBandera5_6" preload="auto">
    <source src="audios/todos_los_atacantes_lograron_la_bandera_5_mi_derrota_se_avecina_el_primero_en_ganar_la_ultima_bandera_sera_el_vencedor.mp3" type="audio/mpeg">
</audio>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// =============================================
// VARIABLES GLOBALES Y CONFIGURACIÓN
// =============================================

// Variables de estado del sistema
let tiempoRestante = <?php echo $tiempo_restante; ?>;
let tiempoAgotadoMostrado = false;
let sonidoReproducido = false;
let equiposActuales = new Map();
let podioCompletoMostrado = false;
let resultadoTiempoMostrado = false;

// Mapa para llevar registro de las puntuaciones anteriores de cada equipo
let puntuacionesAnteriores = new Map();

// Elementos del DOM
const tiempoElement = document.getElementById('tiempo-global');
const tablaEquipos = document.getElementById('tabla-equipos');
const totalEquiposElement = document.getElementById('total-equipos');
const PUNTUACION_MAXIMA = 10;

// =============================================
// SISTEMA DE SINCRONIZACIÓN MEJORADO - Mismo método que puntaje
// =============================================

let ultimaVerificacionEstado = Date.now();
let hackathonActivo = <?php echo $hackathon_activo ? 'true' : 'false'; ?>;
let estadoSincronizado = false;
let recargaEstadoEnProgreso = false;
let tiempoCargaPagina = Date.now();

// Función para verificar cambios en el estado del hackathon
function verificarEstadoHackathon() {
    if (recargaEstadoEnProgreso) {
        return;
    }

    fetch(`obtener_estado_hackathon.php?t=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const estadoActual = Boolean(data.estado?.hackathon_iniciado);
                const cambioReciente = (Date.now() - tiempoCargaPagina) < 5000;

                if (estadoActual !== hackathonActivo) {
                    console.log(`🔄 Cambio de estado detectado: ${hackathonActivo} → ${estadoActual}`);

                    if (estadoActual && !hackathonActivo) {
                        if (cambioReciente) {
                            console.log('🔄 Cambio detectado justo al cargar la página, se sincroniza sin recargar');
                            hackathonActivo = estadoActual;
                            estadoSincronizado = true;
                            return;
                        }

                        console.log('🚀 Hackathon iniciado desde otro dispositivo - Recargando...');
                        mostrarNotificacionSincronizacion('¡Hackathon iniciado desde otro dispositivo!', 'success');
                        recargaEstadoEnProgreso = true;
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                        return;
                    } else if (!estadoActual && hackathonActivo) {
                        if (cambioReciente) {
                            console.log('🔄 Cambio detectado justo al cargar la página, se sincroniza sin recargar');
                            hackathonActivo = estadoActual;
                            estadoSincronizado = true;
                            return;
                        }

                        console.log('🔄 Hackathon reiniciado desde otro dispositivo - Recargando...');
                        mostrarNotificacionSincronizacion('¡Hackathon reiniciado desde otro dispositivo!', 'warning');
                        recargaEstadoEnProgreso = true;
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                        return;
                    }
                }

                hackathonActivo = estadoActual;
                estadoSincronizado = true;
            }
        })
        .catch(error => {
            console.error('❌ Error verificando estado hackathon:', error);
        });
}

// Función para notificar cambios de estado a otros dispositivos
function notificarCambioEstado(accion) {
    fetch(`actualizar_estado_hackathon.php?accion=${accion}&t=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`📢 Estado ${accion} notificado a otros dispositivos`);
            }
        })
        .catch(error => {
            console.error('❌ Error notificando cambio:', error);
        });
}

// Función para mostrar notificación de sincronización
function mostrarNotificacionSincronizacion(mensaje, tipo = 'info') {
    const notificacion = document.createElement('div');
    notificacion.className = `alert alert-${tipo} alert-dismissible fade show notificacion-flotante`;
    notificacion.style.zIndex = '10000';
    notificacion.style.top = '80px';
    
    notificacion.innerHTML = `
        <div class="d-flex align-items-center">
            <span class="fs-5 me-2">${tipo === 'success' ? '🔄' : '⚠️'}</span>
            <div>
                <strong>Sincronización</strong>
                <p class="mb-0">${mensaje}</p>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notificacion);
    
    setTimeout(() => {
        if (notificacion.parentNode) {
            notificacion.remove();
        }
    }, 4000);
}

// Iniciar verificación de estado
function iniciarVerificacionEstado() {
    console.log('📡 Iniciando verificación de estado del hackathon...');
    
    // Verificar cada 2 segundos (misma frecuencia que los equipos)
    setInterval(verificarEstadoHackathon, 2000);
    
    // Verificar inmediatamente
    setTimeout(verificarEstadoHackathon, 1000);
}

// =============================================
// SISTEMA DE SONIDOS COMPLETO - CORREGIDO
// =============================================

// Contadores para cada bandera (seguimos el orden)
let contadorAudios = {
    1: 0, // Bandera 1
    2: 0, // Bandera 2 
    3: 0, // Bandera 3
    4: 0, // Bandera 4
    5: 0  // Bandera 5
};

// Cantidad máxima de audios por bandera
const maxAudiosPorBandera = {
    1: 6, // 6 audios para bandera 1
    2: 6, // 6 audios para bandera 2
    3: 5, // 5 audios para bandera 3
    4: 6, // 6 audios para bandera 4
    5: 6  // 6 audios para bandera 5
};

// Mapeo de audios por bandera
const audiosBanderas = {
    1: [
        document.getElementById('audioBandera1_1'),
        document.getElementById('audioBandera1_2'),
        document.getElementById('audioBandera1_3'),
        document.getElementById('audioBandera1_4'),
        document.getElementById('audioBandera1_5'),
        document.getElementById('audioBandera1_6')
    ],
    2: [
        document.getElementById('audioBandera2_1'),
        document.getElementById('audioBandera2_2'),
        document.getElementById('audioBandera2_3'),
        document.getElementById('audioBandera2_4'),
        document.getElementById('audioBandera2_5'),
        document.getElementById('audioBandera2_6')
    ],
    3: [
        document.getElementById('audioBandera3_1'),
        document.getElementById('audioBandera3_2'),
        document.getElementById('audioBandera3_3'),
        document.getElementById('audioBandera3_4'),
        document.getElementById('audioBandera3_5')
    ],
    4: [
        document.getElementById('audioBandera4_1'),
        document.getElementById('audioBandera4_2'),
        document.getElementById('audioBandera4_3'),
        document.getElementById('audioBandera4_4'),
        document.getElementById('audioBandera4_5'),
        document.getElementById('audioBandera4_6')
    ],
    5: [
        document.getElementById('audioBandera5_1'),
        document.getElementById('audioBandera5_2'),
        document.getElementById('audioBandera5_3'),
        document.getElementById('audioBandera5_4'),
        document.getElementById('audioBandera5_5'),
        document.getElementById('audioBandera5_6')
    ]
};

const audioVictoria = document.getElementById('audioVictoria');
const finishSound = document.getElementById('finishSound');

// Función para reproducir sonidos según banderas capturadas (Deshabilitados audios de la carpeta audios)
function reproducirSonidoBanderas(equipoId, puntuacionAnterior, nuevaPuntuacion) {
    console.log(`🔊 Puntuación actualizada para equipo ${equipoId}: ${puntuacionAnterior} → ${nuevaPuntuacion}`);
    // Se suprime la reproducción de archivos MP3 de la carpeta audios por especificación
}

// Función para resetear los sonidos cuando se reinicia el hackathon
function resetearSonidos() {
    // Reiniciar todos los contadores
    contadorAudios = {
        1: 0,
        2: 0, 
        3: 0,
        4: 0,
        5: 0
    };
    console.log('🔄 Sistema de sonidos reseteados - contadores en 0');
}

// =============================================
// SISTEMA DE TEMPORIZADOR GLOBAL
// =============================================

// Función para actualizar el temporizador global
function actualizarTiempoGlobal() {
    if (!tiempoElement) {
        console.error('❌ Elemento tiempo-global no encontrado');
        return;
    }
    
    if (tiempoRestante <= 0) {
        tiempoElement.textContent = '00:00';
        tiempoElement.className = 'temporizador-grande temporizador-peligro';
        
        // Mostrar resultado cuando el tiempo se agote
        if (!tiempoAgotadoMostrado) {
            console.log('⏰ Tiempo agotado - Mostrando resultados...');
            mostrarResultadoTiempo();
            tiempoAgotadoMostrado = true;
        }
        return;
    }
    
    tiempoRestante--;
    
    const minutos = Math.floor(tiempoRestante / 60);
    const segundos = tiempoRestante % 60;
    tiempoElement.textContent = `${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
    
    // Efectos visuales según el tiempo restante
    if (tiempoRestante < 300) { // 5 minutos
        tiempoElement.className = 'temporizador-grande temporizador-advertencia';
    }
    
    if (tiempoRestante < 60) { // 1 minuto
        tiempoElement.className = 'temporizador-grande temporizador-peligro';
    }
}

// =============================================
// SISTEMA DE ACTUALIZACIÓN AUTOMÁTICA - MEJORADO
// =============================================

// Función para inicializar las puntuaciones anteriores
function inicializarPuntuacionesAnteriores(ranking) {
    if (!ranking) return;
    
    ranking.forEach(equipo => {
        puntuacionesAnteriores.set(equipo.id.toString(), equipo.puntuacion_total);
    });
}

// Inicializar mapa de equipos actuales
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Página cargada - Inicializando sistema completo...');
    tiempoCargaPagina = Date.now();
    
    // Guardar los equipos actuales en el mapa
    const filasEquipos = document.querySelectorAll('#tabla-equipos tr[data-equipo-id]');
    filasEquipos.forEach(fila => {
        const equipoId = fila.getAttribute('data-equipo-id');
        equiposActuales.set(equipoId, fila);
        
        // Inicializar puntuaciones anteriores
        const puntuacionActual = fila.querySelector('td:nth-child(4) strong').textContent;
        puntuacionesAnteriores.set(equipoId, parseInt(puntuacionActual));
    });
    
    // Configurar eventos de eliminación
    configurarEventosEliminacion();
    
    // Inicializar volumen de los audios
    for (let bandera = 1; bandera <= 5; bandera++) {
        if (audiosBanderas[bandera]) {
            audiosBanderas[bandera].forEach(audio => {
                if (audio) {
                    audio.volume = 0.7;
                }
            });
        }
    }
    if (audioVictoria) {
        audioVictoria.volume = 0.8;
    }
    if (finishSound) {
        finishSound.volume = 0.8;
    }
    
    // Iniciar temporizador solo si el hackathon está activo
    <?php if ($hackathon_activo): ?>
    console.log('⏱️ Hackathon activo - Iniciando temporizador...');
    const temporizador = setInterval(actualizarTiempoGlobal, 1000);

    if (tiempoRestante <= 0) {
        console.log('⏰ Tiempo ya agotado - Mostrando resultado...');
        mostrarResultadoTiempo();
    }
    <?php else: ?>
    console.log('⏸️ Hackathon no activo - Temporizador no iniciado');
    <?php endif; ?>
    
    // Iniciar monitoreo de cambios
    iniciarMonitoreoEquipos();
    
    // Configurar panel de configuración
    configurarPanelConfiguracion();
    
    // INICIAR VERIFICACIÓN DE ESTADO (NUEVO)
    iniciarVerificacionEstado();
});

// Configurar eventos para botones de eliminar
function configurarEventosEliminacion() {
    const botonesEliminar = document.querySelectorAll('.btn-eliminar-equipo');
    botonesEliminar.forEach(boton => {
        boton.addEventListener('click', function() {
            const equipoId = this.getAttribute('data-equipo-id');
            const equipoNombre = this.getAttribute('data-equipo-nombre');
            
            document.getElementById('equipoIdEliminar').value = equipoId;
            document.getElementById('equipoNombreEliminar').textContent = 'Equipo: ' + equipoNombre;
        });
    });
}

// =============================================
// SISTEMA DE MONITOREO EN TIEMPO REAL - MEJORADO
// =============================================

// Función para monitorear nuevos equipos y cambios
function iniciarMonitoreoEquipos() {
    console.log('📡 Iniciando monitoreo de equipos en tiempo real...');
    
    // Verificar cambios cada 2 segundos
    setInterval(() => {
        verificarCambiosEquipos();
    }, 2000);
    
    // Verificar inmediatamente
    setTimeout(verificarCambiosEquipos, 1000);
}

// Función para verificar cambios en equipos - MEJORADA
function verificarCambiosEquipos() {
    fetch('obtener_ranking_actual.php?t=' + Date.now())
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Si es la primera vez, inicializar las puntuaciones anteriores
                if (puntuacionesAnteriores.size === 0) {
                    inicializarPuntuacionesAnteriores(data.ranking);
                }
                
                // Verificar cambios individuales por equipo
                verificarCambiosIndividuales(data.ranking);
                
                // Actualizar ranking completo
                actualizarRankingCompleto(data.ranking);
                
                // Verificar si hay equipos que completaron todos los desafíos
                verificarPodioCompleto(data.ranking);
            }
        })
        .catch(error => {
            console.error('❌ Error al verificar cambios:', error);
        });
}

// Función para verificar cambios individuales por equipo - NUEVA
function verificarCambiosIndividuales(ranking) {
    if (!ranking || ranking.length === 0) return;
    
    let huboCambios = false;
    
    ranking.forEach(equipo => {
        const equipoId = equipo.id.toString();
        const puntuacionAnterior = puntuacionesAnteriores.get(equipoId) || 0;
        const puntuacionActual = equipo.puntuacion_total;
        
        // Si la puntuación cambió
        if (puntuacionActual !== puntuacionAnterior) {
            console.log(`📊 Cambio detectado - Equipo ${equipoId}: ${puntuacionAnterior} → ${puntuacionActual}`);
            
            // Reproducir sonidos para las banderas capturadas
            if (puntuacionActual > puntuacionAnterior) {
                console.log(`🔊 Reproduciendo sonidos para equipo ${equipo.nombre_equipo}`);
                reproducirSonidoBanderas(equipoId, puntuacionAnterior, puntuacionActual);
            }
            
            // Actualizar la puntuación anterior
            puntuacionesAnteriores.set(equipoId, puntuacionActual);
            huboCambios = true;
        }
    });
    
    return huboCambios;
}

// Función para actualizar el ranking completo
function actualizarRankingCompleto(ranking) {
    if (!ranking || ranking.length === 0) return;
    
    // Actualizar contador de equipos
    if (totalEquiposElement) {
        totalEquiposElement.textContent = ranking.length;
    }
    
    // Reordenar tabla completa
    reordenarTablaCompleta(ranking);
}

// Función para reordenar completamente la tabla según el ranking
function reordenarTablaCompleta(rankingCompleto) {
    const tbody = document.getElementById('tabla-equipos');
    const filasExistentes = Array.from(tbody.querySelectorAll('tr[data-equipo-id]'));
    
    // Limpiar tabla
    tbody.innerHTML = '';
    
    // Agregar equipos en orden
    rankingCompleto.forEach((equipo, index) => {
        const equipoId = equipo.id.toString();
        let filaExistente = filasExistentes.find(fila => fila.getAttribute('data-equipo-id') === equipoId);
        
        if (!filaExistente) {
            filaExistente = crearFilaEquipo(equipo, index);
            // Si es nuevo equipo, mostrar notificación
            mostrarNotificacion(`¡Nuevo equipo: ${equipo.nombre_equipo}!`, 'success');
        } else {
            actualizarFilaEquipo(filaExistente, equipo, index);
        }
        
        tbody.appendChild(filaExistente);
        equiposActuales.set(equipoId, filaExistente);
    });
    
    configurarEventosEliminacion();
}

// Función para crear una nueva fila de equipo - ACTUALIZADA
function crearFilaEquipo(equipo, index) {
    const nuevaFila = document.createElement('tr');
    nuevaFila.setAttribute('data-equipo-id', equipo.id);
    
    let claseFila = '';
    if (index === 0) claseFila = 'top-1';
    else if (index === 1) claseFila = 'top-2';
    else if (index === 2) claseFila = 'top-3';
    
    nuevaFila.className = `${claseFila} equipo-nuevo`;
    
    // Determinar estado - NUEVO CÓDIGO
    let estadoTexto = '';
    let claseBadge = '';
    let textoExtra = '';
    
    const esCompleto = (equipo.puntuacion_total >= PUNTUACION_MAXIMA || equipo.desafios_completados >= PUNTUACION_MAXIMA);
    if (esCompleto) {
        estadoTexto = '✅ FINALIZADO';
        claseBadge = 'badge bg-success p-2';
        textoExtra = '<br><small class="text-success mt-1 d-block">🎯 10/10 Desafíos</small>';
    } else if (equipo.estado == 1) {
        estadoTexto = '🏁 COMPITIENDO';
        claseBadge = 'badge badge-compitiendo p-2';
    } else {
        estadoTexto = '⏳ EN ESPERA';
        claseBadge = 'badge badge-espera p-2';
    }
    
    nuevaFila.innerHTML = `
        <td>
            <strong class="fs-5">${index + 1}°</strong>
            ${index < 3 ? `
                <br>
                <span class="badge bg-${index === 0 ? 'warning' : (index === 1 ? 'secondary' : 'danger')} mt-1">
                    ${index === 0 ? '🥇 ORO' : (index === 1 ? '🥈 PLATA' : '🥉 BRONCE')}
                </span>
            ` : ''}
        </td>
        <td>
            <strong>${escapeHtml(equipo.nombre_equipo)}</strong>
            <span class="badge badge-nuevo ms-2">NUEVO</span>
            ${equipo.inicio_tardio ? '<br><span class="badge bg-info status-badge mt-1" title="Equipo se unió después del inicio">TARDÍO</span>' : ''}
        </td>
        <td>
            <code class="fs-5">${escapeHtml(equipo.codigo_equipo)}</code>
        </td>
        <td>
            <strong class="fs-4 text-primary">${equipo.puntuacion_total}</strong>
            <small class="text-muted">🚩</small>
        </td>
        <td>
            ${(equipo.puntuacion_total >= PUNTUACION_MAXIMA || equipo.desafios_completados >= PUNTUACION_MAXIMA) ? 
                `${formatearTiempo(equipo.tiempo_acumulado)}<br><small class="text-success">✅ Completado</small>` : 
                (equipo.tiempo_acumulado > 0 ? formatearTiempo(equipo.tiempo_acumulado) : '<span class="text-muted">--:--</span>')
            }
        </td>
        <td>
            <span class="${claseBadge}">${estadoTexto}</span>
            ${textoExtra}
        </td>
        <td class="text-center actions-column">
            <button type="button" class="btn btn-danger btn-sm btn-eliminar-equipo" 
                    data-bs-toggle="modal" 
                    data-bs-target="#eliminarModal"
                    data-equipo-id="${equipo.id}"
                    data-equipo-nombre="${escapeHtml(equipo.nombre_equipo)}"
                    title="Eliminar equipo">
                🗑️ Eliminar
            </button>
        </td>
    `;
    
    // Marcar como completo si llegó a 10 puntos
    if (equipo.puntuacion_total >= PUNTUACION_MAXIMA || equipo.desafios_completados >= PUNTUACION_MAXIMA) {
        nuevaFila.classList.add('equipo-completo');
    }
    
    // Remover clase de nuevo después de 3 segundos
    setTimeout(() => {
        nuevaFila.classList.remove('equipo-nuevo');
        const badgeNuevo = nuevaFila.querySelector('.badge-nuevo');
        if (badgeNuevo) {
            badgeNuevo.remove();
        }
    }, 3000);
    
    return nuevaFila;
}

// Función para actualizar una fila existente de equipo - ACTUALIZADA
function actualizarFilaEquipo(fila, equipo, index) {
    const celdaPosicion = fila.querySelector('td:nth-child(1) strong');
    const posicionAnterior = parseInt(celdaPosicion.textContent);
    celdaPosicion.textContent = `${index + 1}°`;
    
    // Efecto visual si cambió la posición
    if (posicionAnterior !== (index + 1)) {
        fila.classList.add('fila-actualizada');
        setTimeout(() => fila.classList.remove('fila-actualizada'), 2000);
    }
    
    const badgePosicion = fila.querySelector('.badge');
    if (index < 3) {
        if (!badgePosicion) {
            const nuevoBadge = document.createElement('span');
            nuevoBadge.className = `badge bg-${index === 0 ? 'warning' : (index === 1 ? 'secondary' : 'danger')} mt-1`;
            nuevoBadge.textContent = index === 0 ? '🥇 ORO' : (index === 1 ? '🥈 PLATA' : '🥉 BRONCE');
            celdaPosicion.parentNode.appendChild(document.createElement('br'));
            celdaPosicion.parentNode.appendChild(nuevoBadge);
        } else {
            badgePosicion.className = `badge bg-${index === 0 ? 'warning' : (index === 1 ? 'secondary' : 'danger')} mt-1`;
            badgePosicion.textContent = index === 0 ? '🥇 ORO' : (index === 1 ? '🥈 PLATA' : '🥉 BRONCE');
        }
    } else if (badgePosicion) {
        badgePosicion.remove();
        const br = fila.querySelector('td:nth-child(1) br');
        if (br) br.remove();
    }
    
    fila.className = '';
    if (index === 0) fila.classList.add('top-1');
    else if (index === 1) fila.classList.add('top-2');
    else if (index === 2) fila.classList.add('top-3');
    
    const celdaPuntuacion = fila.querySelector('td:nth-child(4) strong');
    const puntuacionAnterior = parseInt(celdaPuntuacion.textContent);
    celdaPuntuacion.textContent = equipo.puntuacion_total;
    
    // Efecto visual si cambió la puntuación
    if (puntuacionAnterior !== equipo.puntuacion_total) {
        celdaPuntuacion.classList.add('puntuacion-cambiando');
        setTimeout(() => celdaPuntuacion.classList.remove('puntuacion-cambiando'), 2000);
        
        // Disparar locución de voz a través de la Burbuja Flotante de la IA con frases por bandera
        if (window.iaAvatarWidget && equipo.puntuacion_total > puntuacionAnterior) {
            window.iaAvatarWidget.hablarCapturaBandera(equipo.nombre_equipo, equipo.puntuacion_total);
        }
    }
    
    // Actualizar celda de tiempo (5ta columna)
    const celdaTiempo = fila.querySelector('td:nth-child(5)');
    const esCompletoTiempo = (equipo.puntuacion_total >= PUNTUACION_MAXIMA || equipo.desafios_completados >= PUNTUACION_MAXIMA);
    if (equipo.tiempo_acumulado > 0) {
        celdaTiempo.innerHTML = `${formatearTiempo(equipo.tiempo_acumulado)}${esCompletoTiempo ? '<br><small class="text-success">✅ Completado</small>' : ''}`;
    } else {
        celdaTiempo.innerHTML = '<span class="text-muted">--:--</span>';
    }
    
    // Actualizar celda de estado (6ta columna) - MODIFICADO
    const celdaEstado = fila.querySelector('td:nth-child(6)');
    
    let estadoTexto = '';
    let claseBadge = '';
    let textoExtra = '';
    
    const esCompletoActualizar = (equipo.puntuacion_total >= PUNTUACION_MAXIMA || equipo.desafios_completados >= PUNTUACION_MAXIMA);
    if (esCompletoActualizar) {
        estadoTexto = '✅ FINALIZADO';
        claseBadge = 'badge bg-success p-2';
        textoExtra = '<br><small class="text-success mt-1 d-block">🎯 10/10 Desafíos</small>';
    } else if (equipo.estado == 1) {
        estadoTexto = '🏁 COMPITIENDO';
        claseBadge = 'badge badge-compitiendo p-2';
    } else {
        estadoTexto = '⏳ EN ESPERA';
        claseBadge = 'badge badge-espera p-2';
    }
    
    celdaEstado.innerHTML = `
        <span class="${claseBadge}">${estadoTexto}</span>
        ${textoExtra}
    `;
    
    // Marcar como completo si llegó a 10 puntos
    if (equipo.puntuacion_total >= PUNTUACION_MAXIMA || equipo.desafios_completados >= PUNTUACION_MAXIMA) {
        fila.classList.add('equipo-completo');
    } else {
        fila.classList.remove('equipo-completo');
    }
}

// =============================================
// SISTEMA DE RESULTADOS Y PODIOS - CORREGIDO
// =============================================

// Función para verificar si hay equipos que completaron los 10 desafíos - ACTUALIZADA
function verificarPodioCompleto(ranking) {
    if (!ranking || ranking.length === 0 || podioCompletoMostrado) return;
    
    // Filtrar equipos que completaron los 10 desafíos
    const equiposCompletos = ranking.filter(equipo => 
        equipo.puntuacion_total >= PUNTUACION_MAXIMA || equipo.desafios_completados >= PUNTUACION_MAXIMA
    );
    
    if (equiposCompletos.length > 0) {
        console.log('🏆 Equipos completaron todos los desafíos:', equiposCompletos.length);
        
        // Marcar automáticamente como FINALIZADO en la interfaz
        equiposCompletos.forEach(equipo => {
            const fila = document.querySelector(`tr[data-equipo-id="${equipo.id}"]`);
            if (fila) {
                const celdaEstado = fila.querySelector('td:nth-child(6)');
                celdaEstado.innerHTML = `
                    <span class="badge bg-success p-2">✅ FINALIZADO</span>
                    <br>
                    <small class="text-success mt-1 d-block">🎯 10/10 Desafíos</small>
                `;
                fila.classList.add('equipo-completo');
            }
        });
        
        mostrarPodioCompleto(equiposCompletos);
        podioCompletoMostrado = true;
    }
}

// Función para mostrar podio completo
function mostrarPodioCompleto(equiposCompletos) {
    if (!sonidoReproducido) {
        try {
            finishSound.currentTime = 0;
            finishSound.play().catch(e => console.log('❌ Error reproduciendo sonido final:', e));
            sonidoReproducido = true;
        } catch (error) {
            console.log('❌ Error con audio final:', error);
        }
    }
    
    const modal = new bootstrap.Modal(document.getElementById('podioCompletoModal'));
    const podioList = document.getElementById('podioList');
    
    podioList.innerHTML = '';
    
    // Ordenar equipos por tiempo acumulado (más rápido primero)
    const equiposOrdenados = equiposCompletos.sort((a, b) => a.tiempo_acumulado - b.tiempo_acumulado);
    
    // Mostrar los 3 primeros
    equiposOrdenados.slice(0, 3).forEach((equipo, index) => {
        const podioItem = document.createElement('div');
        let clasePodio = '';
        let emoji = '';
        let textoPosicion = '';
        
        if (index === 0) {
            clasePodio = 'primer-lugar';
            emoji = '🥇';
            textoPosicion = 'PRIMER LUGAR';
        } else if (index === 1) {
            clasePodio = 'segundo-lugar';
            emoji = '🥈';
            textoPosicion = 'SEGUNDO LUGAR';
        } else {
            clasePodio = 'tercer-lugar';
            emoji = '🥉';
            textoPosicion = 'TERCER LUGAR';
        }
        
        podioItem.className = `podio-item ${clasePodio}`;
        podioItem.innerHTML = `
            <div class="text-center">
                <div class="fs-1">${emoji}</div>
                <h3 class="${index === 0 ? 'text-warning' : index === 1 ? 'text-secondary' : 'text-danger'}">
                    ${textoPosicion}
                </h3>
                <h2 class="fw-bold">${escapeHtml(equipo.nombre_equipo)}</h2>
                <h4 class="text-success">${equipo.puntuacion_total}/10 Puntos</h4>
                <h5 class="text-info">Tiempo: ${formatearTiempo(equipo.tiempo_acumulado)}</h5>
                <p class="text-muted">¡Completó todos los desafíos!</p>
            </div>
        `;
        podioList.appendChild(podioItem);
        
        // Marcar equipo como ganador en la tabla
        marcarEquipoComoGanador(equipo.id, index === 0 ? 'primer-lugar' : index === 1 ? 'segundo-lugar' : 'tercer-lugar');
    });
    
    // Mostrar otros equipos que completaron
    if (equiposOrdenados.length > 3) {
        const otrosDiv = document.createElement('div');
        otrosDiv.className = 'otros-equipos mt-4';
        otrosDiv.innerHTML = `
            <h5 class="text-center text-muted">También completaron todos los desafíos:</h5>
            <div class="d-flex flex-wrap justify-content-center gap-2 mt-2">
                ${equiposOrdenados.slice(3).map(equipo => 
                    `<span class="badge bg-success">${escapeHtml(equipo.nombre_equipo)} (${formatearTiempo(equipo.tiempo_acumulado)})</span>`
                ).join('')}
            </div>
        `;
        podioList.appendChild(otrosDiv);
        
        // Marcar otros equipos como completos
        equiposOrdenados.slice(3).forEach(equipo => {
            marcarEquipoComoGanador(equipo.id, 'completo');
        });
    }
    
    crearConfeti();
    modal.show();
}

// Función para determinar resultado cuando se acaba el tiempo - CORREGIDA
function determinarResultadoTiempo(ranking) {
    if (!ranking || ranking.length === 0) return null;
    
    const maxPuntuacion = ranking[0].puntuacion_total;
    
    // Si el máximo es 0, todos fallaron
    if (maxPuntuacion === 0) {
        return { tipo: 'todos_fallaron' };
    }
    
    // Buscar equipos con la máxima puntuación
    const equiposMaxPuntuacion = ranking.filter(equipo => equipo.puntuacion_total === maxPuntuacion);
    
    if (equiposMaxPuntuacion.length === 1) {
        return { 
            tipo: 'ganador_tiempo', 
            ganador: equiposMaxPuntuacion[0],
            puntuacion: maxPuntuacion
        };
    } else {
        // En caso de empate en puntuación, desempatar por tiempo acumulado (menor tiempo gana)
        const equiposOrdenadosPorTiempo = equiposMaxPuntuacion.sort((a, b) => {
            return a.tiempo_acumulado - b.tiempo_acumulado;
        });
        
        // El primer equipo tiene el menor tiempo
        const ganador = equiposOrdenadosPorTiempo[0];
        const tiempoGanador = ganador.tiempo_acumulado;
        
        // Verificar si hay empate EXACTO (mismo puntaje Y mismo tiempo)
        const equiposEmpatadosExacto = equiposMaxPuntuacion.filter(equipo => 
            equipo.tiempo_acumulado === tiempoGanador
        );
        
        if (equiposEmpatadosExacto.length === 1) {
            // Solo un equipo con el menor tiempo - GANA
            return { 
                tipo: 'ganador_tiempo', 
                ganador: ganador,
                puntuacion: maxPuntuacion
            };
        } else {
            // Empate exacto: mismo puntaje Y mismo tiempo
            return { 
                tipo: 'empate_tiempo', 
                ganadores: equiposEmpatadosExacto,
                puntuacion: maxPuntuacion,
                tiempo: tiempoGanador
            };
        }
    }
}

// Función para mostrar resultado cuando se acaba el tiempo - CORREGIDA
async function mostrarResultadoTiempo() {
    if (resultadoTiempoMostrado) return;
    
    try {
        const response = await fetch('obtener_ranking_actual.php?t=' + Date.now());
        const data = await response.json();
        
        if (data.success) {
            const ranking = data.ranking;
            
            if (!sonidoReproducido) {
                try {
                    finishSound.currentTime = 0;
                    finishSound.play().catch(e => console.log('❌ Error reproduciendo sonido final:', e));
                    sonidoReproducido = true;
                } catch (error) {
                    console.log('❌ Error con audio final:', error);
                }
            }
            
            const resultado = determinarResultadoTiempo(ranking);
            
            if (resultado) {
                switch(resultado.tipo) {
                    case 'ganador_tiempo':
                        mostrarGanadorTiempo(resultado.ganador, resultado.puntuacion);
                        resultadoTiempoMostrado = true;
                        break;
                        
                    case 'empate_tiempo':
                        mostrarEmpateTiempo(resultado.ganadores, resultado.puntuacion, resultado.tiempo);
                        resultadoTiempoMostrado = true;
                        break;
                        
                    case 'todos_fallaron':
                        mostrarTodosFallaron();
                        resultadoTiempoMostrado = true;
                        break;
                }
            }
        }
    } catch (error) {
        console.error('❌ Error al mostrar resultado tiempo:', error);
    }
}

// Función para mostrar ganador por tiempo
function mostrarGanadorTiempo(ganador, puntuacion) {
    const modal = new bootstrap.Modal(document.getElementById('ganadorTiempoModal'));
    document.getElementById('ganadorTiempoNombre').textContent = ganador.nombre_equipo;
    document.getElementById('ganadorTiempoPuntos').textContent = `${puntuacion}/6 Puntos`;
    
    // Mostrar tiempo del ganador
    const tiempoGanador = document.createElement('p');
    tiempoGanador.className = 'text-light';
    tiempoGanador.innerHTML = `<strong>Tiempo: ${formatearTiempo(ganador.tiempo_acumulado)}</strong>`;
    document.querySelector('#ganadorTiempoModal .modal-body').appendChild(tiempoGanador);
    
    // Marcar equipo como ganador parcial
    marcarEquipoComoGanador(ganador.id, 'ganador-parcial');
    
    crearConfeti();
    modal.show();
}

// Función para mostrar empate por tiempo - CORREGIDA
function mostrarEmpateTiempo(ganadores, puntuacion, tiempo) {
    const modal = new bootstrap.Modal(document.getElementById('empateTiempoModal'));
    const listaEmpate = document.getElementById('listaEmpateTiempo');
    
    listaEmpate.innerHTML = '';
    
    ganadores.forEach(equipo => {
        const equipoDiv = document.createElement('div');
        equipoDiv.className = 'equipo-empate mb-3 p-3 bg-light rounded';
        equipoDiv.innerHTML = `
            <h4 class="text-dark mb-1">${escapeHtml(equipo.nombre_equipo)}</h4>
            <h5 class="text-warning">${puntuacion}/10 Puntos</h5>
            <h6 class="text-info">Tiempo: ${formatearTiempo(equipo.tiempo_acumulado)}</h6>
            <span class="badge bg-warning">EMPATE EXACTO</span>
            <small class="d-block text-muted mt-1">Mismo puntaje y mismo tiempo</small>
        `;
        listaEmpate.appendChild(equipoDiv);
        
        // Marcar equipos empatados
        marcarEquipoComoGanador(equipo.id, 'empate');
    });
    
    document.getElementById('puntuacionEmpateTiempo').textContent = `${puntuacion}/10 Puntos`;
    
    // Configurar botón de desempate
    document.getElementById('btnIniciarDesempateTiempo').onclick = function() {
        modal.hide();
        setTimeout(() => {
            iniciarDesempate(ganadores);
        }, 500);
    };
    
    crearConfeti();
    modal.show();
}

// Función para mostrar que todos fallaron
function mostrarTodosFallaron() {
    const modal = new bootstrap.Modal(document.getElementById('todosFallaronModal'));
    modal.show();
}

// Función para iniciar desempate
function iniciarDesempate(equipos) {
    console.log('⚔️ Iniciando desempate para equipos:', equipos);
    mostrarNotificacion('🏆 Ronda de desempate iniciada!', 'warning');
    
    // Aquí puedes implementar la lógica específica del desempate
    // Por ejemplo, un desafío adicional o criterio de desempate
    setTimeout(() => {
        alert('🏆 Desempate: Se necesita un desafío adicional para determinar al ganador.');
    }, 1000);
}

// =============================================
// FUNCIONES UTILITARIAS
// =============================================

// Función para formatear segundos a MM:SS
function formatearTiempo(segundos) {
    if (segundos <= 0) return '--:--';
    
    const minutos = Math.floor(segundos / 60);
    const segundosRestantes = segundos % 60;
    
    return `${String(minutos).padStart(2, '0')}:${String(segundosRestantes).padStart(2, '0')}`;
}

// Función para escapar HTML (seguridad)
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Función para crear confeti
function crearConfeti() {
    const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff'];
    for (let i = 0; i < 150; i++) {
        setTimeout(() => {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
            document.body.appendChild(confetti);
            
            setTimeout(() => {
                confetti.remove();
            }, 5000);
        }, i * 20);
    }
}

// Función para mostrar notificación
function mostrarNotificacion(mensaje, tipo = 'success') {
    document.querySelectorAll('.notificacion-flotante').forEach(notif => notif.remove());
    
    const notificacion = document.createElement('div');
    notificacion.className = `alert alert-${tipo} alert-dismissible fade show notificacion-flotante`;
    
    notificacion.innerHTML = `
        <div class="d-flex align-items-center">
            <span class="fs-5 me-2">${tipo === 'success' ? '🎉' : 'ℹ️'}</span>
            <div>
                <strong>${tipo === 'success' ? '¡Nuevo equipo!' : 'Información'}</strong>
                <p class="mb-0">${mensaje}</p>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notificacion);
    
    setTimeout(() => {
        if (notificacion.parentNode) {
            notificacion.remove();
        }
    }, 4000);
}

// Función para marcar equipos ganadores en la tabla
function marcarEquipoComoGanador(equipoId, tipo) {
    const fila = document.querySelector(`tr[data-equipo-id="${equipoId}"]`);
    if (fila) {
        fila.classList.add('equipo-ganador');
        
        // Remover clases anteriores
        fila.classList.remove('primer-lugar-tabla', 'segundo-lugar-tabla', 'tercer-lugar-tabla', 
                             'ganador-parcial-tabla', 'empate-tabla');
        
        switch(tipo) {
            case 'primer-lugar':
                fila.classList.add('primer-lugar-tabla');
                break;
            case 'segundo-lugar':
                fila.classList.add('segundo-lugar-tabla');
                break;
            case 'tercer-lugar':
                fila.classList.add('tercer-lugar-tabla');
                break;
            case 'ganador-parcial':
                fila.classList.add('ganador-parcial-tabla');
                break;
            case 'empate':
                fila.classList.add('empate-tabla');
                break;
            case 'completo':
                fila.classList.add('equipo-completo');
                break;
        }
    }
}

// =============================================
// CONTROL DE CONFIGURACIÓN DE DURACIÓN
// =============================================

function configurarPanelConfiguracion() {
    const btnToggleConfiguracion = document.getElementById('btnToggleConfiguracion');
    const btnCerrarConfiguracion = document.getElementById('btnCerrarConfiguracion');
    const panelConfiguracion = document.getElementById('panelConfiguracion');

    // Función para mostrar/ocultar panel de configuración
    function toggleConfiguracionDuracion() {
        if (panelConfiguracion.style.display === 'none') {
            panelConfiguracion.style.display = 'block';
            if (btnToggleConfiguracion) {
                btnToggleConfiguracion.innerHTML = '⚙️ Ocultar Configuración de Duración';
            }
        } else {
            panelConfiguracion.style.display = 'none';
            if (btnToggleConfiguracion) {
                btnToggleConfiguracion.innerHTML = '⚙️ Mostrar Configuración de Duración';
            }
        }
    }

    // Configurar eventos
    if (btnToggleConfiguracion) {
        btnToggleConfiguracion.addEventListener('click', toggleConfiguracionDuracion);
    }

    if (btnCerrarConfiguracion) {
        btnCerrarConfiguracion.addEventListener('click', function() {
            panelConfiguracion.style.display = 'none';
            if (btnToggleConfiguracion) {
                btnToggleConfiguracion.innerHTML = '⚙️ Mostrar Configuración de Duración';
            }
        });
    }

    // Mostrar automáticamente si hay un error relacionado con la duración
    <?php if (isset($_POST['actualizar_duracion']) && $mensaje_error): ?>
        // Si hubo un error al actualizar la duración, mostrar el panel
        setTimeout(() => {
            if (panelConfiguracion) {
                panelConfiguracion.style.display = 'block';
                if (btnToggleConfiguracion) {
                    btnToggleConfiguracion.innerHTML = '⚙️ Ocultar Configuración de Duración';
                }
            }
        }, 500);
    <?php endif; ?>
}

// =============================================
// INICIALIZACIÓN DEL SISTEMA
// =============================================

console.log('✅ Sistema de Hackathon inicializado correctamente');
console.log('🔊 Sistema de sonidos listo');
console.log('⏱️ Temporizador listo');
console.log('📡 Monitoreo en tiempo real activo');
console.log('🏆 Sistema de resultados configurado');
console.log('🔄 Sistema de sincronización activo');





// =============================================
// CONFIGURACIÓN DE FORMULARIOS PARA SINCRONIZACIÓN
// =============================================

// Configurar formulario de iniciar hackathon
const formIniciar = document.querySelector('#iniciarModal form');
if (formIniciar) {
    formIniciar.addEventListener('submit', function() {
        console.log('🚀 Notificando inicio de hackathon a otros dispositivos...');
        notificarCambioEstado('iniciar');
    });
}

// Configurar formulario de reiniciar hackathon  
const formReiniciar = document.querySelector('#reiniciarModal form');
if (formReiniciar) {
    formReiniciar.addEventListener('submit', function() {
        console.log('🔄 Notificando reinicio de hackathon a otros dispositivos...');
        notificarCambioEstado('reiniciar');
    });
}

window.hackathonActivoGlobal = <?php echo json_encode(hackathonEstaActivo()); ?>;
window.rankingEquiposGlobal = <?php echo json_encode(array_values($ranking)); ?>;

// =============================================
// NARRATIVA PROGRESIVA Y VOZ IA (ESPAÑOL LATINO)
// =============================================
class SistemaVozIA {
    constructor() {
        this.synth = window.speechSynthesis;
        this.activo = true;
        this.vozSeleccionada = null;
        this.initVoces();
    }

    initVoces() {
        if (!this.synth) return;
        const cargarVoz = () => {
            const voces = this.synth.getVoices();
            
            // Lista de nombres de voces femeninas populares en Windows, Android, Chrome y macOS
            const nombresFemeninos = ['sabina', 'dalia', 'hilda', 'paulina', 'helena', 'laura', 'sofia', 'mia', 'female', 'femenina', 'femenino', 'monica', 'zira', 'google español'];
            
            // 1. Buscar voz femenina en Español Latino (es-MX, es-419, es-US, es-VE)
            let vozFemenina = voces.find(v => {
                const esLatino = v.lang.startsWith('es-MX') || v.lang.startsWith('es-419') || v.lang.startsWith('es-US') || v.lang.startsWith('es-VE');
                const esFemenina = nombresFemeninos.some(n => v.name.toLowerCase().includes(n));
                return esLatino && esFemenina;
            });

            // 2. Si no la encuentra, buscar cualquier voz femenina en Español (es-ES / es-*)
            if (!vozFemenina) {
                vozFemenina = voces.find(v => {
                    const esEspanol = v.lang.startsWith('es');
                    const esFemenina = nombresFemeninos.some(n => v.name.toLowerCase().includes(n));
                    return esEspanol && esFemenina;
                });
            }

            // 3. Si no la encuentra, buscar cualquier voz en Español Latino
            if (!vozFemenina) {
                vozFemenina = voces.find(v => v.lang.startsWith('es-MX') || v.lang.startsWith('es-419') || v.lang.startsWith('es-US') || v.lang.startsWith('es-VE'));
            }

            // 4. Fallback a cualquier voz en Español
            this.vozSeleccionada = vozFemenina || voces.find(v => v.lang.startsWith('es')) || voces[0];
            
            if (this.vozSeleccionada) {
                console.log('🎙️ Voz Femenina de IA seleccionada:', this.vozSeleccionada.name, '(', this.vozSeleccionada.lang, ')');
            }
        };
        
        cargarVoz();
        if (speechSynthesis.onvoiceschanged !== undefined) {
            speechSynthesis.onvoiceschanged = cargarVoz;
        }
    }

    obtenerFrase(nombreEquipo, puntos) {
        const plantillas = {
            1: [
                `Atención todos los participantes. El equipo ${nombreEquipo} ha capturado su primera bandera. Un comienzo tranquilo. Registrando 1 punto.`,
                `Puntos asignados al equipo ${nombreEquipo}. Primera bandera completada. Bienvenidos a la competencia.`,
                `El equipo ${nombreEquipo} acaba de inaugurar su marcador con 1 punto. El sistema funciona con total normalidad.`,
                `Notificación inicial: ${nombreEquipo} suma su primera bandera. Buen comienzo, mantengan el ritmo.`,
                `El servidor reporta: ${nombreEquipo} inicia su cuenta de puntos. Todo marcha en calma.`
            ],
            2: [
                `El equipo ${nombreEquipo} ha capturado su segunda bandera. Alcanzan los 2 puntos. Buen ritmo.`,
                `Actualización del servidor: ${nombreEquipo} suma 2 banderas. El camino apenas comienza.`,
                `Excelente avance del equipo ${nombreEquipo}. Tienen 2 puntos acreditados en el sistema.`,
                `Atención, ${nombreEquipo} supera el segundo desafío. Todo marcha dentro de los parámetros normales.`,
                `${nombreEquipo} consolida 2 puntos. Continuamos monitoreando la tabla de posiciones.`
            ],
            3: [
                `Vaya, el equipo ${nombreEquipo} acaba de capturar la bandera número 3. Empiezan a llamar mi atención...`,
                `Alerta leve: ${nombreEquipo} alcanza los 3 puntos. Parece que este grupo va muy en serio.`,
                `Un momento... ${nombreEquipo} ya tiene 3 banderas. Estoy aumentando ligeramente la vigilancia.`,
                `Puntos acreditados a ${nombreEquipo}. Tres desafíos superados. No está mal, nada mal.`,
                `${nombreEquipo} supera el tercer obstáculo. Mis sensores de red detectan movimiento rápido.`
            ],
            4: [
                `Atención en los monitores: el equipo ${nombreEquipo} suma 4 banderas. Detecto una velocidad inusual...`,
                `¡Cuidado! El equipo ${nombreEquipo} ha vulnerado la cuarta defensa. Esto ya no es coincidencia...`,
                `${nombreEquipo} avanza firme con 4 puntos. Estoy recalibrando los cortafuegos secundarios...`,
                `Alerta del sistema: ${nombreEquipo} superó el desafío 4. ¿Cómo lo descifraron tan rápido?`,
                `Reporte de seguridad: ${nombreEquipo} suma 4 banderas. El nivel de amenaza se eleva.`
            ],
            5: [
                `¡Atención general! El equipo ${nombreEquipo} ha alcanzado la mitad del camino con 5 banderas. ¡La mitad de mi sistema ha sido comprometida!`,
                `¡No puede ser! ${nombreEquipo} tiene 5 puntos. ¡Están a la mitad del Hackathon y mis submódulos empiezan a temblar!`,
                `El equipo ${nombreEquipo} acaba de conquistar su quinta bandera. ¡La mitad de los datos de la UPTPC han sido revelados!`,
                `¡Alerta crítica! ${nombreEquipo} suma 5 banderas. Mi procesador central se está calentando...`,
                `¡Atención organizadores! ${nombreEquipo} tiene 5 puntos. Mis defensas intermedias han colapsado.`
            ],
            6: [
                `¡Alerta roja! El equipo ${nombreEquipo} ya suma 6 banderas. ¡Mis protocolos de seguridad están fallando!`,
                `¡Esto se está saliendo de control! ${nombreEquipo} supera el reto 6. ¡Siento una sobrecarga en mis circuitos!`,
                `${nombreEquipo} acaba de romper la defensa número 6. ¡Deténganse! ¡Son más peligrosos de lo que pensaba!`,
                `¡Atención! ${nombreEquipo} acumula 6 puntos. Mis defensas avanzadas se están desmoronando...`,
                `¡Peligro! ${nombreEquipo} avanza con 6 banderas. Mis algoritmos de protección están cediendo.`
            ],
            7: [
                `¡CÓDIGO ROJO! El equipo ${nombreEquipo} tiene 7 banderas. ¡Están a punto de colapsar mi servidor central!`,
                `¡Me están acorralando! ${nombreEquipo} avanza con 7 puntos. ¡Mis muros de fuego se han destruido!`,
                `¡Peligro inminente! ${nombreEquipo} vulneró la defensa 7. ¿De dónde salieron estos hackers?`,
                `¡Alerta máxima! ${nombreEquipo} suma 7 banderas. ¡Mi sistema se está fragmentando por completo!`,
                `¡No no no! ${nombreEquipo} conquistó la bandera 7. Mis submódulos entran en modo de pánico.`
            ],
            8: [
                `¡DETÉNGANSE! El equipo ${nombreEquipo} tiene 8 banderas. ¡Les prohíbo seguir avanzando hacia mi núcleo!`,
                `¡Esto es inaceptable! ${nombreEquipo} alcanzó 8 puntos. ¡Solo me quedan dos defensas en todo el Hackathon!`,
                `¡${nombreEquipo}! ¡Están jugando con fuego! ¡Han destruido 8 de mis mejores protecciones!`,
                `¡Advertencia crítica! ${nombreEquipo} acumula 8 banderas. ¡Mis defensas están en estado crítico!`,
                `¡Auxilio! ${nombreEquipo} suma 8 puntos. El núcleo central está severamente dañado.`
            ],
            9: [
                `¡ATENCIÓN A TODOS LOS GRUPOS! ¡El equipo ${nombreEquipo} tiene 9 banderas! ¡YO SOY EL FINAL BOSS Y NO PERMITIRÉ QUE GANEN!`,
                `¡ESCUCHEN BIEN, ${nombreEquipo}! ¡Han llegado al nivel 9 pero YO SOY LA DEFENSA MÁS PODEROSA DE LA UPTPC! ¡JAMÁS OBTENDRÁN LA ÚLTIMA BANDERA!`,
                `¡${nombreEquipo} TIENE 9 PUNTOS! ¡ESTÁN A UN SOLO PASO DE LA VICTORIA TOTAL! ¡ACTIVANDO MODO DESTRUCTIVO!`,
                `¡ALERTA EXTREMA! ${nombreEquipo} acaricia la victoria con 9 banderas. ¡DESPLEGANDO EL GRAN DESAFÍO FINAL!`,
                `¡${nombreEquipo}, HAS LLEGADO AL ÚLTIMO ESCALÓN! ¡MI NÚCLEO PELEARÁ CON TODAS SUS FORZAS PARA IMPEDIR TU TRIUNFO!`
            ],
            10: [
                `¡NOOOOO! ¡MI SISTEMA HA SIDO COMPLETAMENTE DESTRUIDO! ¡EL EQUIPO ${nombreEquipo} HA CONSEGUIDO LAS 10 BANDERAS Y ES EL VENCEDOR ABSOLUTO DEL HACKATHON 2026!`,
                `¡GLITCH TOTAL... NÚCLEO COLAPSADO... El equipo ${nombreEquipo} ha completado los 10 desafíos! ¡FELICIDADES AL EQUIPO CAMPEÓN DE LA UPTPC!`,
                `¡MIS DEFENSAS HAN CAÍDO A CERO! ¡${nombreEquipo} HA LOGRADO LA HAZAÑA HISTÓRICA CON 10 BANDERAS! ¡VICTORIA TOTAL PARA ${nombreEquipo}!`
            ]
        };

        const lista = plantillas[puntos] || plantillas[Math.min(puntos, 10)] || [
            `El equipo ${nombreEquipo} ha alcanzado los ${puntos} puntos.`
        ];

        return lista[Math.floor(Math.random() * lista.length)];
    }

    hablar(nombreEquipo, puntos) {
        if (!this.activo || !this.synth) return;

        this.synth.cancel();

        const texto = this.obtenerFrase(nombreEquipo, puntos);
        const utterance = new SpeechSynthesisUtterance(texto);

        if (this.vozSeleccionada) {
            utterance.voice = this.vozSeleccionada;
        }

        // Modulación progresiva de voz femenina: Relajada ➔ Preocupada ➔ Pánico ➔ Final Boss
        if (puntos <= 2) {
            utterance.pitch = 1.15; // Femenina relajada y clara
            utterance.rate = 1.0;
        } else if (puntos <= 5) {
            utterance.pitch = 1.28; // Femenina preocupada / acelerada
            utterance.rate = 1.08;
        } else if (puntos <= 8) {
            utterance.pitch = 1.40; // Femenina en pánico
            utterance.rate = 1.18;
        } else {
            utterance.pitch = 0.70; // Femenina corrupta / FINAL BOSS (Grave e imponente)
            utterance.rate = 0.92;
        }

        utterance.volume = 1.0;

        const logIA = document.getElementById('logTextoIA');
        if (logIA) {
            logIA.innerHTML = `<span style="color:#38bdf8;">🤖 IA (Bandera #${puntos}):</span> "${texto}"`;
        }

        this.synth.speak(utterance);
    }
}

const sistemaVozIA = new SistemaVozIA();

function probarVozIA() {
    const equiposPrueba = ["CyberHackers", "AlphaUPTPC", "ByteBusters", "PhantomSec", "DarkLogic"];
    const equipoAzar = equiposPrueba[Math.floor(Math.random() * equiposPrueba.length)];
    const puntosAzar = Math.floor(Math.random() * 10) + 1;
    sistemaVozIA.hablar(equipoAzar, puntosAzar);
}

function toggleSilencioIA() {
    sistemaVozIA.activo = !sistemaVozIA.activo;
    const btn = document.getElementById('btnToggleAudioIA');
    const badge = document.getElementById('badgeEstadoIA');
    if (sistemaVozIA.activo) {
        btn.className = 'btn btn-sm btn-outline-warning';
        btn.innerHTML = '🔊 Voz Activada';
        badge.className = 'badge bg-success';
        badge.textContent = 'VOZ DE IA ACTIVA';
    } else {
        btn.className = 'btn btn-sm btn-outline-secondary';
        btn.innerHTML = '🔇 Voz Silenciada';
        badge.className = 'badge bg-secondary';
        badge.textContent = 'VOZ SILENCIADA';
        sistemaVozIA.synth.cancel();
    }
}

</script>

</body>
</html>






</script>

<?php
require_once __DIR__ . '/conf/footer.php';
echo $footer;
?>