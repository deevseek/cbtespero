<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamLogResource\Pages;
use App\Models\ExamLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExamLogResource extends Resource
{
    protected static ?string $model = ExamLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Log Pelanggaran';

    public static function form(Form $form): Form
    {
        return $form->schema([
                Forms\Components\TextInput::make('exam_id')->label('Exam'),
                Forms\Components\TextInput::make('student_id')->label('Siswa'),
                Forms\Components\TextInput::make('activity_type')->label('Tipe'),
                Forms\Components\TextInput::make('ip_address')->label('IP'),
                Forms\Components\TextInput::make('logged_at')->label('Waktu'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
                Tables\Columns\TextColumn::make('exam_id')->label('Exam')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('student_id')->label('Siswa')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('activity_type')->label('Tipe')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('ip_address')->label('IP')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('logged_at')->label('Waktu')->searchable()->sortable(),
            ])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamLogs::route('/'),
            'create' => Pages\CreateExamLog::route('/create'),
            'edit' => Pages\EditExamLog::route('/{record}/edit'),
        ];
    }
}
