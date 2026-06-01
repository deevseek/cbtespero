@extends('student.layout', ['title' => 'Riwayat Nilai', 'subtitle' => 'Hasil Ujian'])

@section('content')
<section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
    <div class="mb-5">
        <h1 class="text-2xl font-black text-slate-950">Riwayat Nilai</h1>
        <p class="mt-1 text-sm font-semibold text-slate-500">Daftar hasil ujian milik kamu.</p>
    </div>

    @forelse($results as $result)
        @php
            $correct = $result->answers->where('is_correct', true)->count();
            $wrong = $result->answers->where('is_correct', false)->whereNotNull('jawaban_siswa')->count();
        @endphp
        <article class="grid gap-4 border-t border-slate-100 py-5 first:border-t-0 lg:grid-cols-[1fr_320px] lg:items-center">
            <div>
                <h2 class="text-lg font-black text-slate-950">{{ $result->exam?->nama_ujian ?? 'Ujian' }}</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">{{ $result->exam?->mata_pelajaran ?? '-' }} · {{ optional($result->submitted_at ?? $result->updated_at)->translatedFormat('d M Y H:i') }}</p>
                <div class="mt-3 flex flex-wrap gap-2 text-xs font-black">
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">Status: {{ str_replace('_', ' ', ucfirst($result->status)) }}</span>
                    <span class="rounded-full bg-green-50 px-3 py-1 text-green-700">Benar: {{ $correct }}</span>
                    <span class="rounded-full bg-red-50 px-3 py-1 text-red-700">Salah: {{ $wrong }}</span>
                </div>
            </div>
            <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 p-4">
                <div>
                    <p class="text-xs font-bold uppercase text-slate-400">Nilai</p>
                    <p class="text-3xl font-black text-slate-950">{{ $result->nilai ?? 0 }}</p>
                </div>
                @if($result->status === 'sedang_mengerjakan')
                    <a href="{{ route('student.exams.room', $result) }}" class="rounded-2xl bg-amber-500 px-4 py-3 text-sm font-black text-white">Lanjutkan</a>
                @else
                    <button disabled class="rounded-2xl bg-white px-4 py-3 text-sm font-black text-slate-400">Detail</button>
                @endif
            </div>
        </article>
    @empty
        <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-blue-50 text-3xl">🏆</div>
            <h3 class="text-xl font-black text-slate-950">Belum ada riwayat nilai</h3>
            <p class="mt-2 text-sm font-semibold text-slate-500">Hasil ujian akan muncul setelah kamu menyelesaikan ujian.</p>
        </div>
    @endforelse

    <div class="mt-6">{{ $results->links() }}</div>
</section>
@endsection
