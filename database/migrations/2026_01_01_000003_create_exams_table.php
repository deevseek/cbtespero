<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('nama_ujian');
            $table->string('mata_pelajaran');
            $table->string('kelas');
            $table->date('tanggal_ujian');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->unsignedInteger('durasi');
            $table->unsignedInteger('jumlah_soal');
            $table->enum('status', ['draft', 'aktif', 'selesai'])->default('draft');
            $table->string('token', 5)->nullable();
            $table->boolean('acak_soal')->default(true);
            $table->boolean('acak_jawaban')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
