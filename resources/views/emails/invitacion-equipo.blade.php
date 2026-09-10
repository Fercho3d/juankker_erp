@extends('emails.layout', ['title' => __('Invitación al equipo')])

@section('content')
    <h1 style="font-size:22px;margin:0 0 12px;">{{ __('Te invitaron a :empresa', ['empresa' => $invitacion->organization->name]) }}</h1>
    <p style="margin:0 0 16px;color:#475569;">
        {{ __(':nombre te sumó a su equipo en Juankker ERP con el perfil :perfil. Crea tu contraseña con el siguiente botón.', ['nombre' => $invitadoPor, 'perfil' => __($invitacion->role->nombre)]) }}
    </p>
    <div style="text-align:center;margin:28px 0;">
        <a href="{{ route('team.join', $invitacion->token) }}" style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;font-weight:600;padding:13px 28px;border-radius:10px;">{{ __('Aceptar invitación') }}</a>
    </div>
    <p style="margin:0;color:#94a3b8;font-size:13px;">
        {{ __('La invitación vence el :fecha. Si no esperabas este correo, ignóralo.', ['fecha' => $invitacion->expires_at->format('d/m/Y')]) }}
    </p>
@endsection
