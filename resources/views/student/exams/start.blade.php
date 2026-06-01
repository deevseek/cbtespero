@extends('student.layout', ['title' => 'Instruksi Ujian', 'subtitle' => 'Mulai Ujian'])

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.25em] text-[#2563eb]">CBT Julia</p>
                <h1 class="mt-3 text-3xl font-black text-slate-950">{{ $exam->nama_ujian }}</h1>
                <p class="mt-2 text-base font-semibold text-slate-500">{{ $exam->mata_pelajaran }} · Kelas {{ $exam->kelas }}</p>
            </div>
            <span class="rounded-full bg-blue-50 px-4 py-2 text-sm font-black text-[#2563eb]">{{ $exam->status_label }}</span>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-4">
            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-400">Tanggal</p><p class="mt-1 font-black">{{ optional($exam->starts_at)->translatedFormat('d M Y') }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-400">Waktu</p><p class="mt-1 font-black">{{ optional($exam->starts_at)->format('H:i') }} - {{ optional($exam->ends_at)->format('H:i') }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-400">Durasi</p><p class="mt-1 font-black">{{ $exam->durasi }} menit</p></div>
            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-400">Jumlah Soal</p><p class="mt-1 font-black">{{ $exam->question_total ?: $exam->jumlah_soal }}</p></div>
        </div>
    </section>

    <section class="rounded-[2rem] border border-amber-200 bg-amber-50 p-6 shadow-sm sm:p-8">
        <h2 class="text-2xl font-black text-amber-900">Aturan keamanan ujian</h2>
        <p class="mt-2 text-sm font-semibold text-amber-800">Dengan menekan tombol mulai, kamu menyetujui aturan pengawasan CBT berikut.</p>
        <div class="mt-5 grid gap-3 md:grid-cols-2">
            @foreach(['Wajib fullscreen selama mengerjakan', 'Dilarang pindah tab atau membuka aplikasi lain', 'Dilarang keluar dari halaman ujian', 'Aktivitas mencurigakan akan tercatat otomatis', 'Screenshot dan copy/paste diblokir jika didukung browser'] as $rule)
                <div class="rounded-2xl bg-white/80 p-4 text-sm font-bold text-amber-900">✓ {{ $rule }}</div>
            @endforeach
        </div>
    </section>

    <form method="post" action="{{ route('student.exams.begin', $exam) }}" class="flex flex-col gap-3 rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        @csrf
        <p class="text-sm font-semibold text-slate-500">Pastikan baterai dan koneksi internet stabil sebelum memulai.</p>
        <button class="rounded-2xl bg-[#2563eb] px-6 py-3 text-sm font-black text-white shadow-sm hover:bg-blue-700">Saya Mengerti, Mulai Ujian</button>
    </form>
</div>
@endsection
