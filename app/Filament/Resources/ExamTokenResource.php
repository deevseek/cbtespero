<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamTokenResource\Pages;
use App\Models\ExamToken;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExamTokenResource extends Resource
{
    protected static ?string $model = ExamToken::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Token Ujian';

    public static function form(Form $form): Form
    {
        return $form->schema([
                Forms\Components\TextInput::make('exam_id')->label('Exam ID'),
                Forms\Components\TextInput::make('token')->label('Token'),
                Forms\Components\TextInput::make('is_active')->label('Aktif'),
                Forms\Components\TextInput::make('expires_at')->label('Kedaluwarsa'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
                Tables\Columns\TextColumn::make('exam_id')->label('Exam ID')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('token')->label('Token')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('is_active')->label('Aktif')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('expires_at')->label('Kedaluwarsa')->searchable()->sortable(),
            ])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamTokens::route('/'),
            'create' => Pages\CreateExamToken::route('/create'),
            'edit' => Pages\EditExamToken::route('/{record}/edit'),
        ];
    }
}
