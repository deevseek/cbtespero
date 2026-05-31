<x-filament-panels::page>
    <div wire:poll.10s class="space-y-4">
        <div class="rounded-xl bg-white p-4 shadow dark:bg-gray-900">
            <h2 class="text-lg font-semibold">Monitoring Ujian Siswa</h2>
            <p class="text-sm text-gray-500">Refresh otomatis setiap 10 detik, menampilkan progress, sisa waktu server, device, IP, dan pelanggaran.</p>
        </div>
        <div class="overflow-x-auto rounded-xl bg-white shadow dark:bg-gray-900">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                <thead><tr class="text-left"><th class="p-3">Siswa</th><th class="p-3">Ujian</th><th class="p-3">Status</th><th class="p-3">Progress</th><th class="p-3">Sisa</th><th class="p-3">Pelanggaran</th><th class="p-3">Last Seen</th><th class="p-3">Device/IP</th><th class="p-3">Aksi</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($this->rows as $row)
                    @php
                        $answered = $row->answers->whereNotNull('jawaban_siswa')->count();
                        $total = max(1, $row->answers->count());
                        $remaining = $row->server_ends_at ? max(0, now()->diffInSeconds($row->server_ends_at, false)) : null;
                    @endphp
                    <tr>
                        <td class="p-3 font-medium">{{ $row->student?->nama }}</td>
                        <td class="p-3">{{ $row->exam?->nama_ujian }}</td>
                        <td class="p-3">{{ $row->status }}</td>
                        <td class="p-3">{{ $answered }}/{{ $total }}</td>
                        <td class="p-3">{{ $remaining === null ? '-' : gmdate('H:i:s', $remaining) }}</td>
                        <td class="p-3">App: {{ $row->app_exit_count }} | Fullscreen: {{ $row->fullscreen_exit_count }} | Heartbeat: {{ $row->heartbeat_missed_count }}</td>
                        <td class="p-3">{{ $row->last_heartbeat_at }}</td>
                        <td class="p-3">{{ $row->device_name }}<br><span class="text-xs text-gray-500">{{ $row->device_id }} / {{ $row->ip_address }}</span></td>
                        <td class="p-3 space-x-2"><button wire:click="forceSubmit({{ $row->id }})" class="text-primary-600">Submit</button><button wire:click="unlock({{ $row->id }})" class="text-success-600">Buka</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
