@extends('layouts.app')
@push('styles')<script src="https://cdn.tailwindcss.com"></script>@endpush

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
        <span class="inline-block px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold mb-3">{{ __('Invitación al equipo') }}</span>
        <h1 class="text-2xl font-bold text-gray-900 mb-1">{{ __('Únete a :empresa', ['empresa' => $invitacion->organization->name]) }}</h1>
        <p class="text-sm text-gray-500 mb-6">
            {{ __('Entrarás como :perfil con el correo :email.', ['perfil' => __($invitacion->role->nombre), 'email' => $invitacion->email]) }}
        </p>

        <form method="POST" action="{{ route('team.join.accept', $invitacion->token) }}" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Tu nombre') }}</label>
                <input id="name" name="name" value="{{ old('name') }}" required autofocus class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Contraseña') }}</label>
                <div class="relative">
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none pr-12">
                    @include('partials.password-toggle')
                </div>
                <p class="text-xs text-gray-400 mt-1">{{ __('Mínimo 8 caracteres, con mayúsculas, minúsculas y números.') }}</p>
                @error('password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Confirmar contraseña') }}</label>
                <div class="relative">
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none pr-12">
                    @include('partials.password-toggle')
                </div>
            </div>
            <button class="w-full py-2.5 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition">{{ __('Crear mi acceso') }}</button>
        </form>
    </div>
</div>
@endsection
