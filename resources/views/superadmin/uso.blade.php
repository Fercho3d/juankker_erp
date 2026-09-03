@extends('layouts.superadmin')
@section('title', 'Estadísticas de uso')

@section('content')
    <h2 class="font-semibold text-navy mb-3">Uso por organización</h2>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-x-auto mb-10">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-5 py-3">Empresa</th><th class="px-5 py-3">Plan</th>
                    <th class="px-5 py-3">Usuarios</th><th class="px-5 py-3">Productos</th>
                    <th class="px-5 py-3">Ventas</th><th class="px-5 py-3">Ingresos</th>
                    <th class="px-5 py-3">Última actividad</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($orgs as $o)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium">{{ $o->name }}</td>
                        <td class="px-5 py-3 capitalize">{{ $o->plan }}</td>
                        <td class="px-5 py-3">{{ $o->users_count }}</td>
                        <td class="px-5 py-3">{{ $o->products_count }}</td>
                        <td class="px-5 py-3">{{ $o->ventas_count }}</td>
                        <td class="px-5 py-3">${{ number_format((float) $o->ingresos, 2) }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $o->ultima_actividad ? \Illuminate\Support\Carbon::parse($o->ultima_actividad)->diffForHumans() : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2 class="font-semibold text-navy mb-3">Actividad de usuarios (últimos accesos)</h2>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-5 py-3">Usuario</th><th class="px-5 py-3">Empresa</th>
                    <th class="px-5 py-3">Ingresos (logins)</th><th class="px-5 py-3">Último acceso</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($usuarios as $u)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium">{{ $u->name }}<div class="text-xs text-slate-400">{{ $u->email }}</div></td>
                        <td class="px-5 py-3">{{ $u->organization->name ?? '—' }}</td>
                        <td class="px-5 py-3">{{ $u->login_count }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $u->last_login_at?->diffForHumans() ?? 'Nunca' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
