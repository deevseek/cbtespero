<?php

namespace App\Filament\Pages;

use App\Models\ExamResult;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class MonitoringUjian extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'Monitoring Ujian';
    protected static string | \UnitEnum | null $navigationGroup = 'MONITORING';
    protected static ?string $title = 'Monitoring Ujian';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.monitoring-ujian';

    public function getSubheading(): ?string
    {
        return 'Pantau peserta ujian secara real-time';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label('Export Laporan Lengkap')->icon('heroicon-m-arrow-down-tray')->color('success')->url(fn (): string => route('filament.admin.exam-results.export'))->openUrlInNewTab(),
            Action::make('refresh')->label('Refresh')->icon('heroicon-m-arrow-path')->color('primary')->action(fn (): null => null),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getMonitoringQuery())
            ->poll('10s')
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('student.nama')->label('Siswa')->placeholder('-')->searchable()->sortable()->weight('semibold'),
                Tables\Columns\TextColumn::make('student.nis')->label('NIS')->placeholder('-')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('student.kelas')->label('Kelas')->badge()->placeholder('-')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('exam.nama_ujian')->label('Ujian')->placeholder('-')->searchable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn (?string $state, ExamResult $record): string => $this->statusColor($state, $record))->formatStateUsing(fn (?string $state, ExamResult $record): string => $this->statusLabel($state, $record)),
                Tables\Columns\ViewColumn::make('progress_percent')->label('Progress')->view('filament.tables.columns.progress-bar')->state(function (ExamResult $record): int {
                    $total = max(1, (int) ($record->answers_count ?? 0));
                    $answered = (int) ($record->answered_count ?? 0);
                    return (int) round(($answered / $total) * 100);
                }),
                Tables\Columns\TextColumn::make('sisa_waktu')->label('Sisa Waktu')->state(function (ExamResult $record): string {
                    if (! $record->server_ends_at) {
                        return '-';
                    }
                    $seconds = max(0, now()->diffInSeconds($record->server_ends_at, false));
                    return gmdate('H:i:s', $seconds);
                })->badge()->color(fn (ExamResult $record): string => $record->server_ends_at && now()->greaterThan($record->server_ends_at) ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('answered_count')->label('Terjawab')->state(fn (ExamResult $record): string => ((int) ($record->answered_count ?? $record->answered_questions)).' / '.max(1, (int) ($record->answers_count ?? $record->total_questions)))->alignCenter(),
                Tables\Columns\TextColumn::make('violation_logs_count')->label('Pelanggaran')->badge()->state(fn (ExamResult $record): string => (string) ($record->violation_logs_count ?? 0))->color(fn (ExamResult $record): string => ($record->violation_logs_count ?? 0) >= 3 ? 'danger' : (($record->violation_logs_count ?? 0) > 0 ? 'warning' : 'success')),
                Tables\Columns\TextColumn::make('nilai')->label('Nilai')->numeric(2)->placeholder('-')->sortable(),
                Tables\Columns\TextColumn::make('last_heartbeat_at')->label('Last Seen')->since()->dateTime('d M Y H:i:s')->placeholder('-')->sortable(),
                Tables\Columns\TextColumn::make('device_info')->label('Device/IP')->state(fn (ExamResult $record): string => collect([$record->device_name ?: $record->platform, $record->device_id, $record->ip_address, $record->user_agent])->filter()->join(' / ') ?: '-')->limit(70)->wrap()->searchable(query: fn (Builder $query, string $search): Builder => $query->where('device_name', 'like', "%{$search}%")->orWhere('device_id', 'like', "%{$search}%")->orWhere('ip_address', 'like', "%{$search}%")),
            ])
            ->filters([
                SelectFilter::make('exam_id')->label('Pilih Ujian')->relationship('exam', 'nama_ujian')->searchable()->preload(),
                SelectFilter::make('kelas')->label('Kelas/Ruangan')->options(fn (): array => Student::query()->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas', 'kelas')->all())->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null) ? $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('kelas', $data['value'])) : $query),
                SelectFilter::make('status')->label('Status')->options(['sedang_mengerjakan' => 'Mengerjakan', 'selesai' => 'Selesai', 'terkunci' => 'Pelanggaran', 'auto_submit' => 'Pelanggaran', 'belum_mulai' => 'Belum Mulai']),
            ])
            ->actions([
                Action::make('view_violations')->label('Detail Log')->icon('heroicon-m-shield-exclamation')->color('warning')->visible(fn (ExamResult $record): bool => ($record->violation_logs_count ?? 0) > 0)->modalHeading('Detail Log Pelanggaran')->modalSubmitAction(false)->modalCancelActionLabel('Tutup')->modalContent(fn (ExamResult $record) => view('filament.pages.monitoring-violations', ['logs' => $this->violationLogs($record)])),
                Action::make('force_submit')->label('Submit')->icon('heroicon-m-paper-airplane')->color('danger')->requiresConfirmation()->action(fn (ExamResult $record) => $this->forceSubmit($record)),
                Action::make('unlock')->label('Buka')->icon('heroicon-m-lock-open')->color('success')->requiresConfirmation()->visible(fn (ExamResult $record): bool => filled($record->locked_at) || in_array($record->status, ['terkunci', 'auto_submit'], true))->action(fn (ExamResult $record) => $this->unlock($record)),
            ])
            ->emptyStateHeading('Belum ada peserta ujian')
            ->emptyStateDescription('Peserta yang mulai ujian akan muncul otomatis dengan refresh setiap 10 detik.')
            ->emptyStateIcon('heroicon-o-computer-desktop');
    }

    public function getSummary(): array
    {
        return [
            'total' => ExamResult::query()->count(),
            'running' => ExamResult::query()->where('status', 'sedang_mengerjakan')->count(),
            'finished' => ExamResult::query()->where('status', 'selesai')->count(),
            'not_started' => ExamResult::query()->where(fn (Builder $query) => $query->whereNull('started_at')->orWhere('status', 'belum_mulai'))->count(),
            'violations' => ExamResult::query()->whereHas('logs', fn (Builder $query) => $query->whereIn('activity_type', ['exit_fullscreen', 'tab_switch', 'window_blur', 'forbidden_shortcut', 'right_click', 'clipboard', 'devtools', 'page_reload', 'idle', 'connection_lost', 'heartbeat_missed', 'fullscreen_exit']))->count(),
        ];
    }

    protected function getMonitoringQuery(): Builder
    {
        return ExamResult::query()->with(['exam', 'student'])->withCount(['answers', 'answers as answered_count' => fn (Builder $query) => $query->whereNotNull('jawaban_siswa'), 'logs as violation_logs_count' => fn (Builder $query) => $query->whereIn('activity_type', ['exit_fullscreen', 'tab_switch', 'window_blur', 'forbidden_shortcut', 'right_click', 'clipboard', 'devtools', 'page_reload', 'idle', 'connection_lost', 'heartbeat_missed', 'fullscreen_exit'])]);
    }

    private function statusLabel(?string $state, ExamResult $record): string
    {
        if (($record->app_exit_count ?? 0) > 0 || ($record->fullscreen_exit_count ?? 0) > 0 || ($record->violation_logs_count ?? 0) > 0 || in_array($state, ['terkunci', 'auto_submit'], true)) {
            return 'Pelanggaran';
        }

        return match ($state) {
            'sedang_mengerjakan' => 'Mengerjakan',
            'selesai' => 'Selesai',
            'belum_mulai' => 'Belum Mulai',
            default => filled($state) ? Str::of($state)->replace('_', ' ')->title()->toString() : 'Tidak Aktif',
        };
    }

    private function statusColor(?string $state, ExamResult $record): string
    {
        if (($record->app_exit_count ?? 0) > 0 || ($record->fullscreen_exit_count ?? 0) > 0 || ($record->violation_logs_count ?? 0) > 0 || in_array($state, ['terkunci', 'auto_submit'], true)) {
            return 'danger';
        }

        return match ($state) {
            'sedang_mengerjakan' => 'info',
            'selesai' => 'success',
            'belum_mulai' => 'warning',
            default => 'gray',
        };
    }

    public function violationLogs(ExamResult $result): \Illuminate\Support\Collection
    {
        return $result->logs()
            ->with(['student', 'exam'])
            ->whereIn('activity_type', ['exit_fullscreen', 'tab_switch', 'window_blur', 'forbidden_shortcut', 'right_click', 'clipboard', 'devtools', 'page_reload', 'idle', 'connection_lost', 'heartbeat_missed', 'fullscreen_exit'])
            ->latest('logged_at')
            ->limit(20)
            ->get();
    }

    public function forceSubmit(ExamResult $result): void
    {
        $total = max(1, $result->answers()->count());
        $correct = $result->answers()->where('is_correct', true)->count();

        $result->update(['nilai' => round(($correct / $total) * 100, 2), 'status' => 'selesai', 'submitted_at' => now()]);

        Notification::make()->title('Ujian peserta berhasil disubmit')->success()->send();
    }

    public function unlock(ExamResult $result): void
    {
        $result->update(['status' => 'sedang_mengerjakan', 'locked_at' => null, 'lock_reason' => null]);

        Notification::make()->title('Sesi peserta berhasil dibuka')->success()->send();
    }
}
