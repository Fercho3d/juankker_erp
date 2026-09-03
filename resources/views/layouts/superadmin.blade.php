<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Superadmin · Juankker ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{brand:'#2563eb',navy:'#1e3a8a'}}}}</script>
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="min-h-screen flex">
        <aside class="w-60 bg-navy text-slate-300 flex flex-col shrink-0">
            <div class="px-6 py-5 flex items-center gap-2 border-b border-white/10">
                <span class="w-7 h-7 rounded-lg bg-brand inline-block"></span>
                <span class="text-white font-bold text-lg">Juankker</span>
            </div>
            @php $nav = [
                'superadmin.dashboard' => ['Dashboard','📊'],
                'superadmin.index' => ['Organizaciones','🏢'],
                'superadmin.uso' => ['Estadísticas de uso','📈'],
                'superadmin.planes' => ['Planes','💳'],
                'superadmin.invitations' => ['Invitaciones','✉️'],
                'superadmin.beta' => ['Solicitudes beta','🧪'],
            ]; @endphp
            <nav class="flex-1 py-4">
                @foreach($nav as $route => [$label,$icon])
                    <a href="{{ route($route) }}"
                       class="flex items-center gap-3 px-6 py-2.5 text-sm hover:bg-white/5 {{ request()->routeIs($route) ? 'bg-white/10 text-white border-l-4 border-brand' : '' }}">
                        <span>{{ $icon }}</span> {{ $label }}
                    </a>
                @endforeach
            </nav>
            <div class="px-6 py-4 border-t border-white/10 text-xs">
                <div class="text-slate-400 mb-2">{{ auth()->user()->name }}</div>
                <a href="{{ url('/') }}" class="text-brand hover:underline">← Volver al ERP</a>
                <form action="{{ route('logout') }}" method="POST" class="mt-2">@csrf
                    <button class="text-slate-400 hover:text-white">Cerrar sesión</button>
                </form>
            </div>
        </aside>

        <main class="flex-1 overflow-x-hidden">
            <header class="bg-white border-b border-slate-200 px-8 py-4">
                <h1 class="text-xl font-bold text-navy">@yield('title', 'Panel')</h1>
            </header>
            <div class="p-8">
                @if(session('status'))
                    <div class="mb-6 px-4 py-3 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-6 px-4 py-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl">{{ $errors->first() }}</div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
