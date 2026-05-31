<?php

namespace App\Filament\Pages;

use App\Models\ExamResult;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MonitoringUjian extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'Monitoring Ujian';
    protected static ?string $title = 'Monitoring Ujian';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.monitoring-ujian';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getMonitoringQuery())
            ->poll('10s')
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('student.nama')
                    ->label('Siswa')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('exam.nama_ujian')
                    ->label('Ujian')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sedang_mengerjakan' => 'warning',
                        'selesai' => 'success',
                        'terkunci', 'auto_submit' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString()),
                Tables\Columns\TextColumn::make('progress_jawaban')
                    ->label('Progress Jawaban')
                    ->state(fn (ExamResult $record): string => sprintf('%s/%s', $record->answered_count ?? 0, max(1, $record->answers_count ?? 0)))
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('sisa_waktu')
                    ->label('Sisa Waktu')
                    ->state(function (ExamResult $record): string {
                        if (! $record->server_ends_at) {
                            return '-';
                        }

                        $seconds = max(0, now()->diffInSeconds($record->server_ends_at, false));

                        return gmdate('H:i:s', $seconds);
                    })
                    ->badge()
                    ->color(fn (ExamResult $record): string => $record->server_ends_at && now()->greaterThan($record->server_ends_at) ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('pelanggaran')
                    ->label('Pelanggaran')
                    ->state(fn (ExamResult $record): string => sprintf(
                        'App %d · Fullscreen %d · Heartbeat %d · Log %d',
                        $record->app_exit_count ?? 0,
                        $record->fullscreen_exit_count ?? 0,
                        $record->heartbeat_missed_count ?? 0,
                        $record->logs_count ?? 0,
                    ))
                    ->wrap(),
                Tables\Columns\TextColumn::make('last_heartbeat_at')
                    ->label('Last Seen')
                    ->dateTime('d M Y H:i:s')
                    ->since()
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('device_info')
                    ->label('Device/IP')
                    ->state(fn (ExamResult $record): string => collect([
                        $record->device_name ?: $record->platform,
                        $record->device_id,
                        $record->ip_address,
                    ])->filter()->join(' / ') ?: '-')
                    ->wrap()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('device_name', 'like', "%{$search}%")
                        ->orWhere('device_id', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")),
            ])
            ->actions([
                Action::make('force_submit')
                    ->label('Submit')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (ExamResult $record) => $this->forceSubmit($record)),
                Action::make('unlock')
                    ->label('Buka')
                    ->icon('heroicon-m-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ExamResult $record): bool => filled($record->locked_at) || in_array($record->status, ['terkunci', 'auto_submit'], true))
                    ->action(fn (ExamResult $record) => $this->unlock($record)),
            ])
            ->emptyStateHeading('Belum ada peserta ujian')
            ->emptyStateDescription('Peserta yang mulai ujian akan muncul otomatis dengan refresh setiap 10 detik.')
            ->emptyStateIcon('heroicon-o-computer-desktop');
    }

    protected function getMonitoringQuery(): Builder
    {
        return ExamResult::query()
            ->with(['exam', 'student'])
            ->withCount([
                'answers',
                'answers as answered_count' => fn (Builder $query) => $query->whereNotNull('jawaban_siswa'),
                'logs',
            ]);
    }

    public function forceSubmit(ExamResult $result): void
    {
        $total = max(1, $result->answers()->count());
        $correct = $result->answers()->where('is_correct', true)->count();

        $result->update([
            'nilai' => round(($correct / $total) * 100, 2),
            'status' => 'selesai',
            'submitted_at' => now(),
        ]);

        Notification::make()
            ->title('Ujian peserta berhasil disubmit')
            ->success()
            ->send();
    }

    public function unlock(ExamResult $result): void
    {
        $result->update([
            'status' => 'sedang_mengerjakan',
            'locked_at' => null,
            'lock_reason' => null,
        ]);

        Notification::make()
            ->title('Sesi peserta berhasil dibuka')
            ->success()
            ->send();
    }
}

