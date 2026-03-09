<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $exams = Exam::query()->when($request->q, fn ($q) => $q->where('nama_ujian', 'like', "%{$request->q}%"))
            ->latest()->paginate(10)->withQueryString();

        return view('admin.exams.index', compact('exams'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_ujian' => 'required', 'mata_pelajaran' => 'required', 'kelas' => 'required', 'tanggal_ujian' => 'required|date',
            'jam_mulai' => 'required', 'jam_selesai' => 'required', 'durasi' => 'required|integer', 'jumlah_soal' => 'required|integer',
            'status' => 'required|in:draft,aktif,selesai',
        ]);

        $data['token'] = strtoupper(Str::random(5));
        $exam = Exam::create($data);
        ExamToken::create(['exam_id' => $exam->id, 'token' => $data['token'], 'is_active' => true]);

        return back()->with('success', 'Jadwal ujian dibuat.');
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $data = $request->validate([
            'nama_ujian' => 'required', 'mata_pelajaran' => 'required', 'kelas' => 'required', 'tanggal_ujian' => 'required|date',
            'jam_mulai' => 'required', 'jam_selesai' => 'required', 'durasi' => 'required|integer', 'jumlah_soal' => 'required|integer',
            'status' => 'required|in:draft,aktif,selesai',
        ]);

        $exam->update($data);
        return back()->with('success', 'Jadwal ujian diperbarui.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $exam->delete();
        return back()->with('success', 'Jadwal ujian dihapus.');
    }

    public function regenerateToken(Exam $exam): RedirectResponse
    {
        $token = strtoupper(Str::random(5));
        $exam->update(['token' => $token]);
        ExamToken::where('exam_id', $exam->id)->update(['is_active' => false]);
        ExamToken::create(['exam_id' => $exam->id, 'token' => $token, 'is_active' => true]);

        return back()->with('success', 'Token ujian berhasil diganti.');
    }
}
