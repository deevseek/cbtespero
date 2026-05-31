<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResource\Pages;
use App\Models\Exam;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Ujian';
    protected static string | \UnitEnum | null $navigationGroup = 'AKADEMIK';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Ujian';
    protected static ?string $pluralModelLabel = 'Ujian';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            self::examDetailsSection(),
            Section::make('Keamanan CBT')->relationship('securitySetting')->schema([
                Forms\Components\Toggle::make('require_fullscreen')->label('Wajib Fullscreen')->default(true),
                Forms\Components\Toggle::make('block_screenshot')->label('Blok Screenshot')->default(true),
                Forms\Components\Toggle::make('device_binding')->label('Kunci Perangkat')->default(true),
                Forms\Components\Toggle::make('auto_submit_on_cheat')->label('Auto Submit saat Pelanggaran')->default(false),
                Forms\Components\Toggle::make('randomize_questions')->label('Acak Soal')->default(true),
                Forms\Components\Toggle::make('randomize_answers')->label('Acak Jawaban')->default(true),
                Forms\Components\Toggle::make('allow_reentry')->label('Izinkan Masuk Ulang')->default(true),
                Forms\Components\Toggle::make('show_result_after_exam')->label('Tampilkan Hasil')->default(false),
                Forms\Components\TextInput::make('max_app_exit')->label('Maks. Keluar App')->numeric()->default(3),
                Forms\Components\TextInput::make('max_fullscreen_exit')->label('Maks. Keluar Fullscreen')->numeric()->default(3),
                Forms\Components\TextInput::make('max_heartbeat_missed')->label('Toleransi Heartbeat')->numeric()->default(3),
                Forms\Components\TextInput::make('connection_tolerance_seconds')->label('Toleransi Koneksi (detik)')->numeric()->default(60),
                Forms\Components\TextInput::make('max_relogin')->label('Maks. Relogin')->numeric()->default(1),
                Forms\Components\Select::make('orientation')->label('Orientasi')->options(['portrait' => 'Portrait', 'landscape' => 'Landscape'])->default('portrait'),
            ])->columns(2),
        ]);
    }

    public static function examDetailsSection(): Section
    {
        return Section::make('Data Ujian')->schema(self::examDetailsFormComponents())->columns(2);
    }

    public static function examDetailsFormComponents(): array
    {
        return [
            Forms\Components\TextInput::make('nama_ujian')->label('Nama Ujian')->required(),
            Forms\Components\TextInput::make('mata_pelajaran')->label('Mata Pelajaran')->required(),
            Forms\Components\TextInput::make('kelas')->label('Kelas')->required(),
            Forms\Components\DatePicker::make('tanggal_ujian')->label('Tanggal Ujian')->required(),
            Forms\Components\TimePicker::make('jam_mulai')->label('Jam Mulai')->required(),
            Forms\Components\TimePicker::make('jam_selesai')->label('Jam Selesai')->required(),
            Forms\Components\TextInput::make('durasi')->label('Durasi')->numeric()->required(),
            Forms\Components\TextInput::make('jumlah_soal')->label('Jumlah Soal')->numeric()->required(),
            Forms\Components\Select::make('status')->label('Status')->options(self::statusOptions())->default('draft')->required(),
            Forms\Components\TextInput::make('token')
                ->label('Token Lama')
                ->maxLength(5)
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null)
                ->helperText('Opsional. Token utama sebaiknya dibuat lewat menu Token Ujian.'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'aktif' => 'Berlangsung',
            'berlangsung' => 'Berlangsung',
            'terjadwal' => 'Terjadwal',
            'belum_dimulai' => 'Belum Dimulai',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_ujian')->label('Nama Ujian')->searchable()->sortable()->weight('semibold'),
                Tables\Columns\TextColumn::make('mata_pelajaran')->label('Mata Pelajaran')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('kelas')->label('Kelas')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tanggal_ujian')->label('Tanggal Ujian')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (?string $state): string => self::statusLabel($state))->color(fn (?string $state): string => self::statusColor($state)),
                Tables\Columns\TextColumn::make('results_count')->label('Peserta')->counts('results')->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options([
                    'draft' => 'Draft',
                    'aktif' => 'Berlangsung',
                    'berlangsung' => 'Berlangsung',
                    'terjadwal' => 'Terjadwal',
                    'belum_dimulai' => 'Belum Dimulai',
                    'selesai' => 'Selesai',
                    'dibatalkan' => 'Dibatalkan',
                ]),
                SelectFilter::make('kelas')->label('Kelas')->options(fn (): array => Exam::query()->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas', 'kelas')->all()),
                SelectFilter::make('mata_pelajaran')->label('Mapel')->options(fn (): array => Exam::query()->whereNotNull('mata_pelajaran')->distinct()->orderBy('mata_pelajaran')->pluck('mata_pelajaran', 'mata_pelajaran')->all()),
            ])
            ->actions([
                \Filament\Actions\Action::make('regenerate_token')->label('Regenerate Token')->icon('heroicon-m-arrow-path')->color('info')->action(fn (Exam $record) => $record->update(['token' => strtoupper(Str::random(5))])),
                \Filament\Actions\EditAction::make()->label('Edit'),
            ])
            ->emptyStateHeading('Belum ada ujian')
            ->emptyStateDescription('Buat ujian baru untuk mulai mengatur jadwal CBT Julia.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    public static function statusLabel(?string $state): string
    {
        return match ($state) {
            'aktif', 'berlangsung' => 'Berlangsung',
            'selesai' => 'Selesai',
            'terjadwal' => 'Terjadwal',
            'belum_dimulai' => 'Belum Dimulai',
            'dibatalkan' => 'Dibatalkan',
            'draft' => 'Draft',
            default => filled($state) ? Str::of($state)->replace('_', ' ')->title()->toString() : '-',
        };
    }

    public static function statusColor(?string $state): string
    {
        return match ($state) {
            'aktif', 'berlangsung' => 'info',
            'selesai' => 'success',
            'terjadwal', 'belum_dimulai' => 'warning',
            'dibatalkan' => 'danger',
            'draft' => 'gray',
            default => 'gray',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExams::route('/'),
            'create' => Pages\CreateExam::route('/create'),
            'edit' => Pages\EditExam::route('/{record}/edit'),
        ];
    }
}
