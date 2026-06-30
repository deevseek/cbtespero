<?php

namespace App\Services\QuestionImport;

use App\Models\Question;
use App\Models\QuestionImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class QuestionImportPersister
{
    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<string, mixed> $options
     * @return array{created: int, review: int, failed: int, errors: array<int, string>, question_import_id: int|null, question_ids: array<int, int>}
     */
    public function import(array $questions, array $options): array
    {
        return DB::transaction(function () use ($questions, $options): array {
            $batch = $this->createBatch($questions, $options);
            $created = 0;
            $review = 0;
            $failed = (int) ($options['pre_failed_questions'] ?? 0);
            $errors = [];
            $questionIds = [];

            foreach ($questions as $index => $question) {
                $validationMessage = $this->validateQuestion($question, $options);

                if ($validationMessage !== null) {
                    $review++;
                    $failed++;
                    $errors[] = 'Soal '.($index + 1).': '.$validationMessage;
                    continue;
                }

                try {
                    $createdQuestion = Question::create($this->mapToQuestionAttributes($question, $options, $batch));
                    $questionIds[] = $createdQuestion->id;
                    $created++;

                    if ((bool) ($question['needs_review'] ?? false) || blank($question['correct_answer'] ?? null)) {
                        $review++;
                    }
                } catch (Throwable $exception) {
                    report($exception);
                    $failed++;
                    $errors[] = 'Soal '.($index + 1).': gagal disimpan.';
                }
            }

            $batch->update([
                'imported_questions' => $created,
                'failed_questions' => $failed,
                'needs_review_count' => $review,
                'status' => $this->resolveBatchStatus($created, $review, $failed, $options),
            ]);

            return [
                'created' => $created,
                'review' => $review,
                'failed' => $failed,
                'errors' => $errors,
                'question_import_id' => $batch->id,
                'question_ids' => $questionIds,
            ];
        });
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<string, mixed> $options
     */
    private function createBatch(array $questions, array $options): QuestionImport
    {
        return QuestionImport::create([
            'source_type' => (string) ($options['source_type'] ?? $options['source'] ?? 'manual'),
            'source_name' => $this->sourceName($options),
            'original_filename' => $options['original_filename'] ?? null,
            'source_url' => $options['source_url'] ?? $options['google_form_url'] ?? null,
            'subject' => $options['mata_pelajaran'] ?? $options['subject'] ?? null,
            'class_level' => $options['kelas'] ?? $options['class_level'] ?? null,
            'difficulty' => $options['tingkat_kesulitan'] ?? $options['difficulty'] ?? null,
            'default_weight' => (int) ($options['bobot_nilai'] ?? $options['default_weight'] ?? 1),
            'total_questions' => count($questions) + (int) ($options['pre_failed_questions'] ?? 0),
            'imported_questions' => 0,
            'failed_questions' => (int) ($options['pre_failed_questions'] ?? 0),
            'needs_review_count' => 0,
            'status' => 'draft',
            'imported_by' => auth()->id(),
            'imported_at' => now(),
            'meta' => $options['meta'] ?? null,
        ]);
    }

    /** @param array<string, mixed> $options */
    private function sourceName(array $options): ?string
    {
        if (filled($options['source_name'] ?? null)) {
            return (string) $options['source_name'];
        }

        if (filled($options['original_filename'] ?? null)) {
            return (string) $options['original_filename'];
        }

        if (filled($options['google_form_title'] ?? null)) {
            return (string) $options['google_form_title'];
        }

        if (($options['source_type'] ?? $options['source'] ?? null) === 'google_form' && filled($options['source_url'] ?? null)) {
            return 'Google Form '.parse_url((string) $options['source_url'], PHP_URL_PATH);
        }

        return null;
    }

    /** @param array<string, mixed> $options */
    private function resolveBatchStatus(int $created, int $review, int $failed, array $options): string
    {
        if (filled($options['batch_status'] ?? null)) {
            return (string) $options['batch_status'];
        }

        if ($created === 0 || $review > 0 || $failed > 0) {
            return 'draft';
        }

        return 'imported';
    }

    /** @param array<string, mixed> $importOptions */
    private function validateQuestion(array $question, array $importOptions): ?string
    {
        $questionText = trim((string) ($question['question_text'] ?? ''));
        $options = (array) ($question['options'] ?? []);
        $correctAnswer = strtoupper((string) ($question['correct_answer'] ?? ''));
        $allowMissingCorrectAnswer = (bool) ($importOptions['allow_missing_correct_answer'] ?? false);
        $allowPartialOptions = (bool) ($importOptions['allow_partial_options'] ?? false);

        if ($questionText === '') {
            return 'teks pertanyaan kosong.';
        }

        if (! $allowPartialOptions) {
            foreach (['A', 'B', 'C', 'D'] as $optionKey) {
                if (blank($options[$optionKey] ?? null)) {
                    return 'opsi '.$optionKey.' belum lengkap.';
                }
            }
        }

        if ($correctAnswer === '') {
            return $allowMissingCorrectAnswer ? null : 'jawaban benar belum ditemukan.';
        }

        if (! in_array($correctAnswer, ['A', 'B', 'C', 'D', 'E'], true)) {
            return 'jawaban benar belum ditemukan.';
        }

        if ($correctAnswer === 'E' && blank($options['E'] ?? null)) {
            return 'jawaban benar E dipilih, tetapi opsi E kosong.';
        }

        return null;
    }

    /** @param array<string, mixed> $options */
    private function mapToQuestionAttributes(array $question, array $options, QuestionImport $batch): array
    {
        $questionOptions = (array) ($question['options'] ?? []);
        $attributes = [
            'question_import_id' => $batch->id,
            'mata_pelajaran' => (string) ($options['mata_pelajaran'] ?? ''),
            'tipe_soal' => $this->normalizeQuestionType($question, $options),
            'soal' => (string) ($question['question_text'] ?? ''),
            'pilihan_a' => (string) ($questionOptions['A'] ?? ''),
            'pilihan_b' => (string) ($questionOptions['B'] ?? ''),
            'pilihan_c' => (string) ($questionOptions['C'] ?? ''),
            'pilihan_d' => (string) ($questionOptions['D'] ?? ''),
            'pilihan_e' => filled($questionOptions['E'] ?? null) ? (string) $questionOptions['E'] : null,
            'jawaban_benar' => filled($question['correct_answer'] ?? null) ? strtolower((string) $question['correct_answer']) : null,
            'bobot_nilai' => (int) ($options['bobot_nilai'] ?? 1),
            'tingkat_kesulitan' => (string) ($options['tingkat_kesulitan'] ?? 'sedang'),
        ];

        if (Schema::hasColumn('questions', 'kelas') && filled($options['kelas'] ?? null)) {
            $attributes['kelas'] = $options['kelas'];
        }

        if (Schema::hasColumn('questions', 'status')) {
            $attributes['status'] = filled($question['correct_answer'] ?? null)
                ? ($question['status'] ?? $options['status'] ?? 'draft')
                : 'draft';
        }

        if (Schema::hasColumn('questions', 'needs_review')) {
            $attributes['needs_review'] = (bool) ($question['needs_review'] ?? false) || blank($question['correct_answer'] ?? null);
        }

        return $attributes;
    }

    private function normalizeQuestionType(array $question, array $options): string
    {
        $forcedType = $options['tipe_soal_import'] ?? 'auto';

        if ($forcedType !== 'auto' && filled($forcedType)) {
            return $forcedType;
        }

        $type = $question['tipe_soal'] ?? $question['type'] ?? 'pilihan_ganda';

        return match ($type) {
            'pilihan_ganda' => 'pilihan_ganda',
            'multiple_choice' => 'pilihan_ganda',
            'radio' => 'pilihan_ganda',

            'multiple_answer' => 'multiple_answer',
            'checkbox' => 'multiple_answer',
            'checkboxes' => 'multiple_answer',

            'checklist' => 'checklist',
            'true_false' => 'checklist',
            'benar_salah' => 'checklist',

            'dropdown' => 'dropdown',
            'select' => 'dropdown',

            default => 'pilihan_ganda',
        };
    }
}
