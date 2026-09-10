@if(auth()->check() && auth()->user()->organization && !auth()->user()->organization->esPremium())
    <span class="pro-badge" title="{{ __('Disponible en los planes de pago') }}">PRO</span>
@endif
