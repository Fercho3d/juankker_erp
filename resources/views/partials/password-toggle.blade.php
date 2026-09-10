{{--
    Botón de ver/ocultar contraseña.
    Se coloca dentro de un contenedor con position:relative junto al input.
    Uso: <div class="relative"><input type="password" ...>@include('partials.password-toggle')</div>
--}}
<button type="button" tabindex="-1"
        onclick="alternarPassword(this)"
        aria-label="{{ __('Mostrar contraseña') }}"
        class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 bg-transparent border-0 cursor-pointer leading-none">
    <svg class="ojo-abierto" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
    </svg>
    <svg class="ojo-cerrado hidden" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
        <line x1="1" y1="1" x2="23" y2="23"/>
    </svg>
</button>

@once
    @push('scripts')
        <script>
            function alternarPassword(boton) {
                const campo = boton.parentElement.querySelector('input');
                if (!campo) return;

                const oculto = campo.type === 'password';
                campo.type = oculto ? 'text' : 'password';
                boton.setAttribute('aria-label', oculto ? {{ \Illuminate\Support\Js::from(__('Ocultar contraseña')) }} : {{ \Illuminate\Support\Js::from(__('Mostrar contraseña')) }});
                boton.querySelector('.ojo-abierto').classList.toggle('hidden', oculto);
                boton.querySelector('.ojo-cerrado').classList.toggle('hidden', !oculto);
            }
        </script>
    @endpush
@endonce
