<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ExamStatusService
{
    private const TIMEZONE = 'Asia/Jakarta';

    public function getAdminStatus(Exam $exam): string
    {
        return match ($this->normalizeStatus($exam->status)) {
            'aktif', 'berlangsung', 'active' => 'Berlangsung',
            'selesai', 'finished', 'completed' => 'Selesai',
            'terjadwal', 'scheduled' => 'Terjadwal',
            'belum_dimulai' => 'Belum Dimulai',
            'dibatalkan', 'cancelled', 'canceled' => 'Dibatalkan',
            'draft' => 'Draft',
            default => filled($exam->status) ? Str::of((string) $exam->status)->replace('_', ' ')->title()->toString() : '-',
        };
    }

    /**
     * @return array{key: string, label: string, color: string, action: string, disabled: bool}
     */
    public function getStudentStatus(Exam $exam, Student $student, ?ExamResult $result = null): array
    {
        $result ??= $this->findStudentResult($exam, $student);
        $questionTotal = $this->questionCount($exam);

        if ($this->isSubmitted($result)) {
            return $this->studentStatus('finished', 'Selesai', 'green', 'view_result', false);
        }

        if ($this->isInProgress($result)) {
            return $this->studentStatus('in_progress', 'Sedang Berlangsung', 'amber', 'continue_exam', false);
        }

        if ($questionTotal <= 0) {
            return $this->studentStatus('not_ready', 'Ujian Belum Siap', 'red', 'disabled', true);
        }

        $requiresToken = $this->requiresToken($exam);
        $readyAction = $requiresToken ? 'enter_token' : 'start_exam';
        $adminStatus = $this->normalizeStatus($exam->status);

        if (in_array($adminStatus, ['aktif', 'berlangsung', 'active'], true)) {
            return $this->studentStatus('available', 'Bisa Dikerjakan', 'blue', $readyAction, false);
        }

        $now = Carbon::now(self::TIMEZONE);
        $start = $this->getExamStartAt($exam);
        $end = $this->getExamEndAt($exam);

        if ($start && $end && $now->betweenIncluded($start, $end)) {
            return $this->studentStatus('available', 'Bisa Dikerjakan', 'blue', $readyAction, false);
        }

        if ($start && $now->lt($start)) {
            return $this->studentStatus('upcoming', 'Belum Mulai', 'slate', 'disabled', true);
        }

        if ($end && $now->gt($end)) {
            return $this->studentStatus('missed', 'Terlewat', 'red', 'disabled', true);
        }

        return $this->studentStatus('upcoming', 'Belum Mulai', 'slate', 'disabled', true);
    }

    public function normalizeTime(mixed $time): ?string
    {
        if ($time instanceof \DateTimeInterface) {
            return Carbon::instance($time)->setTimezone(self::TIMEZONE)->format('H:i:s');
        }

        $value = trim((string) $time);
        if ($value === '') {
            return null;
        }

        $value = str_replace('.', ':', $value);
        $parts = array_values(array_filter(explode(':', $value), fn (string $part): bool => $part !== ''));

        if (count($parts) === 2) {
            $parts[] = '00';
        }

        if (count($parts) !== 3) {
            return null;
        }

        [$hour, $minute, $second] = array_map(fn (string $part): string => str_pad($part, 2, '0', STR_PAD_LEFT), $parts);
        $normalized = "$hour:$minute:$second";

        try {
            Carbon::createFromFormat('H:i:s', $normalized, self::TIMEZONE);
        } catch (\Throwable) {
            return null;
        }

        return $normalized;
    }

    public function getExamStartAt(Exam $exam): ?Carbon
    {
        if ($startAt = $this->firstFilledAttribute($exam, ['start_at', 'starts_at'])) {
            return Carbon::parse($startAt, self::TIMEZONE)->setTimezone(self::TIMEZONE);
        }

        $date = $this->firstFilledAttribute($exam, ['exam_date', 'tanggal_ujian', 'tanggal', 'date']);
        $time = $this->firstFilledAttribute($exam, ['start_time', 'jam_mulai', 'mulai']);

        return $this->combineDateAndTime($date, $time);
    }

    public function getExamEndAt(Exam $exam): ?Carbon
    {
        if ($endAt = $this->firstFilledAttribute($exam, ['end_at', 'ends_at', 'finish_at'])) {
            return Carbon::parse($endAt, self::TIMEZONE)->setTimezone(self::TIMEZONE);
        }

        $date = $this->firstFilledAttribute($exam, ['exam_date', 'tanggal_ujian', 'tanggal', 'date']);
        $time = $this->firstFilledAttribute($exam, ['end_time', 'jam_selesai', 'selesai']);
        $end = $this->combineDateAndTime($date, $time);

        if ($end) {
            return $end;
        }

        return $this->getExamStartAt($exam)?->copy()->addMinutes((int) $exam->durasi);
    }

    public function requiresToken(Exam $exam): bool
    {
        if (filled($exam->token)) {
            return true;
        }

        if (isset($exam->tokens_count)) {
            return (int) $exam->tokens_count > 0;
        }

        return $exam->exists && $exam->tokens()->where('is_active', true)->exists();
    }

    public function questionCount(Exam $exam): int
    {
        if (isset($exam->questions_count)) {
            return (int) $exam->questions_count;
        }

        return $exam->exists ? $exam->questions()->count() : 0;
    }

    public function normalizeClass(mixed $value): string
    {
        return Str::of((string) $value)
            ->trim()
            ->replaceMatches('/\s+/', ' ')
            ->upper()
            ->toString();
    }

    public function normalizeClassForCompactCompare(mixed $value): string
    {
        return str_replace(' ', '', $this->normalizeClass($value));
    }

    private function findStudentResult(Exam $exam, Student $student): ?ExamResult
    {
        if (! $exam->exists || ! $student->exists) {
            return null;
        }

        return ExamResult::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->latest('updated_at')
            ->first();
    }

    private function isSubmitted(?ExamResult $result): bool
    {
        return $result !== null
            && ($result->submitted_at !== null || in_array($result->status, ['selesai', 'auto_submit'], true));
    }

    private function isInProgress(?ExamResult $result): bool
    {
        return $result !== null
            && ! $this->isSubmitted($result)
            && ($result->started_at !== null || in_array($result->status, ['sedang_mengerjakan', 'terkunci'], true));
    }

    /**
     * @return array{key: string, label: string, color: string, action: string, disabled: bool}
     */
    private function studentStatus(string $key, string $label, string $color, string $action, bool $disabled): array
    {
        return compact('key', 'label', 'color', 'action', 'disabled');
    }

    private function normalizeStatus(mixed $status): string
    {
        return Str::of((string) $status)->trim()->lower()->replace(' ', '_')->toString();
    }

    private function firstFilledAttribute(Exam $exam, array $attributes): mixed
    {
        foreach ($attributes as $attribute) {
            $value = $exam->getAttribute($attribute);
            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    private function combineDateAndTime(mixed $date, mixed $time): ?Carbon
    {
        if (! filled($date) || ! filled($time)) {
            return null;
        }

        $normalizedTime = $this->normalizeTime($time);
        if (! $normalizedTime) {
            return null;
        }

        try {
            $dateString = $date instanceof \DateTimeInterface
                ? Carbon::instance($date)->setTimezone(self::TIMEZONE)->format('Y-m-d')
                : Carbon::parse((string) $date, self::TIMEZONE)->format('Y-m-d');

            return Carbon::createFromFormat('Y-m-d H:i:s', $dateString.' '.$normalizedTime, self::TIMEZONE);
        } catch (\Throwable) {
            return null;
        }
    }
}
