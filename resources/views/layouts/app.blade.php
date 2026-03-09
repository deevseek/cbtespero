<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espero CBT</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-100 text-slate-700">
<div class="min-h-screen flex" x-data="{open:true}">
    <aside class="bg-blue-900 text-blue-50 w-72 p-5 space-y-2" :class="open ? 'block':'hidden md:block'">
        <h1 class="text-xl font-bold mb-5">Espero CBT</h1>
        @php($menus = [
            'Dashboard' => auth()->user()->isAdmin() ? route('admin.dashboard') : route('student.dashboard'),
            'Data Siswa' => route('admin.students.index'),
            'Bank Soal' => route('admin.questions.index'),
            'Jadwal Ujian' => route('admin.exams.index'),
            'Pemantauan Ujian' => route('admin.monitoring'),
            'Berita Acara & Absen' => route('admin.attendance'),
            'Cetak Kartu Ujian' => route('admin.cards'),
            'Rekapitulasi Nilai' => route('admin.recap'),
            'Konfigurasi' => route('admin.config'),
        ])
        @foreach($menus as $label => $url)
            @if(auth()->user()->isAdmin() || $label === 'Dashboard')
            <a href="{{ $url }}" class="block px-4 py-2 rounded-xl hover:bg-blue-800">{{ $label }}</a>
            @endif
        @endforeach
        <form action="{{ route('logout') }}" method="post" class="pt-4">@csrf<button class="w-full px-4 py-2 rounded-xl bg-red-500">Logout</button></form>
    </aside>
    <main class="flex-1">
        <header class="bg-white shadow-sm p-4 flex justify-between items-center">
            <button @click="open=!open" class="px-3 py-2 rounded-lg bg-slate-200">☰</button>
            <div class="font-semibold">{{ auth()->user()->name }}</div>
        </header>
        <section class="p-6">@yield('content')</section>
    </main>
</div>
@if(session('success'))
<script>Swal.fire({icon:'success',title:'Berhasil',text:@json(session('success'))})</script>
@endif
</body>
</html>
