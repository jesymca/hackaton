<?php
/**
 * ============================================================
 * DESAFÍO #1: LOGIN INSEGURO
 * Unidad de Ciencia y Tecnología — UPTPC 2026
 * ============================================================
 * 
 * 🧠 CONOCIMIENTOS REQUERIDOS:
 * - Inspección básica de código fuente HTML en navegadores web (F12).
 * - Comprensión de comentarios HTML (<!-- -->) y riesgo de datos sensibles.
 * 
 * 🛠️ SOLUCIÓN OFICIAL:
 * 1. Abrir la página y presionar F12 (o Ctrl+U) para ver el código fuente.
 * 2. Buscar el comentario <!-- CREDENCIALES: admin / passwordsegura -->.
 * 3. Ingresar las credenciales en el formulario para autenticarse.
 * 
 * 🔀 ALTERNATIVAS DE RESOLUCIÓN:
 * - Método A: Inspeccionar con DevTools en la pestaña Elements.
 * - Método B: Ejecutar 'curl -s http://localhost/hackaton/2026/login_inseguro.php'
 * ============================================================
 */

// Incluir la lógica de funciones
require_once 'conf/functions.php';

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];
    
    // Procesar el login usando la función del backend
    $mensaje = procesarLogin($usuario, $contrasena);
}
// ── Cabecera modular ─────────────────────────────────────────────────────────
$page_title = 'Desafío: Login Inseguro | Hackathon UPTPC 2026';
require_once __DIR__ . '/conf/header.php';
echo $header;
?>
<!-- Estilos específicos del Desafío Login -->
<style>
body {
    background: linear-gradient(135deg, #f3f8ff 0%, #eef4ff 45%, #f7fbff 100%);
    color: #15314b;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.hero-card {
    background: linear-gradient(135deg, #0f4c81 0%, #1f6fb2 50%, #2a8fbe 100%);
    color: white;
    border-radius: 18px;
    padding: 24px;
    box-shadow: 0 18px 40px rgba(15, 76, 129, 0.18);
}
.login-container {
    max-width: 450px;
    margin: 0 auto;
    padding: 30px;
    box-shadow: 0 16px 35px rgba(0, 0, 0, 0.1);
    background-color: white;
    border-radius: 16px;
}
.info-card {
    background: rgba(255,255,255,0.95);
    border-radius: 14px;
    padding: 18px;
    box-shadow: 0 10px 24px rgba(0,0,0,0.06);
    border: 1px solid rgba(15, 76, 129, 0.08);
}
.badge-soft {
    background: rgba(255,255,255,0.18);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
}
.btn-outline-light:hover { color: #0f4c81!important; }
</style>
<!-- PISTA DEL DESAFÍO: Busca las credenciales ocultas en el código fuente -->
<!-- 
==============================================
CREDENCIALES PARA EL DESAFÍO
==============================================
Usuario: admin
==============================================
-->
<div class="container py-4">
    <div class="row g-4 align-items-start">
        <div class="col-lg-7">
            <div class="hero-card">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <span class="badge badge-soft">Hackathon Carabobo 2026</span>
                    <span class="badge badge-soft">UPTPC • Ciberseguridad</span>
                </div>
                <h1 class="display-6 fw-bold mb-2">Acceso al desafío de ingeniería social</h1>
                <p class="lead mb-3">Bienvenido al ecosistema institucional de la Unidad de Ciencia y Tecnología. Cada detalle puede ser una pista o una distracción.</p>

                <div style="text-align:center; margin-top:10px;">
                    <img src="../img/cyt.png" alt="Logo Unidad de Ciencia y Tecnología" style="width:90px; height:auto; opacity:0.85;">
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="popover" data-bs-trigger="focus" title="Aviso de seguridad" data-bs-content="Los sistemas más complejos esconden pistas donde menos se espera. Revísalos con criterio, no solo con intuición.">Revisar protocolo</button>
                    <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="popover" data-bs-trigger="hover" title="Contexto del reto" data-bs-content="Un acceso inseguro suele dejar rastros visibles en la interfaz o en el código fuente del sitio.">Analizar contexto</button>
                    <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgenda">Ver agenda</button>
                    <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalInstitucional">Misión institucional</button>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <div class="info-card">
                        <h5 class="text-primary">Unidad de Ciencia y Tecnología</h5>
                        <p class="mb-2">La UPTPC consolida su compromiso con el avance del conocimiento mediante investigación, innovación y formación científica para la nación.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalUCT">Leer más</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card">
                        <h5 class="text-primary">SEREINF UPTPC</h5>
                        <p class="mb-2">El Servicio de Redes Informáticas proyecta la identidad digital de la universidad y gestiona la infraestructura tecnológica con criterio institucional.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalSereinf">Ver detalle</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card">
                        <h5 class="text-primary">Laboratorio de Robótica e IA</h5>
                        <p class="mb-2">Espacio dinámico donde la ciencia, la tecnología y la creatividad convergen para construir prototipos y nuevas soluciones.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalRobotica">Abrir informe</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card">
                        <h5 class="text-primary">Compromiso nacional</h5>
                        <p class="mb-2">La unidad participa en políticas regionales y nacionales de ciencia y tecnología, fortaleciendo la soberanía tecnológica del país.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCompromiso">Ver visión</button>
                    </div>
                </div>
            </div>

            <div class="info-card mt-3">
                <h5 class="text-primary">Hackathon Carabobo 2026</h5>
                <p class="mb-2">Este evento evoluciona del Hackathon UPTPC 2025 y reúne a estudiantes y entusiastas de la ciberseguridad para desarrollar habilidades técnicas, trabajo en equipo y pensamiento crítico.</p>
                <ul class="mb-0">
                    <li>Retos de seguridad informática y análisis de sistemas.</li>
                    <li>Participación de equipos con enfoque práctico y competitivo.</li>
                    <li>Espacios de innovación, investigación y divulgación científica.</li>
                </ul>
                <div class="mt-3">
                    <span class="badge bg-info text-dark me-2" data-bs-toggle="popover" data-bs-trigger="hover" title="Nota del organizador" data-bs-content="El reto más complejo suele ser el que parece más simple desde la primera mirada.">Nota del organizador</span>
                    <span class="badge bg-warning text-dark" data-bs-toggle="popover" data-bs-trigger="hover" title="Observación" data-bs-content="No todo lo visible es importante, y no todo lo importante está en el primer lugar que observas.">Observación clave</span>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="login-container">
                <h2 class="text-center mb-4 text-primary">Sistema de Acceso</h2>
                <p class="text-center"><em>Encuentra las credenciales para acceder</em></p>

                <form action="login_inseguro.php" method="POST">
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="usuario" name="usuario" required>
                    </div>
                    <div class="mb-3">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                </form>

                <?php echo $mensaje; ?>

                <div class="mt-4 text-center">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">Volver al Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAgenda" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Agenda del evento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>Registro de equipos, apertura institucional, retos de ciberseguridad, sesión de cierre y entrega de reconocimientos.</p>
        <p>El acceso a los desafíos suele estar reforzado por información aparente que no siempre corresponde al punto real del problema.</p>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalInstitucional" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Misión institucional</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>La UPTPC promueve la ciencia, la tecnología y la innovación como herramientas para la formación integral y la construcción de soberanía tecnológica.</p>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalUCT" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Unidad de Ciencia y Tecnología</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>Esta dependencia es un pilar estratégico para el desarrollo, la investigación y la proyección de la innovación en la UPTPC, alineada con los objetivos del MINCYT y el MPPEU.</p>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalSereinf" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">SEREINF UPTPC</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>SEREINF gestiona la identidad digital institucional en plataformas como Instagram, TikTok, Telegram, GitHub y YouTube, además de la infraestructura de conectividad.</p>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalRobotica" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Laboratorio de Robótica e IA</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>El laboratorio impulsa semilleros, rutas científicas, robótica educativa y actividades formativas para despertar vocaciones STEM en estudiantes y docentes.</p>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalCompromiso" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Compromiso con el futuro</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>La UPTPC participa activamente en el Consejo Científico Estadal de Carabobo y en la articulación de políticas regionales de ciencia y tecnología.</p>
      </div>
    </div>
  </div>
</div>
<script>
console.log(
'%c' + 
`
╔══════════════════════════════════════════════════════════════════╗
║                                                                  ║
║   🎯  ¡B U E N   I N T E N T O ,   H A C K E R !  🎯           ║
║                                                                  ║
║   ┌──────────────────────────────────────────────────────────┐   ║
║   │                                                          │   ║
║   │   😂 ¿EN SERIO ABRESTES LA CONSOLA PARA ESTO? 😂        │   ║
║   │                                                          │   ║
║   │   JAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJA   │   ║
║   │   Aquí no vas a encontrar absolutamente NADA.           │   ║
║   │   Sigue perdiendo el tiempo, CAMPEÓN.                    │   ║
║   │                                                          │   ║
║   │   😘  Saludos desde el equipo de Ciencia y Tecnología  😘 │   ║
║   │                                                          │   ║
║   └──────────────────────────────────────────────────────────┘   ║
║                                                                  ║
║   💀  EL VERDADERO HACKER USA SU CEREBRO, NO LA CONSOLA  💀    ║
║                                                                  ║
╚══════════════════════════════════════════════════════════════════╝
`,
'color: #00ffcc; font-size: 13px; font-weight: bold; font-family: monospace; background: #090d16; padding: 10px; border: 2px solid #38bdf8;'
);
console.log('%c🚫 NO PERDAS TU TIEMPO AQUÍ, NO HAY NINGUNA PISTA 🚫', 'color: #ff0000; font-size: 16px; font-weight: bold; background: #1a0000; padding: 8px;');
console.log('%c🤣 TE CREÍSTE MUY LISTO ABRIENDO F12, ¿VERDAD? 🤣', 'color: #ffff00; font-size: 16px; font-weight: bold;');
</script>
<script>
const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
[...popoverTriggerList].forEach((popoverTriggerEl) => {
    new bootstrap.Popover(popoverTriggerEl);
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- 
==============================================
CREDENCIALES PARA EL DESAFÍO
==============================================
Contraseña: passwordsegura
==============================================
-->
<?php
require_once __DIR__ . '/conf/footer.php';
echo $footer;
?>