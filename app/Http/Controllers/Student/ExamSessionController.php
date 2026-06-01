<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamLog;
use App\Models\ExamResult;
use App\Models\Question;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamSessionController extends Controller
{
    private const VIOLATION_TYPES = [
        'exit_fullscreen',
        'tab_switch',
        'window_blur',
        'forbidden_shortcut',
        'right_click',
        'clipboard',
        'devtools',
        'page_reload',
        'idle',
        'connection_lost',
        'heartbeat_missed',
        'fullscreen_exit',
    ];

    public function dashboard(): View
    {
        $student = $this->student();
        $exams = Exam::where('status', 'aktif')->where('kelas', $student->kelas)->get();
        $results = ExamResult::with('exam')->where('student_id', $student->id)->latest()->paginate(10);

        return view('student.dashboard', compact('exams', 'results'));
    }

    public function start(Request $request, Exam $exam): RedirectResponse
    {
        $request->validate(['token' => 'required|string|size:5']);
        abort_if(strtoupper($request->token) !== $exam->token, 422, 'Token salah');

        $student = $this->student();
        $result = ExamResult::firstOrCreate(
            ['exam_id' => $exam->id, 'student_id' => $student->id],
            ['status' => 'sedang_mengerjakan', 'started_at' => now()]
        );

        $questions = Question::where('mata_pelajaran', $exam->mata_pelajaran)->inRandomOrder()->limit($exam->jumlah_soal)->pluck('id');
        foreach ($questions as $qid) {
            ExamAnswer::firstOrCreate(['exam_result_id' => $result->id, 'question_id' => $qid]);
        }

        ExamLog::create([
            'exam_result_id' => $result->id,
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'activity_type' => 'exam_started',
            'description' => 'Ujian dimulai dengan token valid.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'logged_at' => now(),
        ]);

        return redirect()->route('student.exams.room', $result);
    }

    public function room(ExamResult $result): View|RedirectResponse
    {
        abort_unless($result->student_id === $this->student()->id, 403);

        if (in_array($result->status, ['selesai', 'auto_submit'], true)) {
            return redirect()->route('student.results')->with('success', 'Ujian sudah selesai dan tidak bisa dibuka ulang.');
        }

        $result->load('exam.securitySetting', 'answers', 'student');
        $answers = $result->questionOrders()->with('question')->orderBy('position')->get();

        if ($answers->isEmpty()) {
            $answers = $result->answers()->with('question')->get()->shuffle();
        }

        return view('student.exam-room', compact('result', 'answers'));
    }

    public function answer(Request $request, ExamResult $result): JsonResponse
    {
        abort_unless($result->student_id === $this->student()->id, 403);
        abort_if(in_array($result->status, ['selesai', 'auto_submit', 'terkunci'], true), 423, 'Ujian sudah ditutup.');

        $data = $request->validate(['question_id' => 'required|integer', 'jawaban' => 'required|in:a,b,c,d,e']);
        $answer = ExamAnswer::where('exam_result_id', $result->id)->where('question_id', $data['question_id'])->firstOrFail();
        $correct = Question::findOrFail($data['question_id'])->jawaban_benar === $data['jawaban'];
        $answer->update(['jawaban_siswa' => $data['jawaban'], 'is_correct' => $correct, 'answered_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function violation(Request $request, Exam $exam): JsonResponse
    {
        $student = $this->student();
        $result = $this->activeResult($exam, $student);

        $data = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', self::VIOLATION_TYPES)],
            'message' => ['nullable', 'string', 'max:500'],
            'meta' => ['nullable', 'array'],
        ]);

        $type = $data['type'] === 'fullscreen_exit' ? 'exit_fullscreen' : $data['type'];
        $message = $data['message'] ?? $this->defaultViolationMessage($type);
        $meta = array_merge($data['meta'] ?? [], [
            'received_at' => now()->toIso8601String(),
            'route' => 'student.exams.violations',
        ]);

        if ($type === 'tab_switch') {
            $result->increment('tab_switch_count');
        }

        if ($type === 'exit_fullscreen') {
            $result->increment('fullscreen_exit_count');
        }

        if (in_array($type, ['window_blur', 'connection_lost'], true)) {
            $result->increment('app_exit_count');
        }

        if ($type === 'heartbeat_missed') {
            $result->increment('heartbeat_missed_count');
        }

        ExamLog::create([
            'exam_result_id' => $result->id,
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'activity_type' => $type,
            'description' => $message,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $meta,
            'logged_at' => now(),
        ]);

        $result->refresh();
        $violationCount = $this->violationCount($result);
        $maxViolations = $this->maxViolationsFor($exam, $type);
        $autoSubmit = $this->autoSubmitEnabled($exam);
        $action = 'warn';

        if ($violationCount >= $maxViolations) {
            $action = $autoSubmit ? 'auto_submit' : 'lock';
            if ($autoSubmit) {
                $this->autoSubmit($result, 'Auto submit karena pelanggaran anti-cheat');
            } else {
                $result->update([
                    'status' => 'terkunci',
                    'locked_at' => now(),
                    'lock_reason' => 'Ujian dikunci karena pelanggaran anti-cheat',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'violation_count' => $violationCount,
            'max_violations' => $maxViolations,
            'action' => $action,
            'message' => $action === 'auto_submit'
                ? 'Ujian otomatis dikumpulkan karena melebihi batas pelanggaran.'
                : 'Peringatan: Anda terdeteksi meninggalkan halaman ujian.',
        ]);
    }

    public function heartbeat(Request $request, Exam $exam): JsonResponse
    {
        $student = $this->student();
        $result = $this->activeResult($exam, $student);

        $data = $request->validate([
            'current_question' => ['nullable', 'integer'],
            'remaining_time' => ['nullable', 'integer', 'min:0'],
            'fullscreen_status' => ['nullable', 'boolean'],
            'visibility_state' => ['nullable', 'string', 'max:30'],
        ]);

        $setting = $exam->securitySetting;
        $toleranceSeconds = (int) ($setting?->connection_tolerance_seconds ?: 60);
        $lastSeen = $result->last_heartbeat_at;
        $missedHeartbeat = $lastSeen && $lastSeen->diffInSeconds(now()) > $toleranceSeconds;

        $result->forceFill([
            'last_heartbeat_at' => now(),
            'current_question_id' => $data['current_question'] ?? $result->current_question_id,
            'remaining_time_seconds' => $data['remaining_time'] ?? $result->remaining_time_seconds,
            'fullscreen_status' => $data['fullscreen_status'] ?? $result->fullscreen_status,
            'visibility_state' => $data['visibility_state'] ?? $result->visibility_state,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ])->save();

        if ($missedHeartbeat) {
            $result->increment('heartbeat_missed_count');
            ExamLog::create([
                'exam_result_id' => $result->id,
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'activity_type' => 'heartbeat_missed',
                'description' => 'Heartbeat peserta terputus melebihi toleransi.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'last_seen' => $lastSeen?->toIso8601String(),
                    'tolerance_seconds' => $toleranceSeconds,
                    'heartbeat' => $data,
                ],
                'logged_at' => now(),
            ]);
        }

        return response()->json(['success' => true, 'last_seen' => now()->toIso8601String()]);
    }

    public function logCheating(Request $request, ExamResult $result): JsonResponse
    {
        abort_unless($result->student_id === $this->student()->id, 403);

        return $this->violation($request->merge([
            'message' => $request->input('message', 'Pelanggaran anti cheating terdeteksi.'),
        ]), $result->exam);
    }

    public function submit(ExamResult $result): RedirectResponse
    {
        abort_unless($result->student_id === $this->student()->id, 403);

        $this->submitResult($result, 'selesai');

        return redirect()->route('student.dashboard')->with('success', 'Ujian selesai.');
    }

    private function student(): Student
    {
        return Student::findOrFail(session('student_id'));
    }

    private function activeResult(Exam $exam, Student $student): ExamResult
    {
        $result = ExamResult::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        abort_if(in_array($result->status, ['selesai', 'auto_submit', 'terkunci'], true), 423, 'Ujian sudah ditutup.');

        return $result;
    }

    private function violationCount(ExamResult $result): int
    {
        return $result->logs()
            ->whereIn('activity_type', self::VIOLATION_TYPES)
            ->count();
    }

    private function maxViolationsFor(Exam $exam, string $type): int
    {
        $setting = $exam->securitySetting;

        return match ($type) {
            'exit_fullscreen' => max(1, (int) ($setting?->max_fullscreen_exit ?: 3)),
            'window_blur', 'tab_switch', 'connection_lost' => max(1, (int) ($setting?->max_app_exit ?: 3)),
            'heartbeat_missed' => max(1, (int) ($setting?->max_heartbeat_missed ?: 3)),
            default => 3,
        };
    }

    private function autoSubmitEnabled(Exam $exam): bool
    {
        $setting = $exam->securitySetting;

        return $setting ? (bool) $setting->auto_submit_on_cheat : true;
    }

    private function autoSubmit(ExamResult $result, string $reason): void
    {
        $this->submitResult($result, 'auto_submit', $reason);
    }

    private function submitResult(ExamResult $result, string $status, ?string $reason = null): void
    {
        DB::transaction(function () use ($result, $status, $reason): void {
            $result->refresh();
            if (in_array($result->status, ['selesai', 'auto_submit'], true)) {
                return;
            }

            $total = max(1, $result->answers()->count());
            $correct = $result->answers()->where('is_correct', true)->count();

            $result->update([
                'nilai' => round(($correct / $total) * 100, 2),
                'status' => $status,
                'submitted_at' => now(),
                'auto_submitted_at' => $status === 'auto_submit' ? now() : $result->auto_submitted_at,
                'lock_reason' => $reason,
            ]);
        });
    }

    private function defaultViolationMessage(string $type): string
    {
        return match ($type) {
            'exit_fullscreen' => 'Siswa keluar dari mode fullscreen',
            'tab_switch' => 'Siswa meninggalkan tab ujian',
            'window_blur' => 'Browser kehilangan fokus, kemungkinan siswa pindah aplikasi',
            'forbidden_shortcut' => 'Shortcut terlarang ditekan',
            'right_click' => 'Siswa mencoba klik kanan',
            'clipboard' => 'Siswa mencoba copy/paste/cut',
            'devtools' => 'Indikasi Developer Tools terbuka',
            'page_reload' => 'Siswa me-refresh halaman ujian',
            'idle' => 'Siswa tidak aktif saat ujian berlangsung',
            'connection_lost' => 'Koneksi peserta terputus saat ujian berlangsung',
            'heartbeat_missed' => 'Heartbeat peserta terputus melebihi toleransi',
            default => 'Pelanggaran anti-cheat terdeteksi',
        };
    }
}
