@extends('layouts.app')

@section('content')
@php
    $exam = $result->exam;
    $answerMap = $result->answers->keyBy('question_id');
    $endsAtMs = optional($result->server_ends_at)->getTimestamp() ? $result->server_ends_at->getTimestamp() * 1000 : now()->addMinutes((int) $exam->durasi)->getTimestamp() * 1000;
@endphp

<div class="space-y-5">
    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm font-semibold text-blue-900">
        Sistem akan mendeteksi jika peserta meninggalkan halaman ujian. Browser tidak bisa memblokir Alt+Tab, tombol Windows, Ctrl+Alt+Del, atau Task Manager secara penuh; efeknya dideteksi melalui blur/visibilitychange dan dicatat sebagai pelanggaran.
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-blue-600">Ruang Ujian</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">{{ $exam->nama_ujian }}</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">{{ $exam->mata_pelajaran }} · Kelas {{ $exam->kelas }}</p>
            </div>
            <div class="grid gap-2 sm:grid-cols-3">
                <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs font-bold uppercase text-slate-400">Peserta</p><p class="font-black">{{ $result->student->nama ?? 'Siswa' }}</p></div>
                <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs font-bold uppercase text-slate-400">Auto Save</p><p class="font-black text-green-600">Aktif</p></div>
                <div class="rounded-2xl bg-slate-50 px-4 py-3"><p class="text-xs font-bold uppercase text-slate-400">Heartbeat</p><p class="font-black text-blue-600">10 detik</p></div>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        @foreach($answers as $item)
            @php
                $question = $item->question;
                $savedAnswer = optional($answerMap->get($question->id))->jawaban_siswa;
            @endphp
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm" id="question-{{ $question->id }}">
                <div class="flex gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-blue-600 font-black text-white">{{ $loop->iteration }}</div>
                    <div class="min-w-0 flex-1">
                        <p class="whitespace-pre-line text-base font-semibold leading-relaxed text-slate-900">{{ $question->soal }}</p>
                        <div class="mt-4 space-y-2">
                            @foreach(['a','b','c','d','e'] as $opt)
                                @php($field = 'pilihan_'.$opt)
                                @if($question->$field)
                                    <button
                                        type="button"
                                        data-answer-button
                                        data-question-id="{{ $question->id }}"
                                        data-answer="{{ $opt }}"
                                        aria-pressed="{{ $savedAnswer === $opt ? 'true' : 'false' }}"
                                        class="block w-full rounded-2xl border p-3 text-left text-sm font-semibold transition hover:border-blue-300 hover:bg-blue-50 {{ $savedAnswer === $opt ? 'border-blue-500 bg-blue-50 text-blue-900 ring-2 ring-blue-200' : 'border-slate-200 text-slate-700' }}"
                                    >
                                        <span class="mr-2 font-black">{{ strtoupper($opt) }}.</span> {{ $question->$field }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <form method="post" action="{{ route('student.exams.submit', $result) }}" class="sticky bottom-4 rounded-3xl border border-green-200 bg-white/95 p-4 shadow-2xl shadow-slate-900/10 backdrop-blur" onsubmit="window.__studentExamSubmitting = true">
        @csrf
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-semibold text-slate-500">Jawaban tersimpan otomatis saat memilih opsi dan setiap 10 detik.</p>
            <button class="rounded-2xl bg-green-600 px-5 py-3 text-sm font-black text-white hover:bg-green-700">Selesai Ujian</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    window.studentExamSecurityConfig = {
        active: true,
        resultId: {{ $result->id }},
        examId: {{ $exam->id }},
        csrfToken: @json(csrf_token()),
        answerUrl: @json(route('student.exams.answer', $result)),
        violationUrl: @json(route('student.exams.violations', $exam)),
        heartbeatUrl: @json(route('student.exams.heartbeat', $exam)),
        afterSubmitUrl: @json(route('student.dashboard')),
        endsAtMs: {{ $endsAtMs }},
        idleTimeoutMs: 180000,
    };
</script>
<x-partials.vite-assets :entries="['resources/js/student-exam-security.js']" />
@endpush
