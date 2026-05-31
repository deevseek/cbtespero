<?php
namespace App\Filament\Resources\ExamTokenResource\Pages;
use App\Filament\Resources\ExamTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditExamToken extends EditRecord { protected static string $resource = ExamTokenResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }

