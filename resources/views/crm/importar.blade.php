@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="max-w-3xl mx-auto px-6 py-8">

    <div class="mb-6">
        <a href="{{ route('crm.tablero') }}" class="text-sm text-gray-500 no-underline hover:text-gray-700">&larr; Embudo</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Alta masiva de prospectos</h1>
        <p class="text-sm text-gray-500 mt-0.5">Pega tu lista. Una empresa por línea, separada por comas o tabuladores.</p>
    </div>

    @if ($errors->any())
        <div class="px-4 py-3 mb-5 text-sm text-rose-800 bg-rose-50 border border-rose-200 rounded-xl">
            <ul class="m-0 pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-5">
        <span class="block text-[11px] uppercase tracking-wider text-gray-500 font-semibold mb-2">Orden de las columnas</span>
        <code class="block text-xs text-gray-700 font-mono">empresa, contacto, teléfono, correo, origen</code>
        <span class="block text-xs text-gray-500 mt-2">Solo la empresa es obligatoria. Si pegas desde una hoja de cálculo, las columnas ya vienen separadas por tabulador y funciona directo. Se omiten las empresas, teléfonos o correos que ya existan en tu embudo.</span>
    </div>

    <form method="POST" action="{{ route('crm.leads.importar') }}" class="bg-white rounded-xl border border-gray-200 p-6 flex flex-col gap-5">
        @csrf

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Lista</label>
            <textarea name="datos" rows="14" required
                      placeholder="Refaccionaria del Centro, Luis Ramírez, 3312345678, contacto@refadelcentro.mx&#10;Ferretería San José, , 3339876543, ventas@ferrasanjose.com"
                      class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15">{{ old('datos') }}</textarea>
        </div>

        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Etapa inicial</label>
                <select name="stage_id" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white cursor-pointer focus:outline-none focus:border-indigo-500">
                    @foreach ($etapas as $etapa)
                        <option value="{{ $etapa->id }}" {{ $loop->first ? 'selected' : '' }}>{{ $etapa->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Origen por defecto</label>
                <select name="origen" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white cursor-pointer focus:outline-none focus:border-indigo-500">
                    @foreach (\App\Models\Lead::ORIGENES as $clave => $etiqueta)
                        <option value="{{ $clave }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 border-0 cursor-pointer">Dar de alta</button>
        </div>
    </form>
</div>
@endsection
