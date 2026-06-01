<x-filament-panels::page>
    <div class="space-y-6" wire:poll.10s>
        @php($summary = $this->getSummary())
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['label' => 'Total Peserta', 'value' => $summary['total'], 'class' => 'from-blue-500/20 to-blue-400/5 text-blue-200'],
                ['label' => 'Sedang Mengerjakan', 'value' => $summary['running'], 'class' => 'from-cyan-500/20 to-cyan-400/5 text-cyan-200'],
                ['label' => 'Selesai', 'value' => $summary['finished'], 'class' => 'from-green-500/20 to-green-400/5 text-green-200'],
                ['label' => 'Belum Mulai', 'value' => $summary['not_started'], 'class' => 'from-slate-500/20 to-slate-400/5 text-slate-200'],
                ['label' => 'Pelanggaran', 'value' => $summary['violations'], 'class' => 'from-red-500/20 to-red-400/5 text-red-200'],
            ] as $card)
                <div class="rounded-3xl border border-slate-700/60 bg-gradient-to-br {{ $card['class'] }} p-5 shadow-2xl shadow-slate-950/20">
                    <div class="text-3xl font-black">{{ number_format($card['value']) }}</div>
                    <div class="mt-1 text-xs font-bold uppercase tracking-wide text-slate-400">{{ $card['label'] }}</div>
                </div>
            @endforeach
        </div>

        <x-filament::section>
            <x-slot name="heading">Monitoring Ujian</x-slot>
            <x-slot name="description">Pantau peserta ujian secara real-time. Tabel otomatis refresh setiap 10 detik.</x-slot>
            <div class="overflow-x-auto">
                {{ $this->table }}
            </div>
        </x-filament::section>
    </div>
    @push('scripts')
        <script>
            window.cbtMonitoring = window.cbtMonitoring || {};
            window.cbtMonitoring.subscribe = function (examId) {
                if (!window.Echo || !examId) {
                    return false;
                }

                window.Echo.private(`exam.${examId}`)
                    .listen('.StudentExamStarted', (event) => window.dispatchEvent(new CustomEvent('cbt-monitoring-update', { detail: event })))
                    .listen('.StudentAnswerSaved', (event) => window.dispatchEvent(new CustomEvent('cbt-monitoring-update', { detail: event })))
                    .listen('.StudentHeartbeatUpdated', (event) => window.dispatchEvent(new CustomEvent('cbt-monitoring-update', { detail: event })))
                    .listen('.StudentExamSubmitted', (event) => window.dispatchEvent(new CustomEvent('cbt-monitoring-update', { detail: event })))
                    .listen('.ExamViolationLogged', (event) => window.dispatchEvent(new CustomEvent('cbt-monitoring-update', { detail: event })));

                return true;
            };

            @foreach(\App\Models\ExamResult::query()->whereNotNull('exam_id')->distinct()->pluck('exam_id') as $examId)
                window.cbtMonitoring.subscribe({{ (int) $examId }});
            @endforeach

            window.addEventListener('cbt-monitoring-update', () => {
                if (window.Livewire) {
                    window.Livewire.dispatch('$refresh');
                }
            });
        </script>
    @endpush
</x-filament-panels::page>
