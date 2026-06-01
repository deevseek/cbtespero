<?php

namespace App\Services;

use App\Events\StudentExamSubmitted;
use App\Models\ExamResult;
use Illuminate\Support\Facades\DB;

class ExamResultScoringService
{
    /**
     * Finalize and persist the exact result row read by admin pages.
     *
     * @return array{total_questions:int,answered_questions:int,correct_count:int,wrong_count:int,unanswered_count:int,score:float,duration_seconds:int|null,status:string}
     */
    public function finalize(ExamResult $result, string $status = 'selesai', ?string $reason = null, bool $broadcast = true): array
    {
        $summary = [];

        DB::transaction(function () use ($result, $status, $reason, &$summary): void {
            $result->refresh();

            if (in_array($result->status, ['selesai', 'auto_submit'], true)) {
                $summary = $this->summary($result);
                return;
            }

            $summary = $this->summary($result);
            $submittedAt = now();

            $result->forceFill([
                'nilai' => $summary['score'],
                'status' => $status,
                'submitted_at' => $submittedAt,
                'auto_submitted_at' => $status === 'auto_submit' ? $submittedAt : $result->auto_submitted_at,
                'lock_reason' => $reason,
                'remaining_time_seconds' => 0,
                'total_questions' => $summary['total_questions'],
                'answered_questions' => $summary['answered_questions'],
                'correct_count' => $summary['correct_count'],
                'wrong_count' => $summary['wrong_count'],
                'unanswered_count' => $summary['unanswered_count'],
                'duration_seconds' => $summary['duration_seconds'],
                'submit_reason' => $reason,
            ])->save();

            $summary['status'] = $status;
        });

        if ($broadcast) {
            $result->refresh()->loadMissing(['student', 'exam']);
            StudentExamSubmitted::dispatch($result);
        }

        return $summary;
    }


    /** @return array{total_questions:int,answered_questions:int,correct_count:int,wrong_count:int,unanswered_count:int,score:float,duration_seconds:int|null,status:string} */
    public function recalculate(ExamResult $result, ?string $reason = null): array
    {
        $summary = $this->summary($result);
        $result->forceFill([
            'nilai' => $summary['score'],
            'total_questions' => $summary['total_questions'],
            'answered_questions' => $summary['answered_questions'],
            'correct_count' => $summary['correct_count'],
            'wrong_count' => $summary['wrong_count'],
            'unanswered_count' => $summary['unanswered_count'],
            'duration_seconds' => $summary['duration_seconds'],
            'submit_reason' => $reason ?: $result->submit_reason,
        ])->save();

        return $summary;
    }

    /** @return array{total_questions:int,answered_questions:int,correct_count:int,wrong_count:int,unanswered_count:int,score:float,duration_seconds:int|null,status:string} */
    public function summary(ExamResult $result): array
    {
        $total = max(1, $result->answers()->count());
        $answered = $result->answers()->whereNotNull('jawaban_siswa')->count();
        $correct = $result->answers()->where('is_correct', true)->count();
        $wrong = max(0, $answered - $correct);
        $unanswered = max(0, $total - $answered);
        $durationSeconds = $result->started_at
            ? max(0, $result->started_at->diffInSeconds($result->submitted_at ?: now()))
            : null;

        return [
            'total_questions' => $total,
            'answered_questions' => $answered,
            'correct_count' => $correct,
            'wrong_count' => $wrong,
            'unanswered_count' => $unanswered,
            'score' => round(($correct / $total) * 100, 2),
            'duration_seconds' => $durationSeconds,
            'status' => (string) $result->status,
        ];
    }

    public function syncCounters(ExamResult $result): void
    {
        $summary = $this->summary($result);
        $result->forceFill([
            'total_questions' => $summary['total_questions'],
            'answered_questions' => $summary['answered_questions'],
            'correct_count' => $summary['correct_count'],
            'wrong_count' => $summary['wrong_count'],
            'unanswered_count' => $summary['unanswered_count'],
            'duration_seconds' => $summary['duration_seconds'],
        ])->save();
    }
}
