@php
    $tema = \App\Support\Theme::current();
    $temaResuelto = \App\Support\Theme::resolved();

    // Menú lateral por secciones: [etiqueta, ruta, activo, ícono, es Pro, módulo].
    // Sólo se ve lo que el perfil del usuario permite; una sección vacía no sale.
    $nav = [];
    if (auth()->check()) {
        $u = auth()->user();
        $secciones = [
            __('Ventas') => [
                [__('Pendientes'), 'crm.pendientes', ['crm.pendientes'], 'agenda', true, 'crm'],
                [__('Embudo'), 'crm.tablero', ['crm.tablero', 'crm.leads.*', 'crm.papelera'], 'embudo', true, 'crm'],
                [__('Punto de venta'), 'pos.index', ['pos.*'], 'pos', true, 'pos'],
                [__('Ventas'), 'sales.index', ['sales.*'], 'recibo', true, 'ventas'],
            ],
            __('Catálogo') => [
                [__('Productos'), 'productos.index', ['productos.*'], 'paquete', false, 'productos'],
                [__('Inventario'), 'inventario.index', ['inventario.*'], 'almacen', true, 'inventario'],
                [__('Categorías'), 'categorias.index', ['categorias.*'], 'etiqueta', false, 'productos'],
                [__('Marcas'), 'marcas.index', ['marcas.*'], 'marca', false, 'productos'],
                [__('Atributos'), 'atributos-producto.index', ['atributos-producto.*'], 'atributos', false, 'productos'],
            ],
            __('Contactos') => [
                [__('Clientes'), 'clientes.index', ['clientes.*'], 'usuarios', false, 'clientes'],
                [__('Proveedores'), 'proveedores.index', ['proveedores.*'], 'camion', false, 'proveedores'],
            ],
            __('Cuenta') => [
                [__('Equipo'), 'team.index', ['team.*'], 'equipo', false, 'equipo'],
                $u->isSuperadmin()
                    ? [__('Superadmin'), 'superadmin.dashboard', ['superadmin.*'], 'escudo', false, null]
                    : [__('Mi plan'), 'subscription.index', ['subscription.*'], 'tarjeta', false, 'plan'],
                [__('Mi perfil'), 'profile.edit', ['profile.*'], 'usuario', false, null],
            ],
        ];
        foreach ($secciones as $seccion => $items) {
            $visibles = [];
            foreach ($items as [$etiqueta, $ruta, $patrones, $icono, $esPro, $modulo]) {
                if ($modulo === null || $u->puede($modulo)) {
                    $visibles[] = [$etiqueta, route($ruta), request()->routeIs(...$patrones), $icono, $esPro];
                }
            }
            if ($visibles) {
                $nav[$seccion] = $visibles;
            }
        }
    }

    $activo = collect($nav)->flatten(1)->first(fn ($item) => $item[2]);
    $titulo = $activo[0] ?? null;
    $version = fn (string $archivo) => asset($archivo).'?v='.@filemtime(public_path($archivo));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="{{ $temaResuelto === \App\Support\Theme::Dark ? 'dark' : '' }}"
      data-theme="{{ $tema->value }}" style="color-scheme: {{ $temaResuelto->value }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titulo ? $titulo.' · ' : '' }}{{ config('app.name', 'Juankker ERP') }}</title>
    @include('partials.theme-script')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ $version('css/shell.css') }}">
    <link rel="stylesheet" href="{{ $version('css/dark.css') }}">
    @stack('styles')
</head>

<body class="shell-body {{ auth()->check() ? 'has-sidebar' : 'is-guest' }}"
      data-ruta-tema="{{ route('preferences.theme') }}" data-ruta-idioma="{{ route('preferences.locale') }}">

