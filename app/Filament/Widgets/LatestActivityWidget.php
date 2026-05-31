<?php

namespace App\Filament\Widgets;

use App\Models\Exam;
use App\Models\ExamLog;
use App\Models\ExamResult;
use App\Models\ExamToken;
use App\Models\Question;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class LatestActivityWidget extends Widget
{
    protected string $view = 'filament.widgets.latest-activity';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 2,
    ];
    protected ?string $pollingInterval = '10s';

    public function getActivities(): Collection
    {
        return collect()
            ->merge(ExamResult::query()->with(['exam', 'student'])->whereNotNull('started_at')->latest('started_at')->limit(4)->get()->map(fn (ExamResult $result): array => [
                'type' => 'Ujian dimulai', 'title' => $result->student?->nama ?? 'Peserta', 'description' => $result->exam?->nama_ujian ?? 'Ujian', 'time' => $result->started_at, 'color' => 'blue',
            ]))
            ->merge(ExamResult::query()->with(['exam', 'student'])->whereNotNull('submitted_at')->latest('submitted_at')->limit(4)->get()->map(fn (ExamResult $result): array => [
                'type' => 'Siswa menyelesaikan ujian', 'title' => $result->student?->nama ?? 'Peserta', 'description' => $result->exam?->nama_ujian ?? 'Ujian', 'time' => $result->submitted_at, 'color' => 'green',
            ]))
            ->merge(ExamLog::query()->with(['exam', 'student'])->latest('logged_at')->limit(4)->get()->map(fn (ExamLog $log): array => [
                'type' => 'Pelanggaran terdeteksi', 'title' => $log->student?->nama ?? 'Peserta', 'description' => $log->activity_type ?? 'Log pelanggaran', 'time' => $log->logged_at, 'color' => 'red',
            ]))
            ->merge(ExamToken::query()->latest()->limit(2)->get()->map(fn (ExamToken $token): array => [
                'type' => 'Token digunakan', 'title' => $token->token, 'description' => 'Token ujian aktif', 'time' => $token->updated_at, 'color' => 'cyan',
            ]))
            ->merge(Question::query()->latest()->limit(2)->get()->map(fn (Question $question): array => [
                'type' => 'Soal baru ditambahkan', 'title' => $question->mata_pelajaran ?? 'Bank Soal', 'description' => str($question->soal)->limit(60)->toString(), 'time' => $question->created_at, 'color' => 'amber',
            ]))
            ->sortByDesc('time')
            ->take(7)
            ->values();
    }
}
