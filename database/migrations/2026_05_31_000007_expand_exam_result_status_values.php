<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE exam_results MODIFY status ENUM('belum_mulai','sedang_mengerjakan','selesai','terkunci','auto_submit') DEFAULT 'belum_mulai'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE exam_results MODIFY status ENUM('belum_mulai','sedang_mengerjakan','selesai') DEFAULT 'belum_mulai'");
        }
    }
};
