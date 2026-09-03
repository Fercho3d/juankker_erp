@if(config('services.turnstile.site'))
    <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site') }}"></div>
    @push('scripts')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endpush
@endif
