<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StudentDevice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_id' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
            'app_version' => ['nullable', 'string', 'max:50'],
        ]);

        $user = User::with('student')->where('username', $data['username'])->where('role', 'siswa')->first();
        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->is_active || ! $user->student) {
            throw ValidationException::withMessages(['username' => 'Login siswa tidak valid.']);
        }

        StudentDevice::updateOrCreate(
            ['student_id' => $user->student->id, 'device_id' => $data['device_id']],
            [
                'device_name' => $data['device_name'] ?? null,
                'platform' => $data['platform'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'last_seen_at' => now(),
                'ip_address' => $request->ip(),
                'is_active' => true,
            ]
        );

        $token = $user->createToken('flutter-cbt-'.$data['device_id'])->plainTextToken;

        return response()->json(['token' => $token, 'token_type' => 'Bearer', 'user' => $this->payload($user)]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->payload($request->user()->load('student'))]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['ok' => true]);
    }

    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'role' => $user->role,
            'student' => $user->student ? [
                'id' => $user->student->id,
                'nis' => $user->student->nis,
                'nama' => $user->student->nama,
                'kelas' => $user->student->kelas,
            ] : null,
        ];
    }
}
