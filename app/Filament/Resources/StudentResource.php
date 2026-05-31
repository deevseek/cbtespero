<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Student;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Data Siswa';
    protected static string | \UnitEnum | null $navigationGroup = 'DATA';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Siswa';
    protected static ?string $pluralModelLabel = 'Data Siswa';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('nis')->label('NIS'),
            Forms\Components\TextInput::make('nama')->label('Nama'),
            Forms\Components\TextInput::make('kelas')->label('Kelas'),
            Forms\Components\TextInput::make('username')->label('Username'),
            Forms\Components\Select::make('status')->label('Status')->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nis')->label('NIS')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nama')->label('Nama')->searchable()->sortable()->weight('semibold'),
                Tables\Columns\TextColumn::make('kelas')->label('Kelas')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('username')->label('Username')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (?string $state): string => $state === 'aktif' ? 'Aktif' : 'Nonaktif')->color(fn (?string $state): string => $state === 'aktif' ? 'success' : 'gray')->searchable()->sortable(),
            ])
            ->actions([\Filament\Actions\EditAction::make()->label('Edit')])
            ->bulkActions([\Filament\Actions\BulkActionGroup::make([\Filament\Actions\DeleteBulkAction::make()->label('Hapus')])])
            ->emptyStateHeading('Belum ada siswa')
            ->emptyStateDescription('Tambahkan data siswa agar peserta dapat mengakses CBT Julia.')
            ->emptyStateIcon('heroicon-o-user-group');
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
