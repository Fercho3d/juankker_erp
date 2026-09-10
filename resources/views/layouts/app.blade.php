@php
    $tema = \App\Support\Theme::current();
    $temaResuelto = \App\Support\Theme::resolved();

    // Menú lateral por secciones: [etiqueta, ruta, activo, ícono, es Pro].
    $nav = [];
    if (auth()->check()) {
        $u = auth()->user();
        $nav = [
            __('Ventas') => [
                [__('Pendientes'), route('crm.pendientes'), request()->routeIs('crm.pendientes'), 'agenda', true],
                [__('Embudo'), route('crm.tablero'), request()->routeIs('crm.tablero', 'crm.leads.*', 'crm.papelera'), 'embudo', true],
                [__('Punto de venta'), route('pos.index'), request()->routeIs('pos.*'), 'pos', true],
                [__('Ventas'), route('sales.index'), request()->routeIs('sales.*'), 'recibo', true],
            ],
            __('Catálogo') => [
                [__('Productos'), route('productos.index'), request()->routeIs('productos.*'), 'paquete', false],
                [__('Inventario'), route('inventario.index'), request()->routeIs('inventario.*'), 'almacen', true],
                [__('Categorías'), route('categorias.index'), request()->routeIs('categorias.*'), 'etiqueta', false],
                [__('Marcas'), route('marcas.index'), request()->routeIs('marcas.*'), 'marca', false],
                [__('Atributos'), route('atributos-producto.index'), request()->routeIs('atributos-producto.*'), 'atributos', false],
            ],
            __('Contactos') => [
                [__('Clientes'), route('clientes.index'), request()->routeIs('clientes.*'), 'usuarios', false],
                [__('Proveedores'), route('proveedores.index'), request()->routeIs('proveedores.*'), 'camion', false],
            ],
            __('Cuenta') => array_filter([
                $u->isSuperadmin()
                    ? [__('Superadmin'), route('superadmin.dashboard'), request()->routeIs('superadmin.*'), 'escudo', false]
                    : [__('Mi plan'), route('subscription.index'), request()->routeIs('subscription.*'), 'tarjeta', false],
                [__('Mi perfil'), route('profile.edit'), request()->routeIs('profile.*'), 'usuario', false],
            ]),
        ];
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
            <a href="{{ route('crm.pendientes') }}" class="shell-brand-link">
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
                    @unless (auth()->user()->isSuperadmin())
                        <a href="{{ route('subscription.index') }}" class="shell-menu-item" role="menuitem">
                            @include('partials.nav-icon', ['icon' => 'tarjeta']) {{ __('Mi plan') }}
                        </a>
                    @endunless
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
