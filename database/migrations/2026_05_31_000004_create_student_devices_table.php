<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('device_id');
            $table->string('device_name')->nullable();
            $table->string('platform')->nullable();
            $table->string('app_version')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['student_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_devices');
    }
};
