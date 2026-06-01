@extends('student.layout', ['title' => 'Ujian Aktif', 'subtitle' => 'Daftar Ujian'])

@section('content')
<div class="space-y-6" x-data="{ tokenExam: null }">
    <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <form method="get" class="grid gap-3 lg:grid-cols-[1fr_220px_auto]">
            <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama ujian atau mata pelajaran" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold outline-none focus:border-[#2563eb] focus:ring-4 focus:ring-blue-100">
            <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold outline-none focus:border-[#2563eb] focus:ring-4 focus:ring-blue-100">
                <option value="">Semua status</option>
                @foreach(['upcoming' => 'Belum Mulai', 'available' => 'Bisa Dikerjakan', 'in_progress' => 'Sedang Berlangsung', 'finished' => 'Selesai', 'missed' => 'Terlewat'] as $value => $label)
                    <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-2xl bg-[#2563eb] px-5 py-3 text-sm font-black text-white shadow-sm hover:bg-blue-700">Filter</button>
        </form>
    </section>

    <section class="grid gap-4">
        @forelse($exams as $exam)
            @php
                $badge = match($exam->student_status) {
                    'available' => 'bg-blue-50 text-blue-700 border-blue-100',
                    'in_progress' => 'bg-amber-50 text-amber-700 border-amber-100',
                    'finished' => 'bg-green-50 text-green-700 border-green-100',
                    'missed' => 'bg-red-50 text-red-700 border-red-100',
                    default => 'bg-slate-50 text-slate-700 border-slate-100',
                };
            @endphp
            <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="grid gap-5 xl:grid-cols-[1fr_220px] xl:items-center">
                    <div>
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <span class="rounded-full border px-3 py-1 text-xs font-black {{ $badge }}">{{ $exam->status_label }}</span>
                            @if($exam->requires_token)<span class="rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">Pakai token</span>@endif
                            @unless($exam->is_ready)<span class="rounded-full border border-red-100 bg-red-50 px-3 py-1 text-xs font-black text-red-700">Ujian belum siap</span>@endunless
                        </div>
                        <h2 class="text-xl font-black text-slate-950">{{ $exam->nama_ujian }}</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">{{ $exam->mata_pelajaran }} · Kelas {{ $exam->kelas }}</p>
                        <div class="mt-4 grid gap-3 text-sm font-semibold text-slate-600 md:grid-cols-5">
                            <div><span class="block text-xs text-slate-400">Tanggal</span>{{ optional($exam->starts_at)->translatedFormat('d M Y') ?? '-' }}</div>
                            <div><span class="block text-xs text-slate-400">Waktu</span>{{ optional($exam->starts_at)->format('H:i') }} - {{ optional($exam->ends_at)->format('H:i') }}</div>
                            <div><span class="block text-xs text-slate-400">Durasi</span>{{ $exam->durasi }} menit</div>
                            <div><span class="block text-xs text-slate-400">Soal</span>{{ $exam->question_total ?: $exam->jumlah_soal }}</div>
                            <div><span class="block text-xs text-slate-400">Status</span>{{ $exam->status_label }}</div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        @include('student.exams.partials.action', ['exam' => $exam])
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-blue-50 text-3xl">🗓️</div>
                <h3 class="text-xl font-black text-slate-950">Belum ada ujian aktif</h3>
                <p class="mt-2 text-sm font-semibold text-slate-500">Jadwal ujian akan muncul di sini jika sudah tersedia untuk kelas kamu.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection
