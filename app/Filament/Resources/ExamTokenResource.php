<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamTokenResource\Pages;
use App\Models\ExamToken;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ExamTokenResource extends Resource
{
    protected static ?string $model = ExamToken::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Token Ujian';
    protected static string | \UnitEnum | null $navigationGroup = 'AKADEMIK';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Token Ujian';
    protected static ?string $pluralModelLabel = 'Token Ujian';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('exam_id')
                ->label('Ujian')
                ->relationship('exam', 'nama_ujian')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('token')
                ->label('Token')
                ->required()
                ->maxLength(50)
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null),
            Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
            Forms\Components\DateTimePicker::make('expires_at')->label('Kedaluwarsa'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('exam.nama_ujian')->label('Ujian')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('token')->label('Token')->badge()->color('info')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('expires_at')->label('Kedaluwarsa')->dateTime('d M Y H:i')->placeholder('Tidak ada')->sortable(),
            ])
            ->actions([\Filament\Actions\EditAction::make()->label('Edit')])
            ->bulkActions([\Filament\Actions\BulkActionGroup::make([\Filament\Actions\DeleteBulkAction::make()->label('Hapus')])])
            ->emptyStateHeading('Belum ada token ujian')
            ->emptyStateDescription('Token ujian yang dibuat akan tampil di sini untuk akses peserta.')
            ->emptyStateIcon('heroicon-o-key');
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
