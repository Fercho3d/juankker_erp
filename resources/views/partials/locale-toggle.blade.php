{{-- Selector de idioma, gemelo del de tema. Lo maneja public/js/shell.js. --}}
@php $idiomaActual = \App\Support\Locale::current(); @endphp
<div class="shell-pill" role="radiogroup" aria-label="{{ __('Idioma de la interfaz') }}" data-locale-toggle>
    @foreach (\App\Support\Locale::cases() as $opcion)
        <button type="button" role="radio" class="shell-pill-btn shell-pill-text"
                data-locale-value="{{ $opcion->value }}"
                aria-checked="{{ $idiomaActual === $opcion ? 'true' : 'false' }}"
                title="{{ $opcion->label() }}" aria-label="{{ $opcion->label() }}">{{ $opcion->short() }}</button>
    @endforeach
</div>
