<?php
namespace App\Filament\Resources\ExamLogResource\Pages;
use App\Filament\Resources\ExamLogResource;
use Filament\Resources\Pages\ListRecords;
class ListExamLogs extends ListRecords { protected static string $resource = ExamLogResource::class; protected static ?string $title = 'Log Pelanggaran'; protected function getHeaderActions(): array { return []; } }
