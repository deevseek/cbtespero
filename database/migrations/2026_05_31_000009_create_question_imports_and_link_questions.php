<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('question_imports')) {
            Schema::create('question_imports', function (Blueprint $table) {
                $table->id();
                $table->string('source_type')->default('manual');
                $table->string('source_name')->nullable();
                $table->string('original_filename')->nullable();
                $table->text('source_url')->nullable();
                $table->string('subject')->nullable();
                $table->string('class_level')->nullable();
                $table->string('difficulty')->nullable();
                $table->unsignedInteger('default_weight')->default(1);
                $table->unsignedInteger('total_questions')->default(0);
                $table->unsignedInteger('imported_questions')->default(0);
                $table->unsignedInteger('failed_questions')->default(0);
                $table->unsignedInteger('needs_review_count')->default(0);
                $table->string('status')->default('draft');
                $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('imported_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['source_type', 'status']);
                $table->index(['subject', 'class_level']);
            });
        }

        Schema::table('questions', function (Blueprint $table) {
            if (! Schema::hasColumn('questions', 'question_import_id')) {
                $table->foreignId('question_import_id')->nullable()->after('id')->constrained('question_imports')->nullOnDelete();
            }

            if (! Schema::hasColumn('questions', 'kelas')) {
                $table->string('kelas')->nullable()->after('mata_pelajaran');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'question_import_id')) {
                $table->dropConstrainedForeignId('question_import_id');
            }

            if (Schema::hasColumn('questions', 'kelas')) {
                $table->dropColumn('kelas');
            }
        });

        Schema::dropIfExists('question_imports');
    }
};
