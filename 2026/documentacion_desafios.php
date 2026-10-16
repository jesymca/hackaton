<?php
// ============================================================
// documentacion_desafios.php - Guía y Documentación de Desafíos
// Unidad de Ciencia y Tecnología — UPTPC 2026
// ============================================================
session_start();
require_once __DIR__ . '/conf/functions.php';
// ── Cabecera modular ─────────────────────────────────────────────────────────
$page_title = '📖 Documentación Técnica de Desafíos — Hackathon 2026 UPTPC';
require_once __DIR__ . '/conf/header.php';
echo $header;
?>
<!-- Estilos específicos de la Documentación de Desafíos -->
<style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #090d16 linear-gradient(135deg, #070a10 0%, #0f172a 100%);
            font-family: 'Inter', sans-serif;
            color: #e2e8f0;
            min-height: 100vh;
            padding: 30px 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .header-doc {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            text-align: center;
        }

        .header-doc h1 {
            font-size: 2.3rem;
            font-weight: 900;
            color: #38bdf8;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .header-doc p {
            color: #94a3b8;
            font-size: 1rem;
        }

        .credits {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.85rem;
            color: #cbd5e1;
        }

        .toc {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 35px;
        }

        .toc-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 12px;
        }

        .toc-item {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(56, 189, 248, 0.2);
            padding: 10px 16px;
            border-radius: 10px;
            color: #38bdf8;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toc-item:hover {
            background: rgba(56, 189, 248, 0.15);
            border-color: #38bdf8;
            transform: translateY(-2px);
        }

        .challenge-card {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            transition: border-color 0.3s;
        }

        .challenge-card:hover {
            border-color: rgba(56, 189, 248, 0.4);
        }

        .challenge-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .challenge-num {
            background: #38bdf8;
            color: #0f172a;
            font-weight: 900;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .challenge-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: #f8fafc;
        }

        .section-box {
            margin-bottom: 20px;
        }

        .section-tag {
            font-size: 0.95rem;
            font-weight: 700;
            color: #38bdf8;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .knowledge-list, .alt-list {
            list-style: none;
            padding-left: 0;
        }

        .knowledge-list li, .alt-list li {
            position: relative;
            padding-left: 24px;
            margin-bottom: 8px;
            font-size: 0.93rem;
            color: #cbd5e1;
        }

        .knowledge-list li::before {
            content: "🎓";
            position: absolute;
            left: 0;
        }

        .alt-list li::before {
            content: "🔀";
            position: absolute;
            left: 0;
        }

        code {
            font-family: 'Fira Code', monospace;
            background: rgba(0, 0, 0, 0.5);
            color: #4ade80;
            padding: 2px 8px;
            border-radius: 5px;
            font-size: 0.85rem;
        }

        .nav-back {
            text-align: center;
            margin-top: 40px;
            margin-bottom: 20px;
        }

        .btn-home {
            background: #38bdf8;
            color: #0f172a;
            font-weight: 700;
            padding: 12px 30px;
            border-radius: 12px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-home:hover {
            background: #7dd3fc;
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.4);
        }
</style>
<!-- Google Fonts: Fira Code & Inter -->
<link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600;700&family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">

