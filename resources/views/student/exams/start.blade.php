@extends('student.layout', ['title' => 'Aturan Ujian', 'subtitle' => 'Instruksi Ujian'])

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
        <h2 class="text-2xl font-black text-amber-900">Aturan Ujian</h2>
        <p class="mt-2 text-sm font-semibold text-amber-800">Sistem akan mendeteksi jika peserta meninggalkan halaman ujian. Browser tidak bisa memblokir tombol OS seperti Alt+Tab, tombol Windows, Ctrl+Alt+Del, atau Task Manager secara penuh.</p>
        <div class="mt-5 grid gap-3 md:grid-cols-2">
            @foreach([
                'Ujian wajib menggunakan fullscreen.',
                'Dilarang pindah tab atau membuka aplikasi lain.',
                'Dilarang menekan shortcut keyboard.',
                'Dilarang copy, paste, dan klik kanan.',
                'Setiap pelanggaran akan tercatat otomatis.',
                'Ujian dapat dikumpulkan otomatis jika pelanggaran melewati batas.',
            ] as $rule)
                <div class="rounded-2xl bg-white/80 p-4 text-sm font-bold text-amber-900">✓ {{ $rule }}</div>
            @endforeach
        </div>
    </section>

    <form id="begin-exam-form" method="post" action="{{ route('student.exams.begin', $exam) }}" class="space-y-4 rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
        @csrf
        <label class="flex items-start gap-3 rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-700">
            <input id="rules-agreement" type="checkbox" class="mt-1 h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" required>
            <span>Saya sudah membaca dan menyetujui aturan ujian.</span>
        </label>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p id="fullscreen-error" class="hidden text-sm font-semibold text-red-600">Browser tidak mengizinkan fullscreen. Silakan klik tombol Mulai Ujian lagi.</p>
            <p class="text-sm font-semibold text-slate-500">Pastikan baterai dan koneksi internet stabil sebelum memulai.</p>
            <button id="begin-exam-button" type="submit" class="rounded-2xl bg-[#2563eb] px-6 py-3 text-sm font-black text-white shadow-sm hover:bg-blue-700">Mulai Ujian dalam Fullscreen</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('begin-exam-form')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const error = document.getElementById('fullscreen-error');
        const button = document.getElementById('begin-exam-button');

        if (!document.getElementById('rules-agreement').checked) {
            return;
        }

        try {
            button.disabled = true;
            button.textContent = 'Membuka Fullscreen...';
            await document.documentElement.requestFullscreen();
            form.submit();
        } catch (e) {
            error.classList.remove('hidden');
            button.disabled = false;
            button.textContent = 'Mulai Ujian dalam Fullscreen';
        }
    });
</script>
@endpush
