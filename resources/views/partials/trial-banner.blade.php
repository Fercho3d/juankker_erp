@isset($trialDaysRemaining)
    <div style="background:#1e3a8a;color:#fff;text-align:center;padding:8px 16px;font-size:13px;">
        🎁 Prueba gratuita: te quedan <strong>{{ $trialDaysRemaining }}</strong>
        {{ \Illuminate\Support\Str::plural('día', $trialDaysRemaining) }}.
        <a href="{{ route('pricing') }}" style="color:#93c5fd;font-weight:600;text-decoration:underline;">Ver planes</a>
    </div>
@endisset
