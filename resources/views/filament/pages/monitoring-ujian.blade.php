<x-filament-panels::page>
    <div class="space-y-6" wire:poll.10s>
        <x-filament::section>
            <x-slot name="heading">
                Monitoring Ujian Siswa
            </x-slot>

            <x-slot name="description">
                Data peserta, progress jawaban, sisa waktu, device/IP, dan pelanggaran diperbarui otomatis setiap 10 detik.
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
