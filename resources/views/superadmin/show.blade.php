@extends('layouts.superadmin')
@section('title', $organization->name)

@section('content')
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @php $cards = [
            ['Usuarios', $stats['usuarios'].'/'.$organization->max_users],
            ['Productos', $stats['productos'].'/'.$organization->max_products],
            ['Ventas', $stats['ventas']],
            ['Ingresos', '$'.number_format((float) $stats['ingresos'], 2)],
        ]; @endphp
        @foreach($cards as [$l,$v])
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                <div class="text-sm text-slate-500">{{ $l }}</div>
                <div class="text-2xl font-bold mt-1 text-navy">{{ $v }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        {{-- Gestión de suscripción --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
            <h2 class="font-semibold text-navy mb-4">Suscripción</h2>
            <form method="POST" action="{{ route('superadmin.subscription.update', $organization) }}" class="space-y-3">
                @csrf @method('PUT')
                <label class="block text-sm">Plan
                    <select name="plan" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300">
                        @foreach(\App\Models\Plan::orderBy('orden')->get() as $p)
                            <option value="{{ $p->key }}" @selected($organization->plan===$p->key)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">Estado
                    <select name="subscription_status" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300">
                        @foreach(['trial','active','free','expired','cancelled'] as $s)
                            <option value="{{ $s }}" @selected($organization->subscription_status===$s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">Fin de prueba
                    <input type="date" name="trial_ends_at" value="{{ optional($organization->trial_ends_at)->format('Y-m-d') }}"
                           class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300">
                </label>
                <button class="px-4 py-2 rounded-lg bg-navy text-white text-sm font-semibold">Guardar</button>
            </form>
            <form method="POST" action="{{ route('superadmin.subscription.trial', $organization) }}" class="mt-3">
                @csrf
                <button class="text-brand text-sm font-semibold hover:underline">Activar prueba de 30 días</button>
            </form>
        </div>

        {{-- Usuarios --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
            <h2 class="font-semibold text-navy mb-4">Usuarios</h2>
            <div class="space-y-2">
                @foreach($usuarios as $u)
                    <div class="flex items-center justify-between text-sm border-b border-slate-100 pb-2">
                        <div>{{ $u->name }}<div class="text-xs text-slate-400">{{ $u->email }} · {{ $u->login_count }} logins</div></div>
                        <form method="POST" action="{{ route('superadmin.impersonate', $u) }}">@csrf
                            <button class="text-brand text-xs font-semibold hover:underline">Entrar como</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Actividad reciente --}}
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 mt-6">
        <h2 class="font-semibold text-navy mb-4">Actividad reciente</h2>
        @forelse($actividad as $log)
            <div class="flex items-center justify-between text-sm py-1.5 border-b border-slate-100">
                <span>{{ $log->user->name ?? 'Sistema' }} · <span class="text-slate-500">{{ $log->action }}</span></span>
                <span class="text-xs text-slate-400">{{ $log->created_at->diffForHumans() }}</span>
            </div>
        @empty
            <p class="text-sm text-slate-400">Sin actividad registrada.</p>
        @endforelse
    </div>
@endsection
