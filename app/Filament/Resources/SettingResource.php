<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Pengaturan CBT';
    protected static ?string $navigationGroup = 'PENGATURAN';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Pengaturan CBT';
    protected static ?string $pluralModelLabel = 'Pengaturan CBT';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
                Forms\Components\TextInput::make('nama_aplikasi')->label('Aplikasi'),
                Forms\Components\TextInput::make('nama_sekolah')->label('Sekolah'),
                Forms\Components\TextInput::make('tahun_ajaran')->label('Tahun'),
                Forms\Components\TextInput::make('acak_soal')->label('Acak Soal'),
                Forms\Components\TextInput::make('acak_jawaban')->label('Acak Jawaban'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
                Tables\Columns\TextColumn::make('nama_aplikasi')->label('Aplikasi')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nama_sekolah')->label('Sekolah')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tahun_ajaran')->label('Tahun')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('acak_soal')->label('Acak Soal')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('acak_jawaban')->label('Acak Jawaban')->searchable()->sortable(),
            ])->actions([\Filament\Actions\EditAction::make()])->bulkActions([\Filament\Actions\BulkActionGroup::make([\Filament\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}

