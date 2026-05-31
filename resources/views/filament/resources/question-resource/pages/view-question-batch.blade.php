<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-5">
        <x-filament::section>
            <x-slot name="heading">Source</x-slot>
            {{ $record->display_name }}
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">Mapel</x-slot>
            {{ $record->subject ?: '-' }}
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">Kelas</x-slot>
            {{ $record->class_level ?: '-' }}
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">Jumlah soal</x-slot>
            {{ $record->questions()->count() }} soal
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">Jumlah perlu review</x-slot>
            {{ $record->needs_review_count }} review
        </x-filament::section>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
