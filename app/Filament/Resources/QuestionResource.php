<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionResource\Pages;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Bank Soal';

    public static function form(Form $form): Form
    {
        return $form->schema([
                Forms\Components\TextInput::make('mata_pelajaran')->label('Mapel'),
                Forms\Components\TextInput::make('soal')->label('Soal'),
                Forms\Components\TextInput::make('tingkat_kesulitan')->label('Kesulitan'),
                Forms\Components\TextInput::make('bobot_nilai')->label('Bobot'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
                Tables\Columns\TextColumn::make('mata_pelajaran')->label('Mapel')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('soal')->label('Soal')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tingkat_kesulitan')->label('Kesulitan')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('bobot_nilai')->label('Bobot')->searchable()->sortable(),
            ])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit' => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
