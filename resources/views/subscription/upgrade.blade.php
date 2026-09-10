@extends('layouts.app')
@push('styles')<script src="https://cdn.tailwindcss.com"></script>@endpush

@section('content')
<div class="max-w-md mx-auto px-6 py-12">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ __('Activar :plan', ['plan' => __($plan->name)]) }}</h1>
        <div class="my-4"><span class="text-4xl font-bold">${{ number_format((float) $plan->price, 0) }}</span><span class="text-gray-500">{{ __('/mes') }}</span></div>
        <ul class="text-sm text-gray-600 space-y-1 mb-6">
            <li>{{ __(':n usuarios', ['n' => $plan->max_users]) }} · {{ trans_choice(':n sucursal|:n sucursales', $plan->max_branches, ['n' => $plan->max_branches]) }}</li>
            <li>{{ __(':n productos', ['n' => $plan->max_products]) }} · {{ $plan->max_storage_gb }} GB</li>
        </ul>
        <form method="POST" action="{{ route('subscription.confirm', $plan->key) }}">@csrf
            <button class="w-full py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">{{ __('Confirmar y activar') }}</button>
        </form>
        <a href="{{ route('subscription.index') }}" class="block text-sm text-gray-500 mt-4 hover:underline">{{ __('Volver') }}</a>
    </div>
</div>
@endsection
