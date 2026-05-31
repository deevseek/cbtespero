<?php

namespace App\Services\QuestionImport;

use App\Models\Question;
use Illuminate\Support\Facades\Schema;
use Throwable;

class QuestionImportPersister
{
    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<string, mixed> $options
     * @return array{created: int, review: int, failed: int, errors: array<int, string>}
     */
    public function import(array $questions, array $options): array
    {
        $created = 0;
        $review = 0;
        $failed = 0;
        $errors = [];

        foreach ($questions as $index => $question) {
            $validationMessage = $this->validateQuestion($question, $options);

            if ($validationMessage !== null) {
                $review++;
                $errors[] = 'Soal '.($index + 1).': '.$validationMessage;
                continue;
            }

            try {
                Question::create($this->mapToQuestionAttributes($question, $options));
                $created++;

                if ((bool) ($question['needs_review'] ?? false)) {
                    $review++;
                }
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
                $errors[] = 'Soal '.($index + 1).': gagal disimpan.';
            }
        }

        return [
            'created' => $created,
            'review' => $review,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

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

    private function mapToQuestionAttributes(array $question, array $options): array
    {
        $questionOptions = (array) ($question['options'] ?? []);
        $attributes = [
            'mata_pelajaran' => (string) ($options['mata_pelajaran'] ?? ''),
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
            $attributes['status'] = $options['status'] ?? 'draft';
        }

        if (Schema::hasColumn('questions', 'needs_review')) {
            $attributes['needs_review'] = (bool) ($question['needs_review'] ?? false);
        }

        return $attributes;
    }
}
