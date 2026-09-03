@extends('emails.layout', ['title' => 'Código de verificación'])

@section('content')
    <h1 style="font-size:22px;margin:0 0 12px;">Verifica tu correo</h1>
    <p style="margin:0 0 20px;color:#475569;">Usa este código para activar tu cuenta del ERP. Vence en 10 minutos.</p>
    <div style="text-align:center;margin:28px 0;">
        <span style="display:inline-block;font-size:34px;font-weight:700;letter-spacing:10px;color:#1e3a8a;background:#eff6ff;border:1px dashed #2563eb;border-radius:12px;padding:16px 24px;">{{ $code }}</span>
    </div>
    <p style="margin:0;color:#94a3b8;font-size:13px;">Si no solicitaste este código, ignora este mensaje.</p>
@endsection
