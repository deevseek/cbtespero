<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_answer_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->json('option_order');
            $table->timestamps();
            $table->unique(['exam_result_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answer_orders');
    }
};
