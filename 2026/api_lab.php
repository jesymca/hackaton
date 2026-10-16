<?php
session_start();
require_once __DIR__ . '/conf/functions.php';
// ── Cabecera modular ─────────────────────────────────────────────────────────
$page_title = 'API Lab — Hackathon UPTPC 2026';
require_once __DIR__ . '/conf/header.php';
echo $header;
?>
<!-- Estilos específicos del Desafío API Lab -->
<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0a0a0f;
            font-family: 'Courier New', monospace;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #0f0;
            font-size: 1.8rem;
        }

        .header p {
            color: #ffffff;
            margin-top: 10px;
        }

        .url-bar {
            background: #000;
            border: 1px solid #0f0;
            padding: 12px;
            margin-bottom: 30px;
            text-align: center;
            word-break: break-all;
        }

        .url-bar span {
            color: #0f0;
            font-size: 12px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .card {
            background: #0f0f14;
            border: 1px solid #222;
            border-radius: 8px;
            overflow: hidden;
        }

        .card:hover {
            border-color: #0f0;
        }

        .card-header {
            background: #14141c;
            padding: 12px 15px;
            border-bottom: 1px solid #222;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .method {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }

        .method.get { background: #0a6; color: #000; }
        .method.post { background: #f90; color: #000; }
        .method.put { background: #ff0; color: #000; }
        .method.delete { background: #f00; color: #fff; }

        .endpoint {
            color: #0f0;
            font-size: 12px;
            background: #000;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .card-body {
            padding: 15px;
        }

        .desc {
            color: #ffffff;
            font-size: 11px;
            margin-bottom: 15px;
        }

        input {
            width: 100%;
            background: #000;
            border: 1px solid #333;
            color: #0f0;
            padding: 8px;
            font-family: monospace;
            margin: 5px 0;
        }

        button {
            width: 100%;
            background: #1a1a2a;
            border: 1px solid #0f0;
            color: #0f0;
            padding: 8px;
            font-family: monospace;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background: #0f0;
            color: #000;
        }

        .response {
            background: #000;
            border: 1px solid #333;
            padding: 10px;
            margin-top: 12px;
            font-size: 11px;
            white-space: pre-wrap;
            word-break: break-all;
            color: #0f0;
            max-height: 150px;
            overflow: auto;
        }

        .hint-panel {
            margin-top: 30px;
            background: #0a0a12;
            border: 1px dashed #444;
            border-radius: 8px;
            overflow: hidden;
        }

        .hint-header {
            background: #14141c;
            padding: 12px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #ff0;
            font-size: 13px;
        }

        .hint-header:hover {
            background: #1a1a24;
        }

        .hint-content {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .hint-panel.open .hint-content {
            max-height: 300px;
            padding: 20px;
        }

        .hint-content p {
            color: #ffffff;
            font-size: 12px;
            margin: 8px 0;
            border-left: 2px solid #ff0;
            padding-left: 12px;
        }

        .flag-reveal {
            margin-top: 30px;
            padding: 20px;
            background: #0a1a0a;
            border: 2px solid gold;
            text-align: center;
            display: none;
        }

        .flag-reveal span {
            color: gold;
            font-weight: bold;
            font-size: 18px;
        }

        .btn-volver {
            margin-top: 30px;
            background: #1a0000;
            border-color: #f00;
            color: #f66;
        }

        .btn-volver:hover {
            background: #f00;
            color: #fff;
        }

        footer {
            text-align: center;
            margin-top: 30px;
            color: #ffffff;
            font-size: 10px;
        }
</style>

<div class="container">
    <div class="header">
        <h1>>_ API_LAB</h1>
        <p>interfaz de pruebas de la unidad de ciencia y tecnología</p>
    </div>

    <!-- Panel de Misión y Objetivo del Desafío -->
    <div style="background:#0a0d18; border:1px solid #00ffcc; border-radius:8px; padding:15px; margin-bottom:25px; text-align:left;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <span style="color:#00ffcc; font-weight:bold; font-size:13px; font-family:monospace;">🎯 OBJETIVO DEL DESAFÍO: INYECCIÓN LÓGICA / SQL EN API REST</span>
            <span style="background:#00ffcc; color:#000; padding:2px 8px; font-size:10px; font-weight:bold; border-radius:4px;">RETO #5</span>
        </div>
        <p style="color:#e2e8f0; font-size:12px; margin:0; line-height:1.6;">
            <strong>Misión:</strong> El backend de esta API REST procesa la autenticación en el endpoint <strong>POST /login</strong> concatenando el parámetro de usuario sin sanitizar. Tu objetivo es realizar una <strong>Inyección Lógica (SQL Injection)</strong> en la casilla de <code>usuario</code> (utilizando comillas <code>'</code> y operadores de comparación lógica como <code>OR</code>) para romper la validación del login y hacer que el servidor devuelva la <strong>Bandera (FLAG)</strong> de acceso.
        </p>
    </div>

    <div class="url-bar">
        <span>BASE_URL: </span><span id="apiUrl"></span>
    </div>

    <div class="grid">
        <div class="card">
            <div class="card-header">
                <span class="method get">GET</span>
                <span class="endpoint">/usuarios</span>
            </div>
            <div class="card-body">
                <div class="desc">lista de usuarios registrados</div>
                <button onclick="request('GET', 'usuarios')">ejecutar</button>
                <div id="resp_usuarios" class="response"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="method get">GET</span>
                <span class="endpoint">/usuario?id=</span>
            </div>
            <div class="card-body">
                <div class="desc">obtener usuario por ID</div>
                <input type="number" id="userId" placeholder="id" value="1">
                <button onclick="request('GET', 'usuario', null, document.getElementById('userId').value)">ejecutar</button>
                <div id="resp_usuario" class="response"></div>
            </div>
        </div>

        <div class="card" style="border: 4px solid #00ffcc;">
            <div class="card-header" style="background:#102220;">
                <span class="method post">POST</span>
                <span class="endpoint">/login</span>
                <span style="color:#00ffcc; font-size:10px; margin-left:auto; font-weight:bold;">⚡ VULNERABLE A INYECCIÓN</span>
            </div>
            <div class="card-body">
                <div class="desc" style="color:#00ffcc;">autenticación de usuarios (Inyecta SQL/Lógica en usuario)</div>
                <input type="text" id="loginUser" placeholder="usuario (e.g. admin' OR '1'='1)" value="admin">
                <input type="password" id="loginPass" placeholder="contraseña" value="admin123">
                <button onclick="login()">ejecutar</button>
                <div id="resp_login" class="response"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="method put">PUT</span>
                <span class="endpoint">/usuario?id=</span>
            </div>
            <div class="card-body">
                <div class="desc">actualizar usuario (requiere rol)</div>
                <input type="number" id="putId" placeholder="id">
                <input type="text" id="putRol" placeholder="nuevo rol">
                <button onclick="updateRole()">ejecutar</button>
                <div id="resp_put" class="response"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="method delete">DELETE</span>
                <span class="endpoint">/usuario?id=</span>
            </div>
            <div class="card-body">
                <div class="desc">eliminar usuario del sistema</div>
                <input type="number" id="deleteId" placeholder="id">
                <button onclick="deleteUser()">ejecutar</button>
                <div id="resp_delete" class="response"></div>
            </div>
        </div>
    </div>

    <!-- Panel de pistas (sutiles, no dan la respuesta) -->
    <div class="hint-panel" id="hintPanel">
        <div class="hint-header" onclick="toggleHints()">
            <span>📌 PISTAS (si llevas 15 minutos sin éxito)</span>
            <span>▼</span>
        </div>
        <div class="hint-content">
            <p>💡 "El endpoint POST /login procesa parámetros JSON de usuario sin filtrar adecuadamente la entrada."</p>
            <p>💡 "Una comilla simple <code>'</code> en el campo de usuario puede alterar la sintaxis lógica de verificación del backend."</p>
            <p>💡 "Si logras que la condición de validación interna evalúe como verdadera (lógica booleana), el servidor entregará la Bandera."</p>
            <p>💡 "No necesitas adivinar la contraseña si la verificación del usuario siempre resulta cierta."</p>
        </div>
    </div>

    <div class="flag-reveal" id="flagBox">
        <span id="flagContent"></span>
    </div>

    <button class="btn-volver" onclick="window.location.href='index.php'">← volver</button>

</div>

<script>
    let base = window.location.pathname.replace(/[^/]*$/, '') + 'api_vulnerable.php';
    document.getElementById('apiUrl').innerText = window.location.origin + base + '?ruta=';

    function toggleHints() {
        document.getElementById('hintPanel').classList.toggle('open');
    }

    async function call(method, ruta, body = null, id = null) {
        let url = base + '?ruta=' + ruta;
        if (id !== null && ruta === 'usuario') url += '&id=' + id;

        let options = { method: method, headers: { 'Content-Type': 'application/json' } };
        if (body) options.body = JSON.stringify(body);

        try {
            let res = await fetch(url, options);
            return await res.json();
        } catch(e) {
            return { error: e.message };
        }
    }

    function show(element, data) {
        document.getElementById(element).innerHTML = JSON.stringify(data, null, 2);
        checkFlag(data);
    }

    function checkFlag(data) {
        if (data && typeof data === 'object') {
            for (let k in data) {
                if (k === 'flag' && data[k]) {
                    document.getElementById('flagBox').style.display = 'block';
                    document.getElementById('flagContent').innerHTML = data[k];
                }
                if (typeof data[k] === 'string' && data[k].startsWith('FLAG{')) {
                    document.getElementById('flagBox').style.display = 'block';
                    document.getElementById('flagContent').innerHTML = data[k];
                }
            }
        }
    }

    async function request(method, ruta, body, id) {
        let data = await call(method, ruta, body, id);
        let target = 'resp_' + (ruta === 'usuarios' ? 'usuarios' : 
                                 ruta === 'usuario' ? 'usuario' : 
                                 ruta === 'login' ? 'login' : '');
        if (target === 'resp_') target = 'resp_usuario';
        show(target, data);
    }

    async function login() {
        let user = document.getElementById('loginUser').value;
        let pass = document.getElementById('loginPass').value;
        let data = await call('POST', 'login', { nombre: user, password: pass });
        show('resp_login', data);
    }

    async function updateRole() {
        let id = document.getElementById('putId').value;
        let rol = document.getElementById('putRol').value;
        let data = await call('PUT', 'usuario', { rol: rol }, id);
        show('resp_put', data);
    }

    async function deleteUser() {
        let id = document.getElementById('deleteId').value;
        let data = await call('DELETE', 'usuario', null, id);
        show('resp_delete', data);
    }

    window.request = request;
</script>
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
║   │   😂 ¿ABRISTE LA CONSOLA BUSCANDO LA RESPUESTA? 😂      │   ║
║   │                                                          │   ║
║   │   JAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJAJA   │   ║
║   │   No hay respuestas aquí. La API no regala nada.         │   ║
║   │   Sigue sufriendo, CAMPEÓN.                              │   ║
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
console.log('%c🚫 LA CONSOLA NO TE VA A DAR LA FLAG, SIGUE INTENTANDO 🚫', 'color: #ff0000; font-size: 16px; font-weight: bold; background: #1a0000; padding: 8px;');
console.log('%c🤣 QUÉ FÁCIL SERÍA SI ESTUVIERA EN F12, ¿VERDAD? 🤣', 'color: #ffff00; font-size: 16px; font-weight: bold;');
</script>
<?php
require_once __DIR__ . '/conf/footer.php';
echo $footer;
?>
<footer>
    "Laboratorio de pruebas | explora, prueba, encuentra la flag
"</footer>