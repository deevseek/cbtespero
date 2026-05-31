<x-filament::section>
    <x-slot name="heading">Ringkasan Perangkat</x-slot>
    <x-slot name="description">Estimasi status perangkat dari heartbeat monitoring.</x-slot>

    @php($summary = $this->getSummary())
    <div class="grid grid-cols-2 gap-3">
        @foreach ([
            ['label' => 'Online', 'value' => $summary['online'], 'class' => 'from-green-500/20 to-green-400/5 text-green-300'],
            ['label' => 'Offline', 'value' => $summary['offline'], 'class' => 'from-amber-500/20 to-amber-400/5 text-amber-300'],
            ['label' => 'Tidak Aktif', 'value' => $summary['inactive'], 'class' => 'from-slate-500/20 to-slate-400/5 text-slate-300'],
            ['label' => 'Total', 'value' => $summary['total'], 'class' => 'from-blue-500/20 to-cyan-400/5 text-blue-300'],
        ] as $item)
            <div class="cbt-device-card rounded-2xl bg-gradient-to-br {{ $item['class'] }} p-4">
                <div class="text-2xl font-black">{{ number_format($item['value']) }}</div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $item['label'] }}</div>
            </div>
        @endforeach
    </div>
</x-filament::section>
