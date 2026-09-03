@extends('layouts.app')
@push('styles')<script src="https://cdn.tailwindcss.com"></script>@endpush

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Iniciar sesión</h1>
        <p class="text-sm text-gray-500 mb-6">Accede a tu ERP de Juankker.</p>

        @if(session('status'))
            <div class="mb-4 px-4 py-3 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
                <input name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <input name="password" type="password" required
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 text-gray-600"><input type="checkbox" name="remember"> Mantener sesión</label>
                <a href="{{ route('password.request') }}" class="text-blue-600 hover:underline">¿Olvidaste tu contraseña?</a>
            </div>
            @include('partials.turnstile')
            <button class="w-full py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">Entrar</button>
        </form>

        <p class="text-sm text-gray-500 mt-6 text-center">¿No tienes cuenta?
            <a href="{{ route('register') }}" class="text-blue-600 font-semibold hover:underline">Prueba 30 días gratis</a>
        </p>
    </div>
</div>
@endsection
