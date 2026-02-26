<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Ticket #{{ $sale->folio }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 10px;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .fw-bold {
            font-weight: bold;
        }

        .mb-1 {
            margin-bottom: 5px;
        }

        .mb-2 {
            margin-bottom: 10px;
        }

        .mb-4 {
            margin-bottom: 20px;
        }

        .w-100 {
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 5px 0;
        }

        .border-top {
            border-top: 1px dashed #000;
        }

        .border-bottom {
            border-bottom: 1px dashed #000;
        }

        .total-row td {
            font-size: 14px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="text-center mb-4">
        <h2 class="mb-1">{{ $sale->organization->nombre }}</h2>
        <p class="mb-1">Ticket de Venta</p>
        <p class="mb-1">{{ $sale->created_at->format('d/m/Y H:i:s') }}</p>
        <p>Folio: #{{ $sale->folio }}</p>
    </div>

    <div class="mb-4">
        <p class="mb-1"><strong>Cliente:</strong>
            {{ $sale->client ? $sale->client->nombre_completo : 'Público General' }}</p>
        <p class="mb-1"><strong>Atendido por:</strong> {{ $sale->user->name }}</p>
        <p><strong>Forma de Pago:</strong> {{ ucfirst($sale->metodo_pago) }}</p>
    </div>

    <table class="mb-4">
        <thead>
            <tr class="border-bottom">
                <th class="text-start">Desc</th>
                <th class="text-center">Cant</th>
                <th class="text-end">Precio</th>
                <th class="text-end">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td>{{ $item->producto_nombre_snapshot }}</td>
                    <td class="text-center">{{ $item->cantidad }}</td>
                    <td class="text-end">${{ number_format($item->precio_unitario, 2) }}</td>
                    <td class="text-end">${{ number_format($item->importe, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="border-top">
                <td colspan="3" class="text-end">Subtotal:</td>
                <td class="text-end">${{ number_format($sale->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="text-end">IVA (16%):</td>
                <td class="text-end">${{ number_format($sale->impuesto, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="text-end">TOTAL:</td>
                <td class="text-end">${{ number_format($sale->total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="text-center">
        @if(isset($qrCode))
            <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="QR Code" style="width: 100px; height: 100px;">
        @endif
        <p class="mt-2">¡Gracias por su compra!</p>
    </div>
</body>

</html>