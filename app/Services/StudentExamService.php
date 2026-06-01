<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamToken;
use App\Models\Question;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class StudentExamService
{
    public function __construct(private ExamStatusService $statusService)
    {
    }

    public function examsForStudentQuery(Student $student): Builder
    {
        $studentClass = $this->statusService->normalizeClass($student->kelas ?? $student->class ?? $student->class_level ?? null);
        $studentClassCompact = $this->statusService->normalizeClassForCompactCompare($student->kelas ?? $student->class ?? $student->class_level ?? null);

        return Exam::query()
            ->with(['securitySetting'])
            ->withCount('questions')
            ->whereIn('status', ['aktif', 'berlangsung', 'active', 'terjadwal', 'belum_dimulai', 'scheduled', 'selesai'])
            ->where(function (Builder $query) use ($studentClass, $studentClassCompact, $student) {
                foreach (['kelas', 'class', 'class_level'] as $field) {
                    if (Schema::hasColumn('exams', $field)) {
                        $query->orWhereRaw('UPPER(TRIM('.$field.')) = ?', [$studentClass])
                            ->orWhereRaw("UPPER(REPLACE(TRIM($field), ' ', '')) = ?", [$studentClassCompact]);
                    }
                }

                if (method_exists(Exam::class, 'classes')) {
                    $query->orWhereHas('classes', function (Builder $classQuery) use ($student) {
                        $classQuery->where('name', $student->kelas ?? $student->class ?? $student->class_level);
                    });
                }
            })
            ->orderBy('tanggal_ujian')
            ->orderBy('jam_mulai');
    }

    public function decorateExams(Collection $exams, Student $student): Collection
    {
        $results = ExamResult::query()
            ->where('student_id', $student->id)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->get()
            ->keyBy('exam_id');

        return $exams->map(function (Exam $exam) use ($results, $student) {
            $result = $results->get($exam->id);
            $studentStatus = $this->statusService->getStudentStatus($exam, $student, $result);
            $status = $studentStatus['key'];
            $startAt = $this->startAt($exam);
            $endAt = $this->endAt($exam);
            $questionTotal = $this->questionCount($exam);

            $exam->setAttribute('student_result', $result);
            $exam->setAttribute('student_status', $status);
            $exam->setAttribute('student_status_detail', $studentStatus);
            $exam->setAttribute('student_action', $studentStatus['action']);
            $exam->setAttribute('student_action_disabled', $studentStatus['disabled']);
            $exam->setAttribute('status_label', $studentStatus['label']);
            $exam->setAttribute('status_color', $studentStatus['color']);
            $exam->setAttribute('status_badge_class', $this->statusBadgeClass($status));
            $exam->setAttribute('starts_at', $startAt);
            $exam->setAttribute('ends_at', $endAt);
            $exam->setAttribute('requires_token', $this->requiresToken($exam));
            $exam->setAttribute('question_total', $questionTotal);
            $exam->setAttribute('is_ready', $questionTotal > 0);

            Log::debug('[STUDENT EXAM STATUS]', [
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'exam_status' => $exam->status,
                'exam_date' => $exam->tanggal_ujian ?? $exam->exam_date ?? null,
                'start_time' => $exam->jam_mulai ?? $exam->start_time ?? null,
                'end_time' => $exam->jam_selesai ?? $exam->end_time ?? null,
                'start_at' => $startAt?->toDateTimeString(),
                'end_at' => $endAt?->toDateTimeString(),
                'now' => now('Asia/Jakarta')->toDateTimeString(),
                'calculated_status' => $studentStatus['label'],
                'action' => $studentStatus['action'],
            ]);

            return $exam;
        });
    }

    public function stats(Collection $decoratedExams, Student $student): array
    {
        $finishedResults = ExamResult::query()
            ->where('student_id', $student->id)
            ->whereNotNull('nilai')
            ->whereIn('status', ['selesai', 'auto_submit'])
            ->get();

        return [
            'active' => $decoratedExams->whereIn('student_status', ['available', 'in_progress'])->count(),
            'pending' => $decoratedExams->whereIn('student_status', ['upcoming', 'available'])->count(),
            'finished' => $finishedResults->count(),
            'average' => $finishedResults->count() ? round((float) $finishedResults->avg('nilai'), 1) : 0,
        ];
    }

    public function studentStatus(Exam $exam, ?ExamResult $result = null): string
    {
        if ($result && in_array($result->status, ['selesai', 'auto_submit'], true)) {
            return 'finished';
        }

        if ($result && in_array($result->status, ['sedang_mengerjakan', 'terkunci'], true)) {
            return 'in_progress';
        }

        $student = $result?->student ?: new Student();

        return $this->statusService->getStudentStatus($exam, $student, $result)['key'];
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'upcoming' => 'Belum Mulai',
            'available' => 'Bisa Dikerjakan',
            'in_progress' => 'Sedang Berlangsung',
            'finished' => 'Selesai',
            'missed' => 'Terlewat',
            'not_ready' => 'Ujian Belum Siap',
            default => 'Tidak Diketahui',
        };
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'available' => 'blue',
            'in_progress' => 'amber',
            'finished' => 'green',
            'missed', 'not_ready' => 'red',
            default => 'slate',
        };
    }

    public function statusBadgeClass(string $status): string
    {
        return match ($this->statusColor($status)) {
            'blue' => 'bg-blue-50 text-blue-700 border-blue-100',
            'amber' => 'bg-amber-50 text-amber-700 border-amber-100',
            'green' => 'bg-green-50 text-green-700 border-green-100',
            'red' => 'bg-red-50 text-red-700 border-red-100',
            default => 'bg-slate-50 text-slate-700 border-slate-100',
        };
    }

    public function startAt(Exam $exam): ?Carbon
    {
        return $this->statusService->getExamStartAt($exam);
    }

    public function endAt(Exam $exam): ?Carbon
    {
        return $this->statusService->getExamEndAt($exam);
    }

    public function requiresToken(Exam $exam): bool
    {
        return $this->statusService->requiresToken($exam);
    }

    public function tokenIsValid(Exam $exam, string $token): bool
    {
        $normalized = strtoupper(trim($token));

        return ExamToken::query()
            ->where('exam_id', $exam->id)
            ->where('token', $normalized)
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now('Asia/Jakarta')))
            ->exists()
            || ($exam->token && strtoupper((string) $exam->token) === $normalized);
    }

    public function questionCount(Exam $exam): int
    {
        return $this->statusService->questionCount($exam);
    }

    public function hasFallbackQuestionBank(Exam $exam): bool
    {
        return Question::where('mata_pelajaran', $exam->mata_pelajaran)->exists();
    }

}
