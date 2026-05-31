<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResultResource\Pages;
use App\Models\ExamResult;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExamResultResource extends Resource
{
    protected static ?string $model = ExamResult::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Hasil & Monitoring';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
                Forms\Components\TextInput::make('exam_id')->label('Exam'),
                Forms\Components\TextInput::make('student_id')->label('Siswa'),
                Forms\Components\TextInput::make('status')->label('Status'),
                Forms\Components\TextInput::make('nilai')->label('Nilai'),
                Forms\Components\TextInput::make('last_heartbeat_at')->label('Last Seen'),
                Forms\Components\TextInput::make('app_exit_count')->label('Keluar App'),
                Forms\Components\TextInput::make('fullscreen_exit_count')->label('Keluar Fullscreen'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
                Tables\Columns\TextColumn::make('exam_id')->label('Exam')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('student_id')->label('Siswa')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nilai')->label('Nilai')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('last_heartbeat_at')->label('Last Seen')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('app_exit_count')->label('Keluar App')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('fullscreen_exit_count')->label('Keluar Fullscreen')->searchable()->sortable(),
            ])->actions([\Filament\Actions\EditAction::make()])->bulkActions([\Filament\Actions\BulkActionGroup::make([\Filament\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamResults::route('/'),
            'create' => Pages\CreateExamResult::route('/create'),
            'edit' => Pages\EditExamResult::route('/{record}/edit'),
        ];
    }
}

