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
})();
