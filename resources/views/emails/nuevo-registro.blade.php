@extends('emails.layout', ['title' => 'Nuevo registro'])

@section('content')
    <h1 style="font-size:20px;margin:0 0 12px;">Nuevo registro en el ERP</h1>
    <table role="presentation" cellpadding="6" style="font-size:14px;color:#334155;">
        <tr><td style="color:#64748b;">Empresa:</td><td><strong>{{ $organization->name }}</strong></td></tr>
        <tr><td style="color:#64748b;">Usuario:</td><td>{{ $user->name }}</td></tr>
        <tr><td style="color:#64748b;">Correo:</td><td>{{ $user->email }}</td></tr>
        <tr><td style="color:#64748b;">Plan:</td><td>{{ ucfirst($organization->plan) }} ({{ $organization->environment }})</td></tr>
        <tr><td style="color:#64748b;">Fecha:</td><td>{{ $user->created_at?->format('d/m/Y H:i') }}</td></tr>
    </table>
@endsection
