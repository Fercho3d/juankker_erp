@extends('emails.layout', ['title' => 'Suscripción activada'])

@section('content')
    <h1 style="font-size:22px;margin:0 0 12px;">Tu plan {{ $plan->name }} está activo</h1>
    <p style="margin:0 0 16px;color:#475569;">
        Gracias por confiar en Juankker. La suscripción de <strong>{{ $organization->name }}</strong>
        al plan <strong>{{ $plan->name }}</strong> quedó activa.
    </p>
    <ul style="color:#334155;font-size:14px;padding-left:18px;">
        <li>Hasta {{ $plan->max_users }} usuarios</li>
        <li>Hasta {{ $plan->max_branches }} sucursal(es)</li>
        <li>{{ $plan->max_storage_gb }} GB de almacenamiento</li>
    </ul>
    <div style="text-align:center;margin:28px 0;">
        <a href="{{ url('/') }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:600;padding:13px 28px;border-radius:10px;">Ir al ERP</a>
    </div>
@endsection
