<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Request $request): View
    {
        $questions = Question::query()
            ->when($request->q, fn ($q) => $q->where('soal', 'like', "%{$request->q}%"))
            ->latest()->paginate(10)->withQueryString();

        return view('admin.questions.index', compact('questions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mata_pelajaran' => 'required|string', 'soal' => 'required|string',
            'pilihan_a' => 'required', 'pilihan_b' => 'required', 'pilihan_c' => 'required', 'pilihan_d' => 'required',
            'pilihan_e' => 'nullable', 'jawaban_benar' => 'required|in:a,b,c,d,e', 'bobot_nilai' => 'required|integer|min:1',
            'tingkat_kesulitan' => 'required|in:mudah,sedang,sulit', 'image_path' => 'nullable|string',
        ]);
        Question::create($data);
        return back()->with('success', 'Soal ditambahkan.');
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $data = $request->validate([
            'mata_pelajaran' => 'required|string', 'soal' => 'required|string',
            'pilihan_a' => 'required', 'pilihan_b' => 'required', 'pilihan_c' => 'required', 'pilihan_d' => 'required',
            'pilihan_e' => 'nullable', 'jawaban_benar' => 'required|in:a,b,c,d,e', 'bobot_nilai' => 'required|integer|min:1',
            'tingkat_kesulitan' => 'required|in:mudah,sedang,sulit', 'image_path' => 'nullable|string',
        ]);
        $question->update($data);
        return back()->with('success', 'Soal diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();
        return back()->with('success', 'Soal dihapus.');
    }
}
