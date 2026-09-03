@extends('layouts.app')
@push('styles')<script src="https://cdn.tailwindcss.com"></script>@endpush

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Verifica tu correo</h1>
        <p class="text-sm text-gray-500 mb-6">Escribe el código de 6 dígitos que te enviamos.</p>

        @if(session('status'))
            <div class="mb-4 px-4 py-3 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('register.verify') }}" class="space-y-4">
            @csrf
            <input name="code" inputmode="numeric" maxlength="6" required autofocus
                   class="w-full text-center text-2xl tracking-[0.5em] font-bold px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
            @error('code')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            <button class="w-full py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">Verificar y activar</button>
        </form>

        <form method="POST" action="{{ route('register.resend') }}" class="mt-4">@csrf
            <button class="text-sm text-blue-600 hover:underline">Reenviar código</button>
        </form>
    </div>
</div>
@endsection
