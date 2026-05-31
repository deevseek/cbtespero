<?php
namespace App\Filament\Resources\ExamTokenResource\Pages;
use App\Filament\Resources\ExamTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListExamTokens extends ListRecords { protected static string $resource = ExamTokenResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }

