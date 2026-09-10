{{--
    Aplica el tema antes del primer pintado, para que no parpadee en blanco. Va
    en línea y arriba del <head>, antes de los estilos. Deja además en una cookie
    el tema ya resuelto, para que el servidor pinte bien la siguiente página
    cuando la preferencia es "sistema".
--}}
<script>
    (function () {
        var raiz = document.documentElement;
        var tema = raiz.dataset.theme || 'system';
        var oscuro = tema === 'dark' ||
            (tema === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        raiz.classList.toggle('dark', oscuro);
        raiz.style.colorScheme = oscuro ? 'dark' : 'light';
        document.cookie = 'app_theme_resolved=' + (oscuro ? 'dark' : 'light') + ';path=/;max-age=31536000;SameSite=Lax';

        // Menú lateral contraído (sólo escritorio): antes de pintar, para que no brinque.
        try {
            if (localStorage.getItem('menu-mini') === '1') raiz.classList.add('menu-mini');
        } catch (e) {}
    })();
</script>
