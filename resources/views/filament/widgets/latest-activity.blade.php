<x-filament::section>
    <x-slot name="heading">Aktivitas Terbaru</x-slot>
    <x-slot name="description">Event penting CBT Julia yang diperbarui otomatis.</x-slot>

    @php($activities = $this->getActivities())

    @if ($activities->isEmpty())
        <div class="rounded-2xl border border-slate-700/60 bg-slate-900/50 p-6 text-center">
            <div class="text-sm font-semibold text-slate-100">Belum ada aktivitas terbaru</div>
            <div class="mt-1 text-sm text-slate-400">Ujian dimulai, penyelesaian ujian, pelanggaran, token, dan soal baru akan tampil di sini.</div>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($activities as $activity)
                <div class="flex items-start gap-3 rounded-2xl border border-slate-700/60 bg-slate-950/30 p-3">
                    <div @class([
                        'mt-1 h-2.5 w-2.5 rounded-full shadow-lg',
                        'bg-blue-400 shadow-blue-400/40' => $activity['color'] === 'blue',
                        'bg-green-400 shadow-green-400/40' => $activity['color'] === 'green',
                        'bg-red-400 shadow-red-400/40' => $activity['color'] === 'red',
                        'bg-cyan-400 shadow-cyan-400/40' => $activity['color'] === 'cyan',
                        'bg-amber-400 shadow-amber-400/40' => $activity['color'] === 'amber',
                    ])></div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold uppercase tracking-wide text-blue-200">{{ $activity['type'] }}</div>
                        <div class="truncate text-sm font-semibold text-slate-50">{{ $activity['title'] }}</div>
                        <div class="truncate text-xs text-slate-400">{{ $activity['description'] }}</div>
                    </div>
                    <div class="shrink-0 text-xs text-slate-500">{{ optional($activity['time'])->diffForHumans() }}</div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament::section>
