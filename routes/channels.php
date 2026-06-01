<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('exam.{examId}', function ($user, int $examId): bool {
    return $user && in_array($user->role ?? null, ['admin', 'guru', 'operator'], true);
});

Broadcast::channel('student.exam.{examId}.{studentId}', function ($user, int $examId, int $studentId): bool {
    return (int) ($user->student_id ?? 0) === $studentId;
});
