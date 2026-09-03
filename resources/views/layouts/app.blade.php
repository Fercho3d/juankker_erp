<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>

<body>
    <nav class="navbar">
        <a href="{{ url('/') }}" class="navbar-brand" style="display:inline-flex;align-items:center;gap:8px;">
            <span style="width:26px;height:26px;background:var(--primary-color);border-radius:7px;display:inline-block;"></span>
            <span>Juankker <span style="color:var(--text-main);">ERP</span></span>
        </a>
        <div class="navbar-nav">
            @guest
                <a href="{{ route('login') }}" class="nav-link">Login</a>
                <a href="{{ route('register') }}" class="btn-nav">Get Started</a>
            @else
                <a href="{{ route('clientes.index') }}" class="nav-link">Clientes</a>
                <span class="nav-link">|</span>
                <a href="{{ route('proveedores.index') }}" class="nav-link">Proveedores</a>
                <span class="nav-link">|</span>
                <a href="{{ route('productos.index') }}" class="nav-link">Productos</a>
                <span class="nav-link">|</span>
                <a href="{{ route('categorias.index') }}" class="nav-link">Categorías</a>
                <span class="nav-link">|</span>
                <a href="{{ route('marcas.index') }}" class="nav-link">Marcas</a>
                <span class="nav-link">|</span>
                <a href="{{ route('atributos-producto.index') }}" class="nav-link">Atributos</a>
                <span class="nav-link">|</span>
                <a href="{{ route('inventario.index') }}" class="nav-link">Inventario @include('partials.pro-badge')</a>
                <span class="nav-link">|</span>
                <a href="{{ route('pos.index') }}" class="nav-link fw-bold text-primary">Punto de Venta @include('partials.pro-badge')</a>
                <span class="nav-link">|</span>
                <a href="{{ route('sales.index') }}" class="nav-link">Historial Ventas @include('partials.pro-badge')</a>
                <span class="nav-link">|</span>
                @if(Auth::user()->isSuperadmin())
                    <a href="{{ route('superadmin.dashboard') }}" class="nav-link" style="color:#1e3a8a;font-weight:700;">⚙ Superadmin</a>
                    <span class="nav-link">|</span>
                @else
                    <a href="{{ route('subscription.index') }}" class="nav-link" title="Mi suscripción">Plan</a>
                    <span class="nav-link">|</span>
                @endif
                <a href="{{ route('profile.edit') }}" class="nav-link" title="Mi Perfil">{{ Auth::user()->name }}</a>
                <span class="nav-link">|</span>
                <span class="nav-link">{{ Auth::user()->organization->name ?? 'No Org' }}</span>
                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="nav-link"
                        style="background:none; border:none; cursor:pointer;">Logout</button>
                </form>
            @endguest
        </div>
    </nav>

    @include('partials.trial-banner')

    <main>
        @yield('content')
    </main>
    @stack('scripts')
</body>

</html>