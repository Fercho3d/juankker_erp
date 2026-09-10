@extends('layouts.app')
@push('styles')<script src="https://cdn.tailwindcss.com"></script>@endpush

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('Nueva contraseña') }}</h1>
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Correo') }}</label>
                <input name="email" type="email" value="{{ $email ?? old('email') }}" required
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Contraseña') }}</label>
                <div class="relative">
                <input name="password" type="password" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none pr-12">
                @include('partials.password-toggle')
                </div>
                <p class="text-xs text-gray-400 mt-1">{{ __('Mínimo 8 caracteres, con mayúsculas, minúsculas y números.') }}</p>
                @error('password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Confirmar contraseña') }}</label>
                <div class="relative">
                <input name="password_confirmation" type="password" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none pr-12">
                @include('partials.password-toggle')
                </div>
            </div>
            <button class="w-full py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">{{ __('Restablecer') }}</button>
        </form>
    </div>
</div>
@endsection
