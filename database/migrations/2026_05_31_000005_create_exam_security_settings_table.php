<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_security_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('require_fullscreen')->default(true);
            $table->boolean('block_screenshot')->default(true);
            $table->boolean('device_binding')->default(true);
            $table->boolean('auto_submit_on_cheat')->default(false);
            $table->unsignedTinyInteger('max_app_exit')->default(3);
            $table->unsignedTinyInteger('max_fullscreen_exit')->default(3);
            $table->unsignedTinyInteger('max_heartbeat_missed')->default(3);
            $table->unsignedInteger('connection_tolerance_seconds')->default(60);
            $table->boolean('show_result_after_exam')->default(false);
            $table->boolean('randomize_questions')->default(true);
            $table->boolean('randomize_answers')->default(true);
            $table->boolean('allow_reentry')->default(true);
            $table->unsignedTinyInteger('max_relogin')->default(1);
            $table->string('orientation')->default('portrait');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_security_settings');
    }
};
