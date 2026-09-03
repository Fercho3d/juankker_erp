@extends('emails.layout', ['title' => 'Invitación'])

@section('content')
    <h1 style="font-size:22px;margin:0 0 12px;">Te invitamos al ERP de Juankker</h1>
    <p style="margin:0 0 16px;color:#475569;">
        Fuiste invitado a probar el ERP de Juankker con una cuenta de prueba de <strong>30 días</strong>.
        Crea tu acceso con el siguiente botón.
    </p>
    <div style="text-align:center;margin:28px 0;">
        <a href="{{ route('invitation.show', $invitation->token) }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:600;padding:13px 28px;border-radius:10px;">Aceptar invitación</a>
    </div>
    @if($invitation->expires_at)
        <p style="margin:0;color:#94a3b8;font-size:13px;">La invitación vence el {{ $invitation->expires_at->format('d/m/Y') }}.</p>
    @endif
@endsection
