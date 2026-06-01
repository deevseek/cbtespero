<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamResult;
use App\Models\Student;
use App\Services\StudentExamService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(StudentExamService $examService): View
    {
        $student = $this->student();
        $allExams = $examService->decorateExams($examService->examsForStudentQuery($student)->get(), $student);
        $activeExams = $allExams->filter(fn ($exam) => in_array($exam->student_status, ['upcoming', 'available', 'in_progress'], true))->take(5)->values();
        $latestResults = ExamResult::with(['exam', 'answers'])
            ->where('student_id', $student->id)
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return view('student.dashboard', [
            'student' => $student,
            'activeExams' => $activeExams,
            'upcomingExams' => $allExams->where('student_status', 'upcoming')->values(),
            'latestResults' => $latestResults,
            'stats' => $examService->stats($allExams, $student),
            'examService' => $examService,
        ]);
    }

    private function student(): Student
    {
        return Student::findOrFail(session('student_id'));
    }
}
