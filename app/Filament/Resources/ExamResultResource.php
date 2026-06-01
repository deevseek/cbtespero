<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResultResource\Pages;
use App\Models\ExamResult;
use App\Models\Student;
use App\Services\ExamResultScoringService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
            Section::make('Informasi Hasil')->schema([
                Forms\Components\TextInput::make('exam.nama_ujian')->label('Ujian')->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('student.nama')->label('Siswa')->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('student.nis')->label('NIS')->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('student.kelas')->label('Kelas')->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('nilai')->label('Nilai')->disabled(),
                Forms\Components\TextInput::make('status')->label('Status')->disabled(),
                Forms\Components\DateTimePicker::make('started_at')->label('Mulai')->disabled(),
                Forms\Components\DateTimePicker::make('submitted_at')->label('Submit')->disabled(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['exam', 'student'])->withCount([
                'answers',
                'answers as answered_count' => fn (Builder $query) => $query->whereNotNull('jawaban_siswa'),
                'answers as correct_answers_count' => fn (Builder $query) => $query->where('is_correct', true),
                'logs as violation_logs_count' => fn (Builder $query) => $query->whereIn('activity_type', self::violationTypes()),
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('student.nama')->label('Nama Siswa')->placeholder('-')->searchable()->sortable()->weight('semibold'),
                Tables\Columns\TextColumn::make('student.nis')->label('NIS')->placeholder('-')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('student.username')->label('Username')->placeholder('-')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('student.kelas')->label('Kelas')->badge()->placeholder('-')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('exam.nama_ujian')->label('Nama Ujian')->placeholder('-')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('exam.mata_pelajaran')->label('Mata Pelajaran')->placeholder('-')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('jumlah_soal')->label('Jumlah Soal')->state(fn (ExamResult $record): int => self::totalQuestions($record))->alignCenter(),
                Tables\Columns\TextColumn::make('terjawab')->label('Terjawab')->state(fn (ExamResult $record): int => self::answeredQuestions($record))->alignCenter(),
                Tables\Columns\TextColumn::make('benar')->label('Benar')->state(fn (ExamResult $record): int => self::correctCount($record))->alignCenter()->color('success'),
                Tables\Columns\TextColumn::make('salah')->label('Salah')->state(fn (ExamResult $record): int => self::wrongCount($record))->alignCenter()->color('danger'),
                Tables\Columns\TextColumn::make('tidak_dijawab')->label('Tidak Dijawab')->state(fn (ExamResult $record): int => self::unansweredCount($record))->alignCenter()->color('warning'),
                Tables\Columns\TextColumn::make('nilai')->label('Nilai')->numeric(2)->placeholder('0')->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (?string $state, ExamResult $record): string => self::statusLabel($state, $record))->color(fn (?string $state, ExamResult $record): string => self::statusColor($state, $record))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('started_at')->label('Waktu Mulai')->dateTime('d M Y H:i')->placeholder('-')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('submitted_at')->label('Waktu Submit')->dateTime('d M Y H:i')->placeholder('-')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('durasi')->label('Durasi')->state(fn (ExamResult $record): string => self::formatDuration($record->duration_seconds ?: ($record->started_at && $record->submitted_at ? $record->started_at->diffInSeconds($record->submitted_at) : null)))->toggleable(),
                Tables\Columns\TextColumn::make('violation_logs_count')->label('Pelanggaran')->badge()->state(fn (ExamResult $record): string => (string) ($record->violation_logs_count ?? 0))->color(fn (ExamResult $record): string => ($record->violation_logs_count ?? 0) > 0 ? 'danger' : 'success'),
            ])
            ->filters([
                SelectFilter::make('exam_id')->label('Ujian')->relationship('exam', 'nama_ujian')->searchable()->preload(),
                SelectFilter::make('student_id')->label('Siswa')->relationship('student', 'nama')->searchable()->preload(),
                SelectFilter::make('kelas')->label('Kelas')->options(fn (): array => Student::query()->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas', 'kelas')->all())->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null) ? $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('kelas', $data['value'])) : $query),
                SelectFilter::make('status')->label('Status')->options(['belum_mulai' => 'Belum Mulai', 'sedang_mengerjakan' => 'Mengerjakan', 'selesai' => 'Selesai', 'auto_submit' => 'Auto Submit', 'terkunci' => 'Terindikasi Pelanggaran']),
                Filter::make('tanggal')->form([Forms\Components\DatePicker::make('tanggal_ujian')->label('Tanggal Ujian')])->query(fn (Builder $query, array $data): Builder => filled($data['tanggal_ujian'] ?? null) ? $query->whereHas('exam', fn (Builder $examQuery) => $examQuery->whereDate('tanggal_ujian', $data['tanggal_ujian'])) : $query),
            ])
            ->headerActions([
                Action::make('export_excel')->label('Export Laporan Lengkap')->icon('heroicon-m-arrow-down-tray')->color('success')->url(fn (): string => route('filament.admin.exam-results.export'))->openUrlInNewTab(),
            ])
            ->actions([
                Action::make('detail')->label('Detail')->icon('heroicon-m-eye')->modalHeading(fn (ExamResult $record): string => 'Detail Hasil - '.($record->student?->nama ?: 'Siswa'))->modalSubmitAction(false)->modalCancelActionLabel('Tutup')->modalContent(fn (ExamResult $record) => view('filament.resources.exam-result-resource.detail', ['record' => $record->loadMissing(['exam', 'student', 'answers.question', 'logs'])])),
                Action::make('recalculate')->label('Hitung Ulang Nilai')->icon('heroicon-m-calculator')->color('warning')->requiresConfirmation()->action(function (ExamResult $record): void {
                    app(ExamResultScoringService::class)->recalculate($record, 'Hitung ulang oleh admin');
                    Notification::make()->title('Nilai berhasil dihitung ulang')->success()->send();
                }),
                EditAction::make()->label('Lihat Form'),
            ])
            ->emptyStateHeading('Belum ada hasil ujian')
            ->emptyStateDescription('Hasil ujian peserta akan muncul otomatis setelah peserta mulai mengerjakan dan tersimpan di tabel hasil ujian.')
            ->emptyStateIcon('heroicon-o-chart-bar-square');
    }

    public static function statusLabel(?string $state, ?ExamResult $record = null): string
    {
        if ($record && (($record->violation_logs_count ?? 0) > 0 || ($record->app_exit_count ?? 0) > 0 || ($record->fullscreen_exit_count ?? 0) > 0 || $state === 'terkunci')) {
            return 'Terindikasi Pelanggaran';
        }

        return match ($state) {
            'sedang_mengerjakan' => 'Mengerjakan',
            'selesai' => 'Selesai',
            'auto_submit' => 'Auto Submit',
            'terkunci' => 'Terindikasi Pelanggaran',
            'belum_mulai' => 'Belum Mulai',
            default => filled($state) ? Str::of($state)->replace('_', ' ')->title()->toString() : 'Belum Mulai',
        };
    }

    public static function statusColor(?string $state, ?ExamResult $record = null): string
    {
        if ($record && (($record->violation_logs_count ?? 0) > 0 || ($record->app_exit_count ?? 0) > 0 || ($record->fullscreen_exit_count ?? 0) > 0 || $state === 'terkunci')) {
            return 'danger';
        }

        return match ($state) {
            'sedang_mengerjakan' => 'info',
            'selesai' => 'success',
            'auto_submit' => 'warning',
            'terkunci' => 'danger',
            'belum_mulai' => 'warning',
            default => 'gray',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamResults::route('/'),
            'edit' => Pages\EditExamResult::route('/{record}/edit'),
        ];
    }

    private static function totalQuestions(ExamResult $record): int { return (int) ($record->total_questions ?: ($record->answers_count ?? $record->answers()->count())); }
    private static function answeredQuestions(ExamResult $record): int { return (int) ($record->answered_questions ?: ($record->answered_count ?? $record->answers()->whereNotNull('jawaban_siswa')->count())); }
    private static function correctCount(ExamResult $record): int { return (int) ($record->correct_count ?: ($record->correct_answers_count ?? $record->answers()->where('is_correct', true)->count())); }
    private static function wrongCount(ExamResult $record): int { return (int) ($record->wrong_count ?: max(0, self::answeredQuestions($record) - self::correctCount($record))); }
    private static function unansweredCount(ExamResult $record): int { return (int) ($record->unanswered_count ?: max(0, self::totalQuestions($record) - self::answeredQuestions($record))); }

    private static function formatDuration(?int $seconds): string
    {
        return $seconds === null ? '-' : gmdate('H:i:s', max(0, $seconds));
    }

    private static function violationTypes(): array
    {
        return ['exit_fullscreen', 'tab_switch', 'window_blur', 'forbidden_shortcut', 'right_click', 'clipboard', 'devtools', 'page_reload', 'idle', 'connection_lost', 'heartbeat_missed', 'fullscreen_exit'];
    }
}
