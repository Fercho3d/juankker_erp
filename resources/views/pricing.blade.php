@extends('layouts.app')
@push('styles')<script src="https://cdn.tailwindcss.com"></script>@endpush

@section('content')
<div class="max-w-6xl mx-auto px-6 py-12">
    @if(session('premium_required'))
        <div class="mb-6 px-4 py-3 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl">🔒 {{ session('premium_required') }}</div>
    @endif
    @if(session('subscription_expired'))
        <div class="mb-6 px-4 py-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl">Tu suscripción expiró. Elige un plan para reactivar tu ERP.</div>
    @endif

    <div class="text-center mb-12">
        <span class="text-blue-600 text-sm font-semibold tracking-widest uppercase">Planes</span>
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2">Un plan para cada etapa de tu empresa</h1>
        <p class="text-gray-500 mt-3">Empieza gratis 30 días. Sin tarjeta, sin compromisos.</p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($plans as $plan)
            <div class="bg-white rounded-2xl border {{ $plan->destacado ? 'border-blue-500 shadow-lg ring-1 ring-blue-500' : 'border-gray-200 shadow-sm' }} p-6 flex flex-col">
                @if($plan->destacado)<span class="self-start px-2.5 py-0.5 rounded-full bg-blue-600 text-white text-xs font-semibold mb-3">Más popular</span>@endif
                <h3 class="text-lg font-bold text-gray-900">{{ $plan->name }}</h3>
                <p class="text-sm text-gray-500 mb-4 min-h-[2.5rem]">{{ $plan->tagline }}</p>
                @if($showPrices)
                    <div class="mb-4">
                        <span class="text-3xl font-bold text-gray-900">${{ number_format((float) $plan->price, 0) }}</span>
                        <span class="text-gray-500 text-sm">/mes</span>
                    </div>
                @endif
                <ul class="space-y-2 text-sm text-gray-600 mb-6 flex-1">
                    <li>👥 Hasta {{ $plan->max_users }} usuarios</li>
                    <li>🏢 {{ $plan->max_branches }} sucursal(es)</li>
                    <li>📦 {{ $plan->max_products }} productos</li>
                    <li>☁️ {{ $plan->max_storage_gb }} GB de almacenamiento</li>
                    @foreach(($plan->features ?? []) as $f)<li>✓ {{ $f }}</li>@endforeach
                </ul>
                @auth
                    <form method="POST" action="{{ route('subscription.confirm', $plan->key) }}">
                        @csrf
                        <button class="w-full py-2.5 rounded-xl {{ $plan->destacado ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-900 text-white hover:bg-gray-800' }} font-semibold transition">
                            {{ $plan->isFree() ? 'Usar plan gratis' : 'Elegir '.$plan->name }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('register') }}" class="block text-center w-full py-2.5 rounded-xl {{ $plan->destacado ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-900 text-white hover:bg-gray-800' }} font-semibold transition">Empezar gratis</a>
                @endauth
            </div>
        @endforeach
    </div>
</div>
@endsection
