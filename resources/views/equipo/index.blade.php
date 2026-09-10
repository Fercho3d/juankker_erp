@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
@php
    $campo = 'px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15';
    $lleno = $cupo['usados'] >= $cupo['maximo'];
@endphp
<div class="max-w-5xl mx-auto px-6 py-8">

    <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Equipo') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('Quién entra a tu ERP y a qué partes.') }}</p>
        </div>
        <span class="px-3 py-1.5 rounded-full text-xs font-semibold {{ $lleno ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-600' }}">
            {{ __(':usados de :maximo usuarios de tu plan', ['usados' => $cupo['usados'], 'maximo' => $cupo['maximo']]) }}
        </span>
    </div>

    @if (session('status'))
        <div class="px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="px-4 py-3 mb-5 text-sm text-red-700 bg-red-50 border border-red-200 rounded-xl">{{ $errors->first() }}</div>
    @endif

    {{-- Invitar --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
        <h2 class="text-base font-bold text-gray-900">{{ __('Invitar a alguien') }}</h2>
        <p class="text-sm text-gray-500 mt-0.5 mb-4">{{ __('Le llega un correo para que cree su contraseña. La invitación dura 7 días.') }}</p>
        @if ($lleno)
            <p class="text-sm text-amber-700 m-0">
                {{ __('Ya usas todos los lugares de tu plan.') }}
                @if (auth()->user()->puede('plan'))
                    <a href="{{ route('subscription.index') }}" class="font-semibold text-indigo-600 no-underline hover:underline">{{ __('Cambiar de plan') }}</a>
                @endif
            </p>
        @else
            <form method="POST" action="{{ route('team.invite') }}" class="flex flex-wrap items-center gap-2.5">
                @csrf
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="{{ __('correo@empresa.com') }}" class="flex-1 min-w-[14rem] {{ $campo }}">
                <select name="role_id" required class="{{ $campo }}" aria-label="{{ __('Perfil') }}">
                    @foreach ($perfiles as $perfil)
                        <option value="{{ $perfil->id }}" @selected((int) old('role_id', $perfiles->firstWhere('nombre', 'Vendedor')?->id) === $perfil->id)>{{ __($perfil->nombre) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 cursor-pointer border-0">{{ __('Enviar invitación') }}</button>
            </form>
        @endif
    </div>

    {{-- Miembros --}}
    <h2 class="text-base font-bold text-gray-900 mb-3">{{ __('Miembros') }} <span class="text-gray-400 font-medium">({{ $miembros->count() }})</span></h2>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-8">
        @foreach ($miembros as $miembro)
            @php $editable = $miembro->id !== auth()->id() && ! $miembro->esDueno(); @endphp
            <div class="flex flex-wrap items-center gap-4 px-4 py-3.5 {{ ! $loop->last ? 'border-b border-gray-100' : '' }} {{ $miembro->activo ? '' : 'opacity-60' }}">
                <span class="shrink-0 w-9 h-9 rounded-full grid place-items-center text-sm font-bold text-white" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">{{ mb_strtoupper(mb_substr($miembro->name, 0, 1)) }}</span>
                <div class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-gray-900 truncate">
                        {{ $miembro->name }}
                        @if ($miembro->id === auth()->id()) <span class="text-gray-400 font-medium">· {{ __('tú') }}</span> @endif
                    </span>
                    <span class="block text-xs text-gray-500 truncate">{{ $miembro->email }}</span>
                </div>
                <span class="shrink-0 hidden md:inline text-xs text-gray-400 w-32 text-right">
                    {{ $miembro->last_login_at ? __('Entró :cuando', ['cuando' => $miembro->last_login_at->diffForHumans()]) : __('Nunca ha entrado') }}
                </span>
                <div class="shrink-0 flex items-center gap-2">
                    @if ($miembro->esDueno())
                        <span class="text-[11px] px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 font-semibold">{{ __('Dueño') }}</span>
                    @elseif (! $editable)
                        <span class="text-[11px] px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 font-semibold">{{ $miembro->role ? __($miembro->role->nombre) : __('Acceso completo') }}</span>
                    @else
                        <form method="POST" action="{{ route('team.member.update', $miembro) }}">
                            @csrf @method('PUT')
                            <select name="role_id" onchange="this.form.submit()" class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs bg-white" aria-label="{{ __('Perfil de :nombre', ['nombre' => $miembro->name]) }}">
                                @unless ($miembro->role_id)
                                    <option value="" selected disabled>{{ __('Acceso completo') }}</option>
                                @endunless
                                @foreach ($perfiles as $perfil)
                                    <option value="{{ $perfil->id }}" @selected($miembro->role_id === $perfil->id)>{{ __($perfil->nombre) }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route($miembro->activo ? 'team.member.deactivate' : 'team.member.activate', $miembro) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-medium bg-white cursor-pointer {{ $miembro->activo ? 'text-rose-600 hover:border-rose-300' : 'text-emerald-700 hover:border-emerald-200' }}"
                                    @if ($miembro->activo) onclick="return confirm({{ \Illuminate\Support\Js::from(__('¿Quitarle el acceso a :nombre?', ['nombre' => $miembro->name])) }})" @endif>
                                {{ $miembro->activo ? __('Quitar acceso') : __('Reactivar') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Invitaciones pendientes --}}
    @if ($invitaciones->count())
        <h2 class="text-base font-bold text-gray-900 mb-3">{{ __('Invitaciones pendientes') }} <span class="text-gray-400 font-medium">({{ $invitaciones->count() }})</span></h2>
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-8">
            @foreach ($invitaciones as $invitacion)
                <div class="flex flex-wrap items-center gap-4 px-4 py-3 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-gray-900 truncate">{{ $invitacion->email }}</span>
                        <span class="block text-xs text-gray-500">{{ __($invitacion->role->nombre) }} · {{ __('vence :cuando', ['cuando' => $invitacion->expires_at->diffForHumans()]) }}</span>
                    </div>
                    <form method="POST" action="{{ route('team.resend', $invitacion) }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-medium text-gray-700 bg-white hover:border-indigo-300 cursor-pointer">{{ __('Reenviar') }}</button>
                    </form>
                    <form method="POST" action="{{ route('team.invitation.cancel', $invitacion) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-medium text-rose-600 bg-white hover:border-rose-300 cursor-pointer">{{ __('Cancelar') }}</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Perfiles --}}
    <div class="flex items-end justify-between gap-4 mb-3">
        <div>
            <h2 class="text-base font-bold text-gray-900">{{ __('Perfiles de acceso') }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('Cada perfil decide qué partes del ERP ve quien lo tenga.') }}</p>
        </div>
        <a href="{{ route('team.role.create') }}" class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:border-gray-400 no-underline">+ {{ __('Nuevo perfil') }}</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @foreach ($perfiles as $perfil)
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col gap-2.5">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <span class="block text-sm font-bold text-gray-900">{{ __($perfil->nombre) }}</span>
                        <span class="block text-xs text-gray-500">{{ trans_choice(':n persona|:n personas', $perfil->users_count, ['n' => $perfil->users_count]) }}</span>
                    </div>
                    @unless ($perfil->es_admin)
                        <a href="{{ route('team.role.edit', $perfil) }}" class="text-xs font-semibold text-indigo-600 no-underline hover:underline">{{ __('Editar') }}</a>
                    @endunless
                </div>
                @if ($perfil->es_admin)
                    <p class="text-xs text-gray-500 m-0">{{ __('Todo el ERP, incluido el equipo y el plan. No se edita.') }}</p>
                @else
                    <div class="flex flex-wrap gap-1.5">
                        @forelse ($perfil->modulos() as $modulo)
                            <span class="text-[11px] px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 font-medium">{{ __(\App\Models\Role::MODULOS[$modulo]) }}</span>
                        @empty
                            <span class="text-xs text-gray-400">{{ __('Sin módulos: sólo su perfil.') }}</span>
                        @endforelse
                    </div>
                    @if (in_array('crm', $perfil->modulos()))
                        <p class="text-xs m-0 {{ $perfil->veSoloSusProspectos() ? 'text-amber-700' : 'text-gray-500' }}">
                            {{ $perfil->veSoloSusProspectos() ? __('En el embudo ve sólo sus prospectos.') : __('En el embudo ve a todos.') }}
                        </p>
                    @endif
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
