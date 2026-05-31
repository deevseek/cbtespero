<?php

namespace App\Filament\Pages;

use App\Models\ExamResult;
use Filament\Actions\Action;
use Filament\Pages\Page;

class MonitoringUjian extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'Monitoring Ujian';
    protected static string $view = 'filament.pages.monitoring-ujian';

    public function forceSubmit(int $resultId): void
    {
        $result = ExamResult::with('answers')->findOrFail($resultId);
        $total = max(1, $result->answers()->count());
        $result->update(['nilai' => round(($result->answers()->where('is_correct', true)->count() / $total) * 100, 2), 'status' => 'selesai', 'submitted_at' => now()]);
    }

    public function unlock(int $resultId): void
    {
        ExamResult::findOrFail($resultId)->update(['status' => 'sedang_mengerjakan', 'locked_at' => null, 'lock_reason' => null]);
    }

    public function getRowsProperty()
    {
        return ExamResult::with(['exam', 'student', 'answers', 'logs'])->latest('updated_at')->limit(100)->get();
    }
}
