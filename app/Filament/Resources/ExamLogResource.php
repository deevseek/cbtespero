<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamLogResource\Pages;
use App\Models\ExamLog;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ExamLogResource extends Resource
{
    protected static ?string $model = ExamLog::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationLabel = 'Log Pelanggaran';
    protected static ?string $navigationGroup = 'MONITORING';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Log Pelanggaran';
    protected static ?string $pluralModelLabel = 'Log Pelanggaran';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('exam_id')->label('Ujian'),
            Forms\Components\TextInput::make('student_id')->label('Siswa'),
            Forms\Components\TextInput::make('activity_type')->label('Tipe'),
            Forms\Components\TextInput::make('ip_address')->label('IP'),
            Forms\Components\DateTimePicker::make('logged_at')->label('Waktu'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('exam.nama_ujian')->label('Ujian')->placeholder('-')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('student.nama')->label('Siswa')->placeholder('-')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('activity_type')->label('Tipe')->badge()->formatStateUsing(fn (?string $state): string => self::violationLabel($state))->color(fn (?string $state): string => self::violationColor($state))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('ip_address')->label('IP')->placeholder('-')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('logged_at')->label('Waktu')->dateTime('d M Y H:i:s')->sortable(),
                Tables\Columns\TextColumn::make('description')->label('Status/Tindakan')->limit(45)->placeholder('Terdeteksi otomatis'),
            ])
            ->actions([\Filament\Actions\EditAction::make()->label('Lihat')])
            ->emptyStateHeading('Belum ada log pelanggaran')
            ->emptyStateDescription('Aktivitas pelanggaran peserta akan muncul otomatis di sini.')
            ->emptyStateIcon('heroicon-o-shield-check');
    }

    public static function violationLabel(?string $state): string
    {
        return match ($state) {
            'fullscreen_exit', 'keluar_fullscreen' => 'Keluar Fullscreen',
            'tab_switch', 'pindah_tab' => 'Pindah Tab',
            'copy_paste', 'copy paste' => 'Copy Paste',
            'face_not_visible', 'wajah_tidak_terlihat' => 'Wajah Tidak Terlihat',
            'suspicious_device', 'suspicious_ip' => 'Device/IP Mencurigakan',
            default => filled($state) ? Str::of($state)->replace('_', ' ')->title()->toString() : '-',
        };
    }

    public static function violationColor(?string $state): string
    {
        return match ($state) {
            'tab_switch', 'pindah_tab', 'face_not_visible', 'wajah_tidak_terlihat' => 'warning',
            default => 'danger',
        };
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
