<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('nisn')->unique()->after('nis')->nullable();
            $table->string('email')->unique()->after('nama')->nullable();
            $table->string('asal_smp')->after('email')->nullable();
            $table->text('alamat_rumah')->after('asal_smp')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->after('alamat_rumah')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['nisn', 'email', 'asal_smp', 'alamat_rumah', 'jenis_kelamin']);
        });
    }
};