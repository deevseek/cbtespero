<?php

namespace App\Filament\Widgets;

use App\Models\ExamResult;
use App\Models\StudentDevice;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Schema;

class DeviceSummaryWidget extends Widget
{
    protected string $view = 'filament.widgets.device-summary';
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 2,
    ];
    protected ?string $pollingInterval = '10s';

    public function getSummary(): array
    {
        $online = ExamResult::query()->where('last_heartbeat_at', '>=', now()->subMinutes(2))->count();
        $offline = ExamResult::query()->whereNotNull('last_heartbeat_at')->where('last_heartbeat_at', '<', now()->subMinutes(2))->where('last_heartbeat_at', '>=', now()->subMinutes(15))->count();
        $inactive = ExamResult::query()->where(function ($query): void {
            $query->whereNull('last_heartbeat_at')->orWhere('last_heartbeat_at', '<', now()->subMinutes(15));
        })->count();
        $total = ExamResult::query()->count();

        if (Schema::hasTable('student_devices') && class_exists(StudentDevice::class) && $total === 0) {
            $total = StudentDevice::query()->count();
        }

        return compact('online', 'offline', 'inactive', 'total');
    }
}
