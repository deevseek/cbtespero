<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('student.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $student = Student::query()
            ->where('username', $credentials['username'])
            ->orWhere('nis', $credentials['username'])
            ->first();

        if (! $student || ! Hash::check($credentials['password'], $student->password)) {
            return back()
                ->withErrors(['login' => 'Username atau password salah.'])
                ->withInput($request->only('username'));
        }

        if ($student->status !== 'aktif') {
            return back()
                ->withErrors(['login' => 'Akun siswa tidak aktif.'])
                ->withInput($request->only('username'));
        }

        $request->session()->regenerate();
        $request->session()->put('student_id', $student->id);

        return redirect()->route('student.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            Auth::logout();
        }

        $request->session()->forget('student_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.login');
    }
}
