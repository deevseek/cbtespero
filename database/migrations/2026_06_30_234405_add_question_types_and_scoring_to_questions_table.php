<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Add kelas column if not exists
            if (!Schema::hasColumn('questions', 'kelas')) {
                $table->string('kelas')->nullable()->after('mata_pelajaran');
            }

            // Add question type column
            if (!Schema::hasColumn('questions', 'tipe_soal')) {
                $table->string('tipe_soal')->default('pilihan_ganda')->after('kelas');
            }

            // Change jawaban_benar from ENUM to TEXT/JSON to support multiple answers
            if (Schema::hasColumn('questions', 'jawaban_benar')) {
                $table->text('jawaban_benar')->nullable()->change();
            }

            // Add scoring method column
            if (!Schema::hasColumn('questions', 'scoring_method')) {
                $table->string('scoring_method')->default('binary')->after('bobot_nilai');
            }

            // Add scoring parameters column (JSON)
            if (!Schema::hasColumn('questions', 'scoring_parameters')) {
                $table->json('scoring_parameters')->nullable()->after('scoring_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'kelas')) {
                $table->dropColumn('kelas');
            }
            if (Schema::hasColumn('questions', 'tipe_soal')) {
                $table->dropColumn('tipe_soal');
            }
            if (Schema::hasColumn('questions', 'scoring_method')) {
                $table->dropColumn('scoring_method');
            }
            if (Schema::hasColumn('questions', 'scoring_parameters')) {
                $table->dropColumn('scoring_parameters');
            }
        });
    }
};
