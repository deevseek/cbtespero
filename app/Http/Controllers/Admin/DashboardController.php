<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Question;
use App\Models\Student;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $todayParticipants = ExamResult::whereDate('created_at', now())->count();
        $avgScores = ExamResult::selectRaw('DATE(created_at) as tanggal, AVG(nilai) as rata_nilai')
            ->groupBy('tanggal')->latest('tanggal')->limit(7)->get()->reverse()->values();

        return view('admin.dashboard', [
            'totalSiswa' => Student::count(),
            'totalSoal' => Question::count(),
            'totalUjianAktif' => Exam::where('status', 'aktif')->count(),
            'totalUjianSelesai' => Exam::where('status', 'selesai')->count(),
            'todayParticipants' => $todayParticipants,
            'avgScores' => $avgScores,
        ]);
    }
}
