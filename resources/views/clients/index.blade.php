@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
    <div class="max-w-7xl mx-auto px-6 py-8">
        {{-- Page Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Clientes</h1>
                <p class="text-sm text-gray-500 mt-0.5">Gestiona tu cartera de clientes.</p>
            </div>
            <a href="{{ route('clientes.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nuevo Cliente
            </a>
        </div>

        {{-- Flash Messages --}}
        @if (session('status') === 'client-created')
            <div class="flex items-center gap-2 px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
                <svg class="shrink-0 text-emerald-500" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Cliente creado exitosamente.
            </div>
        @elseif (session('status') === 'client-updated')
            <div class="flex items-center gap-2 px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
                <svg class="shrink-0 text-emerald-500" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Cliente actualizado exitosamente.
            </div>
        @elseif (session('status') === 'client-deleted')
            <div class="flex items-center gap-2 px-4 py-3 mb-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
                <svg class="shrink-0 text-emerald-500" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Cliente eliminado exitosamente.
            </div>
        @endif

        {{-- Toolbar: Search + Filters --}}
        <div class="mb-6">
            <form method="GET" action="{{ route('clientes.index') }}" class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[220px]">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search"
                           class="w-full pl-11 pr-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15 transition"
                           placeholder="Buscar por razón social, RFC o email..."
                           value="{{ request('search') }}">
                </div>

                <select name="tipo_persona" onchange="this.form.submit()"
                        class="px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white cursor-pointer min-w-[160px] focus:outline-none focus:border-indigo-500">
                    <option value="">Todos los tipos</option>
                    <option value="Física" {{ request('tipo_persona') === 'Física' ? 'selected' : '' }}>Persona Física</option>
                    <option value="Moral" {{ request('tipo_persona') === 'Moral' ? 'selected' : '' }}>Persona Moral</option>
                </select>

                <select name="activo" onchange="this.form.submit()"
                        class="px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white cursor-pointer min-w-[160px] focus:outline-none focus:border-indigo-500">
                    <option value="">Todos los estatus</option>
                    <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Inactivo</option>
                </select>

                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors cursor-pointer">
                    Buscar
                </button>

                @if(request()->hasAny(['search', 'tipo_persona', 'activo']))
                    <a href="{{ route('clientes.index') }}"
                       class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors no-underline">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        {{-- Data Table --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($clients->count())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead class="bg-gray-50 border-b-2 border-gray-200">
                            <tr>
                                <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Razón Social</th>
                                <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">RFC</th>
                                <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Tipo</th>
                                <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Email</th>
                                <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Teléfono</th>
                                <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Ciudad</th>
                                <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 whitespace-nowrap">Estatus</th>
                                <th class="px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 w-[100px]">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($clients as $client)
                                <tr class="hover:bg-gray-50 transition-colors [&:not(:last-child)>td]:border-b [&:not(:last-child)>td]:border-gray-200">
                                    <td class="px-4 py-3.5 font-semibold text-gray-900 align-middle">{{ $client->razon_social }}</td>
                                    <td class="px-4 py-3.5 align-middle">
                                        <code class="bg-gray-100 px-2 py-0.5 rounded-md text-xs font-mono text-gray-900 tracking-wide">{{ $client->rfc }}</code>
                                    </td>
                                    <td class="px-4 py-3.5 align-middle">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap
                                            {{ $client->tipo_persona === 'Moral' ? 'bg-blue-50 text-blue-800' : 'bg-violet-50 text-violet-800' }}">
                                            {{ $client->tipo_persona }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-gray-900 align-middle">{{ $client->email }}</td>
                                    <td class="px-4 py-3.5 text-gray-900 align-middle">{{ $client->telefono ?? '—' }}</td>
                                    <td class="px-4 py-3.5 text-gray-900 align-middle">{{ $client->ciudad }}, {{ $client->estado }}</td>
                                    <td class="px-4 py-3.5 align-middle">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap
                                            {{ $client->activo ? 'bg-emerald-50 text-emerald-800' : 'bg-gray-100 text-gray-500' }}">
                                            {{ $client->activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center whitespace-nowrap align-middle">
                                        <a href="{{ route('clientes.edit', $client) }}" title="Editar"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </a>
                                        <form action="{{ route('clientes.destroy', $client) }}" method="POST" class="inline"
                                            onsubmit="return confirm('¿Estás seguro de eliminar a {{ addslashes($client->razon_social) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Eliminar"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 hover:bg-red-50 transition-colors cursor-pointer border-none bg-transparent">
                                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 px-4 py-3.5 border-t border-gray-200 text-sm">
                    <span class="text-gray-500">Mostrando {{ $clients->firstItem() }}–{{ $clients->lastItem() }} de {{ $clients->total() }} clientes</span>
                    {{ $clients->links() }}
                </div>
            @else
                <div class="text-center py-16 px-8 text-gray-500">
                    <svg class="mx-auto mb-4 opacity-40" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay clientes</h3>
                    <p class="text-sm">{{ request()->hasAny(['search', 'tipo_persona', 'activo']) ? 'No se encontraron clientes con esos filtros.' : 'Comienza agregando tu primer cliente.' }}</p>
                    @unless(request()->hasAny(['search', 'tipo_persona', 'activo']))
                        <a href="{{ route('clientes.create') }}"
                           class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors no-underline">
                            Nuevo Cliente
                        </a>
                    @endunless
                </div>
            @endif
        </div>
    </div>
@endsection
