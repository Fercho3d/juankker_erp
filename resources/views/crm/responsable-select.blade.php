{{--
    Selector de responsable. Sólo aparece para quien ve todo el embudo: a quien
    ve sólo lo suyo, el controlador le deja siempre su propio nombre.
    Parámetros: $responsables (colección de User), $seleccionado (id o null).
--}}
@if ($responsables->count())
    <div>
        <label for="owner_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">{{ __('Responsable') }}</label>
        <select id="owner_id" name="owner_id" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm bg-white cursor-pointer focus:outline-none focus:border-indigo-500">
            @foreach ($responsables as $responsable)
                <option value="{{ $responsable->id }}" @selected((int) old('owner_id', $seleccionado ?? auth()->id()) === $responsable->id)>
                    {{ $responsable->name }}{{ $responsable->id === auth()->id() ? ' ('.__('tú').')' : '' }}
                </option>
            @endforeach
        </select>
    </div>
@endif
