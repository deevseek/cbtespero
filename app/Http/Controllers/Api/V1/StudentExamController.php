<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\ExamViolationLogged;
use App\Events\StudentAnswerSaved;
use App\Events\StudentExamStarted;
use App\Events\StudentHeartbeatUpdated;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAnswerOrder;
use App\Models\ExamLog;
use App\Models\ExamQuestionOrder;
use App\Models\ExamResult;
use App\Models\ExamToken;
use App\Models\Question;
use App\Models\StudentDevice;
use App\Services\ExamResultScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StudentExamController extends Controller
{
    public function exams(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        $exams = Exam::with('securitySetting')->where('kelas', $student->kelas)->where('status', 'aktif')->orderBy('tanggal_ujian')->get();
        return response()->json(['server_time' => now()->toISOString(), 'data' => $exams]);
    }

    public function start(Request $request, Exam $exam): JsonResponse
    {
        $student = $request->user()->student;
        abort_if($exam->kelas !== $student->kelas, 403, 'Ujian bukan untuk kelas siswa.');
        $data = $request->validate([
            'token' => ['required', 'string'], 'device_id' => ['required', 'string'], 'device_name' => ['nullable', 'string'],
            'platform' => ['nullable', 'string'], 'app_version' => ['nullable', 'string'],
        ]);
        $this->assertScheduleOpen($exam);
        $token = ExamToken::where('exam_id', $exam->id)->where('token', strtoupper($data['token']))->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->first();
        abort_if(! $token && strtoupper($data['token']) !== strtoupper((string) $exam->token), 422, 'Token ujian tidak valid atau kedaluwarsa.');

        $security = $exam->securitySetting()->firstOrCreate([]);
        $activeOther = ExamResult::where('exam_id', $exam->id)->where('student_id', $student->id)->where('status', 'sedang_mengerjakan')
            ->whereNotNull('device_id')->where('device_id', '!=', $data['device_id'])->first();
        if ($security->device_binding && $activeOther) {
            ExamLog::create(['exam_result_id' => $activeOther->id, 'student_id' => $student->id, 'exam_id' => $exam->id, 'activity_type' => 'login_perangkat_lain', 'description' => 'Login dari perangkat berbeda ditolak.', 'ip_address' => $request->ip(), 'device_id' => $data['device_id'], 'logged_at' => now()]);
            abort(423, 'Sesi ujian sudah aktif di perangkat lain.');
        }

        $result = DB::transaction(function () use ($exam, $student, $data, $request) {
            $result = ExamResult::firstOrNew(['exam_id' => $exam->id, 'student_id' => $student->id]);
            $result->fill([
                'status' => 'sedang_mengerjakan', 'started_at' => $result->started_at ?: now(), 'server_started_at' => $result->server_started_at ?: now(),
                'server_ends_at' => $this->deadlineFor($exam, $result->started_at ?: now()), 'last_heartbeat_at' => now(),
                'session_uuid' => $result->session_uuid ?: (string) Str::uuid(), 'device_id' => $data['device_id'], 'device_name' => $data['device_name'] ?? null,
                'platform' => $data['platform'] ?? null, 'app_version' => $data['app_version'] ?? null, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(),
            ])->save();
            StudentDevice::updateOrCreate(['student_id' => $student->id, 'device_id' => $data['device_id']], ['device_name' => $data['device_name'] ?? null, 'platform' => $data['platform'] ?? null, 'app_version' => $data['app_version'] ?? null, 'last_seen_at' => now(), 'ip_address' => $request->ip(), 'is_active' => true]);
            $this->ensureQuestionSnapshot($exam, $result);
            $result->forceFill(['total_questions' => $result->answers()->count(), 'answered_questions' => $result->answers()->whereNotNull('jawaban_siswa')->count(), 'remaining_time_seconds' => $result->server_ends_at ? max(0, now()->diffInSeconds($result->server_ends_at, false)) : null])->save();
            ExamLog::create(['exam_result_id' => $result->id, 'student_id' => $student->id, 'exam_id' => $exam->id, 'activity_type' => 'exam_started', 'description' => 'Ujian dimulai melalui API Flutter.', 'ip_address' => $request->ip(), 'device_id' => $data['device_id'], 'logged_at' => now()]);
            return $result;
        });
        StudentExamStarted::dispatch($result->loadMissing(['student', 'exam']));
        return response()->json(['server_time' => now()->toISOString(), 'session' => $result->fresh('exam.securitySetting')]);
    }

    public function questions(Request $request, ExamResult $result): JsonResponse
    {
        $this->authorizeResult($request, $result);
        $result->load('exam.securitySetting');
        $items = $result->questionOrders()->with('question')->orderBy('position')->get()->map(function ($order) use ($result) {
            $q = $order->question;
            $optOrder = ExamAnswerOrder::where('exam_result_id', $result->id)->where('question_id', $q->id)->value('option_order') ?: ['a','b','c','d','e'];
            $answer = ExamAnswer::where('exam_result_id', $result->id)->where('question_id', $q->id)->first();
            return ['id' => $q->id, 'position' => $order->position, 'soal' => $q->soal, 'image_path' => $q->image_path, 'options' => collect($optOrder)->filter(fn($k) => $q->{'pilihan_'.$k} !== null)->map(fn($k) => ['key' => $k, 'text' => $q->{'pilihan_'.$k}])->values(), 'answer' => $answer?->jawaban_siswa, 'is_flagged' => (bool) $answer?->is_flagged];
        });
        return response()->json(['server_time' => now()->toISOString(), 'ends_at' => $result->server_ends_at, 'questions' => $items]);
    }

    public function answer(Request $request, ExamResult $result): JsonResponse
    {
        $this->authorizeResult($request, $result);
        $this->ensureOpen($result);
        $data = $request->validate(['question_id' => ['required', 'integer'], 'jawaban' => ['required', Rule::in(['a','b','c','d','e'])]]);
        abort_unless($result->questionOrders()->where('question_id', $data['question_id'])->exists(), 422, 'Soal tidak termasuk sesi ini.');
        $correct = Question::findOrFail($data['question_id'])->jawaban_benar === $data['jawaban'];
        ExamAnswer::updateOrCreate(['exam_result_id' => $result->id, 'question_id' => $data['question_id']], ['jawaban_siswa' => $data['jawaban'], 'is_correct' => $correct, 'answered_at' => now()]);
        app(ExamResultScoringService::class)->syncCounters($result->refresh());
        StudentAnswerSaved::dispatch($result, (int) $data['question_id']);
        return response()->json(['ok' => true, 'server_time' => now()->toISOString(), 'answered_questions' => $result->answered_questions, 'total_questions' => $result->total_questions]);
    }

    public function flag(Request $request, ExamResult $result): JsonResponse
    {
        $this->authorizeResult($request, $result);
        $data = $request->validate(['question_id' => ['required', 'integer'], 'is_flagged' => ['required', 'boolean']]);
        ExamAnswer::where('exam_result_id', $result->id)->where('question_id', $data['question_id'])->update(['is_flagged' => $data['is_flagged']]);
        return response()->json(['ok' => true]);
    }

    public function cheatingLog(Request $request, ExamResult $result): JsonResponse
    {
        $this->authorizeResult($request, $result);
        $data = $request->validate(['type' => ['required', 'string', 'max:80'], 'description' => ['nullable', 'string', 'max:500'], 'metadata' => ['nullable', 'array'], 'device_id' => ['nullable', 'string']]);
        if (in_array($data['type'], ['app_background','app_inactive','app_paused','recent_apps','focus_lost'], true)) $result->increment('app_exit_count');
        if ($data['type'] === 'fullscreen_exit') $result->increment('fullscreen_exit_count');
        $result->refresh();
        $log = ExamLog::create(['exam_result_id' => $result->id, 'student_id' => $result->student_id, 'exam_id' => $result->exam_id, 'activity_type' => $data['type'], 'description' => $data['description'] ?? 'Pelanggaran anti-cheating terdeteksi.', 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'metadata' => $data['metadata'] ?? null, 'device_id' => $data['device_id'] ?? $result->device_id, 'logged_at' => now()]);
        ExamViolationLogged::dispatch($log, $result->logs()->count());
        $this->enforceCheatPolicy($result);
        return response()->json(['ok' => true, 'status' => $result->fresh()->status, 'counts' => ['app_exit' => $result->app_exit_count, 'fullscreen_exit' => $result->fullscreen_exit_count, 'heartbeat_missed' => $result->heartbeat_missed_count]]);
    }

    public function heartbeat(Request $request, ExamResult $result): JsonResponse
    {
        $this->authorizeResult($request, $result);
        if ($result->server_ends_at && now()->greaterThanOrEqualTo($result->server_ends_at)) {
            app(ExamResultScoringService::class)->finalize($result, 'auto_submit', 'Auto submit karena waktu habis');
            return response()->json(['ok' => true, 'action' => 'auto_submit', 'server_time' => now()->toISOString(), 'status' => $result->fresh()->status]);
        }
        $result->update(['last_heartbeat_at' => now(), 'ip_address' => $request->ip(), 'remaining_time_seconds' => $result->server_ends_at ? max(0, now()->diffInSeconds($result->server_ends_at, false)) : $result->remaining_time_seconds]);
        StudentHeartbeatUpdated::dispatch($result->fresh(), $result->logs()->count());
        return response()->json(['ok' => true, 'server_time' => now()->toISOString(), 'status' => $result->status]);
    }

    public function submit(Request $request, ExamResult $result): JsonResponse
    {
        $this->authorizeResult($request, $result);
        $this->finalize($result, $request->boolean('auto_submit') || ($result->server_ends_at && now()->greaterThanOrEqualTo($result->server_ends_at)));
        return response()->json(['ok' => true, 'result' => $result->fresh()]);
    }

    public function results(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        return response()->json(['data' => ExamResult::with('exam.securitySetting')->where('student_id', $student->id)->latest()->get()->map(fn ($r) => ['id' => $r->id, 'exam' => $r->exam->only(['id','nama_ujian','mata_pelajaran']), 'status' => $r->status, 'submitted_at' => $r->submitted_at, 'nilai' => $r->exam->securitySetting?->show_result_after_exam ? $r->nilai : null])]);
    }

    private function assertScheduleOpen(Exam $exam): void
    {
        $start = \Carbon\Carbon::parse($exam->tanggal_ujian.' '.$exam->jam_mulai);
        $end = \Carbon\Carbon::parse($exam->tanggal_ujian.' '.$exam->jam_selesai);
        abort_if(now()->lt($start) || now()->gt($end), 422, 'Ujian belum dibuka atau sudah berakhir.');
    }
    private function authorizeResult(Request $request, ExamResult $result): void { abort_if($result->student_id !== $request->user()->student?->id, 403, 'Sesi bukan milik siswa.'); }
    private function ensureOpen(ExamResult $result): void
    {
        if ($result->server_ends_at && now()->greaterThanOrEqualTo($result->server_ends_at)) {
            app(ExamResultScoringService::class)->finalize($result, 'auto_submit', 'Auto submit karena waktu habis');
            abort(423, 'Sesi ujian tidak aktif.');
        }

        abort_if($result->status !== 'sedang_mengerjakan', 423, 'Sesi ujian tidak aktif.');
    }
    private function ensureQuestionSnapshot(Exam $exam, ExamResult $result): void
    {
        if ($result->questionOrders()->exists()) return;
        $security = $exam->securitySetting()->firstOrCreate([]);
        $query = $exam->questions()->exists() ? $exam->questions() : Question::where('mata_pelajaran', $exam->mata_pelajaran);
        $questions = ($security->randomize_questions ? $query->inRandomOrder() : $query->orderBy('id'))->limit($exam->jumlah_soal)->get();
        foreach ($questions->values() as $i => $q) {
            ExamQuestionOrder::create(['exam_result_id' => $result->id, 'question_id' => $q->id, 'position' => $i + 1]);
            ExamAnswer::firstOrCreate(['exam_result_id' => $result->id, 'question_id' => $q->id]);
            $keys = collect(['a','b','c','d','e'])->filter(fn($k) => $q->{'pilihan_'.$k} !== null)->values();
            ExamAnswerOrder::create(['exam_result_id' => $result->id, 'question_id' => $q->id, 'option_order' => ($security->randomize_answers ? $keys->shuffle() : $keys)->all()]);
        }
    }
    private function enforceCheatPolicy(ExamResult $result): void
    {
        $s = $result->exam->securitySetting()->firstOrCreate([]);
        if ($result->app_exit_count >= $s->max_app_exit || $result->fullscreen_exit_count >= $s->max_fullscreen_exit || $result->heartbeat_missed_count >= $s->max_heartbeat_missed) {
            $s->auto_submit_on_cheat ? $this->finalize($result, true) : $result->update(['status' => 'terkunci', 'locked_at' => now(), 'lock_reason' => 'Batas pelanggaran ujian terlampaui.']);
        }
    }
    private function finalize(ExamResult $result, bool $auto): void
    {
        app(ExamResultScoringService::class)->finalize($result, $auto ? 'auto_submit' : 'selesai', $auto ? 'Auto submit' : null);
    }

    private function deadlineFor(Exam $exam, \Carbon\Carbon $startedAt): \Carbon\Carbon
    {
        $durationEnd = $startedAt->copy()->addMinutes((int) $exam->durasi);
        $examEnd = ($exam->tanggal_ujian && $exam->jam_selesai) ? \Carbon\Carbon::parse($exam->tanggal_ujian.' '.$exam->jam_selesai) : null;
        return $examEnd && $examEnd->lessThan($durationEnd) ? $examEnd : $durationEnd;
    }
}
