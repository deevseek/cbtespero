<?php

namespace App\Filament\Widgets;

use App\Models\Exam;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestExamsTable extends BaseWidget
{
    protected static ?string $heading = 'Ujian Terbaru';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];
    protected ?string $pollingInterval = '15s';

    protected function getTableQuery(): Builder
    {
        return Exam::query()->withCount('results')->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\TextColumn::make('nama_ujian')
                    ->label('Ujian')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('mata_pelajaran')
                    ->label('Mapel')
                    ->sortable(),
                Tables\Columns\TextColumn::make('kelas')
                    ->label('Kelas')
                    ->badge(),
                Tables\Columns\TextColumn::make('tanggal_ujian')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aktif', 'berlangsung' => 'info',
                        'selesai' => 'success',
                        'dibatalkan' => 'danger',
                        'terjadwal', 'belum_dimulai' => 'warning',
                        'draft' => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('results_count')
                    ->label('Peserta')
                    ->alignCenter(),
            ])
            ->emptyStateHeading('Belum ada ujian')
            ->emptyStateDescription('Ujian yang dibuat akan muncul di sini.')
            ->actions([\Filament\Actions\Action::make('lihat')->label('Lihat')->icon('heroicon-m-eye')->url(fn (Exam $record): string => route('filament.admin.resources.exams.edit', ['record' => $record]))])
            ->emptyStateIcon('heroicon-o-academic-cap');
    }
}

