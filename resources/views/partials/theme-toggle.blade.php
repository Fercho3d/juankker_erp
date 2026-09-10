{{-- Selector de tema: claro / oscuro / sistema. Lo maneja public/js/shell.js. --}}
@php $temaActual = \App\Support\Theme::current(); @endphp
<div class="shell-pill" role="radiogroup" aria-label="{{ __('Tema de la interfaz') }}" data-theme-toggle>
    @foreach (\App\Support\Theme::cases() as $opcion)
        <button type="button" role="radio" class="shell-pill-btn"
                data-theme-value="{{ $opcion->value }}"
                aria-checked="{{ $temaActual === $opcion ? 'true' : 'false' }}"
                title="{{ $opcion->label() }}" aria-label="{{ __('Tema') }}: {{ $opcion->label() }}">
            @include('partials.nav-icon', ['icon' => ['light' => 'sol', 'dark' => 'luna', 'system' => 'monitor'][$opcion->value], 'class' => 'shell-pill-icon'])
        </button>
    @endforeach
</div>
