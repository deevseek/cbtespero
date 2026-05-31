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

    protected function getStats(): array
    {
        $activeExamCount = Exam::query()->where('status', 'aktif')->count();
        $runningParticipantCount = ExamResult::query()->where('status', 'sedang_mengerjakan')->count();
        $completedParticipantCount = ExamResult::query()->where('status', 'selesai')->count();
        $todayViolationCount = ExamLog::query()->whereDate('logged_at', today())->count();

        return [
            Stat::make('Total Siswa', number_format(Student::query()->count()))
                ->description('Data siswa terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Ujian Aktif', number_format($activeExamCount))
                ->description('Ujian berstatus aktif')
                ->descriptionIcon('heroicon-m-bolt')
                ->color($activeExamCount > 0 ? 'success' : 'gray'),
            Stat::make('Total Soal', number_format(Question::query()->count()))
                ->description('Bank soal tersedia')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
            Stat::make('Sedang Ujian', number_format($runningParticipantCount))
                ->description('Peserta sedang mengerjakan')
                ->descriptionIcon('heroicon-m-clock')
                ->color($runningParticipantCount > 0 ? 'warning' : 'gray'),
            Stat::make('Selesai Ujian', number_format($completedParticipantCount))
                ->description('Peserta sudah submit')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Pelanggaran Hari Ini', number_format($todayViolationCount))
                ->description('Log pelanggaran tanggal '.now()->format('d M Y'))
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($todayViolationCount > 0 ? 'danger' : 'success'),
        ];
    }
}
