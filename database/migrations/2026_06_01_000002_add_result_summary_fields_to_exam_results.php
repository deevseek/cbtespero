<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_results', function (Blueprint $table): void {
            if (! Schema::hasColumn('exam_results', 'total_questions')) {
                $table->unsignedInteger('total_questions')->default(0)->after('nilai');
            }
            if (! Schema::hasColumn('exam_results', 'answered_questions')) {
                $table->unsignedInteger('answered_questions')->default(0)->after('total_questions');
            }
            if (! Schema::hasColumn('exam_results', 'correct_count')) {
                $table->unsignedInteger('correct_count')->default(0)->after('answered_questions');
            }
            if (! Schema::hasColumn('exam_results', 'wrong_count')) {
                $table->unsignedInteger('wrong_count')->default(0)->after('correct_count');
            }
            if (! Schema::hasColumn('exam_results', 'unanswered_count')) {
                $table->unsignedInteger('unanswered_count')->default(0)->after('wrong_count');
            }
            if (! Schema::hasColumn('exam_results', 'duration_seconds')) {
                $table->unsignedInteger('duration_seconds')->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('exam_results', 'submit_reason')) {
                $table->string('submit_reason')->nullable()->after('lock_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table): void {
            foreach (['total_questions', 'answered_questions', 'correct_count', 'wrong_count', 'unanswered_count', 'duration_seconds', 'submit_reason'] as $column) {
                if (Schema::hasColumn('exam_results', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
