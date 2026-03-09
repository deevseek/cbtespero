<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espero CBT</title>
    <x-partials.vite-assets :entries="['resources/css/app.css', 'resources/js/app.js']" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('head')
</head>
<body class="bg-gray-100 font-sans text-slate-700">
<div class="min-h-screen" x-data="{ open: true, profileOpen: false }">
    <aside
        class="fixed inset-y-0 left-0 z-40 w-64 min-h-screen bg-indigo-700 text-white transition-transform duration-200"
        :class="open ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    >
        <div class="flex h-full flex-col p-4">
            <div class="mb-6 rounded-2xl bg-indigo-800/60 px-4 py-4">
                <p class="text-xs uppercase tracking-[0.2em] text-indigo-200">Admin Panel</p>
                <h1 class="text-2xl font-semibold">Espero CBT</h1>
            </div>

            @php
                $menus = [
                    [
                        'label' => 'Dashboard',
                        'url' => auth()->user()->isAdmin() ? route('admin.dashboard') : route('student.dashboard'),
                        'active' => auth()->user()->isAdmin() ? request()->routeIs('admin.dashboard') : request()->routeIs('student.dashboard'),
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3h16.5M4.5 9.75h15m-15 6h15m-15 6h15" /></svg>',
                    ],
                    [
                        'label' => 'Data Siswa',
                        'url' => route('admin.students.index'),
                        'active' => request()->routeIs('admin.students.*'),
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.742-.479 3 3 0 0 0-4.682-2.72m.94 3.198a6.115 6.115 0 0 0-.94-.198M15 17.25v-.162c0-1.113-.285-2.19-.784-3.132M15 17.25a48.172 48.172 0 0 1-6 0M15 17.25v.162c0 1.113.285 2.19.784 3.132M9 17.25v-.162c0-1.113.285-2.19.784-3.132M9 17.25a48.172 48.172 0 0 0 6 0M9 17.25v.162c0 1.113-.285 2.19-.784 3.132M9.75 8.25a3 3 0 1 1 4.5 0 3 3 0 0 1-4.5 0ZM3.75 17.25a3.75 3.75 0 1 1 7.5 0v.162a48.058 48.058 0 0 1-7.5 0v-.162Z" /></svg>',
                    ],
                    [
                        'label' => 'Bank Soal',
                        'url' => route('admin.questions.index'),
                        'active' => request()->routeIs('admin.questions.*'),
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3.344c.762-1.36 2.712-1.36 3.474 0l.554.99a2 2 0 0 0 1.5 1.003l1.118.163c1.535.223 2.137 2.112 1.026 3.195l-.81.789a2 2 0 0 0-.575 1.77l.192 1.114c.264 1.53-1.342 2.695-2.716 1.973l-1-.525a2 2 0 0 0-1.86 0l-1 .525c-1.374.722-2.98-.443-2.716-1.973l.192-1.114a2 2 0 0 0-.575-1.77l-.81-.789c-1.111-1.083-.51-2.972 1.026-3.195l1.118-.163a2 2 0 0 0 1.5-1.003l.554-.99Z" /></svg>',
                    ],
                    [
                        'label' => 'Jadwal Ujian',
                        'url' => route('admin.exams.index'),
                        'active' => request()->routeIs('admin.exams.*'),
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25m10.5-2.25v2.25M3 8.25h18M4.5 6.75h15A1.5 1.5 0 0 1 21 8.25v10.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.75V8.25a1.5 1.5 0 0 1 1.5-1.5Z" /></svg>',
                    ],
                    [
                        'label' => 'Pemantauan Ujian',
                        'url' => route('admin.monitoring'),
                        'active' => request()->routeIs('admin.monitoring'),
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 14.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" /></svg>',
                    ],
                    [
                        'label' => 'Berita Acara',
                        'url' => route('admin.attendance'),
                        'active' => request()->routeIs('admin.attendance'),
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375H14.25V6.375A3.375 3.375 0 0 0 10.875 3H8.25m0 0H5.625A2.625 2.625 0 0 0 3 5.625v12.75A2.625 2.625 0 0 0 5.625 21h12.75A2.625 2.625 0 0 0 21 18.375V15.75A2.25 2.25 0 0 0 18.75 13.5h-3.375a2.25 2.25 0 0 1-2.25-2.25V7.5A2.25 2.25 0 0 0 10.875 5.25H8.25Z" /></svg>',
                    ],
                    [
                        'label' => 'Cetak Kartu',
                        'url' => route('admin.cards'),
                        'active' => request()->routeIs('admin.cards'),
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v9A2.25 2.25 0 0 1 18.75 18.75H5.25A2.25 2.25 0 0 1 3 16.5v-9Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 9.75h9m-9 3h5.25" /></svg>',
                    ],
                    [
                        'label' => 'Rekap Nilai',
                        'url' => route('admin.recap'),
                        'active' => request()->routeIs('admin.recap'),
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v17.25h16.5M8.25 15v-3.75m3.75 3.75V9.75m3.75 5.25V6.75m3.75 8.25v-2.25" /></svg>',
                    ],
                    [
                        'label' => 'Konfigurasi',
                        'url' => route('admin.config'),
                        'active' => request()->routeIs('admin.config'),
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h3m-7.875 3h11.25m-14.25 3h16.5m-11.25 3h6" /></svg>',
                    ],
                ];
            @endphp

            <div class="space-y-1 overflow-y-auto pr-1">
                <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.2em] text-indigo-200">Menu</p>
                @foreach($menus as $menu)
                    @if(auth()->user()->isAdmin() || $menu['label'] === 'Dashboard')
                        <a
                            href="{{ $menu['url'] }}"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-200 hover:bg-indigo-600 {{ $menu['active'] ? 'bg-indigo-800' : '' }}"
                        >
                            {!! $menu['icon'] !!}
                            <span>{{ $menu['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </div>

            <form action="{{ route('logout') }}" method="post" class="mt-auto pt-5">
                @csrf
                <button class="w-full rounded-xl bg-red-500 px-4 py-2.5 text-sm font-semibold transition duration-200 hover:bg-red-600">
                    Logout
                </button>
            </form>
        </div>
    </aside>

    <main class="md:ml-64">
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b bg-white px-6 shadow-sm">
            <button @click="open = !open" class="rounded-lg border border-slate-200 p-2 text-slate-600 transition hover:bg-slate-100">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5m-16.5 5.25h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <div class="relative" @click.outside="profileOpen = false">
                <button @click="profileOpen = !profileOpen" class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2 transition hover:bg-slate-50">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 font-semibold text-indigo-700">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500">Administrator</p>
                    </div>
                </button>

                <div x-show="profileOpen" x-transition class="absolute right-0 mt-2 w-48 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                    <form action="{{ route('logout') }}" method="post">
                        @csrf
                        <button class="w-full rounded-lg px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50">Logout</button>
                    </form>
                </div>
            </div>
        </header>

        <section class="p-8">
            @yield('content')
        </section>
    </main>
</div>
@if(session('success'))
<script>Swal.fire({icon:'success',title:'Berhasil',text:@json(session('success'))})</script>
@endif
@stack('scripts')
</body>
</html>
