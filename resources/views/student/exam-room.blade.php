@extends('layouts.app')

@section('content')
@php
    $exam = $result->exam;
    $answerMap = $result->answers->keyBy('question_id');
    $serverNowMs = ($serverNow ?? now())->getTimestamp() * 1000;
    $remainingSeconds = (int) ($remainingSeconds ?? ($result->server_ends_at ? max(0, now()->diffInSeconds($result->server_ends_at, false)) : ((int) $exam->durasi * 60)));
    $endsAtMs = $serverNowMs + ($remainingSeconds * 1000);
@endphp

<div class="space-y-5">
    <div id="examStartOverlay" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 p-4 backdrop-blur">
        <section class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-[2rem] bg-white p-6 shadow-2xl sm:p-8">
            <p class="text-sm font-bold uppercase tracking-[0.25em] text-blue-600">Instruksi CBT Julia</p>
            <h1 class="mt-3 text-3xl font-black text-slate-950">Aturan Ujian</h1>
            <p class="mt-2 text-sm font-semibold text-slate-500">Baca aturan berikut sebelum membuka soal. Ujian baru dapat dikerjakan setelah fullscreen berhasil aktif di halaman final ini.</p>

            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @foreach([
                    'Ujian wajib dikerjakan dalam mode fullscreen.',
                    'Dilarang pindah tab atau membuka aplikasi lain.',
                    'Dilarang keluar halaman selama ujian berlangsung.',
                    'Dilarang klik kanan, copy, paste, atau shortcut terlarang.',
                    'Jika keluar fullscreen, aktivitas dicatat sebagai pelanggaran.',
                    'Jawaban tetap tersimpan otomatis tanpa reload halaman.',
                ] as $rule)
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-800">✓ {{ $rule }}</div>
                @endforeach
            </div>

            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900">
                Setelah tombol diklik dan fullscreen sukses, soal akan tampil, timer/heartbeat berjalan, dan anti-cheat mulai aktif. Jangan menekan Esc kecuali diminta pengawas.
            </div>

            <p id="fullscreenStartError" class="mt-4 hidden rounded-2xl bg-red-50 p-3 text-sm font-bold text-red-700">Browser tidak mengizinkan fullscreen. Silakan klik tombol mulai lagi.</p>

            <button type="button" id="startFullscreenExam" class="mt-5 w-full rounded-2xl bg-blue-600 px-6 py-4 text-base font-black text-white shadow-sm hover:bg-blue-700">
                Mulai Ujian dalam Fullscreen
            </button>
        </section>
    </div>

    <div id="fullscreenWarningBackdrop" class="pointer-events-none fixed inset-0 z-40 hidden bg-slate-950/40 backdrop-blur-sm"></div>

    <div id="examContent" class="hidden space-y-5">
        <div id="studentExamTimer" class="sticky top-3 z-30 rounded-3xl border border-blue-200 bg-blue-600 p-4 text-white shadow-2xl shadow-blue-500/20" data-state="normal">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-blue-100">Sisa Waktu</p>
                    <p id="studentExamTimerValue" class="text-3xl font-black tabular-nums">{{ gmdate('H:i:s', max(0, $remainingSeconds)) }}</p>
                </div>
                <p id="studentExamTimerWarning" class="text-sm font-bold text-blue-50">Timer sinkron dari server. Ujian otomatis submit saat waktu habis.</p>
            </div>
        </div>

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
                $questionType = $question->tipe_soal ?? 'pilihan_ganda';
                $savedAnswerArray = is_string($savedAnswer) ? json_decode($savedAnswer, true) : $savedAnswer;
                if (!is_array($savedAnswerArray)) $savedAnswerArray = $savedAnswer ? [$savedAnswer] : [];
            @endphp
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm" id="question-{{ $question->id }}">
                <div class="flex gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-blue-600 font-black text-white">{{ $loop->iteration }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="whitespace-pre-line text-base font-semibold leading-relaxed text-slate-900">{{ $question->soal }}</p>
                            @if($questionType !== 'pilihan_ganda')
                                <span class="shrink-0 rounded-lg bg-purple-100 px-2 py-1 text-xs font-bold text-purple-700">
                                    {{ ['multiple_answer' => 'Multiple Answer', 'checklist' => 'Checklist', 'dropdown' => 'Dropdown'][$questionType] ?? 'Pilihan Ganda' }}
                                </span>
                            @endif
                        </div>

                        @if($questionType === 'dropdown')
                            <div class="mt-4">
                                <select 
                                    data-answer-dropdown
                                    data-question-id="{{ $question->id }}"
                                    class="block w-full rounded-2xl border border-slate-200 p-3 text-sm font-semibold text-slate-700 transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                >
                                    <option value="">-- Pilih Jawaban --</option>
                                    @foreach(['a','b','c','d','e'] as $opt)
                                        @php($field = 'pilihan_'.$opt)
                                        @if($question->$field)
                                            <option value="{{ $opt }}" {{ $savedAnswer === $opt ? 'selected' : '' }}>
                                                {{ strtoupper($opt) }}. {{ $question->$field }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                        @elseif($questionType === 'multiple_answer')
                            <div class="mt-4 space-y-2">
                                @foreach(['a','b','c','d','e'] as $opt)
                                    @php($field = 'pilihan_'.$opt)
                                    @if($question->$field)
                                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border p-3 transition hover:border-blue-300 hover:bg-blue-50 {{ in_array($opt, $savedAnswerArray) ? 'border-blue-500 bg-blue-50 text-blue-900 ring-2 ring-blue-200' : 'border-slate-200 text-slate-700' }}">
                                            <input
                                                type="checkbox"
                                                data-answer-checkbox
                                                data-question-id="{{ $question->id }}"
                                                value="{{ $opt }}"
                                                {{ in_array($opt, $savedAnswerArray) ? 'checked' : '' }}
                                                class="mt-1 h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                                            >
                                            <span class="flex-1 text-sm font-semibold">
                                                <span class="font-black">{{ strtoupper($opt) }}.</span> {{ $question->$field }}
                                            </span>
                                        </label>
                                    @endif
                                @endforeach
                            </div>

                        @elseif($questionType === 'checklist')
                            @php
                                $checklistItems = is_array($question->jawaban_benar) ? $question->jawaban_benar : json_decode($question->jawaban_benar ?? '[]', true);
                                if (!is_array($checklistItems)) $checklistItems = [];
                            @endphp
                            <div class="mt-4 space-y-3">
                                @foreach($checklistItems as $index => $item)
                                    @php
                                        $statement = is_array($item) ? ($item['statement'] ?? '') : '';
                                        $savedValue = $savedAnswerArray[$index] ?? null;
                                    @endphp
                                    @if($statement)
                                        <div class="rounded-2xl border border-slate-200 p-4">
                                            <p class="text-sm font-semibold text-slate-700">{{ $statement }}</p>
                                            <div class="mt-2 flex gap-2">
                                                @foreach(['true' => 'Benar', 'false' => 'Salah'] as $val => $label)
                                                    <label class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-xl border p-2 transition hover:border-blue-300 hover:bg-blue-50 {{ $savedValue === $val ? 'border-blue-500 bg-blue-50 text-blue-900 ring-2 ring-blue-200' : 'border-slate-200 text-slate-700' }}">
                                                        <input
                                                            type="radio"
                                                            name="checklist_{{ $question->id }}_{{ $index }}"
                                                            data-answer-checklist
                                                            data-question-id="{{ $question->id }}"
                                                            data-index="{{ $index }}"
                                                            value="{{ $val }}"
                                                            {{ $savedValue === $val ? 'checked' : '' }}
                                                            class="h-4 w-4 border-slate-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                                                        >
                                                        <span class="text-sm font-bold">{{ $label }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                        @else
                            {{-- Default: pilihan_ganda --}}
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
                        @endif
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
        afterSubmitUrl: @json(route('student.results')),
        endsAtMs: {{ $endsAtMs }},
        serverNowMs: {{ $serverNowMs }},
        remainingSeconds: {{ $remainingSeconds }},
        totalQuestions: {{ $answers->count() }},
        submitUrl: @json(route('student.exams.submit', $result)),
        idleTimeoutMs: 180000,
    };
</script>
<x-partials.vite-assets :entries="['resources/js/student-exam-security.js']" />
@endpush
