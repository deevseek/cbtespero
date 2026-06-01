<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\ExamReportExportService;
use Illuminate\Http\Request;

class ExamReportExportController extends Controller
{
    public function __invoke(Request $request, ExamReportExportService $exportService, ?Exam $exam = null)
    {
        return $exportService->download($request->only(['exam_id', 'student_id', 'kelas', 'status', 'tanggal']), $exam);
    }
}
