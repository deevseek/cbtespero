<?php

namespace App\Filament\Widgets;

use App\Models\ExamLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestExamLogsTable extends BaseWidget
{
    protected static ?string $heading = 'Log Pelanggaran Terbaru';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];
    protected ?string $pollingInterval = '10s';

    protected function getTableQuery(): Builder
    {
        return ExamLog::query()
            ->with(['exam', 'student'])
            ->latest('logged_at');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\TextColumn::make('student.nama')
                    ->label('Siswa')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('exam.nama_ujian')
                    ->label('Ujian')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('activity_type')
                    ->label('Pelanggaran')
                    ->badge()
                    ->color('danger')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('logged_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->emptyStateHeading('Belum ada log pelanggaran')
            ->emptyStateDescription('Pelanggaran peserta ujian akan tampil otomatis di tabel ini.')
            ->emptyStateIcon('heroicon-o-shield-check');
    }
}
