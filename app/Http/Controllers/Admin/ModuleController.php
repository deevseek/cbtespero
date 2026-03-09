<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamResult;
use App\Models\Setting;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function monitoring()
    {
        $rows = ExamResult::with(['student', 'exam'])->latest()->paginate(10);
        return view('admin.modules.monitoring', compact('rows'));
    }

    public function attendance()
    {
        return view('admin.modules.attendance');
    }

    public function cards()
    {
        return view('admin.modules.cards');
    }

    public function recap(Request $request)
    {
        $rows = ExamResult::with(['student', 'exam'])
            ->when($request->kelas, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('kelas', $request->kelas)))
            ->when($request->mata_pelajaran, fn ($q) => $q->whereHas('exam', fn ($e) => $e->where('mata_pelajaran', $request->mata_pelajaran)))
            ->paginate(10)->withQueryString();
        return view('admin.modules.recap', compact('rows'));
    }

    public function config()
    {
        $setting = Setting::firstOrCreate([], ['nama_aplikasi' => 'Espero CBT']);
        return view('admin.modules.config', compact('setting'));
    }

    public function saveConfig(Request $request)
    {
        $data = $request->validate([
            'nama_aplikasi' => 'required', 'nama_sekolah' => 'nullable', 'alamat_sekolah' => 'nullable', 'tahun_ajaran' => 'nullable',
            'acak_soal' => 'nullable|boolean', 'acak_jawaban' => 'nullable|boolean', 'tampilkan_nilai_setelah_ujian' => 'nullable|boolean',
        ]);
        $setting = Setting::firstOrCreate([], ['nama_aplikasi' => 'Espero CBT']);
        $setting->update([
            ...$data,
            'acak_soal' => $request->boolean('acak_soal'),
            'acak_jawaban' => $request->boolean('acak_jawaban'),
            'tampilkan_nilai_setelah_ujian' => $request->boolean('tampilkan_nilai_setelah_ujian'),
        ]);
        return back()->with('success', 'Konfigurasi tersimpan.');
    }
}
