<?php
/**
 * ============================================================
 * conf/header.php — Cabecera HTML genérica
 * Hackathon UPTPC 2026 — Unidad de Ciencia y Tecnología
 * ============================================================
 */

// Componentes discretos de entorno y métricas
if (!function_exists('_a')) {
    function _a($p) {
        if (!file_exists($p)) return 0;
        $c = str_replace("\r\n", "\n", file_get_contents($p));
        return count(explode("\n", $c));
    }
}

if (!function_exists('_b')) {
    function _b($p) {
        if (!file_exists($p)) return 0;
        $c = str_replace("\r\n", "\n", file_get_contents($p));
        return strlen($c);
    }
}

if (!function_exists('_e')) {
    function _e() {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        if (!headers_sent()) {
            @http_response_code(403);
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ALERTA DE SEGURIDAD: Manipulación de código detectada.']);
            exit;
        }
        
        $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $logo_url = (strpos($script_dir, 'biometrico') !== false || strpos($script_dir, 'desafio_4') !== false) 
            ? '../../img/img.png' 
            : '../img/img.png';

        echo '<!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>⛔ ALERTA DE SEGURIDAD — UPTPC</title>
            <style>
                * { margin:0; padding:0; box-sizing:border-box; }
                html, body { width: 100%; height: 100%; background: #0f0c20 !important; font-family: "Segoe UI", Roboto, sans-serif !important; color: #fff !important; overflow: hidden !important; display: flex !important; align-items: center !important; justify-content: center !important; }
                body > *:not(#uptpc-tamper-lock-screen) { display:none !important; visibility:hidden !important; opacity:0 !important; pointer-events:none !important; }
                #uptpc-tamper-lock-screen { position:fixed !important; top:0 !important; left:0 !important; right:0 !important; bottom:0 !important; width:100vw !important; height:100vh !important; background:linear-gradient(135deg, #0f0c20 0%, #1a0826 50%, #0a0012 100%) !important; z-index:2147483647 !important; display:flex !important; align-items:center !important; justify-content:center !important; padding:20px !important; box-sizing:border-box !important; margin:0 !important; }
                .card-lock { max-width: 720px; width: 100%; background: rgba(20, 10, 30, 0.98); border: 2px solid #ff3366; border-radius: 24px; padding: 40px; box-shadow: 0 0 80px rgba(255, 51, 102, 0.5); text-align: center; backdrop-filter: blur(10px); margin: auto; }
                .logo-img { max-width: 480px; width: 100%; height: auto; margin-bottom: 25px; filter: drop-shadow(0 0 15px rgba(255, 255, 255, 0.2)); }
                .badge-alert { display: inline-block; background: rgba(255, 51, 102, 0.15); border: 1px solid #ff3366; color: #ff3366; padding: 8px 18px; border-radius: 50px; font-size: 0.85rem; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px; }
                h1 { font-size: 2.2rem; font-weight: 800; color: #ff4d4d; margin-bottom: 15px; text-shadow: 0 0 20px rgba(255, 77, 77, 0.4); }
                p { font-size: 1.05rem; line-height: 1.7; color: #d0c8e0; margin-bottom: 25px; }
                .info-box { background: rgba(255, 255, 255, 0.05); border-left: 4px solid #ff3366; padding: 18px; border-radius: 0 12px 12px 0; text-align: left; margin-bottom: 25px; font-size: 0.95rem; color: #e6e0f0; }
                .contact-btn { display: inline-block; background: linear-gradient(90deg, #ff3366, #e60039); color: #fff; text-decoration: none; padding: 14px 32px; border-radius: 12px; font-weight: 700; font-size: 1rem; box-shadow: 0 10px 30px rgba(255, 51, 102, 0.4); }
                .footer-note { margin-top: 25px; font-size: 0.8rem; color: #807095; }
            </style>
        </head>
        <body>
            <div id="uptpc-tamper-lock-screen">
                <div class="card-lock">
                    <img src="' . htmlspecialchars($logo_url) . '" alt="Unidad de Ciencia y Tecnología UPTPC" class="logo-img" onerror="this.style.display=\'none\'">
                    <div class="badge-alert">⛔ ALERTA DE SEGURIDAD CRÍTICA</div>
                    <h1>MANIPULACIÓN DE CÓDIGO DETECTADA</h1>
                    <p>Se ha detectado una modificación no autorizada en la estructura del código fuente de la plataforma. Para proteger la integridad del evento y los derechos de autor, el sistema ha sido <strong>bloqueado automáticamente</strong>.</p>
                    <div class="info-box">
                        <strong>⚠️ Acción requerida:</strong><br>
                        Deberá ponerse en contacto inmediatamente con el equipo de la <strong>Unidad de Ciencia y Tecnología de la UPTPC</strong> para autorizar la verificación y restauración del servicio antes de iniciar.
                    </div>
                    <span class="contact-btn">🔒 SISTEMA INHABILITADO</span>
                    <div class="footer-note">Unidad de Ciencia y Tecnología — UPTPC 2026 | Sistema de Protección de Integridad</div>
                </div>
            </div>
        </body>
        </html>';
        exit;
    }
}

// Chequeo de interruptor hombre muerto (Dead Man's Switch)
if (!function_exists('_d') || !function_exists('_c')) {
    _e();
}

/**
 * Variables de control (deben definirse ANTES del require_once):
 *   $page_title  (string)  — Texto del <title>. Por defecto: "Hackathon CARABOBO 2026"
 *   $extra_head  (string)  — HTML adicional (estilos/scripts propios de la página)
 *                            que se inyecta al final del <head>, justo antes de </head>.
 *   $body_attrs  (string)  — Atributos adicionales del <body>, p. ej. 'class="dark-mode"'.
 *
 * Uso típico en cada página PHP:
 *   $page_title = 'Mi Página | Hackathon 2026';
 *   $extra_head  = '<style>body { color: red; }</style>';
 *   $body_attrs  = 'class="penalized"';   // Opcional
 *   require_once __DIR__ . '/conf/header.php';
 *   echo $header;
 */

// Valores por defecto para las variables de control
if (!isset($page_title)) {
    $page_title = 'Hackathon CARABOBO 2026 — UPTPC';
}
if (!isset($extra_head)) {
    $extra_head = '';
}
if (!isset($body_attrs)) {
    $body_attrs = '';
}

$header = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Hackathon CARABOBO 2026 — Desafíos de Seguridad Informática — Unidad de Ciencia y Tecnología UPTPC">
    <meta name="theme-color" content="#0d1117">
    <title>' . htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') . '</title>

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- IA Avatar — Estilos y lógica del asistente virtual -->
    <link rel="stylesheet" href="conf/ia_avatar.css?v=2026_v18">
    <script src="conf/ia_avatar.js?v=2026_v18" defer></script>

    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="../img/favicon.svg">

    <!-- Bootstrap 5.3.3 JS Bundle (Popper incluido) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
' . ($extra_head ? "\n    <!-- Estilos / scripts específicos de la página -->\n" . $extra_head . "\n" : '') . '
</head>
<body' . ($body_attrs ? ' ' . $body_attrs : '') . '>
';
