<?php
namespace App\Filament\Resources\ExamResultResource\Pages;
use App\Filament\Resources\ExamResultResource;
use Filament\Resources\Pages\ListRecords;
class ListExamResults extends ListRecords { protected static string $resource = ExamResultResource::class; protected static ?string $title = 'Hasil & Monitoring'; protected function getHeaderActions(): array { return []; } }
