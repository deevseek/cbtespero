<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Data Siswa';

    public static function form(Form $form): Form
    {
        return $form->schema([
                Forms\Components\TextInput::make('nis')->label('NIS'),
                Forms\Components\TextInput::make('nama')->label('Nama'),
                Forms\Components\TextInput::make('kelas')->label('Kelas'),
                Forms\Components\TextInput::make('username')->label('Username'),
                Forms\Components\TextInput::make('status')->label('Status'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
                Tables\Columns\TextColumn::make('nis')->label('NIS')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('kelas')->label('Kelas')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('username')->label('Username')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->searchable()->sortable(),
            ])->actions([\Filament\Actions\EditAction::make()])->bulkActions([\Filament\Actions\BulkActionGroup::make([\Filament\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
