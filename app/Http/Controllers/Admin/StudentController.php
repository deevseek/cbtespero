<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $students = Student::query()
            ->when($request->q, fn ($q) => $q->whereAny(['nis', 'nama', 'kelas'], 'like', "%{$request->q}%"))
            ->latest()->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return response()->json($students);
        }

        return view('admin.students.index', compact('students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nis' => 'required|unique:students,nis',
            'nama' => 'required|string',
            'kelas' => 'required|string',
            'username' => 'required|unique:students,username|unique:users,username',
            'password' => 'required|min:6',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        $student = Student::create($data);

        User::create([
            'name' => $student->nama,
            'username' => $student->username,
            'email' => null,
            'password' => Hash::make($request->password),
            'role' => 'siswa',
            'student_id' => $student->id,
            'is_active' => $student->status === 'aktif',
        ]);

        return back()->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate([
            'nis' => "required|unique:students,nis,{$student->id}",
            'nama' => 'required|string',
            'kelas' => 'required|string',
            'username' => "required|unique:students,username,{$student->id}",
            'password' => 'nullable|min:6',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        if (blank($data['password'])) {
            unset($data['password']);
        }

        $student->update($data);
        User::where('student_id', $student->id)->update([
            'name' => $student->nama,
            'username' => $student->username,
            'is_active' => $student->status === 'aktif',
            ...(!empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
        ]);

        return back()->with('success', 'Siswa berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        User::where('student_id', $student->id)->delete();
        $student->delete();
        return back()->with('success', 'Siswa berhasil dihapus.');
    }
}
