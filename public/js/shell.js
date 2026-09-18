/*
 * Cascarón de la app: cajón del menú en móvil, menú contraído en escritorio,
 * menú de usuario y selectores de tema e idioma. Sin dependencias y sin paso
 * de compilación: producción se despliega copiando archivos.
 */
(function () {
    'use strict';

    var raiz = document.documentElement;
    var cuerpo = document.body;
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content;

    function guardar(url, datos) {
        return fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify(datos),
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
        });
    }

    function marcar(selector, atributo, valor) {
        document.querySelectorAll(selector).forEach(function (boton) {
            boton.setAttribute('aria-checked', boton.getAttribute(atributo) === valor ? 'true' : 'false');
        });
    }

    /* --- Cajón del menú (móvil) ------------------------------------------ */

    var abrir = document.querySelector('[data-shell-open]');

    function menuMovil(abierto) {
        cuerpo.classList.toggle('menu-abierto', abierto);
        if (abrir) abrir.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    }

    if (abrir) abrir.addEventListener('click', function () { menuMovil(true); });
    document.querySelectorAll('[data-shell-close]').forEach(function (el) {
        el.addEventListener('click', function () { menuMovil(false); });
    });

    /* --- Menú contraído (escritorio) ------------------------------------- */

    document.querySelectorAll('[data-shell-collapse]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var mini = raiz.classList.toggle('menu-mini');
            try { localStorage.setItem('menu-mini', mini ? '1' : '0'); } catch (e) {}
        });
    });

    /* --- Menú de usuario -------------------------------------------------- */

    var usuario = document.querySelector('[data-shell-menu]');
    var botonUsuario = usuario && usuario.querySelector('[data-shell-menu-btn]');
    var menu = usuario && usuario.querySelector('.shell-menu');

    function menuUsuario(abierto) {
        if (!menu) return;
        menu.hidden = !abierto;
        botonUsuario.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    }

    if (botonUsuario) {
        botonUsuario.addEventListener('click', function (e) {
            e.stopPropagation();
            menuUsuario(menu.hidden);
        });
        document.addEventListener('click', function (e) {
            if (!usuario.contains(e.target)) menuUsuario(false);
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        menuMovil(false);
        menuUsuario(false);
    });

    /* --- Tema ------------------------------------------------------------- */

    var sistemaOscuro = window.matchMedia('(prefers-color-scheme: dark)');

    function aplicarTema(tema) {
        var oscuro = tema === 'dark' || (tema === 'system' && sistemaOscuro.matches);
        raiz.dataset.theme = tema;
        raiz.classList.toggle('dark', oscuro);
        raiz.style.colorScheme = oscuro ? 'dark' : 'light';
        document.cookie = 'app_theme_resolved=' + (oscuro ? 'dark' : 'light') + ';path=/;max-age=31536000;SameSite=Lax';
        marcar('[data-theme-value]', 'data-theme-value', tema);
    }

    document.querySelectorAll('[data-theme-value]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var tema = boton.getAttribute('data-theme-value');
            aplicarTema(tema);
            guardar(cuerpo.dataset.rutaTema, { theme: tema }).catch(function (e) {
                console.warn('No se pudo guardar el tema:', e);
            });
        });
    });

    // Con "sistema", seguir al sistema operativo si cambia mientras la página está abierta.
    var alCambiarSistema = function () {
        if (raiz.dataset.theme === 'system') aplicarTema('system');
    };
    if (sistemaOscuro.addEventListener) sistemaOscuro.addEventListener('change', alCambiarSistema);
    else if (sistemaOscuro.addListener) sistemaOscuro.addListener(alCambiarSistema);

    /* --- Idioma ----------------------------------------------------------- */

    // Los textos vienen del servidor: cambiar de idioma es guardar y recargar.
    document.querySelectorAll('[data-locale-value]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            if (boton.getAttribute('aria-checked') === 'true') return;
            var idioma = boton.getAttribute('data-locale-value');
            marcar('[data-locale-value]', 'data-locale-value', idioma);
            guardar(cuerpo.dataset.rutaIdioma, { locale: idioma })
                .then(function () { window.location.reload(); })
                .catch(function (e) {
                    console.warn('No se pudo guardar el idioma:', e);
                    window.location.reload();
                });
        });
    });

    // Campana de respuestas de prospectos: revisa cada minuto y suena cuando
    // llega una nueva. El navegador sólo deja sonar después de que la persona
    // hizo clic en la página al menos una vez.
    var campana = document.querySelector('[data-respuestas]');
    if (campana) {
        var contador = campana.querySelector('.shell-bell-count');
        var tituloBase = document.title;
        var clave = 'crm-respuesta-ultima';
        var audio = null;

        var sonar = function () {
            try {
                audio = audio || new (window.AudioContext || window.webkitAudioContext)();
                [880, 1320].forEach(function (frecuencia, i) {
                    var osc = audio.createOscillator();
                    var vol = audio.createGain();
                    var inicio = audio.currentTime + i * 0.18;
                    osc.frequency.value = frecuencia;
                    vol.gain.setValueAtTime(0.0001, inicio);
                    vol.gain.exponentialRampToValueAtTime(0.25, inicio + 0.02);
                    vol.gain.exponentialRampToValueAtTime(0.0001, inicio + 0.35);
                    osc.connect(vol).connect(audio.destination);
                    osc.start(inicio);
                    osc.stop(inicio + 0.4);
                });
            } catch (e) { /* sin audio, queda el contador */ }
        };

        var revisar = function () {
            fetch(campana.dataset.respuestas, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (datos) {
                    if (!datos) return;
                    contador.hidden = datos.nuevas < 1;
                    contador.textContent = datos.nuevas > 9 ? '9+' : datos.nuevas;
                    document.title = (datos.nuevas ? '(' + datos.nuevas + ') ' : '') + tituloBase;

                    var anterior = null;
                    try { anterior = localStorage.getItem(clave); } catch (e) {}
                    if (anterior !== null && datos.ultima > Number(anterior) && datos.nuevas > 0) {
                        sonar();
                        if (window.Notification && Notification.permission === 'granted') {
                            new Notification(campana.dataset.respuestasTitulo);
                        }
                    }
                    try { localStorage.setItem(clave, String(datos.ultima)); } catch (e) {}
                })
                .catch(function () {});
        };

        // El primer clic en la página habilita el sonido y, si se puede, los avisos del sistema.
        document.addEventListener('click', function habilitar() {
            try { audio = audio || new (window.AudioContext || window.webkitAudioContext)(); } catch (e) {}
            if (window.Notification && Notification.permission === 'default') Notification.requestPermission();
            document.removeEventListener('click', habilitar);
        });

        revisar();
        setInterval(revisar, 60000);
    }
})();