@auth
    <aside class="shell-sidebar" id="shell-sidebar" aria-label="{{ __('Menú principal') }}">
        <div class="shell-brand">
            <a href="{{ auth()->user()->inicio() }}" class="shell-brand-link">
                <span class="shell-logo" aria-hidden="true">J</span>
                <span class="shell-brand-name">Juankker <span>ERP</span></span>
            </a>
            <button type="button" class="shell-icon-btn shell-only-mobile" data-shell-close aria-label="{{ __('Cerrar menú') }}">
                @include('partials.nav-icon', ['icon' => 'cerrar'])
            </button>
        </div>

        <nav class="shell-nav">
            @foreach ($nav as $seccion => $items)
                <div class="shell-nav-section">
                    <p class="shell-nav-label">{{ $seccion }}</p>
                    @foreach ($items as [$etiqueta, $href, $esActivo, $icono, $esPro])
                        <a href="{{ $href }}" class="shell-link {{ $esActivo ? 'is-active' : '' }}"
                           @if ($esActivo) aria-current="page" @endif title="{{ $etiqueta }}">
                            @include('partials.nav-icon', ['icon' => $icono])
                            <span class="shell-link-text">{{ $etiqueta }}</span>
                            @if ($esPro) @include('partials.pro-badge') @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        <div class="shell-sidebar-foot">
            <button type="button" class="shell-link shell-collapse-btn shell-only-desktop" data-shell-collapse
                    title="{{ __('Contraer menú') }}" aria-label="{{ __('Contraer menú') }}">
                @include('partials.nav-icon', ['icon' => 'panel'])
                <span class="shell-link-text">{{ __('Contraer menú') }}</span>
            </button>
        </div>
    </aside>

    <div class="shell-backdrop" data-shell-close></div>

    <div class="shell-main">
        <header class="shell-header">
            <button type="button" class="shell-icon-btn shell-only-mobile" data-shell-open aria-label="{{ __('Abrir menú') }}"
                    aria-controls="shell-sidebar" aria-expanded="false">
                @include('partials.nav-icon', ['icon' => 'menu'])
            </button>

            <h1 class="shell-title">{{ $titulo ?? config('app.name') }}</h1>

            <div class="shell-toggles">
                @include('partials.locale-toggle')
                @include('partials.theme-toggle')
            </div>

            <div class="shell-user" data-shell-menu>
                <button type="button" class="shell-user-btn" data-shell-menu-btn aria-haspopup="true" aria-expanded="false">
                    <span class="shell-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="shell-user-name">
                        <span>{{ auth()->user()->name }}</span>
                        <small>{{ auth()->user()->organization->name ?? __('Sin organización') }}</small>
                    </span>
                    @include('partials.nav-icon', ['icon' => 'abajo', 'class' => 'shell-icon shell-caret'])
                </button>

                <div class="shell-menu" role="menu" hidden>
                    <div class="shell-menu-head">
                        <p>{{ auth()->user()->name }}</p>
                        <small>{{ auth()->user()->email }}</small>
                    </div>
                    <div class="shell-menu-prefs">
                        <div><span>{{ __('Idioma') }}</span> @include('partials.locale-toggle')</div>
                        <div><span>{{ __('Tema') }}</span> @include('partials.theme-toggle')</div>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="shell-menu-item" role="menuitem">
                        @include('partials.nav-icon', ['icon' => 'usuario']) {{ __('Mi perfil') }}
                    </a>
                    @if (auth()->user()->puede('equipo'))
                        <a href="{{ route('team.index') }}" class="shell-menu-item" role="menuitem">
                            @include('partials.nav-icon', ['icon' => 'equipo']) {{ __('Equipo') }}
                        </a>
                    @endif
                    @if (! auth()->user()->isSuperadmin() && auth()->user()->puede('plan'))
                        <a href="{{ route('subscription.index') }}" class="shell-menu-item" role="menuitem">
                            @include('partials.nav-icon', ['icon' => 'tarjeta']) {{ __('Mi plan') }}
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="shell-menu-item is-danger" role="menuitem">
                            @include('partials.nav-icon', ['icon' => 'salir']) {{ __('Cerrar sesión') }}
                        </button>
                    </form>
                </div>
            </div>
        </header>

        @include('partials.trial-banner')

        <main class="shell-content">
            @yield('content')
        </main>
    </div>
@else
    <header class="shell-guestbar">
        <a href="{{ url('/') }}" class="shell-brand-link">
            <span class="shell-logo" aria-hidden="true">J</span>
            <span class="shell-brand-name">Juankker <span>ERP</span></span>
        </a>
        <div class="shell-guestbar-end">
            <div class="shell-toggles is-guest">
                @include('partials.locale-toggle')
                @include('partials.theme-toggle')
            </div>
            <a href="{{ route('login') }}" class="shell-guest-link">{{ __('Iniciar sesión') }}</a>
            <a href="{{ route('register') }}" class="shell-guest-cta">{{ __('Empezar gratis') }}</a>
        </div>
    </header>

    <main class="shell-content">
        @yield('content')
    </main>
@endauth

<script src="{{ $version('js/shell.js') }}" defer></script>
@stack('scripts')
</body>
</html>
