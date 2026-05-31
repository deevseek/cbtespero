<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CbtStatsOverview;
use App\Filament\Widgets\DeviceSummaryWidget;
use App\Filament\Widgets\ExamActivityChart;
use App\Filament\Widgets\ExamStatusChart;
use App\Filament\Widgets\LatestActivityWidget;
use App\Filament\Widgets\LatestExamsTable;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Dashboard';
    protected static ?int $navigationSort = 0;

    public function getTitle(): string
    {
        return 'Dashboard';
    }

    public function getHeading(): string
    {
        return 'Dashboard';
    }

    public function getSubheading(): ?string
    {
        return 'Ringkasan operasional CBT Julia secara real-time';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tanggal')->label(now()->translatedFormat('d F Y'))->icon('heroicon-m-calendar-days')->color('gray')->disabled(),
            Action::make('ekspor_laporan')->label('Ekspor Laporan')->icon('heroicon-m-arrow-down-tray')->color('primary'),
        ];
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
            ExamActivityChart::class,
            ExamStatusChart::class,
            LatestActivityWidget::class,
            LatestExamsTable::class,
            DeviceSummaryWidget::class,
        ];
    }
}
