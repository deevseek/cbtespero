<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Portal Peserta' }} - CBT Julia</title>
    <x-partials.vite-assets :entries="['resources/css/app.css', 'resources/js/app.js']" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('head')
</head>
<body class="bg-[#f3f6fb] text-[#0f172a] antialiased">
@php
    $layoutStudent = $student ?? (session('student_id') ? \App\Models\Student::find(session('student_id')) : null);
    $studentName = $layoutStudent?->nama ?? 'Siswa';
    $studentClass = $layoutStudent?->kelas ?? '-';
    $initials = collect(explode(' ', $studentName))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->join('');
    $menus = [
        ['label' => 'Dashboard', 'route' => 'student.dashboard', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h3.75c.621 0 1.125.504 1.125 1.125v6.75C9 20.496 8.496 21 7.875 21h-3.75A1.125 1.125 0 0 1 3 19.875v-6.75ZM10.5 4.125C10.5 3.504 11.004 3 11.625 3h8.25C20.496 3 21 3.504 21 4.125v3.75C21 8.496 20.496 9 19.875 9h-8.25A1.125 1.125 0 0 1 10.5 7.875v-3.75ZM10.5 13.125c0-.621.504-1.125 1.125-1.125h8.25c.621 0 1.125.504 1.125 1.125v6.75c0 .621-.504 1.125-1.125 1.125h-8.25a1.125 1.125 0 0 1-1.125-1.125v-6.75ZM3 4.125C3 3.504 3.504 3 4.125 3h3.75C8.496 3 9 3.504 9 4.125v3.75C9 8.496 8.496 9 7.875 9h-3.75A1.125 1.125 0 0 1 3 7.875v-3.75Z'],
        ['label' => 'Ujian Aktif', 'route' => 'student.exams', 'icon' => 'M6.75 3v2.25m10.5-2.25v2.25M3 8.25h18M4.5 6.75h15A1.5 1.5 0 0 1 21 8.25v10.5A1.5 1.5 0 0 1 19.5 20.25h-15A1.5 1.5 0 0 1 3 18.75V8.25A1.5 1.5 0 0 1 4.5 6.75Z'],
        ['label' => 'Riwayat Nilai', 'route' => 'student.results', 'icon' => 'M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h12M3.75 3h16.5M3.75 3A2.25 2.25 0 0 0 1.5 5.25v11.25A2.25 2.25 0 0 0 3.75 18.75h16.5M7.5 7.5h9M7.5 10.5h6'],
        ['label' => 'Profil Saya', 'route' => 'student.profile', 'icon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z'],
    ];
@endphp
<div class="min-h-screen" x-data="{ sidebarOpen: false }">
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" @click="sidebarOpen = false"></div>

    <aside class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-slate-200/80 bg-white shadow-xl shadow-blue-950/5 transition-transform duration-200 lg:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        <div class="p-5">
            <div class="rounded-[1.5rem] bg-gradient-to-br from-[#2563eb] to-[#4f46e5] p-5 text-white shadow-lg shadow-blue-900/20">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-100">CBT Julia</p>
                <h1 class="mt-2 text-2xl font-black">Portal Peserta</h1>
                <p class="mt-2 text-sm text-blue-100">Ruang ujian digital siswa</p>
            </div>
        </div>

        <nav class="flex-1 space-y-2 px-4">
            @foreach($menus as $menu)
                @php($active = request()->routeIs($menu['route']) || request()->routeIs($menu['route'].'.*'))
                <a href="{{ route($menu['route']) }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold transition {{ $active ? 'bg-blue-50 text-[#2563eb] shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $menu['icon'] }}" /></svg>
                    {{ $menu['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="border-t border-slate-100 p-4">
            <div class="mb-3 flex items-center gap-3 rounded-2xl bg-slate-50 p-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 font-black text-white">{{ $initials ?: 'S' }}</div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-black text-slate-900">{{ $studentName }}</p>
                    <p class="text-xs font-semibold text-slate-500">Kelas {{ $studentClass }}</p>
                </div>
            </div>
            <form action="{{ route('student.logout') }}" method="post">
                @csrf
                <button class="flex w-full items-center justify-center gap-2 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-bold text-red-600 transition hover:bg-red-100">
                    Logout
                </button>
            </form>
        </div>
    </aside>

    <main class="lg:pl-72">
        <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-[#f3f6fb]/90 backdrop-blur-xl">
            <div class="flex h-20 items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button class="rounded-2xl border border-slate-200 bg-white p-2 text-slate-600 shadow-sm lg:hidden" @click="sidebarOpen = true" aria-label="Buka menu">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    </button>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#2563eb]">{{ $subtitle ?? 'Portal Peserta' }}</p>
                        <h2 class="text-xl font-black text-slate-950 sm:text-2xl">{{ $title ?? 'Dashboard Siswa' }}</h2>
                    </div>
                </div>
                <div class="hidden items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm sm:flex">
                    <div class="text-right">
                        <p class="text-sm font-black text-slate-900">{{ $studentName }}</p>
                        <p class="text-xs font-semibold text-slate-500">Kelas {{ $studentClass }} · Peserta Ujian</p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#2563eb] to-[#4f46e5] font-black text-white">{{ $initials ?: 'S' }}</div>
                </div>
            </div>
        </header>

        <section class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            @if($errors->any())
                <div class="mb-6 rounded-3xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 shadow-sm">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if(session('success'))
                <div class="mb-6 rounded-3xl border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-700 shadow-sm">{{ session('success') }}</div>
            @endif
            @yield('content')
        </section>
    </main>
</div>
@stack('scripts')
</body>
</html>
