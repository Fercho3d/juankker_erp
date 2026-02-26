@extends('layouts.app')

@section('content')
    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Historial de Ventas</h1>
                <p class="page-subtitle">Consulta y administra las ventas realizadas</p>
            </div>
            <a href="{{ route('pos.index') }}" class="btn-primary">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Nueva Venta
            </a>
        </div>

        <div class="table-card">
            @if($sales->count() > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sales as $sale)
                            <tr>
                                <td>
                                    <a href="{{ route('sales.show', $sale) }}" class="fw-bold text-primary text-decoration-none">
                                        {{ $sale->folio }}
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium">{{ $sale->created_at->format('d/m/Y') }}</span>
                                        <small class="text-muted">{{ $sale->created_at->format('H:i') }}</small>
                                    </div>
                                </td>
                                <td>
                                    @if($sale->client)
                                        <div class="d-flex align-items-center gap-2">
                                            <div
                                                class="avatar-xs bg-light rounded-circle d-flex align-items-center justify-content-center text-primary fw-bold">
                                                {{ substr($sale->client->nombre_completo, 0, 1) }}
                                            </div>
                                            <span>{{ $sale->client->nombre_completo }}</span>
                                        </div>
                                    @else
                                        <span class="text-muted fst-italic">Público General</span>
                                    @endif
                                </td>
                                <td>{{ $sale->user->name }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $sale->items->sum('cantidad') }}</span>
                                </td>
                                <td class="fw-bold text-dark">${{ number_format($sale->total, 2) }}</td>
                                <td>
                                    @if($sale->estatus == 'completada')
                                        <span class="badge badge-success">Completada</span>
                                    @elseif($sale->estatus == 'cancelada')
                                        <span class="badge badge-danger">Cancelada</span>
                                    @else
                                        <span class="badge badge-warning">Pendiente</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('sales.show', $sale) }}" class="btn-icon" title="Ver Detalle">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path
                                                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('sales.pdf', $sale) }}" class="btn-icon text-danger" title="Descargar PDF"
                                            target="_blank">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path
                                                    d="M12 15V3m0 12l-4-4m4 4l4-4M2 17l.621 2.485A2 2 0 0 0 4.561 21h14.878a2 2 0 0 0 1.94-1.515L22 17" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="p-3 border-top">
                    {{ $sales->links() }}
                </div>
            @else
                <div class="empty-state">
                    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                    </svg>
                    <h3>No hay ventas registradas</h3>
                    <p>Realiza tu primera venta desde el punto de venta.</p>
                    <a href="{{ route('pos.index') }}" class="btn-link mt-2">Ir al POS &rarr;</a>
                </div>
            @endif
        </div>
    </div>
@endsection