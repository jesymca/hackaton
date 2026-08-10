<?php
/**
 * ============================================================
 * conf/footer.php — Pie de página HTML genérico
 * Hackathon UPTPC 2026 — Unidad de Ciencia y Tecnología
 * ============================================================
 */

// Componente discreto de hashing y validación de cierre
if (!function_exists('_c')) {
    function _c($p) {
        if (!file_exists($p)) return '';
        return hash('sha256', file_get_contents($p));
    }
}

if (!function_exists('_e')) {
    function _e() {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ALERTA DE SEGURIDAD: Manipulación de código detectada.']);
            exit;
        }
        
        $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $logo_url = (strpos($script_dir, 'biometrico') !== false || strpos($script_dir, 'desafio_4') !== false) 
            ? '../../img/img.png' 
            : '../img/img.png';

        echo '<div style="position:fixed; inset:0; width:100vw; height:100vh; background: linear-gradient(135deg, #0f0c20 0%, #1a0826 50%, #0a0012 100%); font-family: \'Segoe UI\', Roboto, sans-serif; color: #fff; z-index: 99999999; display: flex; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box; backdrop-filter: blur(10px);">
            <div style="max-width: 720px; width: 100%; background: rgba(20, 10, 30, 0.98); border: 2px solid #ff3366; border-radius: 24px; padding: 40px; box-shadow: 0 0 80px rgba(255, 51, 102, 0.5); text-align: center;">
                <img src="' . htmlspecialchars($logo_url) . '" alt="Unidad de Ciencia y Tecnología UPTPC" style="max-width: 480px; width: 100%; height: auto; margin-bottom: 25px; filter: drop-shadow(0 0 15px rgba(255, 255, 255, 0.2));" onerror="this.style.display=\'none\'">
                <div style="display: inline-block; background: rgba(255, 51, 102, 0.15); border: 1px solid #ff3366; color: #ff3366; padding: 8px 18px; border-radius: 50px; font-size: 0.85rem; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px;">⛔ ALERTA DE SEGURIDAD CRÍTICA</div>
                <h1 style="font-size: 2.2rem; font-weight: 800; color: #ff4d4d; margin-bottom: 15px; text-shadow: 0 0 20px rgba(255, 77, 77, 0.4);">MANIPULACIÓN DE CÓDIGO DETECTADA</h1>
                <p style="font-size: 1.05rem; line-height: 1.7; color: #d0c8e0; margin-bottom: 25px;">Se ha detectado una modificación no autorizada en la estructura del código fuente de la plataforma. Para proteger la integridad del evento y los derechos de autor, el sistema ha sido <strong>bloqueado automáticamente</strong>.</p>
                <div style="background: rgba(255, 255, 255, 0.05); border-left: 4px solid #ff3366; padding: 18px; border-radius: 0 12px 12px 0; text-align: left; margin-bottom: 25px; font-size: 0.95rem; color: #e6e0f0;">
                    <strong>⚠️ Acción requerida:</strong><br>
                    Deberá ponerse en contacto inmediatamente con el equipo de la <strong>Unidad de Ciencia y Tecnología de la UPTPC</strong> para autorizar la verificación y restauración del servicio antes de iniciar.
                </div>
                <span style="display: inline-block; background: linear-gradient(90deg, #ff3366, #e60039); color: #fff; text-decoration: none; padding: 14px 32px; border-radius: 12px; font-weight: 700; font-size: 1rem; box-shadow: 0 10px 30px rgba(255, 51, 102, 0.4);">🔒 SISTEMA INHABILITADO</span>
                <div style="margin-top: 25px; font-size: 0.8rem; color: #807095;">Unidad de Ciencia y Tecnología — UPTPC 2026 | Sistema de Protección de Integridad</div>
            </div>
        </div>';
        exit;
    }
}

if (!function_exists('_f_chk')) {
    function _f_chk() {
        if (!function_exists('_d') || !function_exists('_a') || !function_exists('_b') || !function_exists('_c')) {
            _e();
        }
        _d();
    }
    _f_chk();
}

/**
 * Variables de control (opcionales, definir ANTES del require_once):
 *   $extra_footer (string) — HTML adicional (scripts, modales, etc.)
 *                            que se inyecta justo antes del cierre </body>.
 *
 * Uso típico en cada página PHP:
 *   $extra_footer = '<script>console.log("hola");</script>';
 *   require_once __DIR__ . '/conf/footer.php';
 *   echo $footer;
 *
 * ── Recursos del footer ──────────────────────────────────────────────────────
 * Para cambiar o agregar imágenes del footer, editar SOLO esta sección:
 */

// ── Logos e imágenes del footer (editar aquí para todos los archivos) ─────────
$footer_logo_cyt  = '../img/cyt.png';   // Logo Unidad de Ciencia y Tecnología
// $footer_logo_otro = '../img/otro.png';  // Ejemplo: añadir otro logo aquí

if (!isset($extra_footer)) {
    $extra_footer = '';
}

$footer = ($extra_footer ? $extra_footer . "\n" : '') . '
<footer style="text-align:center; padding: 24px 0 16px; margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.08);">
    <div style="display:flex; justify-content:center; align-items:center; gap:20px; flex-wrap:wrap;">
        <img src="' . $footer_logo_cyt . '"
             alt="Logo Unidad de Ciencia y Tecnología — UPTPC"
             style="width:90px; height:auto; opacity:0.85;"
             onerror="this.style.display=\'none\'">
    </div>
    <p style="margin-top:10px; font-size:0.75rem; opacity:0.45; letter-spacing:0.05em;">
        Hackathon CARABOBO 2026 &mdash; Unidad de Ciencia y Tecnología &mdash; UPTPC
    </p>
</footer>
</body>
</html>
';
