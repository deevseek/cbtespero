<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAnswerOrder;
use App\Models\ExamLog;
use App\Models\ExamQuestionOrder;
use App\Models\ExamResult;
use App\Models\Question;
use App\Models\Student;
use App\Services\StudentExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request, StudentExamService $examService): View
    {
        $student = $this->student();
        $exams = $examService->decorateExams($examService->examsForStudentQuery($student)->get(), $student);

        if ($search = trim((string) $request->query('q'))) {
            $needle = Str::lower($search);
            $exams = $exams->filter(fn (Exam $exam) => str_contains(Str::lower($exam->nama_ujian), $needle) || str_contains(Str::lower($exam->mata_pelajaran), $needle));
        }

        if ($status = $request->query('status')) {
            $exams = $exams->where('student_status', $status);
        }

        return view('student.exams.index', [
            'student' => $student,
            'exams' => $exams->values(),
            'examService' => $examService,
            'selectedStatus' => $request->query('status'),
            'search' => $request->query('q'),
        ]);
    }

    public function token(Request $request, Exam $exam, StudentExamService $examService): RedirectResponse
    {
        $student = $this->student();
        $this->authorizeExam($exam, $student, $examService);

        $data = $request->validate(['token' => ['required', 'string', 'max:20']]);
        if (! $examService->tokenIsValid($exam, $data['token'])) {
            return back()->withErrors(['token_'.$exam->id => 'Token ujian tidak valid.'])->withInput();
        }

        ExamResult::firstOrCreate(
            ['exam_id' => $exam->id, 'student_id' => $student->id],
            ['status' => 'belum_mulai']
        );

        session()->put($this->tokenSessionKey($exam), true);

        return redirect()->route('student.exams.start', $exam);
    }

    public function start(Exam $exam, StudentExamService $examService): View|RedirectResponse
    {
        $student = $this->student();
        $this->authorizeExam($exam, $student, $examService);
        $decorated = $examService->decorateExams(collect([$exam->loadCount('questions')]), $student)->first();
        $result = $decorated->student_result;

        if ($result && in_array($result->status, ['selesai', 'auto_submit'], true)) {
            return redirect()->route('student.results')->with('success', 'Ujian sudah selesai. Silakan lihat hasil kamu.');
        }

        if (! $decorated->is_ready && ! $examService->hasFallbackQuestionBank($exam)) {
            return redirect()->route('student.exams')->withErrors(['exam' => 'Ujian belum siap. Silakan hubungi pengawas.']);
        }

        if ($decorated->student_status === 'upcoming') {
            return redirect()->route('student.exams')->withErrors(['exam' => 'Ujian belum dimulai.']);
        }

        if ($decorated->student_status === 'missed') {
            return redirect()->route('student.exams')->withErrors(['exam' => 'Jadwal ujian sudah terlewat.']);
        }

        if ($decorated->requires_token && ! session($this->tokenSessionKey($exam)) && ! $result) {
            return redirect()->route('student.exams')->withErrors(['token_'.$exam->id => 'Silakan masukkan token ujian terlebih dahulu.']);
        }

        return view('student.exams.start', [
            'student' => $student,
            'exam' => $decorated,
            'result' => $result,
        ]);
    }

    public function begin(Request $request, Exam $exam, StudentExamService $examService): RedirectResponse
    {
        $student = $this->student();
        $this->authorizeExam($exam, $student, $examService);
        $decorated = $examService->decorateExams(collect([$exam->load(['securitySetting'])->loadCount('questions')]), $student)->first();

        if ($decorated->student_status === 'upcoming') {
            return back()->withErrors(['exam' => 'Ujian belum dimulai.']);
        }

        if ($decorated->student_status === 'missed') {
            return back()->withErrors(['exam' => 'Jadwal ujian sudah terlewat.']);
        }

        if ($decorated->requires_token && ! session($this->tokenSessionKey($exam)) && ! $decorated->student_result) {
            return redirect()->route('student.exams')->withErrors(['token_'.$exam->id => 'Silakan masukkan token ujian terlebih dahulu.']);
        }

        if (! $decorated->is_ready && ! $examService->hasFallbackQuestionBank($exam)) {
            return back()->withErrors(['exam' => 'Ujian belum siap. Silakan hubungi pengawas.']);
        }

        $result = DB::transaction(function () use ($exam, $student, $request) {
            $result = ExamResult::firstOrNew(['exam_id' => $exam->id, 'student_id' => $student->id]);
            $result->fill([
                'status' => 'sedang_mengerjakan',
                'started_at' => $result->started_at ?: now('Asia/Jakarta'),
                'server_started_at' => $result->server_started_at ?: now('Asia/Jakarta'),
                'server_ends_at' => $result->server_ends_at ?: now('Asia/Jakarta')->addMinutes((int) $exam->durasi),
                'last_heartbeat_at' => now('Asia/Jakarta'),
                'session_uuid' => $result->session_uuid ?: (string) Str::uuid(),
                'ip_address' => $request->ip(),
            ])->save();

            $this->ensureQuestionSnapshot($exam, $result);

            ExamLog::create([
                'exam_result_id' => $result->id,
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'activity_type' => 'exam_started',
                'description' => 'Ujian dimulai melalui portal siswa.',
                'ip_address' => $request->ip(),
                'logged_at' => now('Asia/Jakarta'),
            ]);

            return $result;
        });

        return redirect()->route('student.exams.room', $result);
    }

    private function authorizeExam(Exam $exam, Student $student, StudentExamService $examService): void
    {
        abort_unless($examService->examsForStudentQuery($student)->whereKey($exam->id)->exists(), 403, 'Ujian tidak tersedia untuk siswa ini.');
    }

    private function ensureQuestionSnapshot(Exam $exam, ExamResult $result): void
    {
        if ($result->questionOrders()->exists() || $result->answers()->exists()) {
            return;
        }

        $security = $exam->securitySetting()->firstOrCreate([]);
        $query = $exam->questions()->exists() ? $exam->questions() : Question::where('mata_pelajaran', $exam->mata_pelajaran);
        $questions = ($security->randomize_questions ? $query->inRandomOrder() : $query->orderBy('id'))
            ->limit((int) $exam->jumlah_soal)
            ->get();

        foreach ($questions->values() as $index => $question) {
            ExamQuestionOrder::create([
                'exam_result_id' => $result->id,
                'question_id' => $question->id,
                'position' => $index + 1,
            ]);
            ExamAnswer::firstOrCreate(['exam_result_id' => $result->id, 'question_id' => $question->id]);
            $keys = collect(['a', 'b', 'c', 'd', 'e'])->filter(fn ($key) => $question->{'pilihan_'.$key} !== null)->values();
            ExamAnswerOrder::create([
                'exam_result_id' => $result->id,
                'question_id' => $question->id,
                'option_order' => ($security->randomize_answers ? $keys->shuffle() : $keys)->all(),
            ]);
        }
    }

    private function tokenSessionKey(Exam $exam): string
    {
        return 'student_exam_token_verified_'.$exam->id;
    }

    private function student(): Student
    {
        return Student::findOrFail(session('student_id'));
    }
}
