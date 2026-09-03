@extends('layouts.superadmin')
@section('title', 'Organizaciones')

@section('content')
    <form method="GET" class="flex flex-wrap gap-3 mb-6">
        <input name="q" value="{{ request('q') }}" placeholder="Buscar empresa…"
               class="px-4 py-2 rounded-xl border border-slate-300 text-sm w-64">
        <select name="status" class="px-4 py-2 rounded-xl border border-slate-300 text-sm">
            <option value="">Todos los estados</option>
            @foreach(['trial','active','free','expired','cancelled'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button class="px-5 py-2 rounded-xl bg-navy text-white text-sm font-semibold">Filtrar</button>
    </form>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-5 py-3">Empresa</th><th class="px-5 py-3">Plan</th>
                    <th class="px-5 py-3">Estado</th><th class="px-5 py-3">Usuarios</th>
                    <th class="px-5 py-3">Productos</th><th class="px-5 py-3">Prueba</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($orgs as $org)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium">{{ $org->name }}</td>
                        <td class="px-5 py-3 capitalize">{{ $org->plan }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $org->isActive() ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $org->subscription_status }}</span>
                        </td>
                        <td class="px-5 py-3">{{ $org->users_count }}/{{ $org->max_users }}</td>
                        <td class="px-5 py-3">{{ $org->products_count }}/{{ $org->max_products }}</td>
                        <td class="px-5 py-3">{{ $org->inTrial() ? $org->trialDaysRemaining().' días' : '—' }}</td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('superadmin.show', $org) }}" class="text-brand font-semibold hover:underline">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-8 text-center text-slate-400">Sin organizaciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orgs->links() }}</div>
@endsection
