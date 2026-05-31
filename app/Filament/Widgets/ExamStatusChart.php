<?php

namespace App\Filament\Widgets;

use App\Models\Exam;
use Filament\Widgets\ChartWidget;

class ExamStatusChart extends ChartWidget
{
    protected ?string $heading = 'Status Ujian';
    protected ?string $description = 'Komposisi status ujian';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];
    protected ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        return [
            'datasets' => [[
                'data' => [
                    Exam::query()->where('status', 'selesai')->count(),
                    Exam::query()->whereIn('status', ['aktif', 'berlangsung'])->count(),
                    Exam::query()->whereIn('status', ['terjadwal', 'belum_dimulai', 'draft'])->count(),
                    Exam::query()->where('status', 'dibatalkan')->count(),
                ],
                'backgroundColor' => ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444'],
                'borderColor' => '#0f172a',
            ]],
            'labels' => ['Selesai', 'Sedang Berlangsung', 'Terjadwal', 'Dibatalkan'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
