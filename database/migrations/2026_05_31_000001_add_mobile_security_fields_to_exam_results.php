<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_results', function (Blueprint $table): void {
            $table->uuid('session_uuid')->nullable()->unique()->after('student_id');
            $table->string('device_id')->nullable()->after('session_uuid');
            $table->string('device_name')->nullable()->after('device_id');
            $table->string('platform')->nullable()->after('device_name');
            $table->string('app_version')->nullable()->after('platform');
            $table->timestamp('server_started_at')->nullable()->after('started_at');
            $table->timestamp('server_ends_at')->nullable()->after('server_started_at');
            $table->timestamp('last_heartbeat_at')->nullable()->after('server_ends_at');
            $table->timestamp('locked_at')->nullable()->after('last_heartbeat_at');
            $table->string('lock_reason')->nullable()->after('locked_at');
            $table->timestamp('auto_submitted_at')->nullable()->after('lock_reason');
            $table->unsignedTinyInteger('app_exit_count')->default(0)->after('fullscreen_exit_count');
            $table->unsignedTinyInteger('heartbeat_missed_count')->default(0)->after('app_exit_count');
            $table->unsignedTinyInteger('relogin_count')->default(0)->after('heartbeat_missed_count');
            $table->string('ip_address', 45)->nullable()->after('relogin_count');
        });
    }

    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table): void {
            $table->dropColumn([
                'session_uuid', 'device_id', 'device_name', 'platform', 'app_version',
                'server_started_at', 'server_ends_at', 'last_heartbeat_at', 'locked_at',
                'lock_reason', 'auto_submitted_at', 'app_exit_count', 'heartbeat_missed_count',
                'relogin_count', 'ip_address',
            ]);
        });
    }
};
