<?php

namespace App\Filament\Widgets;

use App\Models\ExamLog;
use App\Models\ExamResult;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

class ExamActivityChart extends ChartWidget
{
    protected ?string $heading = 'Trend Ujian';
    protected ?string $description = '30 Hari Terakhir';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];
    protected ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $period = CarbonPeriod::create(now()->subDays(29)->startOfDay(), now()->startOfDay());
        $labels = $started = $submitted = $violations = [];

        foreach ($period as $date) {
            $labels[] = $date->format('d M');
            $started[] = ExamResult::query()->whereDate('started_at', $date)->count();
            $submitted[] = ExamResult::query()->whereDate('submitted_at', $date)->count();
            $violations[] = ExamLog::query()->whereDate('logged_at', $date)->count();
        }

        return [
            'datasets' => [
                ['label' => 'Mulai Ujian', 'data' => $started, 'borderColor' => '#3b82f6', 'backgroundColor' => 'rgba(59, 130, 246, 0.18)', 'fill' => true, 'tension' => 0.42],
                ['label' => 'Selesai', 'data' => $submitted, 'borderColor' => '#22c55e', 'backgroundColor' => 'rgba(34, 197, 94, 0.12)', 'fill' => true, 'tension' => 0.42],
                ['label' => 'Pelanggaran', 'data' => $violations, 'borderColor' => '#ef4444', 'backgroundColor' => 'rgba(239, 68, 68, 0.10)', 'fill' => true, 'tension' => 0.42],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
