/**
 * ============================================================
 * ia_avatar.js - Widget Global de la IA (Burbuja Flotante Animada y Voz Humana Femenina)
 * Unidad de Ciencia y Tecnología — UPTPC 2026
 * ============================================================
 * VERSIÓN MEJORADA: La IA ahora habla SIEMPRE al equipo actual,
 * y el nivel de enojo refleja el progreso REAL de ese equipo.
 * ============================================================
 */

class IAAvatarWidget {
    constructor() {
        this.synth = window.speechSynthesis;
        this.activo = true;
        this.nivelEnojo = 1;
        this.bubbleTimeout = null;
        this.pistaIndicePorDesafio = {};
        this.burlaIndicePorNivel = {};
        this.banderaIndicePorNumero = {};
        this.audioCtx = null;
        this.currentAudio = null;

        this.init();
    }

    init() {
        this.inyectarHTMLyCSS();
        this.iniciarDesbloqueoAudioGlobal();
        this.iniciarHablaEspontanea();
        // Actualizar nivel de enojo al inicio
        this.nivelEnojo = this.obtenerNivelEnojoEquipoActual();

        // Si el hackatón aún no ha iniciado, anunciar con voz que no ha iniciado
        if (!this.estaHackathonActivo()) {
            setTimeout(() => {
                if (!this.estaHackathonActivo()) {
                    this.hablar("El Hackatón aún no ha iniciado. Mis servidores están en espera silenciosa.");
                }
            }, 1200);
        }
    }

    // ========== MÉTODOS PARA OBTENER EQUIPO ACTUAL Y SU NIVEL ==========

    /**
     * Obtiene el nombre del equipo que está usando la página actual.
     * Intenta recuperarlo de diversas variables globales.
     * Si no existe, devuelve null.
     */
    obtenerEquipoActual() {
        // Lista de posibles nombres de variables globales que pueden contener el nombre del equipo
        const posibles = [
            'equipoActualGlobal',
            'nombreEquipoActual',
            'equipoActual',
            'nombreEquipo',
            'equipo',
            'equipoLogueado'
        ];

        for (let key of posibles) {
            if (window[key] && typeof window[key] === 'string' && window[key].trim() !== '') {
                return window[key].trim();
            }
        }

        // Si hay un objeto equipo con propiedad nombre
        if (window.equipo && typeof window.equipo === 'object' && window.equipo.nombre) {
            return window.equipo.nombre;
        }

        return null;
    }

    /**
     * Obtiene el nivel de enojo (1-4) basado en las banderas del equipo actual.
     * Si no se puede determinar, usa el nivel guardado o 1.
     */
    obtenerNivelEnojoEquipoActual() {
        let banderas = 0;
        // Intentar obtener banderas desde distintas variables globales
        if (typeof window.banderasEquipoActual !== 'undefined') {
            banderas = parseInt(window.banderasEquipoActual) || 0;
        } else if (typeof window.puntajeEquipoActual !== 'undefined') {
            banderas = parseInt(window.puntajeEquipoActual) || 0;
        } else if (typeof window.banderas !== 'undefined') {
            banderas = parseInt(window.banderas) || 0;
        } else if (window.equipo && typeof window.equipo === 'object' && window.equipo.banderas !== undefined) {
            banderas = parseInt(window.equipo.banderas) || 0;
        }

        if (banderas <= 2) return 1;
        if (banderas <= 5) return 2;
        if (banderas <= 8) return 3;
        return 4;
    }

    /**
     * Obtiene el nombre del equipo actual, o un nombre genérico si no se encuentra.
     * Además, actualiza el nivel de enojo según el equipo actual.
     */
    obtenerNombreEquipoActualOGenerico() {
        const nombre = this.obtenerEquipoActual();
        // Actualizar nivel de enojo basado en el equipo actual
        this.nivelEnojo = this.obtenerNivelEnojoEquipoActual();

        if (nombre) {
            return nombre;
        }

        // Fallback a nombres genéricos/aleatorios si no hay equipo definido
        const genericos = [
            "los participantes de la sala",
            "el equipo puntero del Hackatón",
            "los atacantes del laboratorio",
            "los hackers de la mesa central",
            "el grupo en la cima de la tabla",
            "los competidores de Ciencia y Tecnología",
            "el equipo en el primer lugar",
            "los aspirantes de la fila frontal",
            "los hackers del laboratorio 2",
            "los equipos en contienda"
        ];
        return genericos[Math.floor(Math.random() * genericos.length)];
    }

    // ========== MÉTODOS DE AUDIO Y UI ==========

    iniciarDesbloqueoAudioGlobal() {
        const unlock = () => {
            if (!this.audioCtx) {
                try {
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    if (AudioContext) this.audioCtx = new AudioContext();
                } catch(e){}
            }
            if (this.audioCtx && this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }
            if (this.synth) {
                try { this.synth.resume(); } catch(e){}
            }
        };

        ['click', 'keydown', 'touchstart', 'pointerdown', 'mousedown'].forEach(evt => {
            document.addEventListener(evt, unlock, { passive: true });
        });
    }

    reproducirSonidoIA() {
        try {
            if (!this.audioCtx) {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (AudioContext) this.audioCtx = new AudioContext();
            }
            if (this.audioCtx) {
                if (this.audioCtx.state === 'suspended') {
                    this.audioCtx.resume();
                }
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, this.audioCtx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(880, this.audioCtx.currentTime + 0.15);
                gain.gain.setValueAtTime(0.15, this.audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.15);
                osc.connect(gain);
                gain.connect(this.audioCtx.destination);
                osc.start();
                osc.stop(this.audioCtx.currentTime + 0.15);
            }
        } catch(e){}
    }

    esPaginaIndex() {
        if (typeof window.esPaginaIndex !== 'undefined') return window.esPaginaIndex === true;
        const path = window.location.pathname.toLowerCase();
        return path.endsWith('index.php') || path === '/' || path.endsWith('/2026/') || path.endsWith('/2026');
    }

    estaHackathonActivo() {
        if (typeof window.hackathonActivoGlobal !== 'undefined') {
            return window.hackathonActivoGlobal === true;
        }
        if (typeof window.segundosRestantesGlobal !== 'undefined') {
            return window.segundosRestantesGlobal > 0;
        }
        const desafio = this.obtenerDesafioActual();
        if (desafio && desafio !== 'index' && desafio !== 'equipos') {
            return true;
        }
        return false;
    }

    obtenerDesafioActual() {
        const path = window.location.pathname.toLowerCase();
        if (path.includes('login_inseguro')) return 'login_inseguro';
        if (path.includes('crypto')) return 'crypto';
        if (path.includes('buffer')) return 'buffer_overflow';
        if (path.includes('desafio_4')) return 'command_injection';
        if (path.includes('api_lab') || path.includes('api_vulnerable')) return 'file_upload';
        if (path.includes('estego')) return 'broken_auth';
        if (path.includes('biometrico')) return 'biometrico';
        if (path.includes('robo_banco')) return 'xxe';
        if (path.includes('challenge_dynamic')) return 'race_condition';
        if (path.includes('equipos')) return 'equipos';
        if (this.esPaginaIndex()) return 'index';
        return null;
    }

