<?php
namespace App\Filament\Resources\QuestionResource\Pages;
use App\Filament\Resources\QuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListQuestions extends ListRecords { protected static string $resource = QuestionResource::class; protected static ?string $title = 'Bank Soal'; protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Buat Soal Baru')->icon('heroicon-m-plus')]; } }
