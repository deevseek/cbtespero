<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CbtStatsOverview;
use App\Filament\Widgets\ExamActivityChart;
use App\Filament\Widgets\LatestExamLogsTable;
use App\Filament\Widgets\LatestExamsTable;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Dashboard CBT';

    public function getTitle(): string
    {
        return 'Dashboard Espero CBT';
    }

    public function getHeading(): string
    {
        return 'Dashboard Espero CBT';
    }

    public function getSubheading(): ?string
    {
        return 'Ringkasan operasional ujian, peserta, soal, dan pelanggaran secara real-time.';
    }

    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }

    public function getWidgets(): array
    {
        return [
            CbtStatsOverview::class,
            LatestExamsTable::class,
            LatestExamLogsTable::class,
            ExamActivityChart::class,
        ];
    }
}
