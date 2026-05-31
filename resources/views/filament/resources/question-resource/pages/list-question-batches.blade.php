<x-filament-panels::page>
    @php($manualCount = \App\Models\Question::whereNull('question_import_id')->count())

    @if ($manualCount > 0)
        <x-filament::section>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold">Soal Manual / Tanpa Batch</h3>
                    <p class="text-sm text-gray-500">{{ $manualCount }} soal lama belum memiliki batch import dan tetap tersedia di tab Semua Soal.</p>
                </div>
                <x-filament::button tag="a" href="{{ \App\Filament\Resources\QuestionResource::getUrl('all') }}">
                    Lihat Soal
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
