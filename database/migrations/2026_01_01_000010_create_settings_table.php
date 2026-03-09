<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_aplikasi')->default('Espero CBT');
            $table->string('logo_sekolah')->nullable();
            $table->string('nama_sekolah')->nullable();
            $table->text('alamat_sekolah')->nullable();
            $table->string('tahun_ajaran')->nullable();
            $table->boolean('acak_soal')->default(true);
            $table->boolean('acak_jawaban')->default(true);
            $table->boolean('tampilkan_nilai_setelah_ujian')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
