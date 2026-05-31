<?php

namespace App\Filament\Resources\ExamResource\Pages;

use App\Filament\Resources\ExamResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExams extends ListRecords
{
    protected static string $resource = ExamResource::class;
    protected static ?string $title = 'Manajemen Ujian';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Buat Ujian Baru')->icon('heroicon-m-plus'),
        ];
    }
}
