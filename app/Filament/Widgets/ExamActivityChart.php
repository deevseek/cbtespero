<?php

namespace App\Filament\Widgets;

use App\Models\ExamResult;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

class ExamActivityChart extends ChartWidget
{
    protected ?string $heading = 'Aktivitas Peserta 7 Hari Terakhir';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $period = CarbonPeriod::create(now()->subDays(6)->startOfDay(), now()->startOfDay());
        $labels = [];
        $started = [];
        $submitted = [];

        foreach ($period as $date) {
            $labels[] = $date->format('d M');
            $started[] = ExamResult::query()->whereDate('started_at', $date)->count();
            $submitted[] = ExamResult::query()->whereDate('submitted_at', $date)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Mulai Ujian',
                    'data' => $started,
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.15)',
                ],
                [
                    'label' => 'Selesai Ujian',
                    'data' => $submitted,
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.15)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
