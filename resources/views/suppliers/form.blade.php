@extends('layouts.app')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
    <div class="max-w-[900px] mx-auto px-6 py-8">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $supplier->exists ? 'Editar Proveedor' : 'Nuevo Proveedor' }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ $supplier->exists ? 'Actualiza los datos del proveedor.' : 'Llena los datos para dar de alta un nuevo proveedor.' }}</p>
            </div>
            <a href="{{ route('proveedores.index') }}" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors no-underline">← Volver a la lista</a>
        </div>

        <form method="POST" action="{{ $supplier->exists ? route('proveedores.update', $supplier) : route('proveedores.store') }}">
            @csrf
            @if($supplier->exists)
                @method('PUT')
            @endif

            {{-- Datos Fiscales --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6 hover:shadow-md transition-shadow">
                <div class="flex items-start gap-4 mb-6 pb-5 border-b border-gray-200">
                    <div class="w-11 h-11 min-w-[44px] bg-indigo-50 rounded-xl text-indigo-600 flex items-center justify-center">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-0.5">Datos Fiscales</h2>
                        <p class="text-sm text-gray-500">Información fiscal del proveedor según el SAT.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label for="tipo_persona" class="block text-sm font-medium text-gray-900 mb-2">Tipo de Persona *</label>
                        <select id="tipo_persona" name="tipo_persona" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('tipo_persona') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror" required>
                            <option value="">Seleccionar...</option>
                            <option value="Física" {{ old('tipo_persona', $supplier->tipo_persona) === 'Física' ? 'selected' : '' }}>Persona Física</option>
                            <option value="Moral" {{ old('tipo_persona', $supplier->tipo_persona) === 'Moral' ? 'selected' : '' }}>Persona Moral</option>
                        </select>
                        @error('tipo_persona')
                            <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="razon_social" class="block text-sm font-medium text-gray-900 mb-2">Razón Social / Nombre *</label>
                        <input id="razon_social" type="text" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('razon_social') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            name="razon_social" value="{{ old('razon_social', $supplier->razon_social) }}" required
                            placeholder="Ej: Insumos Industriales S.A. de C.V.">
                        @error('razon_social')
                            <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label for="rfc" class="block text-sm font-medium text-gray-900 mb-2">RFC *</label>
                        <input id="rfc" type="text" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('rfc') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            name="rfc" value="{{ old('rfc', $supplier->rfc) }}" required maxlength="13"
                            placeholder="Ej: XAXX010101000" style="text-transform: uppercase;">
                        @error('rfc')
                            <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="curp" class="block text-sm font-medium text-gray-900 mb-2">CURP <small class="text-gray-500">(solo personas físicas)</small></label>
                        <input id="curp" type="text" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('curp') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            name="curp" value="{{ old('curp', $supplier->curp) }}" maxlength="18"
                            placeholder="Ej: XEXX010101HNEXXXA4" style="text-transform: uppercase;">
                        @error('curp')
                            <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label for="regimen_fiscal" class="block text-sm font-medium text-gray-900 mb-2">Régimen Fiscal *</label>
                        <select id="regimen_fiscal" name="regimen_fiscal" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('regimen_fiscal') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror" required>
                            <option value="">Seleccionar...</option>
                            @foreach ($regimenes as $regimen)
                                <option value="{{ $regimen }}" {{ old('regimen_fiscal', $supplier->regimen_fiscal) === $regimen ? 'selected' : '' }}>
                                    {{ $regimen }}
                                </option>
                            @endforeach
                        </select>
                        @error('regimen_fiscal')
                            <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="uso_cfdi" class="block text-sm font-medium text-gray-900 mb-2">Uso de CFDI *</label>
                        <select id="uso_cfdi" name="uso_cfdi" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('uso_cfdi') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror" required>
                            <option value="">Seleccionar...</option>
                            @foreach ($usosCfdi as $uso)
                                <option value="{{ $uso }}" {{ old('uso_cfdi', $supplier->uso_cfdi) === $uso ? 'selected' : '' }}>
                                    {{ $uso }}
                                </option>
                            @endforeach
                        </select>
                        @error('uso_cfdi')
                            <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Contacto --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6 hover:shadow-md transition-shadow">
                <div class="flex items-start gap-4 mb-6 pb-5 border-b border-gray-200">
                    <div class="w-11 h-11 min-w-[44px] bg-indigo-50 rounded-xl text-indigo-600 flex items-center justify-center">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-0.5">Contacto</h2>
                        <p class="text-sm text-gray-500">Datos de contacto del proveedor.</p>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="contacto_nombre" class="block text-sm font-medium text-gray-900 mb-2">Nombre del Contacto</label>
                    <input id="contacto_nombre" type="text" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('contacto_nombre') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                        name="contacto_nombre" value="{{ old('contacto_nombre', $supplier->contacto_nombre) }}"
                        placeholder="Ej: Lic. Juan Pérez">
                    @error('contacto_nombre')
                        <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-900 mb-2">Correo Electrónico *</label>
                        <input id="email" type="email" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('email') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            name="email" value="{{ old('email', $supplier->email) }}" required
                            placeholder="ventas@proveedor.com">
                        @error('email')
                            <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="telefono" class="block text-sm font-medium text-gray-900 mb-2">Teléfono</label>
                        <input id="telefono" type="text" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('telefono') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            name="telefono" value="{{ old('telefono', $supplier->telefono) }}"
                            placeholder="Ej: 55 9876 5432">
                        @error('telefono')
                            <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Dirección --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6 hover:shadow-md transition-shadow">
                <div class="flex items-start gap-4 mb-6 pb-5 border-b border-gray-200">
                    <div class="w-11 h-11 min-w-[44px] bg-indigo-50 rounded-xl text-indigo-600 flex items-center justify-center">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-0.5">Dirección</h2>
                        <p class="text-sm text-gray-500">Domicilio fiscal del proveedor.</p>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="calle" class="block text-sm font-medium text-gray-900 mb-2">Calle *</label>
                    <input id="calle" type="text" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('calle') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                        name="calle" value="{{ old('calle', $supplier->calle) }}" required
                        placeholder="Ej: Blvd. Manuel Ávila Camacho">
                    @error('calle')
                        <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="mb-4">
                        <label for="num_exterior" class="block text-sm font-medium text-gray-900 mb-2">Núm. Exterior *</label>
                        <input id="num_exterior" type="text" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('num_exterior') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            name="num_exterior" value="{{ old('num_exterior', $supplier->num_exterior) }}" required
                            placeholder="Ej: 40">
                        @error('num_exterior')
                            <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="num_interior" class="block text-sm font-medium text-gray-900 mb-2">Núm. Interior</label>
                        <input id="num_interior" type="text" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('num_interior') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            name="num_interior" value="{{ old('num_interior', $supplier->num_interior) }}"
                            placeholder="Ej: Bodega 5">
                        @error('num_interior')
                            <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="codigo_postal" class="block text-sm font-medium text-gray-900 mb-2">Código Postal *</label>
                        <input id="codigo_postal" type="text" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('codigo_postal') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            name="codigo_postal" value="{{ old('codigo_postal', $supplier->codigo_postal) }}" required maxlength="5"
                            placeholder="Ej: 11560">
                        @error('codigo_postal')
                            <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label for="colonia" class="block text-sm font-medium text-gray-900 mb-2">Colonia *</label>
                        <input id="colonia" type="text" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('colonia') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            name="colonia" value="{{ old('colonia', $supplier->colonia) }}" required
                            placeholder="Ej: Polanco">
                        @error('colonia')
                            <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="ciudad" class="block text-sm font-medium text-gray-900 mb-2">Ciudad / Municipio *</label>
                        <input id="ciudad" type="text" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('ciudad') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            name="ciudad" value="{{ old('ciudad', $supplier->ciudad) }}" required
                            placeholder="Ej: Miguel Hidalgo">
                        @error('ciudad')
                            <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label for="estado" class="block text-sm font-medium text-gray-900 mb-2">Estado *</label>
                    <select id="estado" name="estado" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 @error('estado') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror" required>
                        <option value="">Seleccionar estado...</option>
                        @foreach ($estados as $edo)
                            <option value="{{ $edo }}" {{ old('estado', $supplier->estado) === $edo ? 'selected' : '' }}>
                                {{ $edo }}
                            </option>
                        @endforeach
                    </select>
                    @error('estado')
                        <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
            </div>

            {{-- Observaciones --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6 hover:shadow-md transition-shadow">
                <div class="flex items-start gap-4 mb-6 pb-5 border-b border-gray-200">
                    <div class="w-11 h-11 min-w-[44px] bg-indigo-50 rounded-xl text-indigo-600 flex items-center justify-center">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-0.5">Observaciones</h2>
                        <p class="text-sm text-gray-500">Notas adicionales y estatus del proveedor.</p>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="notas" class="block text-sm font-medium text-gray-900 mb-2">Notas</label>
                    <textarea id="notas" class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm transition-colors box-border focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 resize-y min-h-[80px] @error('notas') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                        name="notas" rows="3" placeholder="Observaciones opcionales sobre el proveedor...">{{ old('notas', $supplier->notas) }}</textarea>
                    @error('notas')
                        <span class="text-red-500 text-sm mt-1"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-900 cursor-pointer">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1" class="w-[18px] h-[18px] accent-indigo-600 cursor-pointer"
                            {{ old('activo', $supplier->exists ? $supplier->activo : true) ? 'checked' : '' }}>
                        Proveedor activo
                    </label>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between mt-2">
                <a href="{{ route('proveedores.index') }}" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors no-underline">Cancelar</a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors cursor-pointer border-none">
                    {{ $supplier->exists ? 'Guardar Cambios' : 'Crear Proveedor' }}
                </button>
            </div>
        </form>
    </div>
@endsection
