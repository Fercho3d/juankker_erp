@extends('layouts.app')
@push('styles')<script src="https://cdn.tailwindcss.com"></script>@endpush

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Recuperar contraseña</h1>
        <p class="text-sm text-gray-500 mb-6">Te enviaremos un enlace para restablecerla.</p>

        @if(session('status'))
            <div class="mb-4 px-4 py-3 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
                <input name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            @include('partials.turnstile')
            <button class="w-full py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">Enviar enlace</button>
        </form>
        <p class="text-sm text-gray-500 mt-6 text-center"><a href="{{ route('login') }}" class="text-blue-600 hover:underline">Volver a iniciar sesión</a></p>
    </div>
</div>
@endsection
