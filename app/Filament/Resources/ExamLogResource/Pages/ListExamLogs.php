<?php
namespace App\Filament\Resources\ExamLogResource\Pages;
use App\Filament\Resources\ExamLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListExamLogs extends ListRecords { protected static string $resource = ExamLogResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
