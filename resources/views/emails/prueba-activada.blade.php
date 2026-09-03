@extends('emails.layout', ['title' => 'Prueba activada'])

@section('content')
    <h1 style="font-size:22px;margin:0 0 12px;">¡Bienvenido, {{ $user->name }}! 🎉</h1>
    <p style="margin:0 0 16px;color:#475569;">
        Tu prueba gratuita de <strong>30 días</strong> para <strong>{{ $organization->name }}</strong> ya está activa.
        Tienes acceso completo para administrar clientes, inventario, ventas y más.
    </p>
    <div style="text-align:center;margin:28px 0;">
        <a href="{{ url('/') }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:600;padding:13px 28px;border-radius:10px;">Entrar al ERP</a>
    </div>
    <p style="margin:0;color:#94a3b8;font-size:13px;">Tu prueba vence el {{ optional($organization->trial_ends_at)->format('d/m/Y') }}. Puedes elegir un plan cuando quieras.</p>
@endsection
