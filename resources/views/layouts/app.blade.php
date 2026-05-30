<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Pusmendik' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
@php
    $publicNav = [
        ['label' => 'Beranda', 'route' => 'dashboard'],
        ['label' => 'Cek Pembayaran', 'route' => 'payments.status'],
        ['label' => 'Jadwal', 'route' => 'schedules.index'],
        ['label' => 'Ruangan', 'route' => 'rooms.index'],
        ['label' => 'Pengawas', 'route' => 'supervisors.index'],
        ['label' => 'Live', 'route' => 'live.index'],
        ['label' => 'Kehadiran', 'route' => 'attendance.index'],
    ];
    $privateNav = session('data_user') ? [
        ['label' => 'Pembayaran', 'route' => 'students.index'],
        ['label' => 'User Data', 'route' => 'users.index'],
        ['label' => 'Setting', 'route' => 'settings.index'],
    ] : [
        ['label' => 'Login', 'route' => 'login'],
    ];
    $navItems = array_merge($publicNav, $privateNav);
    $mobileNav = [
        ['label' => 'Beranda', 'route' => 'dashboard', 'icon' => 'M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-6H8v6H4a1 1 0 0 1-1-1V10.5Z'],
        ['label' => 'Bayar', 'route' => session('data_user') ? 'students.index' : 'payments.status', 'icon' => 'M4 7h16v10H4V7Zm0 3h16M7 15h4'],
        ['label' => 'Jadwal', 'route' => 'schedules.index', 'icon' => 'M7 3v3m10-3v3M4 8h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z'],
        ['label' => 'Live', 'route' => 'live.index', 'icon' => 'M4 12h3l2-6 4 12 2-6h5'],
        ['label' => session('data_user') ? 'Setting' : 'Login', 'route' => session('data_user') ? 'settings.index' : 'login', 'icon' => 'M12 8a4 4 0 1 1 0 8 4 4 0 0 1 0-8Zm0-5v3m0 12v3m9-9h-3M6 12H3m15.36-6.36-2.12 2.12M7.76 16.24l-2.12 2.12m12.72 0-2.12-2.12M7.76 7.76 5.64 5.64'],
    ];
@endphp

<header class="sticky top-0 z-40 border-b border-white/70 bg-white/85 shadow-sm shadow-slate-200/50 backdrop-blur-xl">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-teal-600 text-lg font-black text-white shadow-lg shadow-teal-600/25">P</span>
            <span>
                <span class="block text-base font-black tracking-tight text-slate-950">Pusmendik</span>
                <span class="hidden text-xs font-medium text-slate-500 sm:block">Pusat Informasi Ujian</span>
            </span>
        </a>

        <nav class="hidden items-center gap-1 lg:flex">
            @foreach($navItems as $item)
                <a href="{{ route($item['route']) }}" @class([
                    'rounded-full px-3 py-2 text-sm font-semibold transition',
                    'bg-teal-600 text-white shadow-sm shadow-teal-600/20' => request()->routeIs($item['route']),
                    'text-slate-600 hover:bg-slate-100 hover:text-slate-950' => ! request()->routeIs($item['route']),
                ])>{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            @if(session('data_user'))
                <div class="hidden text-right md:block">
                    <div class="text-sm font-bold text-slate-800">{{ session('data_user.name') }}</div>
                    <div class="text-xs text-slate-500">Role data</div>
                </div>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="rounded-full bg-slate-950 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-teal-700">Login Petugas</a>
            @endif
        </div>
    </div>
</header>

<main class="mx-auto min-h-[calc(100vh-72px)] max-w-7xl px-4 pb-28 pt-6 sm:px-6 lg:px-8 lg:pb-12">
    @if(session('success'))
        <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 shadow-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 shadow-sm">{{ $errors->first() }}</div>
    @endif
    @yield('content')
</main>

<nav class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white/95 px-2 pb-2 pt-1 shadow-2xl shadow-slate-900/10 backdrop-blur-xl lg:hidden">
    <div class="mx-auto grid max-w-md grid-cols-5 gap-1">
        @foreach($mobileNav as $item)
            <a href="{{ route($item['route']) }}" @class([
                'flex flex-col items-center justify-center gap-1 rounded-2xl px-2 py-2 text-[11px] font-bold transition',
                'bg-teal-50 text-teal-700' => request()->routeIs($item['route']),
                'text-slate-500 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs($item['route']),
            ])>
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $item['icon'] }}"/></svg>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
</body>
</html>
