@extends('layouts.app')
@push('styles')<script src="https://cdn.tailwindcss.com"></script>@endpush

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
        <span class="inline-block px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold mb-3">30 días gratis · sin tarjeta</span>
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Crea tu cuenta</h1>
        <p class="text-sm text-gray-500 mb-6">Empieza a administrar tu empresa con el ERP de Juankker.</p>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tu nombre</label>
                <input name="name" value="{{ old('name') }}" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la empresa</label>
                <input name="organization_name" value="{{ old('organization_name') }}" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                @error('organization_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
                <input name="email" type="email" value="{{ old('email') }}" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <div class="relative">
                <input name="password" type="password" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none pr-12">
                @include('partials.password-toggle')
                </div>
                <p class="text-xs text-gray-400 mt-1">Mínimo 8 caracteres, con mayúsculas, minúsculas y números.</p>
                @error('password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña</label>
                <div class="relative">
                <input name="password_confirmation" type="password" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none pr-12">
                @include('partials.password-toggle')
                </div>
            </div>
            @include('partials.turnstile')
            <button class="w-full py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">Crear cuenta gratis</button>
        </form>

        <p class="text-sm text-gray-500 mt-6 text-center">¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="text-blue-600 font-semibold hover:underline">Inicia sesión</a>
        </p>
    </div>
</div>
@endsection
