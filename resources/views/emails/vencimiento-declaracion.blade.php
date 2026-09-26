@extends('emails.layout', ['title' => 'Pago de declaración'])

@php
    $meses = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $cuando = function ($d) {
        $dias = \App\Mail\VencimientoDeclaracionMail::diasParaVencer($d);

        return match (true) {
            $dias < 0 => 'venció ayer',
            $dias === 0 => 'vence hoy',
            $dias === 1 => 'vence mañana',
            default => "vence en $dias días",
        };
    };
@endphp

@section('content')
    <h1 style="font-size:22px;margin:0 0 12px;">Pago de declaración pendiente</h1>
    <p style="margin:0 0 16px;color:#475569;">
        Hola {{ $user->name }}, tienes {{ $declaraciones->count() === 1 ? 'una declaración presentada' : 'declaraciones presentadas' }} sin pagar:
    </p>
    <table style="width:100%;border-collapse:collapse;margin:0 0 20px;font-size:14px;">
        @foreach($declaraciones as $d)
            <tr style="border-top:1px solid #e2e8f0;">
                <td style="padding:10px 0;">
                    <strong>{{ $d->mes ? $meses[$d->mes].' '.$d->año : 'Anual '.$d->año }}</strong><br>
                    <span style="color:{{ \App\Mail\VencimientoDeclaracionMail::diasParaVencer($d) < 0 ? '#dc2626' : '#b45309' }};">{{ ucfirst($cuando($d)) }} · {{ $d->vence_pago->format('d/m/Y') }}</span>
                </td>
                <td style="padding:10px 0;text-align:right;font-weight:600;">
                    {{ $d->monto_linea_captura !== null ? '$'.number_format($d->monto_linea_captura) : '' }}
                </td>
            </tr>
        @endforeach
    </table>
    <p style="margin:0 0 16px;color:#475569;">
        Paga la línea de captura de tu acuse en tu banco antes de que venza. Si ya se venció, presenta una complementaria
        (con el estímulo de regularización si es de 2024 o antes) para obtener una nueva, y págala el mismo día.
    </p>
    <div style="text-align:center;margin:28px 0;">
        <a href="{{ url('/declaraciones') }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:600;padding:13px 28px;border-radius:10px;">Ver mis declaraciones</a>
    </div>
    <p style="margin:0;color:#94a3b8;font-size:13px;">Cuando pagues, regístralo en el ERP subiendo el comprobante y dejarás de recibir este aviso.</p>
@endsection
