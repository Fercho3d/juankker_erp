@if(auth()->check() && auth()->user()->organization && !auth()->user()->organization->esPremium())
    <span style="display:inline-block;font-size:9px;font-weight:700;letter-spacing:.5px;background:#fde68a;color:#92400e;border-radius:6px;padding:1px 5px;vertical-align:middle;">PRO</span>
@endif