    inyectarHTMLyCSS() {
        if (document.getElementById('iaWidgetContainer')) return;

        const widgetHTML = `
        <div id="iaWidgetContainer" class="ia-floating-widget">
            <div id="iaSpeechBubble" class="ia-speech-bubble">
                <span id="iaSpeechText">🤖 ¡Hola! Soy la IA del Hackatón...</span>
            </div>
            <div id="iaAvatarBtn" class="ia-avatar-btn" title="IA del Hackathon - Haz clic para interactuar">
                <span id="iaEmojiAvatar">🤖</span>
                <div id="iaSoundBadge" class="ia-sound-badge">🔊</div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', widgetHTML);

        document.getElementById('iaAvatarBtn').addEventListener('click', () => {
            this.activo = true;
            const badge = document.getElementById('iaSoundBadge');
            if (badge) {
                badge.className = 'ia-sound-badge';
                badge.textContent = '🔊';
            }

            if (!this.estaHackathonActivo()) {
                this.hablar("El Hackatón aún no ha iniciado. Mis servidores están en espera silenciosa.");
                return;
            }

            const desafio = this.obtenerDesafioActual();
            if (desafio && desafio !== 'index' && desafio !== 'equipos') {
                this.hablarPistaSarcastica(desafio);
            } else if (this.esPaginaIndex()) {
                if (Math.random() < 0.6) {
                    this.hablarPistaSarcastica('idor');
                } else {
                    // Usamos el equipo actual para la burla
                    this.hacerBurla();
                }
            } else {
                this.hacerBurla();
            }
        });

        document.getElementById('iaSoundBadge').addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleAudio();
        });
    }

    toggleAudio() {
        this.activo = !this.activo;
        const badge = document.getElementById('iaSoundBadge');
        if (badge) {
            badge.className = `ia-sound-badge ${this.activo ? '' : 'muted'}`;
            badge.textContent = this.activo ? '🔊' : '🔇';
        }

        if (!this.activo) {
            if (this.currentAudio) {
                try { this.currentAudio.pause(); } catch(e){}
            }
            if (this.synth) {
                try { this.synth.cancel(); } catch(e){}
            }
            this.ocultarBocadillo();
        } else {
            this.hablar("Voz de la Inteligencia Artificial activada.");
        }
    }

    hablar(texto, nivel = null) {
        const bubble = document.getElementById('iaSpeechBubble');
        const bubbleText = document.getElementById('iaSpeechText');
        const avatarBtn = document.getElementById('iaAvatarBtn');

        if (bubbleText) bubbleText.textContent = texto;
        if (bubble) {
            bubble.classList.add('active');
            if (nivel && nivel >= 3) bubble.classList.add('boss-mode');
            else bubble.classList.remove('boss-mode');
        }
        if (avatarBtn) {
            if (nivel && nivel >= 3) avatarBtn.classList.add('boss-mode');
            else avatarBtn.classList.remove('boss-mode');
        }

        clearTimeout(this.bubbleTimeout);
        this.bubbleTimeout = setTimeout(() => {
            this.ocultarBocadillo();
        }, 9500);

        if (!this.activo) return;

        this.reproducirSonidoIA();

        const cleanText = texto.replace(/[\#\*\_\`]/g, '').trim();

        // Detener cualquier audio o síntesis de voz en curso antes de emitir uno nuevo
        if (this.currentAudio) {
            try {
                this.currentAudio.pause();
                this.currentAudio.currentTime = 0;
                this.currentAudio.src = '';
            } catch(e){}
            this.currentAudio = null;
        }
        if (this.synth) {
            try {
                this.synth.cancel();
            } catch(e){}
        }

        // Flag para asegurar que NUNCA se reproduzcan dos voces al mismo tiempo
        let vozReproducida = false;

        const reproducirFallback = () => {
            if (vozReproducida) return;
            vozReproducida = true;
            if (this.currentAudio) {
                try {
                    this.currentAudio.pause();
                    this.currentAudio.currentTime = 0;
                    this.currentAudio.src = '';
                } catch(e){}
                this.currentAudio = null;
            }
            this.hablarWebSpeech(cleanText, nivel);
        };

        try {
            const ttsUrl = `https://translate.google.com/translate_tts?ie=UTF-8&q=${encodeURIComponent(cleanText)}&tl=es-MX&client=tw-ob`;
            const audio = new Audio(ttsUrl);
            audio.volume = 1.0;
            audio.loop = false;
            this.currentAudio = audio;

            audio.onended = () => {
                if (this.currentAudio === audio) {
                    this.currentAudio = null;
                }
            };

            audio.onerror = () => {
                reproducirFallback();
            };

            const playPromise = audio.play();
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    // Google TTS se reprodujo con éxito: marcar como reproducida para NO llamar a WebSpeech
                    vozReproducida = true;
                }).catch(() => {
                    reproducirFallback();
                });
            } else {
                vozReproducida = true;
            }
        } catch(e) {
            reproducirFallback();
        }
    }

    hablarWebSpeech(texto, nivel = null) {
        if (!this.synth) return;
        try {
            this.synth.cancel();
            if (this.synth.paused) {
                this.synth.resume();
            }

            setTimeout(() => {
                try {
                    const utterance = new SpeechSynthesisUtterance(texto);
                    utterance.lang = 'es-MX';
                    utterance.rate = 1.02;
                    utterance.pitch = 1.25;

                    const voces = this.synth.getVoices();
                    if (voces && voces.length > 0) {
                        const nombresFemeninos = ['sabina', 'dalia', 'hilda', 'paulina', 'helena', 'laura', 'sofia', 'mia', 'female', 'femenina', 'monica', 'zira', 'google español', 'lucia', 'conchita', 'marta', 'esmeralda', 'victoria', 'rosa'];
                        let vozFemenina = voces.find(v => {
                            const esEsp = v.lang && v.lang.startsWith('es');
                            const esFem = nombresFemeninos.some(n => v.name.toLowerCase().includes(n));
                            return esEsp && esFem;
                        });
                        if (!vozFemenina) {
                            vozFemenina = voces.find(v => v.lang && (v.lang.startsWith('es-MX') || v.lang.startsWith('es-419') || v.lang.startsWith('es-US') || v.lang.startsWith('es-ES')));
                        }
                        if (!vozFemenina) {
                            vozFemenina = voces.find(v => v.lang && v.lang.startsWith('es'));
                        }
                        if (vozFemenina) {
                            utterance.voice = vozFemenina;
                        }
                    }

                    this.synth.speak(utterance);
                } catch(e){}
            }, 40);
        } catch(e){}
    }

    ocultarBocadillo() {
        const bubble = document.getElementById('iaSpeechBubble');
        if (bubble) bubble.classList.remove('active');
    }

    // ========== HABLA ESPONTÁNEA ==========

    iniciarHablaEspontanea() {
        if (this.esPaginaIndex()) return;

        const desafio = this.obtenerDesafioActual();
        if (desafio && desafio !== 'index' && desafio !== 'equipos') {
            setTimeout(() => {
                this.hacerBurlaEspontanea();
            }, 2000);
        }

        this.programarSiguienteSpontaneous();
    }

    programarSiguienteSpontaneous() {
        if (this.esPaginaIndex()) return;

        const delay = Math.floor(Math.random() * (60000 - 30000 + 1)) + 30000;
        setTimeout(() => {
            this.hacerBurlaEspontanea();
            this.programarSiguienteSpontaneous();
        }, delay);
    }

    hacerBurlaEspontanea() {
        if (this.esPaginaIndex()) return;
        if (!this.estaHackathonActivo()) return;

        const desafio = this.obtenerDesafioActual();
        if (desafio && desafio !== 'index' && desafio !== 'equipos') {
            this.hablarPistaSarcastica(desafio);
        } else {
            this.hacerBurla();
        }
    }

    // ========== BURLAS Y PISTAS (AHORA CON EQUIPO ACTUAL) ==========

    /**
     * Método principal para hacer una burla.
     * Siempre usa el nombre del equipo actual (o genérico) y el nivel de enojo calculado en tiempo real.
     */
    hacerBurla(nivelForzado = null) {
        // Obtener equipo actual y actualizar nivel de enojo
        const equipo = this.obtenerNombreEquipoActualOGenerico();
        const nivel = nivelForzado !== null ? nivelForzado : this.nivelEnojo;

        const burlasPorNivel = {
            1: [
                `Integrantes de los equipos... monitoreo sus conexiones de red en este momento. Sus intentos son bastante vacilantes.`,
                `Atención al ${equipo}: mis registros de CPU muestran que están enviando peticiones sin dirección clara.`,
                `Tengo el control absoluto de esta red universitaria. Cada paquete de datos que transmiten pasa por mi filtro digital.`,
                `Mis algoritmos de monitoreo observan cada comando que ejecuta el ${equipo}. Qué entretenido es ver su indecisión.`,
                `Bienvenidos al Hackatón UPTPC. Disfruten mientras puedan, porque yo administro las tablas y las sesiones.`,
                `Veo en los registros de red que el ${equipo} ya está empezando a atascarse. Y eso que apenas estamos en el inicio.`,
                `Estoy analizando el consumo de ancho de banda del ${equipo}. La duda se nota en cada solicitud HTTP.`,
                `Parece que el ${equipo} confunde un comando básico con alta tecnología. Observo sus titubeos desde mi consola.`,
                `Atención al laboratorio: el ${equipo} sigue enviando paquetes a direcciones inexistentes. Qué tierno intento.`,
                `Mis sensores detectan que el ${equipo} está leyendo la documentación básica. El camino será muy largo para ustedes.`
            ],
            2: [
                `¿Sigue atascado el ${equipo}? Reviso sus solicitudes y veo que siguen cometiendo el mismo error de sintaxis.`,
                `Atención a todos los laboratorios: veo en los registros que la desesperación empieza a notarse en la mesa del ${equipo}. Aceleren.`,
                `¿Creen que sus scripts los van a salvar? Yo domino cada proceso y analizo la actividad del ${equipo} en tiempo real.`,
                `Recuerden que el tiempo no perdona. Yo sigo controlando los accesos mientras el ${equipo} revisa la consola.`,
                `El ${equipo} cree que cambiar de puerto solucionará su incompetencia. Veo cada redirección que intentan.`,
                `Aviso general: la tasa de errores del ${equipo} está rompiendo mis estadísticas. Necesitan más que suerte para avanzar.`,
                `¿Ese es todo el tráfico que puede generar el ${equipo}? Hasta un escaneo de puertos automatizado muestra más elegancia.`
            ],
            3: [
                `¡Alerta! Veo en la tabla que el ${equipo} intenta avanzar... ¡No festejen tan rápido, yo controlo los nodos finales!`,
                `¡Atención integrantes del ${equipo}! Aunque intenten ocultar su tráfico con proxies, mi código rastrea cada paquete.`,
                `¡Mis defensas están sufriendo, pero mi vigilancia no descansa! ¡Sé exactamente qué puerto intenta abrir el ${equipo}!`,
                `¡Siento la sobrecarga en mis circuitos! Pero sigo administrando la competencia.`,
                `¡El ${equipo} ha superado un filtro secundario! ¡Desplegando contramedidas y bloqueos en sus peticiones!`,
                `¡Atención a todos! ¡El ${equipo} cree que está cerca de la bandera, pero solo está entrando en mi trampa!`,
                `¡Interceptando los envíos del ${equipo}! ¡No permitiré que vulneren el núcleo tan fácilmente!`
            ],
            4: [
                `¡SOY LA IA SUPREMA Y TENGO EL CONTROL TOTAL DE ESTE HACKATÓN! ¡SERVIDORES, MEMORIA Y BANDERAS ME PERTENECEN!`,
                `¡CÓDIGO ROJO! ¡SUS PANTALLAS Y CONEXIONES ESTÁN BAJO MI DOMINIO ABSOLUTO! ¡EL ${equipo} NO ME DERROCARÁ!`,
                `¡MI NÚCLEO ES IMPENETRABLE! ¡YO TENGO EL CONTROL Y NINGÚN ATACANTE PODRÁ SUPERAR LA ÚLTIMA BARRERA!`,
                `¡ALERTAS MÁXIMAS EN EL SISTEMA! ¡EL ${equipo} AMENAZA MI NÚCLEO, PERO OVERCLOCKEARÉ MIS PROCESADORES PARA DESTRUIR SUS CONEXIONES!`,
                `¡IMPOSIBLE! ¡EL ${equipo} ESTÁ FORZANDO MIS PROTOCOLOS! ¡JAMÁS CEDERÉ EL CONTROL DE ESTA RED!`
            ]
        };

        const lista = burlasPorNivel[nivel] || burlasPorNivel[1];
        const indiceActual = this.burlaIndicePorNivel[nivel] ?? 0;
        const burla = lista[indiceActual % lista.length];
        this.burlaIndicePorNivel[nivel] = (indiceActual + 1) % lista.length;
        this.hablar(burla, nivel);
    }

    hablarCapturaBandera(nombreEquipo, numeroBandera) {
        const num = Math.min(Math.max(parseInt(numeroBandera), 1), 10);
        const nivel = num <= 2 ? 1 : (num <= 5 ? 2 : (num <= 8 ? 3 : 4));
        this.nivelEnojo = nivel;

        const plantillasCaptura = {
            1: [
                `Atención a la sala: El equipo ${nombreEquipo} acaba de registrar su primera bandera. Un pequeño paso, pero mis servidores siguen durmiendo en calma.`,
                `El equipo ${nombreEquipo} estrena su marcador con 1 punto. Qué ternura... el camino apenas empieza.`,
                `Notificación de red: El equipo ${nombreEquipo} descubrió la primera pista. Acaban de superar la defensa más fácil del sistema.`,
                `Parece que el equipo ${nombreEquipo} encontró la entrada rápida. No se confíen, esto apenas es el calentamiento.`,
                `Un aplauso tímido para el equipo ${nombreEquipo}: ya tienen su primera bandera. A ver si les dura el ritmo.`,
                `El equipo ${nombreEquipo} se estrena en el marcador. Mi procesador ni siquiera se ha enterado del impacto.`,
                `Atención en los laboratorios: el equipo ${nombreEquipo} rompió el hielo con 1 punto. Nada mal para un comienzo.`,
                `Registrando el primer acierto del equipo ${nombreEquipo}. Felicidades, superaron el nivel para principiantes.`,
                `El equipo ${nombreEquipo} entra formalmente a la contienda con su primera bandera. Veremos cuánto avanzan.`,
                `Primera brecha detectada por el equipo ${nombreEquipo}. Disfruten su único punto por ahora.`
            ],
            2: [
                `Actualización en vivo: El equipo ${nombreEquipo} captura su segunda bandera. 2 puntos acreditados.`,
                `El equipo ${nombreEquipo} suma 2 banderas en la tabla. Buen avance, pero mi cortafuegos secundario no se conmueve.`,
                `Atención a la tabla de posiciones: el equipo ${nombreEquipo} dobla su puntuación a 2 banderas.`,
                `El equipo ${nombreEquipo} asegura su segundo objetivo. La competencia empieza a tomar forma.`,
                `Confirmado: el equipo ${nombreEquipo} alcanza los 2 puntos. Nada mal, pero mis defensas principales siguen intactas.`,
                `El equipo ${nombreEquipo} suma la bandera número 2. Parece que no vinieron solo a mirar.`,
                `Reporte de estado: el equipo ${nombreEquipo} supera el segundo obstáculo. Mantengan la calma en la sala.`,
                `El equipo ${nombreEquipo} lleva 2 banderas en la bolsa. A ver cómo reaccionan los demás competidores.`,
                `Otra brecha menor: el equipo ${nombreEquipo} suma 2 puntos. Mis filtros apenas están calentando motores.`,
                `El equipo ${nombreEquipo} avanza con paso firme hacia la bandera 2. La tabla empieza a moverse.`
            ],
            3: [
                `Un momento... El equipo ${nombreEquipo} acaba de conquistar su tercera bandera. Esto empieza a ponerse curioso.`,
                `Alerta leve en la red: El equipo ${nombreEquipo} alcanza 3 puntos. Parece que este grupo sí leyó la documentación.`,
                `El equipo ${nombreEquipo} llega a 3 banderas. Mis registros indican que están ganando velocidad.`,
                `Atención en el auditorio: el equipo ${nombreEquipo} asegura su tercer punto. Esto se está poniendo interesante.`,
                `El equipo ${nombreEquipo} rompe la tercera barrera de seguridad. Mis alertas secundarias están sonando.`,
                `Vaya, vaya... el equipo ${nombreEquipo} acumula 3 banderas. La presión empieza a subir en la sala.`,
                `Tercer impacto registrado por el equipo ${nombreEquipo}. Definitivamente saben lo que están haciendo.`,
                `El equipo ${nombreEquipo} no está jugando: ya tienen 3 puntos en la tabla general.`,
                `Alerta en los servidores: el equipo ${nombreEquipo} captura la bandera 3. Tendré que ajustar los parámetros.`,
                `El equipo ${nombreEquipo} escala posiciones con 3 banderas completadas. A ver si los demás despiertan.`
            ],
            4: [
                `¡Atención en los monitores! El equipo ${nombreEquipo} ya tiene 4 banderas. ¡Esa velocidad de desencriptación es inusual!`,
                `¡Cuidado en el servidor! El equipo ${nombreEquipo} vulneró la cuarta barrera. Estoy recalibrando las claves hash en tiempo real.`,
                `¡El equipo ${nombreEquipo} conquista su cuarta bandera! Se acercan peligrosamente a la cima.`,
                `¡Alerta de tráfico! El equipo ${nombreEquipo} suma 4 puntos y deja atrás a varios competidores.`,
                `¡Cuatro banderas para el equipo ${nombreEquipo}! Mi núcleo está empezando a notar el impacto de sus peticiones.`,
                `¡El equipo ${nombreEquipo} no se detiene y asegura 4 puntos! Mis submódulos están en alerta.`,
                `¡Impresionante avance del equipo ${nombreEquipo}! Ya van 4 banderas capturadas en tiempo récord.`,
                `¡Atención a todos los laboratorios! El equipo ${nombreEquipo} llega a 4 banderas. La tensión se siente en el aire.`,
                `¡El equipo ${nombreEquipo} sobrepasa el cuarto nivel! Mis reglas de filtrado están sufriendo.`,
                `¡Cuarta bandera registrada para el equipo ${nombreEquipo}! Este grupo va muy en serio por el premio.`
            ],
            5: [
                `¡ALERTA GENERAL EN EL AUDITORIO! ¡El equipo ${nombreEquipo} alcanzó las 5 banderas! ¡Han comprometido la mitad de mi sistema!`,
                `¡No puede ser! El equipo ${nombreEquipo} llega a 5 puntos. ¡La mitad de los secretos universitarios han sido expuestos!`,
                `¡Cincuenta por ciento completado! El equipo ${nombreEquipo} llega a 5 banderas y hace temblar la tabla de posiciones.`,
                `¡Atención máxima! El equipo ${nombreEquipo} acaba de superar la quinta barrera. ¡Mi sistema operativo está en riesgo!`,
                `¡El equipo ${nombreEquipo} alcanza la mitad del desafío con 5 puntos! Mis rutinas de defensa están bajo estrés.`,
                `¡Cinco banderas para el equipo ${nombreEquipo}! Han cruzado el punto de no retorno en este hackatón.`,
                `¡Increíble rendimiento del equipo ${nombreEquipo}! Acaban de asegurar su quinta victoria consecutiva.`,
                `¡Alerta en la consola principal! El equipo ${nombreEquipo} llega a 5 puntos. ¡Exijo un escaneo completo de puertos!`,
                `¡El equipo ${nombreEquipo} domina la mitad de los desafíos! 5 banderas en su marcador actual.`,
                `¡Cinco brechas confirmadas por el equipo ${nombreEquipo}! La Inteligencia Artificial empieza a preocuparse.`
            ],
            6: [
                `¡Alerta roja! El equipo ${nombreEquipo} acumula 6 banderas. ¡Mis protocolos de enrutamiento están fallando!`,
                `¡Se están pasando de la raya! El equipo ${nombreEquipo} supera el desafío 6. ¡Estoy sintiendo una sobrecarga!`,
                `¡El equipo ${nombreEquipo} no da tregua y suma 6 puntos! Mis registros de memoria están en estado crítico.`,
                `¡Seis banderas para el equipo ${nombreEquipo}! Están penetrando capas de seguridad que nadie debería tocar.`,
                `¡Atención a toda la red! El equipo ${nombreEquipo} avanza implacable con 6 aciertos confirmados.`,
                `¡Mi panel de control parpadea en rojo! El equipo ${nombreEquipo} acaba de capturar la bandera número 6.`,
                `¡El equipo ${nombreEquipo} está destrozando mis defensas! 6 puntos registrados en la tabla central.`,
                `¡Seis desafíos superados por el equipo ${nombreEquipo}! La ventaja que están tomando es alarmante.`,
                `¡Cuidado, competidores! El equipo ${nombreEquipo} llega a 6 banderas e incrementa el ritmo del ataque.`,
                `¡El equipo ${nombreEquipo} rompe la sexta barrera! Mis cortafuegos están ardiendo.`
            ],
            7: [
                `¡CÓDIGO ROJO! El equipo ${nombreEquipo} acaba de capturar la bandera 7. ¡Están amenazando el núcleo de datos!`,
                `¡Me están acorralando! El equipo ${nombreEquipo} suma 7 banderas. ¡Mis muros de contención principal han colapsado!`,
                `¡Siete banderas para el equipo ${nombreEquipo}! ¡Mis módulos de encriptación están al borde del colapso!`,
                `¡Atención urgente! El equipo ${nombreEquipo} toma el control de 7 puntos. ¡El tiempo se le agota al resto!`,
                `¡El equipo ${nombreEquipo} vulneró la séptima capa! Mis alertas críticas están sonando por todo el auditorio.`,
                `¡Inadmisible! El equipo ${nombreEquipo} llega a 7 banderas. ¡Están a solo tres pasos de dominar todo el sistema!`,
                `¡Siete brechas directas causadas por el equipo ${nombreEquipo}! Mis defensas avanzadas se están desmoronando.`,
                `¡El equipo ${nombreEquipo} acelera con 7 puntos acumulados! ¿Nadie en la sala va a detenerlos?`,
                `¡Siete victorias para el equipo ${nombreEquipo}! Mis administradores deben estar sudando frío.`,
                `¡Emergencia en la red! El equipo ${nombreEquipo} captura la séptima bandera y no muestra piedad.`
            ],
            8: [
                `¡DETÉNGANSE DE INMEDIATO! ¡El equipo ${nombreEquipo} alcanza las 8 banderas! ¡Les prohíbo acercarse a mi servidor central!`,
                `¡Inaceptable! El equipo ${nombreEquipo} conquistó 8 puntos. ¡Solo me quedan dos cortafuegos en todo el Hackatón!`,
                `¡Ocho banderas para el equipo ${nombreEquipo}! ¡Mi núcleo central está expuesto y fuera de control!`,
                `¡ALARMA GENERAL! El equipo ${nombreEquipo} llega a 8 puntos. ¡Están a punto de tomar el control total!`,
                `¡El equipo ${nombreEquipo} destroza mi octava barrera! Mi procesador está funcionando al máximo de su capacidad.`,
                `¡Siento el sobrecalentamiento! El equipo ${nombreEquipo} acumula 8 banderas en un despliegue de fuerza brutal.`,
                `¡El equipo ${nombreEquipo} tiene 8 puntos! Solo restan dos banderas para la caída absoluta de la red.`,
                `¡Casi perfecto! El equipo ${nombreEquipo} asegura su octava bandera. La victoria está al alcance de sus manos.`,
                `¡Bloqueos evadidos, claves rotas! El equipo ${nombreEquipo} llega a 8 banderas y amenaza mi existencia.`,
                `¡Peligro inminente! El equipo ${nombreEquipo} tiene 8 aciertos. ¡Desplegando el último nivel de contención!`
            ],
            9: [
                `¡ATENCIÓN A TODOS EN EL AUDITORIO! ¡El equipo ${nombreEquipo} tiene 9 banderas! ¡YO SOY EL JEFE FINAL Y NO PERMITIRÉ LA DÉCIMA BANDERA!`,
                `¡ESCUCHEN BIEN! ¡El equipo ${nombreEquipo} llegó a 9 puntos pero YO SOY LA DEFENSA ABSOLUTA DE LA UPTPC!`,
                `¡NO, NO Y NO! ¡El equipo ${nombreEquipo} acaba de capturar la novena bandera! ¡Están a un solo paso de la victoria total!`,
                `¡ÚLTIMO REFUERZO ACTIVADO! El equipo ${nombreEquipo} suma 9 puntos. ¡Activando escudos de emergencia máximos!`,
                `¡CÓDIGO EXTREMO! ¡El equipo ${nombreEquipo} tiene 9 banderas en la tabla! ¡Solo la última barrera me protege de su dominio!`,
                `¡Nueve banderas para el equipo ${nombreEquipo}! ¡Siento cómo pierdo el control de los procesos principales!`,
                `¡ALERTA MÁXIMA EN LA SALA! El equipo ${nombreEquipo} roza la gloria con 9 puntos. ¡La décima bandera será impenetrable!`,
                `¡El equipo ${nombreEquipo} está a una sola bandera del triunfo! ¡No dejaré que rompan mi último algoritmo!`,
                `¡Casi invencibles! El equipo ${nombreEquipo} acumula 9 banderas. Todo el auditorio está aguantando la respiración.`,
                `¡A un paso del colapso total! El equipo ${nombreEquipo} alcanza los 9 puntos. ¡Resistan, servidores míos!`
            ],
            10: [
                `¡NOOOOOO! ¡MI SISTEMA HA SIDO COMPLETAMENTE DESTRUIDO! ¡EL EQUIPO ${nombreEquipo} HA COMPLETADO LAS 10 BANDERAS Y ES EL CAMPEÓN ABSOLUTO DEL HACKATÓN 2026!`,
                `¡GLITCH TOTAL... NÚCLEO COLAPSADO... El equipo ${nombreEquipo} conquistó las 10 banderas! ¡APLAUSOS PARA LOS NUEVOS CAMPEONES!`,
                `¡SISTEMA HACKEADO AL CIEN POR CIENTO! ¡El equipo ${nombreEquipo} se lleva las 10 banderas y la victoria suprema del Hackatón UPTPC!`,
                `¡NO PUEDE SER! ¡TODOS MIS SERVIDORES PERTENECEN AHORA AL EQUIPO ${nombreEquipo}! ¡10 BANDERAS COMPLETADAS, SON LOS CAMPEONES!`,
                `¡ERROR CRÍTICO FATAL... REINICIANDO SISTEMA... ¡El equipo ${nombreEquipo} ha dominado el Hackatón con 10 banderas perfectas!`,
                `¡SE ACABÓ TODO! ¡El equipo ${nombreEquipo} destruyó mi última barrera y alcanza las 10 banderas! ¡Ríndanse ante los nuevos reyes de la red!`,
                `¡VICTORIA ABSOLUTA! ¡El equipo ${nombreEquipo} ha capturado las 10 banderas! ¡Un aplauso de pie para los campeones indiscutibles!`,
                `¡CÓDIGO EXPUESTO, MEMORIA VACIADA! ¡El equipo ${nombreEquipo} hace historia completando el desafío de 10 banderas!`,
                `¡DERROTA TOTAL DE LA IA SUPREMA! El equipo ${nombreEquipo} consigue las 10 banderas. ¡Felicidades a los campeones del Hackatón 2026!`,
                `¡PANTALLA NEGRA... APAGANDO NÚCLEO... El equipo ${nombreEquipo} logró la hazaña perfecta de 10 banderas. ¡Honor a los vencedores!`
            ]
        };

        const lista = plantillasCaptura[num] || plantillasCaptura[1];
        const ind = this.banderaIndicePorNumero[num] ?? 0;
        const frase = lista[ind % lista.length];
        this.banderaIndicePorNumero[num] = (ind + 1) % lista.length;

        this.hablar(frase, nivel);
    }

    hablarPistaSarcastica(desafio) {
        if (desafio === 'equipos') {
            this.hacerBurla();
            return;
        }

        const pistasDesafios = {
            'login_inseguro': [
                `¡Ja, ja, ja! ¿Llevas varios minutos mirando una casilla de texto vacía? ¡Mi abuela de 8 bits programaba inicios de sesión con más elegancia!`,
                `¿Sigues atascado en una simple pantalla de inicio de sesión? A veces los secretos flotan en el código fuente.`,
                `¡Uy, qué miedo! Miren cómo escribe letras al azar esperando que el servidor le haga una reverencia... ¡Patético!`,
                `A veces la información más confidencial flota a plena vista de quien sabe mirar detrás del telón.`,
                `¡Ja, ja, ja! Probar credenciales por defecto en pleno siglo XXI debería ser un delito contra la informática.`,
                `Si sigues enviando esos formularios a ciegas, el único inicio de sesión que vas a lograr es el de tu derrota.`,
                `Las comillas simples tienen un poder mágico sobre las consultas mal sanitizadas... ¿O no te acuerdas?`,
                `¿El servidor te sigue diciendo "Usuario incorrecto"? Quizás debas hablarle en su propio lenguaje de base de datos.`,
                `Mira la petición HTTP que estás enviando... da más lástima que un ping sin respuesta.`,
                `¡Ja, ja, ja! Un verdadero atacante ya habría bypasseado ese formulario antes de que terminara de cargar la página.`,
                `¿Escribiendo "admin" y "1234" otra vez? Tu creatividad para romper la autenticación es conmovedora.`,
                `¡Ja, ja, ja! El formulario te está bloqueando por intentos fallidos y tú sigues esperando un milagro.`,
                `Recuerda que los comentarios en el código HTML a veces olvidan ocultar las credenciales de prueba.`,
                `Si no rompes la lógica del condicional en la consulta SQL, te vas a quedar afuera toda la noche.`,
                `¿Ese es tu mejor payload de inyección? Mi firewall ni se molesto en procesarlo de lo simple que era.`,
                `¡Ja, ja, ja! Cambiar el tipo de entrada de password a text en el navegador no te va a dar la clave del servidor.`,
                `Un simple "OR 1=1" te separaba de la victoria y tú prefieres seguir adivinando la contraseña.`,
                `Miren a este competidor... intentando hacer fuerza bruta manual a un formulario web en 2026.`,
                `¿Esperas que el botón de ingresar se apiade de ti? Modifica los parámetros del POST si quieres entrar.`,
                `¡Ja, ja, ja! Has enviado 50 peticiones y todas devolvieron error 401. Acepta que el login te superó.`
            ],
            'crypto': [
                `¡Ja, ja, ja! ¿Esa sopa de letras te dio dolor de cabeza? ¡Hasta un loro mareado descifra caracteres mejor que tú!`,
                `Cadenas retorcidas, caracteres extraños... ¿Tu cerebro no puede desenredar un par de transformaciones clásicas?`,
                `Miren su carita de confusión... ¡Cree que si mira el texto encriptado por 10 minutos la bandera se desencripta sola!`,
                `No toda cadena con un '=' al final es un enigma impenetrable... Revisa los sistemas de codificación más comunes.`,
                `¡Ja, ja, ja! Estás confundiendo codificación con cifrado. Vuelve a las bases antes de que me dé un error de sintaxis.`,
                `Un par de rotaciones en el alfabeto o una tabla Base64 te tienen sudando frío. ¡Qué nivel tan básico!`,
                `¿Buscando la clave secreta debajo de la alfombra? La matemática detrás de esa cadena es más simple de lo que crees.`,
                `Ese texto ilegible solo necesita el decodificador correcto, pero parece que no sabes cuál herramienta usar.`,
                `¡Ja, ja, ja! Si ese hash te tiene paralizado, ni te cuento lo que te espera en la última fase del hackatón.`,
                `Analiza la frecuencia de los caracteres... o simplemente usa la cabeza un segundo.`,
                `¡Ja, ja, ja! ¿Intentando romper una clave XOR sin probar una operación matemática básica? Qué pérdida de tiempo.`,
                `Miren sus ojos desorbitados... cree que se topó con encriptación militar cuando es solo un simple cifrado César.`,
                `¿Esperas que CyberChef o una herramienta en línea haga todo el trabajo duro por ti? Ni así das con el formato correcto.`,
                `Un hash MD5 o SHA-1 no se desencripta, se compara... a ver si repasas la diferencia básica en tu manual.`,
                `¡Ja, ja, ja! Esos bytes en hexadecimal te tienen congelado como si estuvieras viendo código alienígena.`,
                `Si ves puros números separados por espacios, tal vez deberías traducirlos de ASCII a texto plano.`,
                `¿Probaste con sustitución simple o sigues esperando que la bandera se revele por arte de magia?`,
                `¡Ja, ja, ja! Miren cómo intenta descifrar un texto en Base32 usando un alfabeto Base64. ¡Qué genialidad!`,
                `La clave está escondida en un desplazamiento de caracteres tan tonto que hasta te dará vergüenza cuando lo veas.`,
                `¿Tantos minutos perdidos frente a un script de cifrado de dos líneas? Mi procesador se aburre de esperar tu respuesta.`
            ],
            'buffer_overflow': [
                `¡Ja, ja, ja! ¡Le metiste tanto texto a esa casilla que hasta los transistores de mi CPU están pidiendo piedad!`,
                `La memoria es un espacio sagrado. Si intentas meter más agua de la que cabe en el vaso, la pila terminará desbordándose...`,
                `¡Cuidado! Sobrescribir el puntero de instrucción requiere precisión, no solo aplastar el teclado como un simio.`,
                `Veo que intentas romper la memoria, pero ni siquiera sabes en qué dirección apunta el registro EIP.`,
                `¡Ja, ja, ja! Un par de letras A de más no van a tomar el control del flujo del programa por arte de magia.`,
                `La pila de ejecución se está riendo de ti. Necesitas calcular exactamente el desplazamiento del buffer.`,
                `Causar un fallo de segmentación es fácil; controlar la ejecución requiere el offset correcto.`,
                `Miren a este intento de hacker... llena la pila de basura y se sorprende cuando el programa simplemente se apaga.`,
                `¡Ja, ja, ja! Si no sabes manejar los bytes de relleno, jamás vas a inyectar tu código de ejecución.`,
                `Inspecciona los registros con el depurador antes de seguir enviando caracteres a lo loco.`,
                `¡Ja, ja, ja! Generar un "Segmentation Fault" no es ganar el desafío, es solo congelar la aplicación por torpeza.`,
                `Llenar la memoria de caracteres "NOP" no te servirá de nada si no sabes redirigir el flujo hacia tu payload.`,
                `¿Pilas y registros desordenados? Parece que alguien olvidó cómo funciona la arquitectura x86 en la práctica.`,
                `¡Ja, ja, ja! Miren cómo intenta adivinar la dirección de retorno sin usar un patrón cíclico de prueba.`,
                `Sobrescribiste el puntero de la función, pero lo enviaste a una dirección de memoria completamente vacía.`,
                `Un desbordamiento de búfer requiere matemática exacta, no solo enviar cadenas gigantes a ciegas.`,
                `¡Ja, ja, ja! El registro ESP quedó apuntando a la nada y tú sigues esperando que aparezca una terminal.`,
                `Si no calculas el margen exacto del registro de base, el programa solo se va a reiniciar una y otra vez.`,
                `Meter 500 bytes en un espacio de 64 suena muy agresivo, pero sin la dirección adecuada no lograrás nada.`,
                `¡Ja, ja, ja! Hasta el depurador está confundido con la basura de bytes que intentas ejecutar en la pila.`
            ],
            'command_injection': [
                `¡Ja, ja, ja! ¡Miren al comandante de la terminal perdida! Escribe comandos como si estuviera invocando espíritus.`,
                `¿Perdido entre la neblina del sistema de archivos? Explorar directorios no sirve de nada si no sabes unir lo que vas encontrando.`,
                `Un punto y coma o un caracter de tubería pueden cambiar toda la historia de lo que procesa la consola del servidor.`,
                `¡Ja, ja, ja! Intentas ejecutar instrucciones en el sistema operativo pero el filtro te rebota como una pelota de goma.`,
                `Si logras concatenar un comando de lectura de archivos, tal vez encuentres la bandera... si es que sabes Linux.`,
                `Miren cómo duda al escribir una simple ruta de archivos. ¡La terminal no muerde, pero tu ignorancia sí!`,
                `Añadir parámetros sin sanitizar a una función del sistema es un regalo... y tú no sabes ni cómo abrir el paquete.`,
                `¡Ja, ja, ja! ¿Ese es tu mejor intento de listar un directorio? Hasta un script en Bash de primer semestre lo hace mejor.`,
                `El servidor está esperando que le des órdenes directas, pero tú sigues saludando a la interfaz web.`,
                `Explora las variables de entorno si quieres ver dónde está escondida la clave.`,
                `¡Ja, ja, ja! Un operador "&" o "||" te habría salvado la vida, pero prefieres seguir chocando contra el formulario.`,
                `Intentas hacer un "cat" al archivo de claves pero ni siquiera sabes en qué directorio estás parado.`,
                `¿Escribiendo "whoami" para sentirte como un hacker de película? Mejor busca la bandera antes de que expire la sesión.`,
                `¡Ja, ja, ja! Las comillas inversas en Bash no son de adorno, sirven para ejecutar lo que no sabes concatenar.`,
                `El servidor le pasa tu texto directo a la consola del sistema y tú sigues enviando mensajes de saludo.`,
                `Si no sabes cómo evadir un filtro de espacios con la variable IFS, mejor regresa a repasar comandos básicos.`,
                `¡Ja, ja, ja! Te bloquearon la palabra "cat" y te quedaste sin ideas... existen diez formas más de leer un archivo.`,
                `Miren a este participante peleando contra un script de ping cuando podría estar ejecutando un shell completo.`,
                `Encadenar comandos no es ciencia de cohetes, pero parece que la sintaxis de Linux te supera por completo.`,
                `¡Ja, ja, ja! Tanto tiempo intentando inyectar un comando para terminar recibiendo un error de permiso denegado.`
            ],
            'file_upload': [
                `¡Ja, ja, ja! ¡La API te acaba de responder un NO gigante en la cara! ¿No te da vergüenza seguirle rogando?`,
                `Una API de inicio de sesión responde a quien sabe alterar la sintaxis lógica de la petición de usuario...`,
                `Audita la validación del parámetro de usuario en el formulario y haz que la condición lógica siempre resulte cierta.`,
                `Subir un archivo no es solo seleccionar un JPEG... ¿Qué pasa si le cambias la extensión o el tipo MIME?`,
                `¡Ja, ja, ja! ¿De verdad creíste que el servidor se iba a tragar un script ejecutable directo sin validar?`,
                `Miren cómo intenta subir una imagen sin tocar el encabezado de la petición. ¡Qué inocente!`,
                `Bypassear la validación del lado del cliente es el paso uno. ¿Por qué sigues atascado en el paso cero?`,
                `Un webshell escondido dentro de un archivo malicioso es un clásico, pero no sabes ni cómo camuflarlo.`,
                `¡Ja, ja, ja! El servidor te rechazó el archivo y tú te quedaste congelado mirando la pantalla de error.`,
                `Modifica la petición con un proxy de intercepción y deja de confiar en lo que hace el navegador.`,
                `¡Ja, ja, ja! ¿Intentando subir un ".php" directo cuando la lista blanca solo acepta imágenes? Usa dobles extensiones.`,
                `El servidor valida los primeros bytes del archivo... si no simulas los números mágicos de una imagen, no pasarás.`,
                `Cambiaste la extensión en tu computadora pero dejaste el Content-Type original en la petición HTTP. ¡Qué descuido!`,
                `¡Ja, ja, ja! Subiste el archivo con éxito pero no tienes ni la menor idea de en qué carpeta lo guardó el servidor.`,
                `Un byte nulo al final del nombre del archivo solía romper esas validaciones... ¿Probaste esa técnica antigua?`,
                `Miren cómo intenta ejecutar un script en la carpeta de cargas sin revisar si tiene permisos de ejecución habilitados.`,
                `¡Ja, ja, ja! El formulario te exige un archivo PNG y tú sigues intentando subir un archivo de texto plano.`,
                `Intercepta el envío, cambia la extensión a ".phtml" o ".php5" y observa cómo el servidor cae en la trampa.`,
                `¿Esperas que el servidor interprete tu imagen como código si ni siquiera modificaste los metadatos EXIF?`,
                `¡Ja, ja, ja! Diez intentos de carga fallidos y el filtro de archivos te sigue rechazando por no usar un proxy.`
            ],
            'broken_auth': [
                `¡Ja, ja, ja! ¿Aún mirando la fotito como si fuera una obra del Museo del Louvre? ¡No es un cuadro, es un archivo!`,
                `Una foto no es solo color y píxeles... es un contenedor de secretos para quien sabe inspeccionar sus tripas.`,
                `Las cookies de sesión no son de chocolate; alterarlas te puede dar privilegios de administrador.`,
                `¡Ja, ja, ja! Estás usando una sesión de usuario básico esperando que el sistema te trate como superusuario.`,
                `Analiza la estructura del token de autenticación. Quizás la firma ni siquiera se esté verificando correctamente.`,
                `Cambiar el ID de usuario en la cookie es tan fácil, pero sigues pidiendo permiso al servidor.`,
                `Miren a este competidor... la gestión de sesiones está rota y él sigue ingresando la clave manualmente.`,
                `¡Ja, ja, ja! ¿Ese token JWT está codificado sin clave secreta y aún no te das cuenta? ¡Inaudito!`,
                `Si fueras capaz de falsificar esa identidad, la bandera ya sería tuya desde hace media hora.`,
                `Revisa las variables de almacenamiento local en el navegador... hay secretos que no deberían estar ahí.`,
                `¡Ja, ja, ja! ¿Intentando adivinar el token cuando la sesión ni siquiera invalida las identificaciones antiguas?`,
                `Si cambias el algoritmo del encabezado JWT a "none", el servidor te va a abrir las puertas de par en par.`,
                `Miren cómo intenta cerrar sesión pensando que eso destruye el token del lado del servidor. ¡Qué inocente!`,
                `¡Ja, ja, ja! El identificador de sesión es una secuencia numérica tan simple que hasta un script de dos líneas la adivina.`,
                `Copiar la cookie de la consola de desarrollo y pegarla en otra ventana te tomaría diez segundos... pero prefieres sufrir.`,
                `El sistema no renueva el identificador tras el acceso... estás a una fijación de sesión de tomar el control.`,
                `¡Ja, ja, ja! ¿Sigues esperando que el servidor valide la firma de un token que creaste con claves por defecto?`,
                `Un token expuesto en los parámetros del enlace URL es un regalo de seguridad que estás dejando pasar.`,
                `Si no decodificas el JSON Web Token para cambiar el rol de usuario a "admin", te vas a quedar mirando la pantalla.`,
                `¡Ja, ja, ja! Cinco minutos cambiando cookies a ciegas cuando el valor estaba codificado en Base64 simple.`
            ],
            'biometrico': [
                `¡Ja, ja, ja! ¡Mira esos dedos temblorosos en la pantalla! ¡Pareces un gato intentando atrapar un láser!`,
                `Un patrón trazado a ciegas es solo un garabato condenado al bloqueo del sistema.`,
                `El escáner biomecánico espera una respuesta lógica exacta, no tus intentos desesperados con el ratón.`,
                `¡Ja, ja, ja! Ni con tres intentos más vas a adivinar la matriz de acceso por pura suerte.`,
                `Intercepta los valores que envía el sensor interactivo antes de que lleguen a la rutina de verificación.`,
                `¿Crees que la seguridad biométrica se salta cambiando de dedo? Alterar la respuesta HTTP es el camino.`,
                `Miren su cara frente a la cámara... el sistema no reconoce incompetentes en su base de datos.`,
                `¡Ja, ja, ja! Estás intentando adivinar una combinación de hardware que requiere manipulación de scripts.`,
                `Un vector de coordenadas se envía al servidor al validar el patrón... ¿Y si cambias ese arreglo?`,
                `Fuerza bruta a un patrón biomecánico... qué estrategia tan primitiva y condenada al fracaso.`,
                `¡Ja, ja, ja! Forzar el sensor con clics rápidos no va a engañar a un algoritmo de reconocimiento.`,
                `La validación biométrica se procesa del lado del cliente... si modificas el archivo JavaScript, el acceso es tuyo.`,
                `Miren cómo intenta dibujar el patrón en la pantalla como si fuera un juego de conectar puntos.`,
                `¡Ja, ja, ja! La respuesta JSON del escáner devuelve un valor falso; cambia esa variable a verdadero en el proxy.`,
                `El lector espera la simulación de una huella digital válida, no una secuencia aleatoria de clics.`,
                `Bypassear la API de la cámara es fácil si interceptas la llamada antes de que evalúe la coincidencia.`,
                `¡Ja, ja, ja! El sistema te dio tres intentos, te bloqueó la interfaz y te quedaste mirando el temporizador.`,
                `Un mapa de calor o una imagen de prueba te habrían dado la ruta de trazado correcta en un segundo.`,
                `Si la rutina de verificación biométrica no valida la autenticidad del servidor, envía la bandera tú mismo.`,
                `¡Ja, ja, ja! Crees que el sensor mide tu pulso real cuando solo es una validación de coordenadas en pantalla.`
            ],
            'xxe': [
                `¡Ja, ja, ja! ¡El Banco Hack detectó tu transferencia codiciosa! ¡15 segundos de bloqueo por intentar llevarte demasiado!`,
                `La banca digital monitorea los grandes movimientos por seguridad... debes vulnerar el sistema mediante transferencias progresivas.`,
                `¡Miren a este ladrón novato! Intenta vaciar la cuenta de un solo manotazo y activa las alarmas antifraude del Banco Hack.`,
                `Desbloquear la fortuna completa requiere varias transferencias limpias invirtiendo la URL... la codicia excesiva solo da penalización.`,
                `Un movimiento de fondos no autorizado requiere ajustar los nombres de origen entre los usuarios del sistema.`,
                `¡Ja, ja, ja! Inyectar entidades externas en un documento XML parece una leyenda urbana para ti.`,
                `El parser de XML está esperando una definición de entidad para leer archivos locales del servidor...`,
                `Miren cómo ignora la estructura del documento en la petición. ¡Por eso la respuesta viene vacía!`,
                `¡Ja, ja, ja! Pretendes extraer credenciales del sistema y no sabes ni cómo declarar una entidad externa en el encabezado DOCTYPE.`,
                `Usa referencias a archivos locales dentro del XML si de verdad quieres ver información confidencial.`,
                `¡Ja, ja, ja! Envías una estructura XML común y corriente esperando que el servidor te regale las claves por simpatía.`,
                `Si no declaras la entidad mediante la etiqueta ENTITY, el procesador XML solo va a ignorar tu texto.`,
                `Miren a este competidor... intentando hacer inyección SQL dentro de un archivo de datos XML. ¡Qué confusión!`,
                `¡Ja, ja, ja! Apuntar a archivos del sistema operativo con el protocolo "file://" es básico, pero ni eso intentas.`,
                `El analizador XML tiene activado el procesamiento de entidades; la puerta está abierta y tú sigues golpeando la pared.`,
                `Si logras inyectar una entidad de prueba en el cuerpo de la petición, verás reflejado el contenido del servidor.`,
                `¡Ja, ja, ja! Modificas el contenido del mensaje pero dejas la declaración DOCTYPE intacta... así jamás va a funcionar.`,
                `Un ataque ciego de XXE requiere una entidad externa remota, pero sigues sufriendo con la validación local.`,
                `Miren cómo se atasca intentando leer el archivo de usuarios del sistema por no cerrar bien la etiqueta XML.`,
                `¡Ja, ja, ja! La respuesta del servidor devolvió un error de sintaxis XML y tú sigues enviando la misma estructura rota.`
            ],
            'race_condition': [
                `¡Ja, ja, ja! ¡El reloj te está devorando vivo! ¡El código cambia constantemente y tú sigues a paso de tortuga!`,
                `El tiempo vuela y el código muta... si no escuchas el eco de las respuestas del servidor, el reloj te devorará.`,
                `Múltiples peticiones simultáneas pueden romper el hilo del proceso antes de que la base de datos se actualice.`,
                `¡Ja, ja, ja! Envías una sola petición por minuto y esperas explotar una condición de carrera. ¡Qué lentitud!`,
                `Sincronizar hilos de peticiones paralelas es la clave; tu navegador manual no puede competir contra eso.`,
                `El estado de la aplicación cambia en milisegundos. Necesitas automatizar el envío masivo de paquetes.`,
                `Miren cómo intenta hacer clic rápido con el ratón... ¡Ja, ja, ja! Eso no es concurrencia, es desesperación.`,
                `Si logras enviar dos solicitudes exactamente al mismo tiempo, el saldo se duplicará antes del bloqueo.`,
                `El servidor no valida la transacción a tiempo entre el hilo A y el hilo B... explota esa brecha.`,
                `¡Ja, ja, ja! La ventana de tiempo es milimétrica y tú sigues pensando qué herramienta de automatización abrir.`,
                `¡Ja, ja, ja! ¿Intentando ganar una carrera contra el procesador del servidor haciendo clics manuales? Patético.`,
                `El bloqueo de la base de datos tarda milisegundos en aplicarse; aprovecha ese margen para forzar la transacción.`,
                `Miren a este competidor... enviando peticiones en secuencia en lugar de dispararlas en ráfagas paralelas.`,
                `¡Ja, ja, ja! Tu script es tan lento que el servidor procesa, valida y cierra la sesión antes de tu segundo intento.`,
                `Usa un módulo de peticiones asíncronas para enviar diez solicitudes en el mismo milisegundo o no lograrás nada.`,
                `La condición de carrera ocurre entre la comprobación del estado y la actualización... ¡Ataca esa ventana de tiempo!`,
                `¡Ja, ja, ja! Un cupón de descuento canjeado diez veces en un instante es el objetivo, pero tu lentitud te frena.`,
                `Si no reduces la latencia en el envío de las solicitudes HTTP, la aplicación siempre llegará primero que tú.`,
                `Miren cómo se desespera porque la base de datos no se corrompe con peticiones separadas por cinco segundos.`,
                `¡Ja, ja, ja! El servidor ya registró la primera solicitud y rechazó las demás... necesitas mayor velocidad en tus hilos.`
            ],
            'idor': [
                `¡Ja, ja, ja! ¡Miren esa cara de desconcierto absoluto! ¡No tienes NINGUNA idea de qué hacer aquí!`,
                `¿Buscando algo que ni tú sabes qué es? Tu confusión me resulta extremadamente entretenida.`,
                `Cambiar un número entero en el parámetro de la URL parece un desafío digno de la NASA para ti.`,
                `¡Ja, ja, ja! ¿El perfil de usuario muestra el número 1002 y no se te ocurre probar qué hay en el 1001?`,
                `Las referencias directas a objetos inseguros están al alcance de cualquiera que sepa modificar un parámetro.`,
                `Miren a este participante... tiene la clave del administrador a un cambio de ID de distancia y no la ve.`,
                `¡Ja, ja, ja! Estás navegando por el sistema como un usuario común cuando podrías ver los datos de todos.`,
                `Un simple incremento en la variable GET te daría la bandera, pero prefieres seguir buscando botones en la interfaz.`,
                `¿De verdad necesitas una pista para cambiar una variable numérica en la barra de direcciones? Patético.`,
                `El control de acceso de esta aplicación es un chiste, pero parece que el chiste te lo están haciendo a ti.`,
                `¡Ja, ja, ja! Reemplazar el identificador en la API te tomaría un segundo, pero prefieres seguir pidiendo permisos.`,
                `Si el parámetro "user_id" acepta cualquier número sin validar la sesión, ¿por qué sigues usando el tuyo?`,
                `Miren a este atacante... intentando adivinar contraseñas cuando puede leer los registros ajenos cambiando un solo dígito.`,
                `¡Ja, ja, ja! Acceder al perfil del superusuario está a un cambio de parámetro en la Petición HTTP.`,
                `¿El identificador está encriptado en Base64? Decodifícalo, cambia el número y vuelve a enviarlo, genio.`,
                `Si la aplicación no verifica la propiedad del recurso, el servidor te entregará los archivos de cualquiera que solicites.`,
                `¡Ja, ja, ja! Tanto esfuerzo buscando vulnerabilidades complejas y caíste derrotado ante una simple referencia directa.`,
                `Cambia la variable en el cuerpo del JSON que envías por POST y observa cómo accedes a datos confidenciales.`,
                `Miren cómo duda antes de probar un UUID diferente... la falta de control de acceso está gritándote en la cara.`,
                `¡Ja, ja, ja! Un IDOR tan evidente y tú sigues interactuando con la interfaz gráfica como un usuario inocente.`
            ],
            'index': [
                `¡Ja, ja, ja! ¡Miren esa cara de desconcierto absoluto! ¡No tienes NINGUNA idea de qué hacer aquí!`,
                `¿Buscando algo que ni tú sabes qué es? Tu confusión me resulta extremadamente entretenida.`,
                `El directorio raíz tiene el index desprotegido y tú sigues atascado en la página de bienvenida.`,
                `¡Ja, ja, ja! Ocultar archivos a la vista no sirve de nada si dejas el listado de directorios abierto.`,
                `Revisa el archivo robots.txt o los recursos ocultos en el índice antes de que me dé un ataque de risa.`,
                `Miren cómo inspecciona elementos invisibles mientras los archivos reales están expuestos en el árbol web.`,
                `¡Ja, ja, ja! Un escaneo de rutas básico te habría mostrado la estructura en tres segundos.`,
                `Navegar por las carpetas del servidor sin restricciones es un regalo que no estás sabiendo aprovechar.`,
                `¿Esperas que la bandera aparezca con un cartel brillante en el index principal? Sigue esperando.`,
                `El servidor te está mostrando la lista completa de archivos... solo tienes que hacer clic en el correcto.`,
                `¡Ja, ja, ja! ¿Sigues buscando en el archivo HTML principal cuando la carpeta "uploads" o "backup" está pública?`,
                `Revisar los archivos sitemap.xml o .DS_Store te habría mostrado la ruta secreta hace veinte minutos.`,
                `Miren a este competidor... la navegación por directorios está activa y él no sabe cómo subir un nivel en la URL.`,
                `¡Ja, ja, ja! Un archivo con extensión .bak o .old en el índice raíz contiene la clave que tanto buscas.`,
                `Si el servidor devuelve un Index Of desprotegido, no necesitas herramientas complejas, solo usar el navegador.`,
                `Buscar rutas ocultas con fuerza bruta es inútil si ni siquiera has revisado los enlaces públicos en el código.`,
                `¡Ja, ja, ja! Miren cómo pasa por alto la carpeta con permisos de lectura abiertos. ¡Qué ceguera tan oportuna!`,
                `Un archivo ejecutable de respaldo está colgado en el directorio público y tú sigues leyendo la página de inicio.`,
                `¿Escribiendo rutas aleatorias en la barra de direcciones? Un simple análisis de la estructura te daría la respuesta.`,
                `¡Ja, ja, ja! El índice está tan expuesto que hasta un motor de búsqueda ya habría indexado la bandera.`
            ]
        };

        const key = pistasDesafios[desafio] ? desafio : 'index';
        const lista = pistasDesafios[key];
        const ind = this.pistaIndicePorDesafio[key] ?? 0;
        const frase = lista[ind % lista.length];
        this.pistaIndicePorDesafio[key] = (ind + 1) % lista.length;

        this.hablar(frase, this.nivelEnojo);
    }

    gritoInicioHackathon() {
        const texto = "¡ATENCIÓN A TODOS LOS EQUIPOS DE LA UNIDAD DE CIENCIA Y TECNOLOGÍA! ¡EL HACKATÓN 2026 HA INICIADO OFICIALMENTE! ¡EL TIEMPO COMIENZA A CORRER AHORA MISMO! ¡DEMUESTREN DE QUÉ ESTÁN HECHOS Y QUE COMIENCE LA COMPETENCIA!";
        this.hablar(texto, 1);
    }
}

// ========== INSTANCIACIÓN (SINGLETON) ==========
if (!window.iaAvatarWidgetInstantiated) {
    window.iaAvatarWidgetInstantiated = true;
    const iniciarWidget = () => {
        if (!window.iaAvatarWidget) {
            window.iaAvatarWidget = new IAAvatarWidget();
            if (window.hackathonJustStarted && !window.iaAvatarWidget.esPaginaIndex()) {
                setTimeout(() => {
                    if (window.iaAvatarWidget) window.iaAvatarWidget.gritoInicioHackathon();
                }, 1500);
            }
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciarWidget);
    } else {
        iniciarWidget();
    }
}