@extends('student.layout', ['title' => 'Dashboard Siswa', 'subtitle' => 'Ringkasan Ujian'])

@section('content')
<div class="space-y-6" x-data="{ tokenExam: null }">
    <div class="grid gap-6 xl:grid-cols-[1fr_320px]">
        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#2563eb] via-[#2563eb] to-[#4f46e5] p-6 text-white shadow-xl shadow-blue-900/20 sm:p-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-[0.25em] text-blue-100">CBT Julia · Portal Peserta</p>
                <h1 class="mt-4 text-3xl font-black sm:text-4xl">Selamat datang, {{ $student->nama }}</h1>
                <p class="mt-3 text-base text-blue-100 sm:text-lg">Berikut jadwal ujian dan hasil belajar kamu. Pastikan perangkat siap sebelum mulai ujian.</p>
            </div>
            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl bg-white/15 p-4 backdrop-blur"><p class="text-xs uppercase text-blue-100">NIS</p><p class="mt-1 font-black">{{ $student->nis }}</p></div>
                <div class="rounded-2xl bg-white/15 p-4 backdrop-blur"><p class="text-xs uppercase text-blue-100">Kelas</p><p class="mt-1 font-black">{{ $student->kelas }}</p></div>
                <div class="rounded-2xl bg-white/15 p-4 backdrop-blur"><p class="text-xs uppercase text-blue-100">Username</p><p class="mt-1 font-black">{{ $student->username }}</p></div>
            </div>
        </section>

        <aside class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-blue-100 text-2xl font-black text-[#2563eb]">{{ mb_substr($student->nama, 0, 1) }}</div>
                <div>
                    <p class="font-black text-slate-950">{{ $student->nama }}</p>
                    <p class="text-sm font-semibold text-slate-500">Kelas {{ $student->kelas }}</p>
                </div>
            </div>
            <div class="mt-5 rounded-2xl bg-green-50 px-4 py-3 text-sm font-bold text-green-700">Status: {{ ucfirst($student->status) }} · Peserta Ujian</div>
        </aside>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Ujian Aktif', 'value' => $stats['active'], 'color' => 'blue', 'note' => 'Bisa / sedang dikerjakan'],
            ['label' => 'Ujian Belum Dikerjakan', 'value' => $stats['pending'], 'color' => 'amber', 'note' => 'Menunggu atau tersedia'],
            ['label' => 'Ujian Selesai', 'value' => $stats['finished'], 'color' => 'green', 'note' => 'Sudah submit'],
            ['label' => 'Rata-rata Nilai', 'value' => $stats['average'], 'color' => 'indigo', 'note' => 'Dari hasil selesai'],
        ] as $card)
            @php($color = match($card['color']) {'amber' => 'bg-amber-50 text-amber-600', 'green' => 'bg-green-50 text-green-600', 'indigo' => 'bg-indigo-50 text-indigo-600', default => 'bg-blue-50 text-blue-600'})
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-black text-slate-950">{{ $card['value'] }}</p>
                    </div>
                    <div class="rounded-2xl px-3 py-2 text-xs font-black {{ $color }}">CBT</div>
                </div>
                <p class="mt-3 text-xs font-semibold text-slate-400">{{ $card['note'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-xl font-black text-slate-950">Jadwal Ujian Aktif</h2>
                    <p class="text-sm font-semibold text-slate-500">Ujian yang relevan untuk kelas kamu.</p>
                </div>
                <a href="{{ route('student.exams') }}" class="rounded-2xl bg-blue-50 px-4 py-2 text-sm font-black text-[#2563eb] hover:bg-blue-100">Lihat semua</a>
            </div>

            @forelse($activeExams as $exam)
                @php
                    $badge = match($exam->student_status) {
                        'available' => 'bg-blue-50 text-blue-700 border-blue-100',
                        'in_progress' => 'bg-amber-50 text-amber-700 border-amber-100',
                        'finished' => 'bg-green-50 text-green-700 border-green-100',
                        'missed' => 'bg-red-50 text-red-700 border-red-100',
                        default => 'bg-slate-50 text-slate-700 border-slate-100',
                    };
                @endphp
                <article class="mb-4 rounded-3xl border border-slate-100 bg-slate-50/70 p-4 last:mb-0">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                <span class="rounded-full border px-3 py-1 text-xs font-black {{ $badge }}">{{ $exam->status_label }}</span>
                                @unless($exam->is_ready)<span class="rounded-full border border-red-100 bg-red-50 px-3 py-1 text-xs font-black text-red-700">Ujian belum siap</span>@endunless
                            </div>
                            <h3 class="text-lg font-black text-slate-950">{{ $exam->nama_ujian }}</h3>
                            <p class="text-sm font-semibold text-slate-500">{{ $exam->mata_pelajaran }} · Kelas {{ $exam->kelas }}</p>
                            <div class="mt-3 grid gap-2 text-sm font-semibold text-slate-600 sm:grid-cols-2 xl:grid-cols-4">
                                <span>{{ optional($exam->starts_at)->translatedFormat('d M Y') ?? '-' }}</span>
                                <span>{{ optional($exam->starts_at)->format('H:i') }} - {{ optional($exam->ends_at)->format('H:i') }}</span>
                                <span>{{ $exam->durasi }} menit</span>
                                <span>{{ $exam->question_total ?: $exam->jumlah_soal }} soal</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2 sm:min-w-44">
                            @include('student.exams.partials.action', ['exam' => $exam])
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-2xl">🗓️</div>
                    <h3 class="text-lg font-black text-slate-950">Belum ada ujian aktif</h3>
                    <p class="mt-2 text-sm font-semibold text-slate-500">Jadwal ujian akan muncul di sini jika sudah tersedia.</p>
                </div>
            @endforelse
        </section>

        <aside class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black text-slate-950">Informasi CBT</h2>
            <p class="mt-1 text-sm font-semibold text-slate-500">Panduan sebelum memulai ujian.</p>
            <ul class="mt-5 space-y-3 text-sm font-semibold text-slate-600">
                @foreach(['Pastikan koneksi internet stabil', 'Jangan keluar dari fullscreen', 'Jangan pindah tab', 'Jangan membuka aplikasi lain', 'Pelanggaran akan tercatat otomatis'] as $rule)
                    <li class="flex gap-3"><span class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-blue-50 text-xs font-black text-[#2563eb]">✓</span><span>{{ $rule }}</span></li>
                @endforeach
            </ul>
        </aside>
    </div>

    <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-black text-slate-950">Riwayat Nilai Terbaru</h2>
                <p class="text-sm font-semibold text-slate-500">Hasil ujian yang sudah kamu selesaikan.</p>
            </div>
            <a href="{{ route('student.results') }}" class="rounded-2xl bg-blue-50 px-4 py-2 text-sm font-black text-[#2563eb] hover:bg-blue-100">Lihat riwayat</a>
        </div>

        @forelse($latestResults as $result)
            <div class="flex flex-col gap-3 border-t border-slate-100 py-4 first:border-t-0 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-black text-slate-950">{{ $result->exam?->nama_ujian ?? 'Ujian' }}</p>
                    <p class="text-sm font-semibold text-slate-500">{{ $result->exam?->mata_pelajaran ?? '-' }} · {{ optional($result->submitted_at ?? $result->updated_at)->translatedFormat('d M Y H:i') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="rounded-2xl bg-green-50 px-4 py-2 text-sm font-black text-green-700">Nilai {{ $result->nilai ?? 0 }}</span>
                    <span class="text-sm font-bold text-slate-500">{{ str_replace('_', ' ', ucfirst($result->status)) }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-2xl">🏆</div>
                <h3 class="text-lg font-black text-slate-950">Belum ada riwayat nilai</h3>
                <p class="mt-2 text-sm font-semibold text-slate-500">Hasil ujian akan muncul setelah kamu menyelesaikan ujian.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection
