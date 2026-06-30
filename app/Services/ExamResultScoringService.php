<?php

namespace App\Services;

use App\Events\StudentExamSubmitted;
use App\Models\ExamResult;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class ExamResultScoringService
{
    /**
     * Calculate score for a single answer based on question type and scoring method.
     *
     * @param Question $question
     * @param string|null $jawabanSiswa
     * @return array{is_correct: bool, score: float, partial_score: float|null}
     */
    public function calculateAnswerScore(Question $question, ?string $jawabanSiswa): array
    {
        if ($jawabanSiswa === null || $jawabanSiswa === '') {
            return ['is_correct' => false, 'score' => 0, 'partial_score' => null];
        }

        $tipeSoal = $question->tipe_soal ?? 'pilihan_ganda';
        $scoringMethod = $question->scoring_method ?? 'binary';
        $bobotNilai = $question->bobot_nilai ?? 1;
        $jawabanBenar = $question->jawaban_benar;
        $scoringParams = $question->scoring_parameters ?? [];

        // Normalize scoringMethod to new consistent names
        $normalizedScoringMethod = $scoringMethod;
        if ($normalizedScoringMethod === 'proporsional') {
            $normalizedScoringMethod = 'proportional';
        } elseif ($normalizedScoringMethod === 'minus') {
            $normalizedScoringMethod = 'penalty';
        } elseif ($normalizedScoringMethod === 'binary') {
            $normalizedScoringMethod = 'all_or_nothing';
        }

        switch ($tipeSoal) {
            case 'multiple_answer':
                return $this->calculateMultipleAnswerScore($jawabanBenar, $jawabanSiswa, $normalizedScoringMethod, $bobotNilai, $scoringParams);
            case 'checklist':
                return $this->calculateMultipleAnswerScore($jawabanBenar, $jawabanSiswa, $normalizedScoringMethod, $bobotNilai, $scoringParams);
            case 'dropdown':
            case 'pilihan_ganda':
            default:
                return $this->calculateSingleChoiceScore($jawabanBenar, $jawabanSiswa, $bobotNilai);
        }
    }

    /**
     * Calculate score for single choice (pilihan ganda / dropdown).
     */
    private function calculateSingleChoiceScore(string $jawabanBenar, string $jawabanSiswa, int $bobotNilai): array
    {
        $isCorrect = strtoupper($jawabanBenar) === strtoupper($jawabanSiswa);
        return [
            'is_correct' => $isCorrect,
            'score' => $isCorrect ? $bobotNilai : 0,
            'partial_score' => null,
        ];
    }

    /**
     * Calculate score for multiple answer questions.
     * 
     * Scoring methods:
     * - binary: Full point if exactly correct, 0 otherwise
     * - all_or_nothing: Same as binary
     * - proporsional: Score = (correct selections / total correct answers) * bobot
     * - minus: Score with penalty for wrong selections
     */
    private function calculateMultipleAnswerScore(string $jawabanBenar, string $jawabanSiswa, string $scoringMethod, int $bobotNilai, array $scoringParams): array
    {
        $correctAnswers = is_array($decoded = json_decode($jawabanBenar, true)) ? $decoded : [$jawabanBenar];
        $studentAnswers = is_array($decoded = json_decode($jawabanSiswa, true)) ? $decoded : [$jawabanSiswa];

        // Normalize to uppercase for comparison
        $correctAnswers = array_map('strtoupper', $correctAnswers);
        $studentAnswers = array_map('strtoupper', $studentAnswers);

        // Count correct and wrong selections
        $correctSelections = array_intersect($studentAnswers, $correctAnswers);
        $wrongSelections = array_diff($studentAnswers, $correctAnswers);
        $missedCorrect = array_diff($correctAnswers, $studentAnswers);

        $totalCorrectAnswers = count($correctAnswers);
        $totalStudentAnswers = count($studentAnswers);
        $totalCorrectSelections = count($correctSelections);
        $totalWrongSelections = count($wrongSelections);

        // Check if exactly correct
        $isExactlyCorrect = empty($wrongSelections) && empty($missedCorrect);

        switch ($scoringMethod) {
            case 'all_or_nothing':
                return [
                    'is_correct' => $isExactlyCorrect,
                    'score' => $isExactlyCorrect ? $bobotNilai : 0,
                    'partial_score' => $isExactlyCorrect ? null : round(($totalCorrectSelections / max(1, $totalCorrectAnswers)) * $bobotNilai, 2),
                ];

            case 'proporsional':
                // Score = (correct selections / total correct answers) * bobot
                $proportionalScore = ($totalCorrectSelections / max(1, $totalCorrectAnswers)) * $bobotNilai;
                return [
                    'is_correct' => $isExactlyCorrect,
                    'score' => round($proportionalScore, 2),
                    'partial_score' => null,
                ];

            case 'minus':
                // Score with penalty for wrong selections
                // penaltyFactor can be set in scoring_parameters, default 0.25 (25% penalty per wrong)
                $penaltyFactor = $scoringParams['penalty_factor'] ?? 0.25;
                $baseScore = ($totalCorrectSelections / max(1, $totalCorrectAnswers)) * $bobotNilai;
                $penalty = $totalWrongSelections * ($bobotNilai * $penaltyFactor);
                $finalScore = max(0, $baseScore - $penalty);
                return [
                    'is_correct' => $isExactlyCorrect,
                    'score' => round($finalScore, 2),
                    'partial_score' => null,
                ];

            case 'binary':
            default:
                return [
                    'is_correct' => $isExactlyCorrect,
                    'score' => $isExactlyCorrect ? $bobotNilai : 0,
                    'partial_score' => null,
                ];
        }
    }

    /**
     * Calculate score for checklist questions.
     * 
     * Checklist format for jawaban_benar: [{"benar": true}, {"benar": false}]
     * Checklist format for jawaban_siswa: ["benar", "salah"] or [true, false]
     */
    private function calculateChecklistScore(string $jawabanBenar, string $jawabanSiswa, string $scoringMethod, int $bobotNilai, array $scoringParams): array
    {
        $correctAnswers = json_decode($jawabanBenar, true) ?? [];
        $studentAnswers = json_decode($jawabanSiswa, true) ?? [];

        if (!is_array($correctAnswers) || !is_array($studentAnswers)) {
            return ['is_correct' => false, 'score' => 0, 'partial_score' => null];
        }

        $totalItems = count($correctAnswers);
        $correctCount = 0;

        foreach ($correctAnswers as $index => $correctItem) {
            $expectedValue = $correctItem['benar'] ?? $correctItem['value'] ?? $correctItem;
            $studentValue = $studentAnswers[$index] ?? null;

            // Normalize student answer (could be "benar"/"salah" string or boolean)
            if (is_string($studentValue)) {
                $studentBool = strtolower($studentValue) === 'benar' || strtolower($studentValue) === 'ya' || $studentValue === 'true';
            } else {
                $studentBool = (bool) $studentValue;
            }

            $expectedBool = (bool) $expectedValue;
            if ($studentBool === $expectedBool) {
                $correctCount++;
            }
        }

        $isExactlyCorrect = $correctCount === $totalItems;

        switch ($scoringMethod) {
            case 'all_or_nothing':
                return [
                    'is_correct' => $isExactlyCorrect,
                    'score' => $isExactlyCorrect ? $bobotNilai : 0,
                    'partial_score' => $isExactlyCorrect ? null : round(($correctCount / max(1, $totalItems)) * $bobotNilai, 2),
                ];

            case 'proporsional':
                $proportionalScore = ($correctCount / max(1, $totalItems)) * $bobotNilai;
                return [
                    'is_correct' => $isExactlyCorrect,
                    'score' => round($proportionalScore, 2),
                    'partial_score' => null,
                ];

            case 'minus':
                $wrongCount = $totalItems - $correctCount;
                $penaltyFactor = $scoringParams['penalty_factor'] ?? 0.25;
                $baseScore = ($correctCount / max(1, $totalItems)) * $bobotNilai;
                $penalty = $wrongCount * ($bobotNilai * $penaltyFactor / max(1, $totalItems));
                $finalScore = max(0, $baseScore - $penalty);
                return [
                    'is_correct' => $isExactlyCorrect,
                    'score' => round($finalScore, 2),
                    'partial_score' => null,
                ];

            case 'binary':
            default:
                return [
                    'is_correct' => $isExactlyCorrect,
                    'score' => $isExactlyCorrect ? $bobotNilai : 0,
                    'partial_score' => null,
                ];
        }
    }

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
