<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE questions MODIFY jawaban_benar ENUM('a', 'b', 'c', 'd', 'e') NULL");
        }

        Schema::table('questions', function (Blueprint $table) {
            if (! Schema::hasColumn('questions', 'status')) {
                $table->enum('status', ['draft', 'aktif'])->default('aktif')->after('tingkat_kesulitan');
            }

            if (! Schema::hasColumn('questions', 'needs_review')) {
                $table->boolean('needs_review')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'needs_review')) {
                $table->dropColumn('needs_review');
            }

            if (Schema::hasColumn('questions', 'status')) {
                $table->dropColumn('status');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE questions MODIFY jawaban_benar ENUM('a', 'b', 'c', 'd', 'e') NOT NULL");
        }
    }
};
