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
use Illuminate\Support\Facades\Schema;

class StudentExamService
{
    public function examsForStudentQuery(Student $student): Builder
    {
        $studentClass = $this->normalizeClass($student->kelas ?? $student->class ?? $student->class_level ?? null);

        return Exam::query()
            ->with(['securitySetting'])
            ->withCount('questions')
            ->where('status', 'aktif')
            ->where(function (Builder $query) use ($studentClass, $student) {
                foreach (['kelas', 'class', 'class_level'] as $field) {
                    if (Schema::hasColumn('exams', $field)) {
                        $query->orWhereRaw('LOWER(TRIM('.$field.')) = ?', [$studentClass]);
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

        return $exams->map(function (Exam $exam) use ($results) {
            $result = $results->get($exam->id);
            $status = $this->studentStatus($exam, $result);
            $exam->setAttribute('student_result', $result);
            $exam->setAttribute('student_status', $status);
            $exam->setAttribute('status_label', $this->statusLabel($status));
            $exam->setAttribute('status_color', $this->statusColor($status));
            $exam->setAttribute('starts_at', $this->startAt($exam));
            $exam->setAttribute('ends_at', $this->endAt($exam));
            $exam->setAttribute('requires_token', $this->requiresToken($exam));
            $exam->setAttribute('question_total', $this->questionCount($exam));
            $exam->setAttribute('is_ready', $this->questionCount($exam) > 0);

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

        $now = now();
        $start = $this->startAt($exam);
        $end = $this->endAt($exam);

        if ($start && $now->lt($start)) {
            return 'upcoming';
        }

        if ($end && $now->gt($end)) {
            return 'missed';
        }

        return 'available';
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'upcoming' => 'Belum Mulai',
            'available' => 'Bisa Dikerjakan',
            'in_progress' => 'Sedang Berlangsung',
            'finished' => 'Selesai',
            'missed' => 'Terlewat',
            default => 'Tidak Diketahui',
        };
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'available' => 'blue',
            'in_progress' => 'amber',
            'finished' => 'green',
            'missed' => 'red',
            default => 'slate',
        };
    }

    public function startAt(Exam $exam): ?Carbon
    {
        if (isset($exam->start_at) && $exam->start_at) {
            return Carbon::parse($exam->start_at);
        }

        if (isset($exam->start_time) && $exam->start_time) {
            return Carbon::parse($exam->start_time);
        }

        if ($exam->tanggal_ujian && $exam->jam_mulai) {
            return Carbon::parse($exam->tanggal_ujian.' '.$exam->jam_mulai);
        }

        return null;
    }

    public function endAt(Exam $exam): ?Carbon
    {
        if (isset($exam->finish_at) && $exam->finish_at) {
            return Carbon::parse($exam->finish_at);
        }

        if (isset($exam->end_time) && $exam->end_time) {
            return Carbon::parse($exam->end_time);
        }

        if ($exam->tanggal_ujian && $exam->jam_selesai) {
            return Carbon::parse($exam->tanggal_ujian.' '.$exam->jam_selesai);
        }

        return $this->startAt($exam)?->copy()->addMinutes((int) $exam->durasi);
    }

    public function requiresToken(Exam $exam): bool
    {
        return filled($exam->token) || $exam->tokens()->where('is_active', true)->exists();
    }

    public function tokenIsValid(Exam $exam, string $token): bool
    {
        $normalized = strtoupper(trim($token));

        return ExamToken::query()
            ->where('exam_id', $exam->id)
            ->where('token', $normalized)
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists()
            || ($exam->token && strtoupper((string) $exam->token) === $normalized);
    }

    public function questionCount(Exam $exam): int
    {
        if (isset($exam->questions_count)) {
            return (int) $exam->questions_count;
        }

        return $exam->questions()->count();
    }

    public function hasFallbackQuestionBank(Exam $exam): bool
    {
        return Question::where('mata_pelajaran', $exam->mata_pelajaran)->exists();
    }

    private function normalizeClass(?string $value): string
    {
        return strtolower(trim((string) $value));
    }
}