<div class="container">

    <!-- Header -->
    <div class="header-doc">
        <h1>📖 Guía y Documentación de Desafíos</h1>
        <p>Hackathon 2026 — Manual de Conocimientos, Soluciones y Alternativas Técnicas</p>
        <div class="credits">
            <span>💻 <strong>Programador:</strong> Héctor Marulanda</span>
            <span>👔 <strong>Coordinador:</strong> José Herrera</span>
            <span>🏛️ <strong>Unidad de Ciencia y Tecnología (UPTPC)</strong></span>
        </div>
    </div>

    <!-- Tabla de Contenidos -->
    <div class="toc">
        <div class="toc-title">📌 Índice de Desafíos (1 al 10)</div>
        <div class="toc-grid">
            <a href="#d1" class="toc-item">1. Login Inseguro</a>
            <a href="#d2" class="toc-item">2. Criptografía Épica</a>
            <a href="#d3" class="toc-item">3. Buffer Overflow</a>
            <a href="#d4" class="toc-item">4. Servidor SSH (Kali Linux)</a>
            <a href="#d5" class="toc-item">5. API REST Vulnerable</a>
            <a href="#d6" class="toc-item">6. Esteganografía Forense</a>
            <a href="#d7" class="toc-item">7. Reto de Astucia</a>
            <a href="#d8" class="toc-item">8. Acceso Biométrico</a>
            <a href="#d9" class="toc-item">9. Robo Banco (CSRF)</a>
            <a href="#d10" class="toc-item">10. Gran Desafío Final</a>
        </div>
    </div>

    <!-- DESAFÍO 1 -->
    <div class="challenge-card" id="d1">
        <div class="challenge-header">
            <div class="challenge-title">Desafío 1: Login Inseguro (<code>login_inseguro.php</code>)</div>
            <span class="challenge-num">Reto #1</span>
        </div>
        
        <div class="section-box">
            <div class="section-tag">🧠 Conocimientos Requeridos</div>
            <ul class="knowledge-list">
                <li>Estructura básica de documentos HTML y etiquetas del frontend.</li>
                <li>Uso de herramientas de inspección del navegador (DevTools / F12).</li>
                <li>Comprensión de comentarios en HTML (<code>&lt;!-- comentario --&gt;</code>) y riesgos de seguridad por revelar datos sensibles.</li>
            </ul>
        </div>

        <div class="section-box">
            <div class="section-tag">🛠️ Solución Oficial</div>
            <p style="font-size:0.92rem; color:#cbd5e1;">Abre la página, presiona <strong>F12</strong> o clic derecho ➔ <em>Ver código fuente de la página</em>. Revisa las primeras líneas de código HTML para encontrar las credenciales comentadas: <code>admin</code> / <code>passwordsegura</code>. Inicia sesión para obtener la clave.</p>
        </div>

        <div class="section-box">
            <div class="section-tag">🔀 Alternativas que pueden tomar los participantes</div>
            <ul class="alt-list">
                <li><strong>Método 1 (DevTools Inspector):** Inspeccionar el DOM con F12 en la pestaña *Elements*.</li>
                <li><strong>Método 2 (Ver Código Fuente):** Clic derecho ➔ *View Page Source* (Ctrl + U).</li>
                <li><strong>Método 3 (cURL / HTTP):** Ejecutar <code>curl -s http://localhost/hackaton/2026/login_inseguro.php</code> en consola y buscar los comentarios.</li>
            </ul>
        </div>
    </div>

    <!-- DESAFÍO 2 -->
    <div class="challenge-card" id="d2">
        <div class="challenge-header">
            <div class="challenge-title">Desafío 2: Criptografía Épica (<code>crypto.php</code>)</div>
            <span class="challenge-num">Reto #2</span>
        </div>
        
        <div class="section-box">
            <div class="section-tag">🧠 Conocimientos Requeridos</div>
            <ul class="knowledge-list">
                <li>Conceptos fundamentales de cifrado y codificación (Base64, sustitución, ROT13 o secuencias).</li>
                <li>Reconocimiento de patrones de texto y formatos de cadenas hexadecimales/alfanuméricas.</li>
                <li>Manejo de herramientas de decodificación online o scripts.</li>
            </ul>
        </div>

        <div class="section-box">
            <div class="section-tag">🛠️ Solución Oficial</div>
            <p style="font-size:0.92rem; color:#cbd5e1;">Analiza el bloque cifrado presentado en la consola Cyberpunk. Aplica 10 rondas sucesivas de decodificación Base64 hasta obtener la clave original: <code>H4CK4TH0N_3P1C_D3CRYPT10N</code>. Al enviarla en el formulario (ya sea como texto plano o en formato <code>FLAG{...}</code>), obtendrás la bandera oficial: <code>FLAG{CRYPTO_MASTER}</code>.</p>
        </div>

        <div class="section-box">
            <div class="section-tag">🔀 Alternativas que pueden tomar los participantes</div>
            <ul class="alt-list">
                <li><strong>Método 1 (CyberChef / Web Decoders):</strong> Usar CyberChef agregando múltiples operaciones de <em>From Base64</em> consecutivas.</li>
                <li><strong>Método 2 (Python Script):</strong> Escribir un pequeño bucle en Python con <code>base64.b64decode()</code> hasta obtener texto legible.</li>
            </ul>
        </div>
    </div>

    <!-- DESAFÍO 3 -->
    <div class="challenge-card" id="d3">
        <div class="challenge-header">
            <div class="challenge-title">Desafío 3: Buffer Overflow Simulado (<code>challenge_buffer_overflow.php</code>)</div>
            <span class="challenge-num">Reto #3</span>
        </div>
        
        <div class="section-box">
            <div class="section-tag">🧠 Conocimientos Requeridos</div>
            <ul class="knowledge-list">
                <li>Conceptos de arquitectura de computadoras (Memoria Stack, Registros EBP, EIP y Puntero de Retorno).</li>
                <li>Comprensión de desbordamiento de memoria (Buffer Overflow) y cálculo de offsets.</li>
            </ul>
        </div>

        <div class="section-box">
            <div class="section-tag">🛠️ Solución Oficial</div>
            <p style="font-size:0.92rem; color:#cbd5e1;">El programa asigna un buffer de 64 bytes. Para sobrescribir el registro EIP con la dirección de <code>flag_secreta()</code>, se deben enviar 64 bytes de relleno + 4 bytes para EBP (total 68 bytes basura) y finalmente la cadena <code>FLAG_SECRETA</code> o la dirección <code>f1e2d3c4</code>.</p>
        </div>

        <div class="section-box">
            <div class="section-tag">🔀 Alternativas que pueden tomar los participantes</div>
            <ul class="alt-list">
                <li><strong>Método 1 (Payload Basura):** Escribir 68 letras 'A' seguidas de <code>FLAG_SECRETA</code>.</li>
                <li><strong>Método 2 (Dirección Hexadecimal):** Escribir 68 caracteres basura seguidos de <code>f1e2d3c4</code>.</li>
            </ul>
        </div>
    </div>

    <!-- DESAFÍO 4 -->
    <div class="challenge-card" id="d4">
        <div class="challenge-header">
            <div class="challenge-title">Desafío 4: Consola de Servidor Web Kali Linux SSH (<code>desafio4.php</code>)</div>
            <span class="challenge-num">Reto #4</span>
        </div>
        
        <div class="section-box">
            <div class="section-tag">🧠 Conocimientos Requeridos</div>
            <ul class="knowledge-list">
                <li>Uso de consola Linux, comandos de navegación y lectura (<code>ls</code>, <code>cd</code>, <code>cat</code>).</li>
                <li>Conexiones remotas SSH y elevación de privilegios en entornos UNIX (<code>sudo su</code>).</li>
            </ul>
        </div>

        <div class="section-box">
            <div class="section-tag">🛠️ Solución Oficial</div>
            <p style="font-size:0.92rem; color:#cbd5e1;">Abre la terminal interactiva estilo Kali Linux en <code>desafio4.php</code>. Conéctate al servidor mediante <code>ssh invitado@server-hackaton.uptpc.edu.ve</code>. Explora los directorios confidenciales navegando a <code>/etc/secret_vault</code>, eleva privilegios con <code>sudo su</code> y visualiza <code>cat /etc/secret_vault/flag_confidencial.txt</code> o recupera los sectores para obtener la bandera: <code>FLAG{SERVER_TERMINAL_MASTER_2026}</code>.</p>
        </div>

        <div class="section-box">
            <div class="section-tag">🔀 Alternativas que pueden tomar los participantes</div>
            <ul class="alt-list">
                <li><strong>Método 1 (Exploración de Sectores):</strong> Leer los archivos de los 3 sectores del sistema y enviar la clave en el panel inferior.</li>
                <li><strong>Método 2 (Elevación Sudo):</strong> Ejecutar <code>sudo su</code> en la sesión SSH y leer directamente el archivo restringido del root.</li>
            </ul>
        </div>
    </div>

    <!-- DESAFÍO 5 -->
    <div class="challenge-card" id="d5">
        <div class="challenge-header">
            <div class="challenge-title">Desafío 5: API REST Vulnerable (<code>api_vulnerable.php</code>)</div>
            <span class="challenge-num">Reto #5</span>
        </div>
        
        <div class="section-box">
            <div class="section-tag">🧠 Conocimientos Requeridos</div>
            <ul class="knowledge-list">
                <li>Conceptos de arquitectura REST, métodos HTTP (GET, POST, PUT, DELETE) y formato JSON.</li>
                <li>Pruebas de inyección lógica / SQL en payloads JSON.</li>
            </ul>
        </div>

        <div class="section-box">
            <div class="section-tag">🛠️ Solución Oficial</div>
            <p style="font-size:0.92rem; color:#cbd5e1;">Envía una petición POST al endpoint de <code>login</code> inyectando comillas simples o un condicional <code>' OR '1'='1</code> en el campo del nombre para forzar la respuesta de la API con la bandera <code>FLAG{API_HACKED}</code>.</p>
        </div>

        <div class="section-box">
            <div class="section-tag">🔀 Alternativas que pueden tomar los participantes</div>
            <ul class="alt-list">
                <li><strong>Método 1 (API Lab Frontend):</strong> Usar la interfaz web interactiva provista en <code>api_lab.php</code> para enviar las peticiones sin disparar el honeypot.</li>
                <li><strong>Método 2 (Navegador DevTools):</strong> Ejecutar la petición <code>fetch()</code> directamente desde la consola JavaScript del navegador.</li>
            </ul>
        </div>
    </div>

    <!-- DESAFÍO 6 -->
    <div class="challenge-card" id="d6">
        <div class="challenge-header">
            <div class="challenge-title">Desafío 6: Esteganografía Forense (<code>estego_inicio.php</code>)</div>
            <span class="challenge-num">Reto #6</span>
        </div>
        
        <div class="section-box">
            <div class="section-tag">🧠 Conocimientos Requeridos</div>
            <ul class="knowledge-list">
                <li>Informática forense básica y técnicas de esteganografía digital (LSB - Least Significant Bit).</li>
                <li>Uso de herramientas de análisis espectral / de bits en imágenes o scripts de procesamiento (Python PIL).</li>
            </ul>
        </div>

        <div class="section-box">
            <div class="section-tag">🛠️ Solución Oficial</div>
            <p style="font-size:0.92rem; color:#cbd5e1;">Descarga la imagen del dossier <code>hacker.png</code> y extrae los bits menos significativos (LSB) de los canales RGB. El mensaje oculto extraído es: <code>el ataque sera al amanecer</code>. Introdúcelo en el formulario de descifrado del expediente para obtener la bandera oficial: <code>FLAG{STEGANOGRAPHY_SECRET}</code>.</p>
        </div>

        <div class="section-box">
            <div class="section-tag">🔀 Alternativas que pueden tomar los participantes</div>
            <ul class="alt-list">
                <li><strong>Método 1 (StegOnline / Herramientas Web):</strong> Cargar <code>hacker.png</code> en StegOnline o decodificadores LSB online.</li>
                <li><strong>Método 2 (Script en Python):</strong> Escribir un script con la biblioteca <code>PIL/Pillow</code> que itere los bits LSB de cada píxel hasta formar bytes ASCII legibles.</li>
            </ul>
        </div>
    </div>

    <!-- DESAFÍO 7 -->
    <div class="challenge-card" id="d7">
        <div class="challenge-header">
            <div class="challenge-title">Desafío 7: Reto de Astucia (<code>index.php</code>)</div>
            <span class="challenge-num">Reto #7</span>
        </div>
        
        <div class="section-box">
            <div class="section-tag">🧠 Conocimientos Requeridos</div>
            <ul class="knowledge-list">
                <li>Protocolo HTTP y estructura de encabezados de respuesta (Response Headers).</li>
                <li>Inspección de código fuente DOM y eventos interactivos ocultos.</li>
            </ul>
        </div>

        <div class="section-box">
            <div class="section-tag">🛠️ Solución Oficial</div>
            <p style="font-size:0.92rem; color:#cbd5e1;">Este desafío recompensa la observación y ofrece dos vías de resolución válidas (ambas aceptadas por el validador):<br>
            <strong>Vía A (Cabeceras HTTP):</strong> Abre DevTools (F12) en <code>index.php</code> ➔ pestaña Red (Network) ➔ inspecciona los Response Headers del documento principal donde hallarás <code>X-Secret-Flag: FLAG{HTTP_HEADER_SECRET}</code>.<br>
            <strong>Vía B (Elemento Interactivo):</strong> En la pantalla de acceso de <code>index.php</code>, haz clic sobre el texto '¿Olvidaste tu contraseña?' para detonar la alerta con <code>FLAG{LOGIN_CLICKABLE_SECRET}</code>.</p>
        </div>

        <div class="section-box">
            <div class="section-tag">🔀 Alternativas que pueden tomar los participantes</div>
            <ul class="alt-list">
                <li><strong>Método 1 (DevTools Network):</strong> Pestaña Network ➔ Seleccionar <code>index.php</code> ➔ Response Headers.</li>
                <li><strong>Método 2 (Inspección DOM):</strong> Buscar atributos <code>onclick</code> o funciones JavaScript en el formulario de inicio.</li>
                <li><strong>Método 3 (cURL Headers):</strong> Ejecutar <code>curl -sI -H "User-Agent: Mozilla/5.0" http://localhost/hackaton/2026/index.php</code>.</li>
            </ul>
        </div>
    </div>

    <!-- DESAFÍO 8 -->
    <div class="challenge-card" id="d8">
        <div class="challenge-header">
            <div class="challenge-title">Desafío 8: Acceso Biométrico (<code>biometrico/biometrico.php</code>)</div>
            <span class="challenge-num">Reto #8</span>
        </div>
        
        <div class="section-box">
            <div class="section-tag">🧠 Conocimientos Requeridos</div>
            <ul class="knowledge-list">
                <li>Lógica combinatoria y reconocimiento de patrones.</li>
                <li>Análisis de respuestas del servidor frente a intentos fallidos.</li>
            </ul>
        </div>

        <div class="section-box">
            <div class="section-tag">🛠️ Solución Oficial</div>
            <p style="font-size:0.92rem; color:#cbd5e1;">Analiza las pistas visuales y de código del sistema biométrico para inferir el orden correcto del patrón de puntos o bypass del estado de autenticación.</p>
        </div>

        <div class="section-box">
            <div class="section-tag">🔀 Alternativas que pueden tomar los participantes</div>
            <ul class="alt-list">
                <li><strong>Método 1 (Deducción Lógica):** Probar las secuencias basadas en las pistas del registro de intentos.</li>
                <li><strong>Método 2 (Inspección JS):** Auditar los scripts en el navegador para identificar la lógica del patrón.</li>
            </ul>
        </div>
    </div>

    <!-- DESAFÍO 9 -->
    <div class="challenge-card" id="d9">
        <div class="challenge-header">
            <div class="challenge-title">Desafío 9: Robo Banco y CSRF (<code>robo_banco.php</code>)</div>
            <span class="challenge-num">Reto #9</span>
        </div>
        
        <div class="section-box">
            <div class="section-tag">🧠 Conocimientos Requeridos</div>
            <ul class="knowledge-list">
                <li>Manipulación de parámetros HTTP GET/POST y vulnerabilidades de lógica de negocio.</li>
                <li>Comprensión de ataques CSRF (Cross-Site Request Forgery).</li>
            </ul>
        </div>

        <div class="section-box">
            <div class="section-tag">🛠️ Solución Oficial</div>
            <p style="font-size:0.92rem; color:#cbd5e1;">Inicia sesión con la cuenta <code>hacker</code> / <code>hack123</code>. Inicia una transferencia. En el modal de confirmación, inspecciona la URL de confirmación que tiene <code>origen=hacker&destino=mrbeast</code> y cámbiala en la barra de direcciones por <code>origen=mrbeast&destino=hacker</code> para revertir la transferencia y vaciar la cuenta de Mr. Beast.</p>
        </div>

        <div class="section-box">
            <div class="section-tag">🔀 Alternativas que pueden tomar los participantes</div>
            <ul class="alt-list">
                <li><strong>Método 1 (Edición de URL):** Invertir manualmente los valores de <code>origen</code> y <code>destino</code> en la URL del navegador.</li>
                <li><strong>Método 2 (Modificación con DevTools):** Editar los valores de los atributos o campos antes del envío.</li>
            </ul>
        </div>
    </div>

    <!-- DESAFÍO 10 -->
    <div class="challenge-card" id="d10">
        <div class="challenge-header">
            <div class="challenge-title">Desafío 10: Gran Desafío Final - Código Dinámico (<code>challenge_dynamic.php</code>)</div>
            <span class="challenge-num">Reto #10</span>
        </div>
        
        <div class="section-box">
            <div class="section-tag">🧠 Conocimientos Requeridos</div>
            <ul class="knowledge-list">
                <li>Inspección deResponse Headers de red.</li>
                <li>Manejo de codificación Base64.</li>
                <li>Navegación por endpoints dinámicos con tiempo de expiración (2 minutos).</li>
            </ul>
        </div>

        <div class="section-box">
            <div class="section-tag">🛠️ Solución Oficial</div>
            <p style="font-size:0.92rem; color:#cbd5e1;">1) Abre DevTools (F12) -> pestaña Network y busca el header <code>X-Final-Challenge-Gateway</code>. 2) Abre la URL resultante en una nueva pestaña (<code>?api_gateway=v2_token_endpoint&auth=UPTPC-2026-FINAL</code>). 3) Copia el campo <code>payload</code> codificado en Base64. 4) Decodifica el Base64 e ingresa el token de 16 caracteres antes de los 120 segundos.</p>
        </div>

        <div class="section-box">
            <div class="section-tag">🔀 Alternativas que pueden tomar los participantes</div>
            <ul class="alt-list">
                <li><strong>Método 1 (Navegador Nativo):** Abrir la URL en una nueva pestaña y decodificar el Base64 en cualquier herramienta web.</li>
                <li><strong>Método 2 (Consola cURL):** Ejecutar <code>curl "http://localhost/hackaton/2026/challenge_dynamic.php?api_gateway=v2_token_endpoint&auth=UPTPC-2026-FINAL"</code>.</li>
                <li><strong>Método 3 (Script de Python):** Automatizar la petición, decodificación y envío en un script de Python con la librería <code>requests</code>.</li>
            </ul>
        </div>
    </div>

    <div class="nav-back">
        <a href="index.php" class="btn-home">🏠 Regresar al Dashboard Principal</a>
    </div>

</div>

<?php
require_once __DIR__ . '/conf/footer.php';
echo $footer;
?>
