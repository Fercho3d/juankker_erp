@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="max-w-3xl mx-auto px-6 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Respuestas de prospectos') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ __('Los correos que te contestaron. Se revisa el buzón cada 10 minutos y la campana suena cuando llega uno nuevo.') }}</p>
    </div>

    @forelse ($respuestas as $respuesta)
        @php($nueva = ! $vistasAt || $respuesta->created_at->gt($vistasAt))
        <article class="bg-white rounded-xl border {{ $nueva ? 'border-indigo-300' : 'border-gray-200' }} p-5 mb-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <a href="{{ route('crm.leads.show', $respuesta->lead) }}" class="text-base font-bold text-gray-900 no-underline hover:text-indigo-600">{{ $respuesta->lead->empresa ?: $respuesta->lead->nombre }}</a>
                    <p class="text-xs text-gray-500 m-0 mt-0.5 break-all">{{ strtolower($respuesta->lead->email) }} · {{ $respuesta->completada_at?->format('d/m/Y H:i') }}</p>
                </div>
                @if ($nueva)
                    <span class="shrink-0 text-[11px] px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-semibold uppercase tracking-wide">{{ __('Nueva') }}</span>
                @endif
            </div>
            <p class="text-sm text-gray-700 whitespace-pre-line m-0 mt-3">{{ $respuesta->descripcion }}</p>
            <a href="{{ route('crm.leads.show', $respuesta->lead) }}" class="inline-block mt-3 text-xs font-semibold text-indigo-600 no-underline hover:underline">{{ __('Ver ficha y contestar') }} →</a>
        </article>
    @empty
        <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-sm text-gray-500">
            {{ __('Todavía no contesta nadie. Aquí aparecerán las respuestas en cuanto lleguen.') }}
        </div>
    @endforelse
</div>
@endsection
