<?php
namespace App\Filament\Resources\ExamLogResource\Pages;
use App\Filament\Resources\ExamLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditExamLog extends EditRecord { protected static string $resource = ExamLogResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }

