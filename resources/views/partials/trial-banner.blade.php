@isset($trialDaysRemaining)
    <div class="shell-trial">
        <span>
            {{ __('Prueba gratuita') }}:
            {{ trans_choice('te queda :n día|te quedan :n días', $trialDaysRemaining, ['n' => $trialDaysRemaining]) }}
        </span>
        <a href="{{ route('pricing') }}">{{ __('Ver planes') }}</a>
    </div>
@endisset
