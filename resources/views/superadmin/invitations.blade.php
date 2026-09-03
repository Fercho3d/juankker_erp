@extends('layouts.superadmin')
@section('title', 'Invitaciones')

@section('content')
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 max-w-2xl mb-8">
        <h2 class="font-semibold text-navy mb-4">Nueva invitación</h2>
        <form method="POST" action="{{ route('superadmin.invitations.store') }}" class="grid md:grid-cols-2 gap-4">
            @csrf
            <label class="text-sm md:col-span-2">Correo <input name="email" type="email" required class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300"></label>
            <label class="text-sm">Empresa <input name="organization_name" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300"></label>
            <label class="text-sm">Plan
                <select name="plan" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300">
                    @foreach($plans as $p)<option value="{{ $p->key }}">{{ $p->name }}</option>@endforeach
                </select>
            </label>
            <label class="text-sm">Entorno
                <select name="environment" class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300">
                    <option value="beta">Beta</option><option value="produccion">Producción</option>
                </select>
            </label>
            <div class="md:col-span-2"><button class="px-5 py-2.5 rounded-xl bg-brand text-white text-sm font-semibold">Enviar invitación</button></div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr><th class="px-5 py-3">Correo</th><th class="px-5 py-3">Plan</th><th class="px-5 py-3">Entorno</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Enviada</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($invitations as $inv)
                    <tr>
                        <td class="px-5 py-3 font-medium">{{ $inv->email }}</td>
                        <td class="px-5 py-3 capitalize">{{ $inv->plan }}</td>
                        <td class="px-5 py-3">{{ $inv->environment }}</td>
                        <td class="px-5 py-3">{{ $inv->isAccepted() ? 'Aceptada' : ($inv->isExpired() ? 'Expirada' : 'Pendiente') }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $inv->sent_at?->diffForHumans() }}</td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            @unless($inv->isAccepted())
                                <form method="POST" action="{{ route('superadmin.invitations.resend', $inv) }}" class="inline">@csrf
                                    <button class="text-brand text-xs font-semibold hover:underline">Reenviar</button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route('superadmin.invitations.destroy', $inv) }}" class="inline ml-2">@csrf @method('DELETE')
                                <button class="text-red-500 text-xs hover:underline">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Sin invitaciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $invitations->links() }}</div>
@endsection
