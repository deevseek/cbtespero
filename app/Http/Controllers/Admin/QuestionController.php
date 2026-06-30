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
            'jawaban_benar' => 'required|string',
            'bobot_nilai' => 'required|integer|min:1',
            'scoring_method' => 'required|in:binary,all_or_nothing,proporsional,minus',
            'scoring_parameters' => 'nullable|json',
            'tingkat_kesulitan' => 'required|in:mudah,sedang,sulit',
        ]);

        // Validate jawaban_benar based on question type
        $tipeSoal = $data['tipe_soal'];
        $jawabanBenar = $data['jawaban_benar'];
        
        if ($tipeSoal === 'pilihan_ganda' || $tipeSoal === 'dropdown') {
            // Single answer: must be a single character 'a' to 'e'
            if (!in_array($jawabanBenar, ['a', 'b', 'c', 'd', 'e'])) {
                return back()->withErrors(['jawaban_benar' => 'Jawaban benar harus a, b, c, d, atau e untuk tipe pilihan ganda dan dropdown.']);
            }
        } elseif ($tipeSoal === 'multiple_answer') {
            // Multiple answers: must be JSON array like ['a', 'c', 'e']
            $decoded = json_decode($jawabanBenar, true);
            if (!is_array($decoded)) {
                return back()->withErrors(['jawaban_benar' => 'Jawaban benar harus array JSON untuk tipe multiple answer. Contoh: ["a", "c", "e"].']);
            }
            foreach ($decoded as $key) {
                if (!in_array($key, ['a', 'b', 'c', 'd', 'e'])) {
                    return back()->withErrors(['jawaban_benar' => 'Semua elemen dalam array harus a, b, c, d, atau e.']);
                }
            }
        } elseif ($tipeSoal === 'checklist') {
            // Checklist: must be JSON array of objects with key 'benar' (bool)
            $decoded = json_decode($jawabanBenar, true);
            if (!is_array($decoded)) {
                return back()->withErrors(['jawaban_benar' => 'Jawaban benar harus array JSON untuk tipe checklist. Contoh: [{"benar": true}, {"benar": false}].']);
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
            'jawaban_benar' => 'required|string',
            'bobot_nilai' => 'required|integer|min:1',
            'scoring_method' => 'required|in:binary,all_or_nothing,proporsional,minus',
            'scoring_parameters' => 'nullable|json',
            'tingkat_kesulitan' => 'required|in:mudah,sedang,sulit',
        ]);

        // Validate jawaban_benar based on question type
        $tipeSoal = $data['tipe_soal'];
        $jawabanBenar = $data['jawaban_benar'];
        
        if ($tipeSoal === 'pilihan_ganda' || $tipeSoal === 'dropdown') {
            // Single answer: must be a single character 'a' to 'e'
            if (!in_array($jawabanBenar, ['a', 'b', 'c', 'd', 'e'])) {
                return back()->withErrors(['jawaban_benar' => 'Jawaban benar harus a, b, c, d, atau e untuk tipe pilihan ganda dan dropdown.']);
            }
        } elseif ($tipeSoal === 'multiple_answer') {
            // Multiple answers: must be JSON array like ['a', 'c', 'e']
            $decoded = json_decode($jawabanBenar, true);
            if (!is_array($decoded)) {
                return back()->withErrors(['jawaban_benar' => 'Jawaban benar harus array JSON untuk tipe multiple answer. Contoh: ["a", "c", "e"].']);
            }
            foreach ($decoded as $key) {
                if (!in_array($key, ['a', 'b', 'c', 'd', 'e'])) {
                    return back()->withErrors(['jawaban_benar' => 'Semua elemen dalam array harus a, b, c, d, atau e.']);
                }
            }
        } elseif ($tipeSoal === 'checklist') {
            // Checklist: must be JSON array of objects with key 'benar' (bool)
            $decoded = json_decode($jawabanBenar, true);
            if (!is_array($decoded)) {
                return back()->withErrors(['jawaban_benar' => 'Jawaban benar harus array JSON untuk tipe checklist. Contoh: [{"benar": true}, {"benar": false}].']);
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
