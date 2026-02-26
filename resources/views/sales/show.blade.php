@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4f46e5',
                    }
                }
            }
        }
    </script>
@endpush

@section('content')
    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h1 class="text-2xl font-bold text-slate-800">Venta #{{ $sale->folio }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                        Completada
                    </span>
                </div>
                <p class="text-slate-500 text-sm flex items-center gap-2">
                    <i class="ri-calendar-line"></i> {{ $sale->created_at->format('d/m/Y h:i A') }}
                    <span class="text-slate-300">|</span>
                    <i class="ri-store-2-line"></i> {{ $sale->organization->nombre }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('sales.index') }}"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 hover:text-slate-800 transition-colors text-sm font-medium flex items-center gap-2">
                    <i class="ri-arrow-left-line"></i> Volver
                </a>
                <a href="{{ route('sales.pdf', $sale) }}" target="_blank"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium flex items-center gap-2 shadow-sm shadow-indigo-200">
                    <i class="ri-file-pdf-line"></i> Descargar PDF
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content: Items -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="font-semibold text-slate-800 flex items-center gap-2">
                            <i class="ri-shopping-bag-3-line text-indigo-500"></i>
                            Productos Vendidos
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Producto</th>
                                    <th class="px-6 py-3 font-semibold text-center">Cant.</th>
                                    <th class="px-6 py-3 font-semibold text-right">Precio Unit.</th>
                                    <th class="px-6 py-3 font-semibold text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($sale->items as $item)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-slate-900">{{ $item->producto_nombre_snapshot }}</div>
                                            @if($item->variant && $item->variant->sku)
                                                <div class="text-xs text-slate-500 font-mono mt-0.5">{{ $item->variant->sku }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span
                                                class="inline-flex items-center justify-center bg-slate-100 text-slate-700 font-bold px-2.5 py-0.5 rounded-md text-xs">
                                                {{ $item->cantidad }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-slate-600">
                                            ${{ number_format($item->precio_unitario, 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-right font-medium text-slate-900">
                                            ${{ number_format($item->importe, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Notes or Additional Info -->
                <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100 flex items-start gap-4">
                    <div class="p-2 bg-white rounded-full text-indigo-600 shadow-sm shrink-0">
                        <i class="ri-information-line text-lg"></i>
                    </div>
                    <div>
                        <h4 class="font-medium text-indigo-900 text-sm">Información Adicional</h4>
                        <p class="text-indigo-700 text-sm mt-1">
                            Esta venta fue realizada por <strong>{{ $sale->user->name }}</strong>.
                            @if($sale->client)
                                Cliente registrado: <strong>{{ $sale->client->nombre_completo }}</strong>.
                            @else
                                Venta a público general.
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sidebar: Summary & Details -->
            <div class="space-y-6">
                <!-- Payment Summary -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h3
                        class="font-semibold text-slate-800 mb-4 pb-3 border-b border-slate-100 flex items-center justify-between">
                        Resumen de Pago
                        <span
                            class="text-xs font-normal text-slate-500 bg-slate-100 px-2 py-1 rounded-md uppercase tracking-wide">
                            {{ $sale->metodo_pago }}
                        </span>
                    </h3>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">Subtotal</span>
                            <span class="font-medium text-slate-700">${{ number_format($sale->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">IVA (16%)</span>
                            <span class="font-medium text-slate-700">${{ number_format($sale->impuesto, 2) }}</span>
                        </div>
                        <div class="pt-3 mt-3 border-t border-dashed border-slate-200 flex justify-between items-end">
                            <span class="text-slate-800 font-bold">Total</span>
                            <span class="text-2xl font-black text-indigo-600">${{ number_format($sale->total, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Client Info -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-semibold text-slate-800 mb-4 pb-3 border-b border-slate-100">
                        Datos del Cliente
                    </h3>
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 text-xl">
                            <i class="ri-user-3-line"></i>
                        </div>
                        <div>
                            <div class="font-medium text-slate-900">
                                {{ $sale->client ? $sale->client->nombre_completo : 'Público General' }}
                            </div>
                            @if($sale->client)
                                <div class="text-xs text-slate-500 mt-0.5">{{ $sale->client->email }}</div>
                                <div class="text-xs text-slate-500">{{ $sale->client->telefono }}</div>
                            @else
                                <div class="text-xs text-slate-500 mt-0.5">Venta Mostrador</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection