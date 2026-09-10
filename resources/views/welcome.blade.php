@extends('layouts.app')

@section('content')
    <div class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">{{ __('Gestión Total para tu negocio') }}</h1>
            <p class="hero-subtitle">{{ __('ERP + POS + Ventas Online. Todo lo que necesitas para crecer, en un solo lugar.') }}</p>
            <div class="hero-actions">
                @guest
                    <a href="{{ route('register') }}" class="btn-hero-primary">{{ __('Comenzar Ahora') }}</a>
                    <a href="{{ route('login') }}" class="btn-hero-secondary">{{ __('Iniciar Sesión') }}</a>
                @else
                    <a href="#" class="btn-hero-primary">{{ __('Ir al Dashboard') }}</a>
                @endguest
            </div>
        </div>
    </div>

    <div class="features-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">{{ __('10 Características Esenciales') }}</h2>
                <p class="section-subtitle">{{ __('Diseñado para simplificar tu operación y aumentar tus ventas.') }}</p>
            </div>

            <div class="features-grid">
                @foreach($features as $feature)
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <!-- Simple SVG placeholder or mapping could go here. Using a generic colored dot for now or CSS class -->
                            <div class="feature-icon icon-{{ $feature['icon'] }}"></div>
                        </div>
                        <h3 class="feature-title">{{ $feature['title'] }}</h3>
                        <p class="feature-description">{{ $feature['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection