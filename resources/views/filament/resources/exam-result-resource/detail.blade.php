@php
    $answers = $record->answers->sortBy(fn ($answer) => $record->questionOrders->firstWhere('question_id', $answer->question_id)?->position ?? $answer->id)->values();
    $violationTypes = ['exit_fullscreen', 'tab_switch', 'window_blur', 'forbidden_shortcut', 'right_click', 'clipboard', 'devtools', 'page_reload', 'idle', 'connection_lost', 'heartbeat_missed', 'fullscreen_exit'];
    $logs = $record->logs->whereIn('activity_type', $violationTypes)->sortByDesc('logged_at');
@endphp

<div class="space-y-5 text-sm">
    <div class="grid gap-3 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
            <div class="font-semibold">Siswa</div>
            <div>{{ $record->student?->nama ?: '-' }}</div>
            <div class="text-xs text-gray-500">{{ $record->student?->nis ?: '-' }} · {{ $record->student?->kelas ?: '-' }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
            <div class="font-semibold">Ujian</div>
            <div>{{ $record->exam?->nama_ujian ?: '-' }}</div>
            <div class="text-xs text-gray-500">{{ $record->exam?->mata_pelajaran ?: '-' }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
            <div class="font-semibold">Ringkasan</div>
            <div>Nilai: <strong>{{ number_format((float) $record->nilai, 2) }}</strong></div>
            <div class="text-xs text-gray-500">Benar {{ $record->correct_count }} · Salah {{ $record->wrong_count }} · Kosong {{ $record->unanswered_count }}</div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="w-full min-w-[900px] divide-y divide-gray-200 text-left text-xs dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="p-2">No</th>
                    <th class="p-2">Pertanyaan</th>
                    <th class="p-2">Jawaban Siswa</th>
                    <th class="p-2">Kunci</th>
                    <th class="p-2">Status</th>
                    <th class="p-2">Bobot</th>
                    <th class="p-2">Poin</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($answers as $answer)
                    @php($question = $answer->question)
                    <tr>
                        <td class="p-2 font-semibold">{{ $loop->iteration }}</td>
                        <td class="p-2">{{ \Illuminate\Support\Str::limit(strip_tags((string) $question?->soal), 120) }}</td>
                        <td class="p-2">{{ strtoupper($answer->jawaban_siswa ?: '-') }}</td>
                        <td class="p-2">{{ strtoupper($question?->jawaban_benar ?: '-') }}</td>
                        <td class="p-2">
                            @if(blank($answer->jawaban_siswa)) Tidak dijawab @elseif($answer->is_correct) Benar @else Salah @endif
                        </td>
                        <td class="p-2">1</td>
                        <td class="p-2">{{ $answer->is_correct ? 1 : 0 }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-4 text-center text-gray-500">Belum ada jawaban tersimpan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
        <div class="mb-2 font-semibold">Log Pelanggaran</div>
        @forelse($logs as $log)
            <div class="border-t border-gray-100 py-2 text-xs dark:border-gray-800">
                <strong>{{ $log->activity_type }}</strong> — {{ $log->description ?: '-' }}
                <span class="text-gray-500">({{ optional($log->logged_at)->format('d M Y H:i:s') }})</span>
            </div>
        @empty
            <div class="text-xs text-gray-500">Tidak ada pelanggaran.</div>
        @endforelse
    </div>
</div>
