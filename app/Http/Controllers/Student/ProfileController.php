<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __invoke(): View
    {
        $student = Student::findOrFail(session('student_id'));

        return view('student.profile', compact('student'));
    }
}
