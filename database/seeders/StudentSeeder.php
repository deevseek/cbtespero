<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Akun test siswa setelah seeder dijalankan:
     * - siswa001 / password
     * - siswa002 / password
     * - siswa003 / password
     */
    public function run(): void
    {
        $students = [
            ['nisn' => '1001', 'nama' => 'Siswa Test 001', 'kelas' => 'X', 'username' => 'siswa001'],
            ['nisn' => '1002', 'nama' => 'Siswa Test 002', 'kelas' => 'X', 'username' => 'siswa002'],
            ['nisn' => '1003', 'nama' => 'Siswa Test 003', 'kelas' => 'XI', 'username' => 'siswa003'],
        ];

        foreach ($students as $data) {
            $student = Student::updateOrCreate(
                ['username' => $data['username']],
                [
                    'nisn' => $data['nisn'],
                    'nama' => $data['nama'],
                    'kelas' => $data['kelas'],
                    'password' => Hash::make('password'),
                    'status' => 'aktif',
                ]
            );

            User::updateOrCreate(
                ['username' => $data['username']],
                [
                    'name' => $student->nama,
                    'email' => null,
                    'password' => Hash::make('password'),
                    'role' => 'siswa',
                    'student_id' => $student->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
