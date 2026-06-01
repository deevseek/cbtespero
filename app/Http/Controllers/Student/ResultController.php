<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamResult;
use App\Models\Student;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function __invoke(): View
    {
        $student = $this->student();
        $results = ExamResult::with(['exam', 'answers'])
            ->where('student_id', $student->id)
            ->latest('updated_at')
            ->paginate(12);

        return view('student.results.index', compact('student', 'results'));
    }

    private function student(): Student
    {
        return Student::findOrFail(session('student_id'));
    }
}
