<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_logs', function (Blueprint $table): void {
            $table->json('metadata')->nullable()->after('ip_address');
            $table->string('device_id')->nullable()->after('metadata');
        });
        Schema::table('exam_answers', function (Blueprint $table): void {
            $table->boolean('is_flagged')->default(false)->after('is_correct');
        });
        Schema::table('exam_tokens', function (Blueprint $table): void {
            $table->timestamp('expires_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('exam_logs', fn (Blueprint $table) => $table->dropColumn(['metadata', 'device_id']));
        Schema::table('exam_answers', fn (Blueprint $table) => $table->dropColumn('is_flagged'));
        Schema::table('exam_tokens', fn (Blueprint $table) => $table->dropColumn('expires_at'));
    }
};
