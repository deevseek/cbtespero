<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('exam_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
        });

        Schema::table('exam_results', function (Blueprint $table): void {
            if (! Schema::hasColumn('exam_results', 'current_question_id')) {
                $table->foreignId('current_question_id')->nullable()->after('last_heartbeat_at')->constrained('questions')->nullOnDelete();
            }
            if (! Schema::hasColumn('exam_results', 'remaining_time_seconds')) {
                $table->unsignedInteger('remaining_time_seconds')->nullable()->after('current_question_id');
            }
            if (! Schema::hasColumn('exam_results', 'fullscreen_status')) {
                $table->boolean('fullscreen_status')->default(false)->after('remaining_time_seconds');
            }
            if (! Schema::hasColumn('exam_results', 'visibility_state')) {
                $table->string('visibility_state', 30)->nullable()->after('fullscreen_status');
            }
            if (! Schema::hasColumn('exam_results', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table): void {
            if (Schema::hasColumn('exam_results', 'current_question_id')) {
                $table->dropConstrainedForeignId('current_question_id');
            }
            foreach (['remaining_time_seconds', 'fullscreen_status', 'visibility_state', 'user_agent'] as $column) {
                if (Schema::hasColumn('exam_results', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('exam_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('exam_logs', 'user_agent')) {
                $table->dropColumn('user_agent');
            }
        });
    }
};
