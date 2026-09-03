@extends('layouts.superadmin')
@section('title', 'Solicitudes beta')

@section('content')
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr><th class="px-5 py-3">Nombre</th><th class="px-5 py-3">Correo</th><th class="px-5 py-3">Empresa</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Fecha</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($requests as $r)
                    <tr>
                        <td class="px-5 py-3 font-medium">{{ $r->nombre }}</td>
                        <td class="px-5 py-3">{{ $r->email }}</td>
                        <td class="px-5 py-3">{{ $r->empresa ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ ['pendiente'=>'bg-amber-100 text-amber-700','invitado'=>'bg-emerald-100 text-emerald-700','descartado'=>'bg-slate-200 text-slate-500'][$r->status] ?? '' }}">{{ $r->status }}</span>
                        </td>
                        <td class="px-5 py-3 text-slate-500">{{ $r->created_at->diffForHumans() }}</td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            @if($r->status === 'pendiente')
                                <form method="POST" action="{{ route('superadmin.beta.invite', $r) }}" class="inline">@csrf
                                    <button class="text-brand text-xs font-semibold hover:underline">Invitar</button>
                                </form>
                                <form method="POST" action="{{ route('superadmin.beta.dismiss', $r) }}" class="inline ml-2">@csrf
                                    <button class="text-red-500 text-xs hover:underline">Descartar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Sin solicitudes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
