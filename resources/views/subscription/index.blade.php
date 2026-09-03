@extends('layouts.app')
@push('styles')<script src="https://cdn.tailwindcss.com"></script>@endpush

@section('content')
<div class="max-w-4xl mx-auto px-6 py-12">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Mi suscripción</h1>

    @if(session('status'))
        <div class="mb-6 px-4 py-3 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">{{ session('status') }}</div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-8">
        <div class="grid sm:grid-cols-3 gap-4">
            <div><div class="text-sm text-gray-500">Plan actual</div><div class="text-lg font-bold capitalize">{{ $org->plan }}</div></div>
            <div><div class="text-sm text-gray-500">Estado</div><div class="text-lg font-bold capitalize">{{ $org->subscription_status }}</div></div>
            <div><div class="text-sm text-gray-500">
                {{ $org->inTrial() ? 'Prueba vence' : 'Renueva' }}</div>
                <div class="text-lg font-bold">{{ optional($org->inTrial() ? $org->trial_ends_at : $org->subscription_ends_at)->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>
        @if($org->inTrial())
            <p class="text-sm text-amber-700 mt-4">Te quedan {{ $org->trialDaysRemaining() }} días de prueba. Elige un plan para no perder acceso.</p>
        @endif
    </div>

    <h2 class="text-lg font-semibold text-gray-900 mb-4">Cambiar de plan</h2>
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($plans as $plan)
            <div class="bg-white rounded-2xl border {{ $org->plan === $plan->key ? 'border-blue-500 ring-1 ring-blue-500' : 'border-gray-200' }} shadow-sm p-5">
                <h3 class="font-bold text-gray-900">{{ $plan->name }}</h3>
                <div class="my-2"><span class="text-2xl font-bold">${{ number_format((float) $plan->price, 0) }}</span><span class="text-sm text-gray-500">/mes</span></div>
                <ul class="text-xs text-gray-500 space-y-1 mb-4">
                    <li>{{ $plan->max_users }} usuarios · {{ $plan->max_branches }} sucursal(es)</li>
                    <li>{{ $plan->max_products }} productos · {{ $plan->max_storage_gb }} GB</li>
                </ul>
                @if($org->plan === $plan->key)
                    <span class="block text-center text-sm font-semibold text-blue-600 py-2">Plan actual</span>
                @else
                    <form method="POST" action="{{ route('subscription.confirm', $plan->key) }}">@csrf
                        <button class="w-full py-2 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800">Cambiar a este</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>

    @if($org->esPremium() && $org->subscription_status === 'active')
        <form method="POST" action="{{ route('subscription.cancel') }}" class="mt-8" onsubmit="return confirm('¿Cancelar tu suscripción?')">@csrf
            <button class="text-sm text-red-600 hover:underline">Cancelar suscripción</button>
        </form>
    @endif
</div>
@endsection
