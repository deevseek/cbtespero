<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_questions', 'order_number')) {
                $table->unsignedInteger('order_number')->nullable()->after('question_id');
            }

            if (! Schema::hasColumn('exam_questions', 'score')) {
                $table->unsignedInteger('score')->nullable()->after('order_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            if (Schema::hasColumn('exam_questions', 'score')) {
                $table->dropColumn('score');
            }

            if (Schema::hasColumn('exam_questions', 'order_number')) {
                $table->dropColumn('order_number');
            }
        });
    }
};
