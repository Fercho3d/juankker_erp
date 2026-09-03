@extends('layouts.superadmin')
@section('title', 'Dashboard')

@section('content')
    @php $cards = [
        ['Organizaciones', $kpis['orgs'], 'bg-navy text-white'],
        ['Activas', $kpis['orgs_activas'], 'bg-white'],
        ['En prueba', $kpis['orgs_trial'], 'bg-white'],
        ['Bajas', $kpis['orgs_baja'], 'bg-white'],
        ['Usuarios', $kpis['usuarios'], 'bg-white'],
        ['MRR estimado', '$'.number_format($kpis['ingreso_mensual'], 2), 'bg-brand text-white'],
    ]; @endphp
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        @foreach($cards as [$label,$value,$cls])
            <div class="{{ $cls }} rounded-2xl p-5 shadow-sm border border-slate-200">
                <div class="text-sm opacity-70">{{ $label }}</div>
                <div class="text-3xl font-bold mt-1">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
            <h2 class="font-semibold text-navy mb-4">Organizaciones por plan</h2>
            @foreach($porPlan as $plan => $total)
                <div class="flex items-center justify-between py-1.5 text-sm border-b border-slate-100">
                    <span class="capitalize">{{ $plan }}</span><span class="font-semibold">{{ $total }}</span>
                </div>
            @endforeach
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
            <h2 class="font-semibold text-navy mb-4">Por estado de suscripción</h2>
            @foreach($porEstado as $estado => $total)
                <div class="flex items-center justify-between py-1.5 text-sm border-b border-slate-100">
                    <span class="capitalize">{{ $estado }}</span><span class="font-semibold">{{ $total }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endsection
