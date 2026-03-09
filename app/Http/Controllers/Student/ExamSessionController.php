<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamLog;
use App\Models\ExamResult;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamSessionController extends Controller
{
    public function dashboard(): View
    {
        $student = auth()->user()->student;
        $exams = Exam::where('status', 'aktif')->where('kelas', $student->kelas)->get();
        $results = ExamResult::with('exam')->where('student_id', $student->id)->latest()->paginate(10);

        return view('student.dashboard', compact('exams', 'results'));
    }

    public function start(Request $request, Exam $exam): RedirectResponse
    {
        $request->validate(['token' => 'required|string|size:5']);
        abort_if(strtoupper($request->token) !== $exam->token, 422, 'Token salah');

        $student = auth()->user()->student;
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
            'logged_at' => now(),
        ]);

        return redirect()->route('student.exams.room', $result);
    }

    public function room(ExamResult $result): View
    {
        $result->load('exam');
        $answers = $result->answers()->with('question')->get()->shuffle();
        return view('student.exam-room', compact('result', 'answers'));
    }

    public function answer(Request $request, ExamResult $result): JsonResponse
    {
        $data = $request->validate(['question_id' => 'required|integer', 'jawaban' => 'required|in:a,b,c,d,e']);
        $answer = ExamAnswer::where('exam_result_id', $result->id)->where('question_id', $data['question_id'])->firstOrFail();
        $correct = Question::findOrFail($data['question_id'])->jawaban_benar === $data['jawaban'];
        $answer->update(['jawaban_siswa' => $data['jawaban'], 'is_correct' => $correct, 'answered_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function logCheating(Request $request, ExamResult $result): JsonResponse
    {
        $data = $request->validate(['type' => 'required|string']);
        if ($data['type'] === 'tab_switch') {
            $result->increment('tab_switch_count');
        }
        if ($data['type'] === 'fullscreen_exit') {
            $result->increment('fullscreen_exit_count');
        }

        ExamLog::create([
            'exam_result_id' => $result->id,
            'student_id' => $result->student_id,
            'exam_id' => $result->exam_id,
            'activity_type' => $data['type'],
            'description' => 'Pelanggaran anti cheating terdeteksi.',
            'ip_address' => $request->ip(),
            'logged_at' => now(),
        ]);

        if ($result->tab_switch_count >= 3 || $result->fullscreen_exit_count >= 3) {
            $result->update(['status' => 'selesai', 'submitted_at' => now()]);
        }

        return response()->json(['ok' => true, 'tab_switch_count' => $result->tab_switch_count, 'fullscreen_exit_count' => $result->fullscreen_exit_count]);
    }

    public function submit(ExamResult $result): RedirectResponse
    {
        $total = $result->answers()->count();
        $correct = $result->answers()->where('is_correct', true)->count();
        $nilai = $total ? round(($correct / $total) * 100, 2) : 0;

        $result->update(['nilai' => $nilai, 'status' => 'selesai', 'submitted_at' => now()]);
        return redirect()->route('student.dashboard')->with('success', 'Ujian selesai.');
    }
}
