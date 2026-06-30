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
            'mata_pelajaran' => 'required|string',
            'kelas' => 'nullable|string',
            'tipe_soal' => 'required|in:pilihan_ganda,multiple_answer,checklist,dropdown',
            'soal' => 'required|string',
            'image_path' => 'nullable|string',
            'pilihan_a' => 'required_if:tipe_soal,pilihan_ganda,multiple_answer,dropdown',
            'pilihan_b' => 'required_if:tipe_soal,pilihan_ganda,multiple_answer,dropdown',
            'pilihan_c' => 'required_if:tipe_soal,pilihan_ganda,multiple_answer,dropdown',
            'pilihan_d' => 'required_if:tipe_soal,pilihan_ganda,multiple_answer,dropdown',
            'pilihan_e' => 'nullable',
            'jawaban_benar' => 'required', // String or JSON string for legacy admin
            'bobot_nilai' => 'required|integer|min:1',
            'scoring_method' => 'required|in:all_or_nothing,proportional,penalty',
            'scoring_parameters' => 'nullable|json',
            'tingkat_kesulitan' => 'required|in:mudah,sedang,sulit',
        ]);

        // Minimal validation for legacy admin to prevent crash. Full validation handled by Filament.
        $tipeSoal = $data['tipe_soal'];
        $jawabanBenar = $data['jawaban_benar'];

        if ($tipeSoal === 'pilihan_ganda' || $tipeSoal === 'dropdown') {
            if (!in_array(strtolower($jawabanBenar), ['a', 'b', 'c', 'd', 'e'])) {
                // If legacy admin is used, and type is single choice, assume single char
                $data['jawaban_benar'] = strtolower($jawabanBenar);
            }
        } elseif ($tipeSoal === 'multiple_answer' || $tipeSoal === 'checklist') {
            // For complex types from legacy admin, assume JSON array if possible, else default to empty JSON
            if (!is_string($jawabanBenar) || !json_decode($jawabanBenar)) {
                $data['jawaban_benar'] = '[]'; // Save as empty JSON to prevent crash
            }
        }

        Question::create($data);
        return back()->with('success', 'Soal ditambahkan.');
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $data = $request->validate([
            'mata_pelajaran' => 'required|string',
            'kelas' => 'nullable|string',
            'tipe_soal' => 'required|in:pilihan_ganda,multiple_answer,checklist,dropdown',
            'soal' => 'required|string',
            'image_path' => 'nullable|string',
            'pilihan_a' => 'required_if:tipe_soal,pilihan_ganda,multiple_answer,dropdown',
            'pilihan_b' => 'required_if:tipe_soal,pilihan_ganda,multiple_answer,dropdown',
            'pilihan_c' => 'required_if:tipe_soal,pilihan_ganda,multiple_answer,dropdown',
            'pilihan_d' => 'required_if:tipe_soal,pilihan_ganda,multiple_answer,dropdown',
            'pilihan_e' => 'nullable',
            'jawaban_benar' => 'required', // String or JSON string for legacy admin
            'bobot_nilai' => 'required|integer|min:1',
            'scoring_method' => 'required|in:all_or_nothing,proportional,penalty',
            'scoring_parameters' => 'nullable|json',
            'tingkat_kesulitan' => 'required|in:mudah,sedang,sulit',
        ]);

        // Minimal validation for legacy admin to prevent crash. Full validation handled by Filament.
        $tipeSoal = $data['tipe_soal'];
        $jawabanBenar = $data['jawaban_benar'];

        if ($tipeSoal === 'pilihan_ganda' || $tipeSoal === 'dropdown') {
            if (!in_array(strtolower($jawabanBenar), ['a', 'b', 'c', 'd', 'e'])) {
                // If legacy admin is used, and type is single choice, assume single char
                $data['jawaban_benar'] = strtolower($jawabanBenar);
            }
        } elseif ($tipeSoal === 'multiple_answer' || $tipeSoal === 'checklist') {
            // For complex types from legacy admin, assume JSON array if possible, else default to empty JSON
            if (!is_string($jawabanBenar) || !json_decode($jawabanBenar)) {
                $data['jawaban_benar'] = '[]'; // Save as empty JSON to prevent crash
            }
        }

        $question->update($data);
        return back()->with('success', 'Soal diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();
        return back()->with('success', 'Soal dihapus.');
    }
}
