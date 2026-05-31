<?php

namespace App\Filament\Widgets;

use App\Models\Exam;
use App\Models\ExamLog;
use App\Models\ExamResult;
use App\Models\Question;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CbtStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '10s';
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $activeExamCount = Exam::query()->whereIn('status', ['aktif', 'berlangsung'])->count();
        $completedTodayCount = ExamResult::query()->whereDate('submitted_at', today())->count();
        $todayViolationCount = ExamLog::query()->whereDate('logged_at', today())->count();

        return [
            Stat::make('Total Siswa', number_format(Student::query()->count()))
                ->description('Data siswa terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
            Stat::make('Ujian Aktif', number_format($activeExamCount))
                ->description('Ujian sedang berlangsung')
                ->descriptionIcon('heroicon-m-bolt')
                ->color($activeExamCount > 0 ? 'info' : 'gray'),
            Stat::make('Selesai Hari Ini', number_format($completedTodayCount))
                ->description('Submit pada '.now()->format('d M Y'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Pelanggaran Hari Ini', number_format($todayViolationCount))
                ->description('Log pelanggaran real-time')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($todayViolationCount > 0 ? 'danger' : 'success'),
            Stat::make('Bank Soal', number_format(Question::query()->count()))
                ->description('Soal siap digunakan')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
        ];
    }
}
