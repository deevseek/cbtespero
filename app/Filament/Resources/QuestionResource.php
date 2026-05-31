<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionResource\Pages;
use App\Models\Question;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Bank Soal';
    protected static string | \UnitEnum | null $navigationGroup = 'AKADEMIK';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Soal';
    protected static ?string $pluralModelLabel = 'Bank Soal';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('mata_pelajaran')->label('Mapel'),
            Forms\Components\Textarea::make('soal')->label('Soal')->rows(4),
            Forms\Components\TextInput::make('tingkat_kesulitan')->label('Kesulitan'),
            Forms\Components\TextInput::make('bobot_nilai')->label('Bobot')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mata_pelajaran')->label('Mapel')->badge()->color('info')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('soal')->label('Soal')->limit(90)->wrap()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tingkat_kesulitan')->label('Kesulitan')->badge()->color(fn (?string $state): string => match (strtolower((string) $state)) { 'mudah' => 'success', 'sulit' => 'danger', default => 'warning' })->searchable()->sortable(),
                Tables\Columns\TextColumn::make('bobot_nilai')->label('Bobot')->numeric()->sortable(),
            ])
            ->actions([\Filament\Actions\EditAction::make()->label('Edit')])
            ->bulkActions([\Filament\Actions\BulkActionGroup::make([\Filament\Actions\DeleteBulkAction::make()->label('Hapus')])])
            ->emptyStateHeading('Belum ada soal')
            ->emptyStateDescription('Tambahkan soal ke bank soal agar dapat digunakan pada ujian.')
            ->emptyStateIcon('heroicon-o-document-text');
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
