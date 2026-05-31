<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResultResource\Pages;
use App\Models\ExamResult;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ExamResultResource extends Resource
{
    protected static ?string $model = ExamResult::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Hasil & Monitoring';
    protected static string | \UnitEnum | null $navigationGroup = 'MONITORING';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Hasil Ujian';
    protected static ?string $pluralModelLabel = 'Hasil & Monitoring';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('exam_id')->label('Ujian'),
            Forms\Components\TextInput::make('student_id')->label('Siswa'),
            Forms\Components\TextInput::make('status')->label('Status'),
            Forms\Components\TextInput::make('nilai')->label('Nilai'),
            Forms\Components\DateTimePicker::make('last_heartbeat_at')->label('Last Seen'),
            Forms\Components\TextInput::make('app_exit_count')->label('Keluar App'),
            Forms\Components\TextInput::make('fullscreen_exit_count')->label('Keluar Fullscreen'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('exam.nama_ujian')->label('Ujian')->placeholder('-')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('student.nama')->label('Siswa')->placeholder('-')->searchable()->sortable()->weight('semibold'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (?string $state): string => self::statusLabel($state))->color(fn (?string $state, ExamResult $record): string => self::statusColor($state, $record))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nilai')->label('Nilai')->numeric(2)->placeholder('0')->sortable(),
                Tables\Columns\TextColumn::make('last_heartbeat_at')->label('Last Seen')->since()->dateTime('d M Y H:i')->placeholder('-')->sortable(),
                Tables\Columns\TextColumn::make('app_exit_count')->label('Keluar App')->badge()->color(fn ($state): string => ((int) $state) > 0 ? 'danger' : 'gray')->sortable(),
                Tables\Columns\TextColumn::make('fullscreen_exit_count')->label('Keluar Fullscreen')->badge()->color(fn ($state): string => ((int) $state) > 0 ? 'danger' : 'gray')->sortable(),
            ])
            ->actions([\Filament\Actions\EditAction::make()->label('Lihat')])
            ->emptyStateHeading('Belum ada hasil ujian')
            ->emptyStateDescription('Hasil ujian peserta akan muncul otomatis setelah peserta mulai mengerjakan.')
            ->emptyStateIcon('heroicon-o-chart-bar-square');
    }

    public static function statusLabel(?string $state): string
    {
        return match ($state) {
            'sedang_mengerjakan' => 'Mengerjakan',
            'selesai' => 'Selesai',
            'terkunci', 'auto_submit' => 'Terindikasi Pelanggaran',
            'belum_mulai' => 'Belum Mulai',
            default => filled($state) ? Str::of($state)->replace('_', ' ')->title()->toString() : 'Belum Mulai',
        };
    }

    public static function statusColor(?string $state, ?ExamResult $record = null): string
    {
        if ($record && (($record->app_exit_count ?? 0) > 0 || ($record->fullscreen_exit_count ?? 0) > 0 || in_array($state, ['terkunci', 'auto_submit'], true))) {
            return 'danger';
        }

        return match ($state) {
            'sedang_mengerjakan' => 'info',
            'selesai' => 'success',
            'terkunci', 'auto_submit' => 'danger',
            'belum_mulai' => 'warning',
            default => 'gray',
        };
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
