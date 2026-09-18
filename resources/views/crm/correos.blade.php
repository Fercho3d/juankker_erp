@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="max-w-3xl mx-auto px-6 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Correos por mandar') }} <span class="text-gray-400 font-medium">({{ $borradores->count() }})</span></h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ __('Revisa cada correo antes de mandarlo. Al marcarlo como enviado queda en la bitácora del prospecto y se agenda una llamada de seguimiento.') }}
            {{ $remitente === auth()->user()->email
                ? __('Los correos salen de :correo.', ['correo' => $remitente])
                : __('Los correos salen de :correo y las respuestas llegan a tu cuenta.', ['correo' => $remitente]) }}
        </p>
    </div>

    @if (session('status'))
        <div class="px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">{{ session('status') }}</div>
    @endif
    @error('envio')
        <div class="px-4 py-3 mb-5 text-sm text-rose-800 bg-rose-50 border border-rose-200 rounded-xl">{{ $message }}</div>
    @enderror

    @forelse ($borradores as $borrador)
        @php($lead = $borrador->lead)
        <article class="bg-white rounded-xl border border-gray-200 p-5 mb-4">
            <div class="flex items-start gap-3">
                <span class="shrink-0 w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">{{ $loop->iteration }}</span>
                <div class="min-w-0">
                    <a href="{{ route('crm.leads.show', $lead) }}" class="text-base font-bold text-gray-900 no-underline hover:text-indigo-600">{{ $lead->empresa ?: $lead->nombre }}</a>
                    <p class="text-xs text-gray-500 m-0 mt-0.5 break-all">
                        {{ collect([$lead->sector, $lead->rangoPersonal() ? __(':n personas', ['n' => $lead->rangoPersonal()]) : null, $lead->municipio])->filter()->implode(' · ') }}
                        @if ($lead->email) · {{ strtolower($lead->email) }} @endif
                    </p>
                </div>
            </div>

            <p class="text-sm text-gray-900 mt-4 mb-2"><span class="font-semibold">{{ __('Asunto:') }}</span> {{ $borrador->asunto }}</p>
            <pre class="text-sm text-gray-700 whitespace-pre-wrap font-sans m-0 p-3 rounded-lg bg-gray-50 border border-gray-200">{{ $borrador->cuerpo }}</pre>

            <div class="flex flex-wrap gap-2 mt-3">
                <form method="POST" action="{{ route('crm.correos.prueba', $borrador) }}">
                        @csrf
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-200 text-gray-700 bg-white hover:border-gray-400 cursor-pointer">{{ __('Enviar prueba') }}</button>
                </form>
                @if ($lead->email)
                    <form method="POST" action="{{ route('crm.correos.enviar', $borrador) }}" onsubmit="return confirm({{ Js::from(__('¿Mandar este correo a :correo?', ['correo' => strtolower($lead->email)])) }})">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 border-0 cursor-pointer">{{ __('Enviar al cliente') }}</button>
                    </form>
                @endif
                @if ($mailto = $borrador->mailto())
                    <a href="{{ $mailto }}" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-200 text-gray-700 no-underline hover:border-gray-400">{{ __('Abrir en mi correo') }}</a>
                @endif
                <button type="button" data-copiar="{{ $borrador->asunto }}&#10;&#10;{{ $borrador->cuerpo }}"
                        class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-200 text-gray-700 bg-white hover:border-gray-400 cursor-pointer">{{ __('Copiar') }}</button>
                <form method="POST" action="{{ route('crm.correos.enviado', $borrador) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 rounded-lg hover:bg-emerald-100 border-0 cursor-pointer">{{ __('Ya lo mandé') }}</button>
                </form>
                <form method="POST" action="{{ route('crm.correos.destroy', $borrador) }}" class="ml-auto">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-2 text-xs font-medium text-gray-400 hover:text-rose-600 bg-transparent border-0 cursor-pointer">{{ __('Descartar') }}</button>
                </form>
            </div>
        </article>
    @empty
        <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-sm text-gray-500">
            {{ __('No hay correos por mandar.') }}
        </div>
    @endforelse
</div>

<script>
    document.querySelectorAll('[data-copiar]').forEach(function (boton) {
        boton.addEventListener('click', async function () {
            try {
                await navigator.clipboard.writeText(boton.dataset.copiar);
                boton.textContent = {{ Js::from(__('Copiado')) }};
            } catch (e) {
                boton.textContent = {{ Js::from(__('No se pudo copiar')) }};
            }
        });
    });
</script>
@endsection
